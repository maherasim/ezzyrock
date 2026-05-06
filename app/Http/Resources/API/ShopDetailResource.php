<?php

namespace App\Http\Resources\API;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (!$this->resource) {
            return [];
        }

            $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
    $sitesetupValue = json_decode($sitesetup->value);
    $timezone = $sitesetupValue->time_zone ?? 'UTC';
    $timeformate = $sitesetupValue->time_format ?? 'H:i';

        return [
            'id' => $this->id,
            'registration_number' => $this->registration_number,
            'name' => $this->shop_name,
            'country_id' => $this->country_id,
            'country_name' => optional($this->country)->name ?? null,
            'state_id' => $this->state_id,
            'state_name' => optional($this->state)->name ?? null,
            'city_id' => $this->city_id,
            'city_name' => optional($this->city)->name ?? null,
            'address' => $this->address,
           'shop_start_time' => $this->shop_start_time
            ? Carbon::parse($this->shop_start_time)->timezone($timezone)->format($timeformate)
            : null,
        'shop_end_time' => $this->shop_end_time
            ? Carbon::parse($this->shop_end_time)->timezone($timezone)->format($timeformate)
            : null,
            'latitude' => $this->lat,
            'longitude' => $this->long,
            'contact_number' => $this->contact_number,
            'email' => $this->email,
            'shop_image' => getAttachments($this->getMedia('shop_attachment')),
            'services' => $this->services->take(5)->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'price' => $service->price,
                    'attchments' => getAttachments($service->getMedia('service_attachment')),
                    'discount_price' => $service->price - ($service->price * $service->discount / 100),
                    'category_name' => $service->category->name,
                    'rating' => $service->serviceRating->avg('rating'),
                ];
            }),
            'provider_id' => $this->provider_id,
            'provider_name' => optional($this->provider)->display_name,
            'provider_image' => getSingleMedia($this->provider, 'profile_image', null),
            'providers_service_rating' => optional($this->provider)->getServiceRating->avg('rating'),
        ];
    }
}
