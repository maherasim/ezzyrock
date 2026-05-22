# Product Provider API

These endpoints mirror the web pages at `/product` and `/product/create` for ecommerce products. All endpoints below require `Authorization: Bearer <sanctum_token>` and should be sent as `multipart/form-data` when uploading images.

## 1. Product Listing

`GET /api/user-product-list`

Returns products owned by the logged-in provider.

Query params:

| Field | Type | Required | Notes |
| --- | --- | --- | --- |
| `per_page` | integer/string | no | Number per page. Use `all` for no pagination. Default uses `constant.PER_PAGE_LIMIT`. |
| `status` | integer | no | `1` active, `0` inactive. |
| `service_request_status` | string | no | `pending`, `approve`, or `reject`. |
| `category_id` | integer/string/array | no | Single id, comma-separated ids, or array. |
| `subcategory_id` | integer/string/array | no | Single id, comma-separated ids, or array. |
| `search` | string | no | Searches product name and description. |

Success response:

```json
{
  "status": true,
  "data": [
    {
      "id": 12,
      "name": "Blue Guitar",
      "category_id": 5,
      "subcategory_id": 9,
      "provider_id": 44,
      "price": 1200,
      "price_format": "$1,200.00",
      "discount": 10,
      "status": 1,
      "description": "Short product description",
      "is_featured": 0,
      "product_image": "https://example.com/storage/...",
      "attchments_array": [],
      "total_stock": 20,
      "max_purchase_qty": 2,
      "product_unit_id": 1,
      "product_unit_name": "Piece",
      "service_request_status": "approve",
      "variants": []
    }
  ],
  "pagination": {
    "total_items": 1,
    "per_page": 15,
    "currentPage": 1,
    "totalPages": 1
  }
}
```

## 2. Product Categories

Use these product-specific endpoints for the mobile product category filters. They return only active ecommerce categories that currently have available products.

`GET /api/product-category-list`

Query params:

| Field | Type | Required | Notes |
| --- | --- | --- | --- |
| `per_page` | integer/string | no | Number per page. Use `all` for no pagination. |
| `is_featured` | integer | no | Optional `1` or `0` filter. |
| `q` | string | no | Search by category name, matching the web Select2 search behavior. |
| `city_id` | integer | no | Filters categories to products from providers in this city. |
| `latitude` / `longitude` | decimal | no | Filters categories to products available in the matching zone. |

## 3. Product Subcategories

`GET /api/product-subcategory-list`

Returns only subcategories whose parent category is an active ecommerce category and that currently have available products.

Query params:

| Field | Type | Required | Notes |
| --- | --- | --- | --- |
| `category_id` | integer | no | Use after the user selects a product category. |
| `per_page` | integer/string | no | Number per page. Use `all` for no pagination. |
| `is_featured` | integer | no | Optional `1` or `0` filter. |
| `q` | string | no | Search by subcategory name. |
| `city_id` | integer | no | Filters subcategories to products from providers in this city. |
| `latitude` / `longitude` | decimal | no | Filters subcategories to products available in the matching zone. |

Do not use the shared `/api/category-list` or `/api/subcategory-list` without `module_type=ecommerce`; those endpoints can return service/classified categories.

## 4. Product Form Config

`GET /api/product-form-config`

Use this before rendering the mobile create/edit product form. It returns the same active product variant attributes and product units used by `/product/create`.

Success response:

```json
{
  "status": true,
  "data": {
    "attributes": [
      {
        "id": 4,
        "name": "Color",
        "options": [
          { "id": 8, "value": "Blue" },
          { "id": 9, "value": "Red" }
        ]
      }
    ],
    "units": [
      { "id": 1, "name": "Piece" }
    ],
    "variant_payload": {
      "product_attribute_id": "required when variants are used",
      "variant_labels": "array of option values, e.g. Small/Medium/Large",
      "variant_price": "array matching variant_labels order",
      "variant_stock": "array matching variant_labels order"
    }
  }
}
```

Variant behavior is intentionally the same as the web form:

| Step | Mobile behavior |
| --- | --- |
| Select attribute | User chooses one active attribute, for example `Color` or `Size`. Send its id as `product_attribute_id`. |
| Enter values | User enters labels/tags, for example `Blue`, `Red`, `Green`. Send those as `variant_labels[]`. |
| Enter rows | For every label, send one `variant_price[]` and one `variant_stock[]` in the same order. |
| Save | The API reuses an existing option with the same label under that attribute, or creates it if missing. |
| Product totals | If variants are sent, product `price` becomes the minimum variant price and `total_stock` becomes the sum of variant stock. |
| Remove variants | On update, sending no variant labels removes all variants for that product. |

Example variant payload:

