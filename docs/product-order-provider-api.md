# Product Order Provider API

Implemented endpoints for the provider product order delivery flow.

Run this SQL before using these APIs:

```text
docs/product-order-delivery-tables.sql
```

New tables:

- `product_order_assignments`
- `product_order_activities`
- `product_order_live_locations`
- `product_order_proofs`

All endpoints require Sanctum auth:

```http
Authorization: Bearer <token>
Accept: application/json
```

## Endpoints

| Method | Endpoint | Purpose |
| --- | --- | --- |
| `GET` | `/api/provider-product-order-list` | Provider/delivery boy order list. |
| `POST` | `/api/product-order-detail` | Provider/delivery boy/customer order detail. |
| `POST` | `/api/product-order-update` | Accept, reject, start delivery, delivered, completed, cancelled. |
| `POST` | `/api/product-order-assigned` | Assign provider self or provider handyman as delivery boy. |
| `POST` | `/api/product-order-update-location` | Update delivery live location. |
| `GET` | `/api/product-order-location?id=101` | Fetch delivery live location. |
| `POST` | `/api/product-order-location` | Fetch delivery live location. |
| `POST` | `/api/product-order-proof-save` | Upload delivery proof. |
| `POST` | `/api/product-order-payment-confirm` | Confirm/update order payment status. |

## Supported Statuses

```text
pending
accept
assigned
on_going
delivered
completed
cancelled
rejected
confirmed
```

`confirmed` is kept because existing paid product orders already use it.

## Provider List

`GET /api/provider-product-order-list`

Filters:

| Field | Type |
| --- | --- |
| `page` | integer |
| `per_page` | integer or `all` |
| `status` | string or comma-separated string |
| `payment_status` | string or comma-separated string |
| `payment_type` | string or comma-separated string |
| `date_from` | `yyyy-MM-dd` |
| `date_to` | `yyyy-MM-dd` |
| `customer_id` | integer or comma-separated ids |
| `handyman_id` | integer or comma-separated ids |
| `shop_id` | integer or comma-separated ids |
| `search` | string |

Provider users see orders containing their products. Handyman users see orders assigned to them.

## Detail

`POST /api/product-order-detail`

Payload:

```json
{
  "id": 101
}
```

Customer users still get the old customer detail response. Provider/handyman users get delivery detail with customer, provider, shop, assignment, activity, proof, items, and latest location.

## Update Status

`POST /api/product-order-update`

```json
{
  "id": 101,
  "status": "accept",
  "reason": "",
  "payment_status": "pending"
}
```

Creates a row in `product_order_activities`.

If the `product_orders.delivery_status` column exists, this endpoint updates it to the same value as `status`. Assignment also sets `delivery_status = assigned`.

## Assign Delivery Boy

`POST /api/product-order-assigned`

Assign provider to self:

```json
{
  "id": 101,
  "handyman_id": [67]
}
```

Assign provider handyman:

```json
{
  "id": 101,
  "handyman_id": [88]
}
```

The assigned user must be the provider himself or a `handyman` where `users.provider_id` equals the logged-in provider id. Order status becomes `assigned`.

## Update Location

`POST /api/product-order-update-location`

```json
{
  "id": 101,
  "latitude": "24.8607",
  "longitude": "67.0011"
}
```

Only the provider owner or assigned delivery boy can update location.

## Get Location

`GET /api/product-order-location?id=101`

or

```json
{
  "id": 101
}
```

## Proof Upload

`POST /api/product-order-proof-save`

Use `multipart/form-data`.

```text
id=101
description=Delivered to customer
attachment_count=2
proof_attachment_0=@photo1.jpg
proof_attachment_1=@photo2.jpg
```

Files are saved in Spatie media collection `proof_attachment` on `ProductOrderProof`.

## Confirm Payment

`POST /api/product-order-payment-confirm`

```json
{
  "id": 101,
  "payment_status": "pending_by_admin",
  "remarks": "Cash collected"
}
```

Updates `product_orders.payment_status` and records activity.

## Notes

- Existing checkout is not changed.
- Current checkout can still create one order containing products from multiple providers.
- Provider list/detail returns only the logged-in provider's items when the actor is a provider.
- Order-level payment status remains shared across the whole product order.
- Status updates, assignment, proof upload, and payment confirmation send notifications using the existing booking notification template types so the app receives them through the same channels as service bookings.
