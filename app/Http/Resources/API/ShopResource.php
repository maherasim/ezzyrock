<?php

namespace App\Http\Resources\API;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopResource extends JsonResource
{



public function toArray(Request $request): array
{
    if (!$this->resource) {
        return [];
    }

    $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
    $sitesetupValue = json_decode(optional($sitesetup)->value ?? '{}');
    $targetTimezone = isset($sitesetupValue->time_zone) ? trim((string) $sitesetupValue->time_zone) : 'UTC';
    $timeFormat = $sitesetupValue->time_format ?? 'H:i';
    $sourceTimezone = 'UTC';

    $convertFromRawUtc = function ($raw) use ($sourceTimezone, $targetTimezone, $timeFormat) {
        if (empty($raw)) {
            return null;
        }
        try {
            return Carbon::parse($raw, $sourceTimezone)->setTimezone($targetTimezone)->format($timeFormat);
        } catch (\Exception $e) {
            return null;
        }
    };


    return [
        'id' => $this->id,
        'name' => $this->shop_name,
        'country_name' => optional($this->country)->name ?? null,
        'state_name' => optional($this->state)->name ?? null,
        'city_name' => optional($this->city)->name ?? null,
        'address' => $this->address,
        'latitude' => $this->lat,
        'longitude' => $this->long,

        // Convert times using raw DB values (stored in UTC)
        'shop_start_time' => $convertFromRawUtc($this->getRawOriginal('shop_start_time')),
        'shop_end_time' => $convertFromRawUtc($this->getRawOriginal('shop_end_time')),

        'contact_number' => $this->contact_number,
        'email' => $this->email,
        'shop_image' => getAttachments($this->getMedia('shop_attachment')),
        'services_count' => $this->services->count() ?? 0,
        'services' => $this->services->take(3)->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
            ];
        }),
        'provider_name' => optional($this->provider)->display_name,
        'provider_image' => getSingleMedia($this->provider, 'profile_image', null),
    ];
  }

}
