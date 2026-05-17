# Product Order Current System

This document explains what already exists in the backend before building the proposed provider product order/delivery flow.

## Summary

Current product order support is mostly a customer checkout/order-history flow.

Already exists:

- Customer cart APIs.
- Product checkout from cart.
- Product payment handling for cash, wallet, Razorpay, Stripe/web gateways, PhonePe/web callback.
- Customer product order list/detail APIs.
- Admin web pages to view product orders and update simple order/payment statuses.
- Product catalog provider APIs recently added: `user-product-list`, `product-save`, `product-update`, `product-delete`.

Not currently available:

- Provider product order list API.
- Provider accept/reject API specifically for product orders.
- Delivery boy assignment for product orders.
- Product order live location.
- Product order proof upload.
- Product order activity/status timeline table.
- Product order payment confirmation API for provider cash collection.
- Product order `provider_id`, `shop_id`, `handyman_id`, delivery coordinates, delivery status history columns.

## Existing Product Order Tables

### `product_orders`

Created by `database/migrations/2026_03_19_100001_create_product_orders_tables.php`.

Columns from the migration:

| Column | Purpose |
| --- | --- |
| `id` | Product order id. |
| `order_number` | Unique code like `PO-XXXXXXXX`. |
| `user_id` | Customer id. |
| `status` | Current status. Originally `pending`, `confirmed`, `cancelled`. |
| `subtotal` | Product subtotal. |
| `total` | Grand total. |
| `notes` | JSON text. Currently stores shipping address under `shipping`. |
| `created_at`, `updated_at` | Timestamps. |

Model `App\Models\ProductOrder` also supports these fields if columns exist in production:

| Column | Purpose |
| --- | --- |
| `tax_total` | Ecommerce tax total. |
| `payment_type` | Payment method, e.g. `cash`, `wallet`, `razorPay`. |
| `payment_status` | Payment state, e.g. `pending`, `paid`, `failed`. |
| `txn_id` | Transaction id. |
| `other_transaction_detail` | Gateway metadata. |
| `tax_detail` | JSON tax breakdown. |

Important limitation: the order table does not directly store provider/delivery fields. Provider ownership is inferred through the products in `product_order_items`.

### `product_order_items`

Columns:

| Column | Purpose |
| --- | --- |
| `id` | Order item id. |
| `product_order_id` | Parent order. |
| `product_id` | Ordered product. |
| `product_variant_id` | Optional variant id. |
| `product_name` | Snapshot product name. |
| `variant_label` | Snapshot variant label. |
| `unit_price` | Unit price at checkout. |
| `quantity` | Quantity ordered. |
| `line_total` | Item total. |
| `created_at`, `updated_at` | Timestamps. |

Provider can be inferred from `product_order_items.product_id -> products.provider_id`.

## Existing Customer Product APIs

These routes already exist in `routes/api.php`.

### Cart

| Endpoint | Controller | Purpose |
| --- | --- | --- |
| `GET /api/my-cart` | `API\ProductCartController@list` | Customer cart list. |
| `GET /api/cart-list` | `API\ProductCartController@list` | Alias for cart list. |
| `POST /api/cart-add` | `API\ProductCartController@add` | Add product/variant to cart. |
| `POST /api/cart-update` | `API\ProductCartController@update` | Update cart quantity. |
| `POST /api/cart-remove` | `API\ProductCartController@remove` | Remove cart item. |
| `POST /api/cart-checkout` | `API\ProductCartController@checkout` | Creates product order from cart. |

All cart APIs are customer-only. They reject non-`user` accounts.

### Payment

| Endpoint | Purpose |
| --- | --- |
| `POST /api/cart-razorpay-verify` | Verify Razorpay payment. |
| `POST /api/product-razorpay-verify` | Alias for Razorpay verification. |
| `POST /api/product-payment-complete` | Complete client-side gateway payment. |

### Customer Orders

| Endpoint | Controller | Purpose |
| --- | --- | --- |
| `GET /api/my-product-orders` | `API\ProductOrderController@list` | Customer order list. |
| `GET /api/my-product-orders/{id}` | `API\ProductOrderController@detail` | Customer order detail. |
| `POST /api/product-order-detail` | `API\ProductOrderController@detail` | Customer order detail by `id` or `order_id`. |

Important limitation: existing `product-order-detail` is customer-only. Provider accounts cannot use it today.

## Current Checkout Behavior

Checkout reads customer cart rows, validates stock, calculates taxes, decrements product/variant stock, creates order rows, then clears the cart.

Order creation:

- Creates `product_orders.status = pending`.
- Saves shipping address in `product_orders.notes` as:

```json
{
  "shipping": {
    "name": "Customer Name",
    "address": "Address",
    "city": "City",
    "state": "State",
    "pincode": "12345",
    "country": "India"
  }
}
```

Payment status behavior:

| Payment method | Order status | Payment status |
| --- | --- | --- |
| `cash` | `pending` | `pending` |
| `wallet` | `confirmed` | `paid` |
| successful Razorpay/client gateway | `confirmed` | `paid` |
| failed gateway | unchanged/pending | `failed` |

Stock behavior:

- Stock is reduced at checkout time.
- If product has variants, `product_variants.stock` is reduced.
- Product `total_stock` is also reduced.

## Existing Admin Product Order UI

Controller: `App\Http\Controllers\AdminProductOrderController`.

Admin can:

- View all product orders.
- Filter by customer/status.
- Open an order detail page.
- Update basic `status`.
- Update `payment_status` if the column exists.

Current admin status labels are simple:

| Status | Meaning |
| --- | --- |
| `pending` | Pending. |
| `confirmed` | Confirmed. |
| `cancelled` | Cancelled. |

Admin UI does not currently assign delivery boys or track delivery.

## Existing Booking Flow That Can Be Reused As Pattern

The service booking system already has the features requested for product delivery.

Useful existing concepts:

| Booking feature | Existing table/model | Product order equivalent needed |
| --- | --- | --- |
| Assign handyman | `booking_handyman_mappings` / `BookingHandymanMapping` | Product order delivery assignment table or columns. |
| Live location | `live_locations` / `LiveLocation` with `booking_id` | Product order live location table. |
| Service proof | `service_proofs` / `ServiceProof` media collection | Product order proof table/media collection. |
| Status history | `booking_activities` / `BookingActivity` | Product order activity/history table. |
| Provider filtering | `bookings.provider_id` | Product orders need direct provider id or query through items/products. |

Booking routes already exist:

| Endpoint | Purpose |
| --- | --- |
| `POST /api/booking-update` | Update booking status. |
| `POST /api/booking-assigned` | Assign handyman. |
| `POST /api/save-service-proof` | Upload proof. |
| `POST /api/update-location` | Update live location. |
| `GET /api/get-location` | Fetch live location. |

These do not work for product orders because they use booking ids/tables.

## Current Product Provider Catalog APIs

These were added for product catalog management, not order delivery.

| Endpoint | Purpose |
| --- | --- |
| `GET /api/user-product-list` | Provider product list. |
| `POST /api/product-save` | Create provider product. |
| `POST /api/product-update` | Update provider product. |
| `POST /api/product-delete` | Delete provider product. |

They do not handle orders.

## Gap Analysis Against Requested Product Order Provider API

| Requested feature | Exists now? | Notes |
| --- | --- | --- |
| Provider product order listing | Partial data exists | Need new API. Query can infer provider via ordered products. |
| Provider product order detail | No | Existing detail is customer-only. |
| Accept/reject order | Partial | Admin can update status, but no provider API. |
| Assign provider/self/delivery boy | No | No product order assignment table/columns. |
| Delivery boy list | Yes, via user list | Existing handyman list may be reusable. |
| Start delivery | No | Product order statuses do not include `assigned`/`on_going`. |
| Update delivery location | No | Booking location table is booking-only. |
| Fetch delivery location | No | Booking location table is booking-only. |
| Upload delivery proof | No | Service proof is booking-only. |
| Confirm cash payment | No dedicated API | Payment status exists, but no provider endpoint/rules. |
| Activity timeline | No | Product order activity table does not exist. |

## Recommended Build Direction

To implement the proposed spec cleanly, add product-order-specific support instead of overloading booking tables:

1. Add columns or tables for product order assignment:
   - `product_order_assignments` with `product_order_id`, `handyman_id`.
   - Or simpler columns on `product_orders`: `provider_id`, `handyman_id`.

2. Add `product_order_activities`:
   - `product_order_id`
   - `activity_type`
   - `activity_message`
   - `activity_data`
   - `created_by`
   - `datetime`

3. Add `product_order_live_locations`:
   - `product_order_id`
   - `user_id`
   - `latitude`
   - `longitude`

4. Add `product_order_proofs` with Spatie media:
   - `product_order_id`
   - `user_id`
   - `description`
   - media collection `proof_attachment`

5. Add provider APIs:
   - `GET /api/provider-product-order-list`
   - provider-enabled `POST /api/product-order-detail`
   - `POST /api/product-order-update`
   - `POST /api/product-order-assigned`
   - `POST /api/product-order-update-location`
   - `GET|POST /api/product-order-location`
   - `POST /api/product-order-proof-save`
   - `POST /api/product-order-payment-confirm`

## Important Existing Constraint

Current `product_orders` can contain items from multiple providers if the customer cart has products from different providers. The current checkout does not split orders by provider.

For a provider order flow, backend must choose one of these approaches:

1. Split checkout into one `product_order` per provider.
2. Keep one order but make provider APIs show only that provider's items inside the order.
3. Add a provider order/suborder table.

The cleanest delivery flow is option 1 or 3. Option 2 is faster but can create confusing totals and statuses when multiple providers share one customer order.
