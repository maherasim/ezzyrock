<?php

namespace App\Http\Resources\API;

use App\Traits\TranslationTrait;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\API\ProductVariantResource;

class ProductResource extends JsonResource
{
    use TranslationTrait;

    public function toArray($request)
    {
        $headerValue = $request->header('language-code') ?? session()->get('locale', 'en');
        $serviceZones = $this->relationLoaded('zones')
            ? $this->zones->map(function ($zone) {
                return [
                    'id' => $zone->id,
                    'name' => $zone->name,
                ];
            })->values()
            : collect();
        $serviceZoneIds = $serviceZones->pluck('id')->values();

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
            'zone_id' => $serviceZoneIds->first(),
            'zone_name' => $serviceZones->first()['name'] ?? null,
            'service_zones' => $serviceZones,
            'service_zone_ids' => $serviceZoneIds,
            'zones' => $serviceZones,
            'attchments' => getAttachments($this->getMedia('product_attachment')),
            'product_image' => getSingleMedia($this, 'product_attachment', null),
            'attchments_array' => getAttachmentArray($this->getMedia('product_attachment'), null),
            'has_variants' => $this->variants->where('status', true)->where('stock', '>', 0)->count() > 0,
            'variants' => ProductVariantResource::collection($this->variants->where('status', true)->where('stock', '>', 0)),
            'total_stock' => (int) ($this->total_stock ?? 0),
            'max_purchase_qty' => $this->max_purchase_qty,
            'requires_variant_selection' => $this->variants->where('status', true)->where('stock', '>', 0)->count() > 0,
            'variant_attribute_name' => optional(optional(optional($this->variants->where('status', true)->where('stock', '>', 0)->first())->option)->attribute)->name,
            'product_unit_id' => $this->product_unit_id,
            'product_unit_name' => optional($this->productUnit)->name,
            'service_type' => $this->service_type,
            'service_request_status' => $this->service_request_status,
            'total_review' => $this->reviews_count ?? 0,
            'total_rating' => $this->reviews_avg_rating ? (float) number_format($this->reviews_avg_rating, 2) : 0,
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
