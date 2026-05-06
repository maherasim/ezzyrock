<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="fw-bold">Shop Detail</h5>
                            <a href="{{ route('shop.index') }}" class="float-end btn btn-sm btn-primary">
                                <i class="fa fa-angle-double-left"></i> Back
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-lg-3 col-md-4">
                        <div class="shop-image">
                            <div class="border rounded p-2 bg-light">
                                <img src="{{ getSingleMedia($shop, 'shop_attachment', null) }}" alt="Shop Image"
                                    class="img-fluid rounded-2" style="max--height: 300px; object-fit: contain;">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-9 col-md-8">
                        <div class="shop-details">
                            <div class="row">
                                <div class="mb-3">
                                    <span class="fw-bold me-2">Provider:</span>
                                    {{ $shop->provider->first_name }} {{ $shop->provider->last_name }}
                                </div>
                                <div class="mb-3">
                                    <span class="fw-bold me-2">Shop Name:</span>
                                    {{ $shop->shop_name }}
                                </div>
                                <div class="mb-3">
                                    <span class="fw-bold me-2">Address:</span>
                                    {{ $shop->address }}, {{ $shop->city->name }}, {{ $shop->state->name }},
                                    {{ $shop->country->name }}
                                </div>
                                <div class="mb-3">
                                    <span class="fw-bold me-2">Coordinates:</span>
                                    {{ $shop->lat }}, {{ $shop->long }}
                                </div>
                                <div class="mb-3">
                                    <span class="fw-bold me-2">Registration Number:</span>
                                    {{ $shop->registration_number }}
                                </div>
                                <div class="mb-3">
                                    <span class="fw-bold me-2">Opening Hours:</span>
                                    @php
                                        $sitesetup = App\Models\Setting::where('type', 'site-setup')
                                            ->where('key', 'site-setup')
                                            ->first();
                                        $sitesetupValue = json_decode(optional($sitesetup)->value ?? '{}');
                                        $targetTimezone = isset($sitesetupValue->time_zone)
                                            ? trim((string) $sitesetupValue->time_zone)
                                            : 'UTC';
                                        $timeFormat = $sitesetupValue->time_format ?? 'H:i';
                                        $sourceTimezone = 'UTC';

                                        $convertFromRawUtc = function ($raw) use (
                                            $sourceTimezone,
                                            $targetTimezone,
                                            $timeFormat,
                                        ) {
                                            if (empty($raw)) {
                                                return null;
                                            }
                                            try {
                                                return \Carbon\Carbon::parse($raw, $sourceTimezone)
                                                    ->setTimezone($targetTimezone)
                                                    ->format($timeFormat);
                                            } catch (\Exception $e) {
                                                return null;
                                            }
                                        };
                                    @endphp
                                    {{ $convertFromRawUtc($shop->getRawOriginal('shop_start_time')) }} -
                                    {{ $convertFromRawUtc($shop->getRawOriginal('shop_end_time')) }}
                                </div>
                                <div class="mb-3">
                                    <span class="fw-bold me-2">Contact Number:</span>
                                    @if ($shop->contact_country_code)
                                        +{{ $shop->contact_country_code }}-{{ $shop->contact_number }}
                                    @else
                                        {{ $shop->contact_number }}
                                    @endif
                                </div>
                                <div class="mb-3">
                                    <span class="fw-bold me-2">Email:</span>
                                    {{ $shop->email }}
                                </div>
                                <div class="mb-3 ">
                                    <div class="mb-3">
                                        <span class="fw-bold me-2">Status:</span>
                                        <span class="badge bg-{{ $shop->is_active ? 'success' : 'secondary' }}">
                                            {{ $shop->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    <div class="mb-0">
                                        <span class="fw-bold me-2">Services:</span>
                                        @if ($shop->services && $shop->services->count())
                                            <div class="d-flex flex-wrap gap-2 mt-2">
                                                @foreach ($shop->services as $service)
                                                    <span class="badge bg-primary">{{ $service->name }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted">No services assigned</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>
