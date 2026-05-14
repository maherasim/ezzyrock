# App API Guide: Free Posts and Featured Posts

This document explains how the mobile app should handle normal classified posts, featured classified posts, quota display, and API errors.

## Business Logic

There are two different post types:

1. Normal post
   - `is_featured = 0`
   - Uses monthly free post quota.
   - Example: admin sets free posts to `2`, so user can create 2 normal posts this month.
   - This quota resets every month.

2. Featured post
   - `is_featured = 1`
   - Requires an active paid user plan.
   - Uses `Featured Classified limitations` from the purchased plan.
   - Free posts do not allow featured posts.

Important:

- Free post quota is only for non-featured posts.
- Featured post quota is only from paid plan.
- If free post quota is finished, user cannot create more normal posts until next month.
- If user wants featured post, they must purchase a plan with featured classified limit.

## Main APIs

### 1. Login

Endpoint:

```http
POST /api/login
```

Purpose:

- Login user.
- Return quota information for display only.
- Do not rely only on login for enforcement because user may stay logged in for a long time.

Important response fields inside `data`:

```json
{
  "free_posts": 2,
  "free_posts_used_count": 1,
  "featured_posts_used_count": 0,
  "free_post_quota": {
    "monthly_limit": 2,
    "used_this_month": 1,
    "remaining": 1,
    "allow_to_create_post": true,
    "reset_at": "2026-05-31 23:59:59"
  },
  "featured_post_quota": {
    "paid_plan_limit": 5,
    "total_limit": 5,
    "used": 0,
    "remaining": 5,
    "is_unlimited": false,
    "allow_to_create_featured": true,
    "has_active_subscription": true,
    "subscription_id": 12,
    "reset_at": "2026-06-14 10:28:27"
  }
}
```

App handling:

- Show normal post remaining from `free_post_quota.remaining`.
- Show featured post remaining from `featured_post_quota.remaining`.
- If `featured_post_quota.has_active_subscription = false`, show purchase plan CTA for featured posts.

### 2. Post Form Config

Endpoint:

```http
GET /api/post-form-config
Authorization: Bearer {token}
```

Purpose:

- Use before opening the create/edit post form.
- Returns categories, subcategories, zones, existing post if editing, and latest quota.

Example response:

```json
{
  "status": true,
  "data": {
    "categories": [],
    "subcategories": [],
    "zones": [],
    "post": null,
    "free_post_quota": {
      "monthly_limit": 2,
      "used_this_month": 2,
      "remaining": 0,
      "allow_to_create_post": false,
      "reset_at": "2026-05-31 23:59:59"
    },
    "featured_post_quota": {
      "paid_plan_limit": 5,
      "total_limit": 5,
      "used": 1,
      "remaining": 4,
      "is_unlimited": false,
      "allow_to_create_featured": true,
      "has_active_subscription": true,
      "subscription_id": 12,
      "reset_at": "2026-06-14 10:28:27"
    }
  }
}
```

App handling before create:

- If user selects normal post (`is_featured = 0`), check:

```json
free_post_quota.allow_to_create_post
```

- If false, block normal post submit and show:

```text
Your free post limit is finished for this month.
```

- If user selects featured post (`is_featured = 1`), check:

```json
featured_post_quota.allow_to_create_featured
```

- If false, block featured post submit and show purchase plan CTA.

### 3. Save Post

Endpoint:

```http
POST /api/save-post
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

Purpose:

- Create or update classified post.
- This API is the final enforcement point.
- App should still handle UI checks, but API will block invalid quota use.

Common payload fields:

```json
{
  "name": "iPhone 15 Pro",
  "category_id": 1,
  "subcategory_id": 2,
  "description": "Good condition",
  "price": 1000,
  "is_featured": 0,
  "service_zones": [1, 2]
}
```

For normal post:

```json
{
  "is_featured": 0
}
```

For featured post:

```json
{
  "is_featured": 1
}
```

Success response:

```json
{
  "status": true,
  "message": "Post saved successfully",
  "data": {},
  "free_post_quota": {
    "monthly_limit": 2,
    "used_this_month": 2,
    "remaining": 0,
    "allow_to_create_post": false,
    "reset_at": "2026-05-31 23:59:59"
  },
  "featured_post_quota": {
    "paid_plan_limit": 5,
    "total_limit": 5,
    "used": 1,
    "remaining": 4,
    "is_unlimited": false,
    "allow_to_create_featured": true,
    "has_active_subscription": true,
    "subscription_id": 12,
    "reset_at": "2026-06-14 10:28:27"
  }
}
```

### 4. User Subscription History

Endpoint:

```http
GET /api/user-subscription-history
Authorization: Bearer {token}
```

Purpose:

- Shows logged-in user's purchased plan history from `user_subscriptions`.
- App can use this for "My Plans", "Subscription History", or plan status screen.

Optional query params:

```http
GET /api/user-subscription-history?per_page=10&orderby=desc
GET /api/user-subscription-history?status=active
GET /api/user-subscription-history?module=classified
GET /api/user-subscription-history?per_page=all
```

Success response:

```json
{
  "status": true,
  "pagination": {
    "total_items": 1,
    "per_page": 10,
    "currentPage": 1,
    "totalPages": 1,
    "from": 1,
    "to": 1,
    "next_page": null,
    "previous_page": null
  },
  "data": [
    {
      "id": 1,
      "plan_id": 1,
      "title": "BASIC classified",
      "identifier": "ncie",
      "type": "limited",
      "amount": 90,
      "status": "active",
      "computed_status": "active",
      "is_active": true,
      "is_expired": false,
      "start_at": "2026-05-14 10:28:27",
      "end_at": "2026-05-21 10:28:27",
      "days_left": 7,
      "duration": null,
      "plan_type": null,
      "module": "classified",
      "featured_posts_limit": 5,
      "plan_limitation": {
        "featured_classified": {
          "is_checked": "on",
          "limit": "5"
        }
      },
      "payment": {
        "id": 1,
        "amount": 90,
        "payment_type": "razorPay",
        "payment_status": "paid",
        "txn_id": "pay_xxxxx",
        "created_at": "2026-05-14T10:28:27.000000Z"
      },
      "created_at": "2026-05-14T10:28:27.000000Z",
      "updated_at": "2026-05-14T10:28:27.000000Z"
    }
  ]
}
```

App handling:

- Show plan title from `title`.
- Show payment amount from `amount`.
- Use `computed_status` for display. It can show `expired` when DB status is still `active` but `end_at` has passed.
- Use `is_active` to decide whether this plan is currently usable.
- Show featured limit from `featured_posts_limit`.
- Show payment state from `payment.payment_status`.

## Error Responses

### Free Normal Post Limit Finished

This happens when app sends `is_featured = 0` and monthly free quota is finished.

HTTP status:

```http
422
```

Response:

```json
{
  "status": false,
  "message": "Your free post limit is finished for this month. Please wait for next month or purchase a plan to create featured posts.",
  "free_post_quota": {
    "monthly_limit": 2,
    "used_this_month": 2,
    "remaining": 0,
    "allow_to_create_post": false,
    "reset_at": "2026-05-31 23:59:59"
  },
  "featured_post_quota": {
    "paid_plan_limit": 0,
    "total_limit": 0,
    "used": 0,
    "remaining": 0,
    "is_unlimited": false,
    "allow_to_create_featured": false,
    "has_active_subscription": false,
    "subscription_id": null,
    "reset_at": null
  }
}
```

App handling:

- Show the message.
- Disable normal post submit.
- Show reset date from `free_post_quota.reset_at`.
- Optionally show plan purchase CTA if user wants featured post.

### Featured Post Plan Missing or Limit Finished

This happens when app sends `is_featured = 1` and user has no active featured plan or paid featured quota is finished.

HTTP status:

```http
422
```

Response:

```json
{
  "status": false,
  "message": "Please purchase a plan to create featured posts.",
  "free_post_quota": {
    "monthly_limit": 2,
    "used_this_month": 1,
    "remaining": 1,
    "allow_to_create_post": true,
    "reset_at": "2026-05-31 23:59:59"
  },
  "featured_post_quota": {
    "paid_plan_limit": 0,
    "total_limit": 0,
    "used": 0,
    "remaining": 0,
    "is_unlimited": false,
    "allow_to_create_featured": false,
    "has_active_subscription": false,
    "subscription_id": null,
    "reset_at": null
  }
}
```

App handling:

- Show purchase plan CTA.
- User may still create normal post only if `free_post_quota.allow_to_create_post = true`.

## Recommended App UI Rules

On create post screen:

1. Load `/api/post-form-config`.
2. Show remaining free posts:

```text
Free posts remaining this month: {free_post_quota.remaining}
```

3. Show featured status:

```text
Featured posts remaining: {featured_post_quota.remaining}
```

4. If user turns featured off:
   - Use `free_post_quota`.
   - Submit with `is_featured = 0`.

5. If user turns featured on:
   - Use `featured_post_quota`.
   - Submit with `is_featured = 1`.
   - If not allowed, redirect to plan purchase.

## Simple Decision Table

| User action | API field | Required quota |
| --- | --- | --- |
| Create normal post | `is_featured = 0` | `free_post_quota.remaining > 0` |
| Create featured post | `is_featured = 1` | `featured_post_quota.remaining > 0` or `is_unlimited = true` |
| Free quota finished | `is_featured = 0` | Block until next month |
| No featured plan | `is_featured = 1` | Block and show purchase plan |

## Notes

- `free_posts` is kept for backward compatibility, but new app code should use `free_post_quota`.
- `featured_posts_used_count` is kept for backward compatibility, but new app code should use `featured_post_quota.used`.
- Final validation always happens in `/api/save-post`, even if app already checks quota in UI.