```text
product_attribute_id=4
variant_labels[]=Blue
variant_price[]=1200
variant_stock[]=5
variant_labels[]=Red
variant_price[]=1250
variant_stock[]=3
```

## 5. Product Detail for Edit

`POST /api/product-detail`

Use this existing endpoint to load a product before editing. Send either `product_id` or `id`.

Payload:

| Field | Type | Required | Notes |
| --- | --- | --- | --- |
| `product_id` | integer | yes | Product id. `id` also works. |

Success response:

```json
{
  "status": true,
  "data": {
    "id": 12,
    "name": "Blue Guitar",
    "category_id": 5,
    "subcategory_id": 9,
    "price": 1200,
    "status": 1,
    "total_stock": 20,
    "max_purchase_qty": 2,
    "requires_variant_selection": true,
    "variant_attribute_name": "Color",
    "variants": [
      {
        "id": 3,
        "product_variant_id": 3,
        "product_attribute_option_id": 8,
        "option_value": "Blue",
        "attribute_name": "Color",
        "label": "Color: Blue",
        "price": 1200,
        "stock": 20,
        "max_allowed_quantity": 2,
        "is_available": true,
        "status": 1
      }
    ]
  },
  "variants": [],
  "has_variants": true
}
```

## 6. Create Product

`POST /api/product-save`

Create uses the same behavior as `/product/create`: ecommerce product, provider-owned, status chosen by provider, and request status becomes `approve`.

Payload fields:

| Field | Type | Required | Notes |
| --- | --- | --- | --- |
| `name` | string | yes | Unique per provider. |
| `provider_id` | integer | admin only | Optional. Admin/demo admin can create for another provider; normal providers always use their own account. |
| `category_id` | integer | yes | Must be an active ecommerce category from `/api/product-category-list`. |
| `subcategory_id` | integer/null | no | Must belong to `category_id` and to an ecommerce category when supplied. |
| `type` | string | no | Defaults to `fixed`. Web form uses hidden ecommerce product type. |
| `price` | decimal | yes | Minimum `0`. If variants are sent, product price becomes the minimum variant price. |
| `discount` | decimal | no | Percentage, `0` to `99`. |
| `description` | string | no | Product details. |
| `total_stock` | integer | yes | Minimum `0`. If variants are sent, total stock becomes the sum of variant stock. |
| `max_purchase_qty` | integer/null | no | Maximum quantity allowed in one order. Minimum `1`. |
| `product_unit_id` | integer/null | no | Must be an active unit from `/api/product-form-config`. |
| `status` | integer | yes | `1` active, `0` inactive. |
| `is_featured` | boolean/integer | no | `1` featured, `0` normal. Feature quota is checked. |
| `service_zones[]` | integer array | yes | Active zone ids. The web page requires at least one zone. |
| `shop_ids[]` | integer array | no | Shop ids to attach to the product. |
| `product_attribute_id` | integer/null | no | Required only when variants are used. Must be an active attribute from `/api/product-form-config`. |
| `variant_labels[]` | string array | no | Example: `Small`, `Medium`, `Large`. Creates/reuses options for the selected attribute. |
| `variant_price[]` | decimal array | required with variants | Same length/order as `variant_labels[]`. |
| `variant_stock[]` | integer array | required with variants | Same length/order as `variant_labels[]`. |
| `product_attachment[]` | file array | yes on create | Images: jpeg, png, jpg, gif. Max 10 MB each. |
| `attachment_count` | integer | yes if using counted upload | Alternative mobile upload format. |
| `product_attachment_0` | file | yes if using counted upload | Use with `attachment_count`; continue `product_attachment_1`, etc. |
| `seo_enabled` | boolean/integer | no | Enables SEO fields. |
| `meta_title` | string | no | Max 255. Also used to generate slug when present. |
| `meta_description` | string | no | Max 200. |
| `meta_keywords` | string | no | Comma-separated keywords. |
| `seo_image` | file | no | jpeg, png, jpg, gif. Max 10 MB. |

Example multipart payload:

```text
name=Blue Guitar
category_id=5
subcategory_id=9
type=fixed
price=1200
discount=10
description=Short product description
total_stock=20
max_purchase_qty=2
product_unit_id=1
status=1
is_featured=0
service_zones[]=1
service_zones[]=2
product_attribute_id=4
variant_labels[]=Blue
variant_price[]=1200
variant_stock[]=20
product_attachment[]=@/path/image.jpg
```

Success response:

```json
{
  "status": true,
  "message": "Product has been saved successfully",
  "data": {
    "id": 12,
    "name": "Blue Guitar",
    "price": 1200,
    "total_stock": 20,
    "service_type": "ecommerce",
    "service_request_status": "approve",
    "product_image": "https://example.com/storage/..."
  }
}
```

Validation error:

```json
{
  "status": false,
  "message": "The product attachment field is required.",
  "errors": {
    "product_attachment_required": [
      "The product attachment field is required."
    ]
  }
}
```

## 7. Edit Product

`POST /api/product-update`

This uses the same save logic as create. Send `id` plus the fields to save. For now, required create fields are still required on update so the app should submit the full form state.

Important behavior:

| Behavior | Notes |
| --- | --- |
| Ownership | Provider can update only their own product. Admin can update any product. |
| Images | `product_attachment[]` is optional on edit. If sent, it replaces the current product image collection. |
| Variants | If variant arrays are sent, variants are synced. If no variants are sent, all product variants are removed. |
| Zones | `service_zones[]` replaces the product zone mapping. |
| Shops | `shop_ids[]` replaces shop mappings only when the field is present. |

Example payload:

```text
id=12
name=Blue Guitar Updated
category_id=5
price=1300
total_stock=18
status=1
service_zones[]=1
```

Success response is the same shape as create, with an update message.

## 8. Delete Product

`POST /api/product-delete`

Soft-deletes a provider-owned ecommerce product.

Payload:

| Field | Type | Required | Notes |
| --- | --- | --- | --- |
| `id` | integer | yes | Product id. |

Success response:

```json
{
  "status": true,
  "message": "Product has been deleted successfully"
}
```

Unauthorized response:

```json
{
  "status": false,
  "message": "Unauthorized"
}
```


https://ezzyrock.com/ajax-list?type=category&language_id=en&module_type=ecommerce&ids%5B%5D=
Request Method
GET
Status Code
[
    {
        "id": 46,
        "text": "Mobiles"
    },
    {
        "id": 47,
        "text": "Audio Devices"
    },
    {
        "id": 48,
        "text": "Mobile Accessories"
    },
    {
        "id": 49,
        "text": "Laptops & Tablets"
    },
    {
        "id": 50,
        "text": "Computer Accessories"
    },
    {
        "id": 51,
        "text": "Smart Home Devices"
    },
    {
        "id": 52,
        "text": "Electronics"
    },
    {
        "id": 53,
        "text": "Gaming Electronics"
    },
    {
        "id": 54,
        "text": "Appliances"
    },
    {
        "id": 55,
        "text": "Car Electronics"
    },
    {
        "id": 56,
        "text": "Gadgets"
    },
    {
        "id": 68,
        "text": "Pain Relief & Fever"
    },
    {
        "id": 69,
        "text": "Cold, Cough & Flu"
    },
    {
        "id": 70,
        "text": "Skin Care (Medical + OTC)"
    },
    {
        "id": 71,
        "text": "Hair Care (Treatment Based)"
    },
    {
        "id": 72,
        "text": "Oral Care"
    },
    {
        "id": 73,
        "text": "Vitamins & Supplements"
    },
    {
        "id": 74,
        "text": "Brain, Nerve & Energy Care"
    },
    {
        "id": 75,
        "text": "Personal Hygiene & Daily Care"
    },
    {
        "id": 76,
        "text": "First Aid Essentials"
    },
    {
        "id": 77,
        "text": "Medical Devices"
    },
    {
        "id": 78,
        "text": "Baby Care"
    },
    {
        "id": 79,
        "text": "Women\u2019s Health \/ Feminine Care"
    },
    {
        "id": 80,
        "text": "Fitness & Nutrition"
    },
    {
        "id": 81,
        "text": "Digestive & Stomach Care"
    },
    {
        "id": 82,
        "text": "Ayurvedic & Herbal"
    },
    {
        "id": 83,
        "text": "Sexual Wellness"
    },
    {
        "id": 84,
        "text": "Elderly Care"
    },
    {
        "id": 85,
        "text": "Chronic Disease Care"
    },
    {
        "id": 86,
        "text": "Seasonal \/ Preventive Care"
    },
    {
        "id": 87,
        "text": "Skincare"
    },
    {
        "id": 88,
        "text": "Makeup"
    },
    {
        "id": 89,
        "text": "Bath & Body"
    },
    {
        "id": 90,
        "text": "Fragrance & Perfume"
    },
    {
        "id": 91,
        "text": "Grooming & Hygiene"
    },
    {
        "id": 92,
        "text": "Beauty Tools"
    }
]     
https://ezzyrock.com/ajax-list?type=subcategory&category_id=47&language_id=en&ids%5B%5D=
Request Method
GET  [
    {
        "id": 193,
        "text": "Wired earphones"
    },
    {
        "id": 194,
        "text": "Wireless earbuds (TWS)"
    },
    {
        "id": 195,
        "text": "Neckbands"
    },
    {
        "id": 196,
        "text": "Headphones"
    },
    {
        "id": 197,
        "text": "Bluetooth speakers"
    },
    {
        "id": 198,
        "text": "Soundbars"
    }
]