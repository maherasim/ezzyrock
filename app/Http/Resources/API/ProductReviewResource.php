<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductReviewResource extends JsonResource
{
    public function toArray($request)
    {
        $user = $this->whenLoaded('user');

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_order_id' => $this->product_order_id,
            'product_order_item_id' => $this->product_order_item_id,
            'user_id' => $this->user_id,
            'user_name' => optional($user)->display_name,
            'user_email' => optional($user)->email,
            'user_image' => optional($user)->login_type != null
                ? optional($user)->social_image
                : getSingleMedia($user, 'profile_image', null),
            'rating' => (float) $this->rating,
            'comment' => $this->comment,
            'status' => (int) $this->status,
            'created_at' => optional($this->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($this->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
