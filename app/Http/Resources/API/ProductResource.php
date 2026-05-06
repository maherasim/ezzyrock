<?php

namespace App\Http\Resources\API;

use App\Traits\TranslationTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    use TranslationTrait;

    public function toArray($request)
    {
        $headerValue = $request->header('language-code') ?? session()->get('locale', 'en');

        return [
            'id' => $this->id,
            'name' => $this->getTranslation($this->translations, $headerValue, 'name', $this->name) ?? $this->name,
            'category_id' => $this->category_id,
            'subcategory_id' => $this->subcategory_id,
            'provider_id' => $this->provider_id,
            'price' => $this->price,
            'price_format' => getPriceFormat($this->price),
            'discount' => $this->discount,
            'status' => $this->status,
            'description' => $this->getTranslation($this->translations, $headerValue, 'description', $this->description ?? null) ?? $this->description,
            'is_featured' => $this->is_featured,
            'provider_name' => optional($this->providers)->display_name,
            'provider_image' => optional($this->providers)->login_type != null
                ? optional($this->providers)->social_image
                : getSingleMedia(optional($this->providers), 'profile_image', null),
            'city_id' => optional($this->providers)->city_id,
            'category_name' => $this->getTranslation(optional($this->category)->translations, $headerValue, 'name', optional($this->category)->name ?? null) ?? optional($this->category)->name,
            'subcategory_name' => $this->getTranslation(optional($this->subcategory)->translations, $headerValue, 'name', optional($this->subcategory)->name ?? null) ?? optional($this->subcategory)->name,
            'attchments' => getAttachments($this->getMedia('product_attachment')),
            'attchments_array' => getAttachmentArray($this->getMedia('product_attachment'), null),
            'has_variants' => $this->variants->where('status', true)->where('stock', '>', 0)->count() > 0,
            'service_type' => $this->service_type,
            'service_request_status' => $this->service_request_status,
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
