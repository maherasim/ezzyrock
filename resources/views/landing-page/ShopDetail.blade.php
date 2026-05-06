@extends('landing-page.layouts.default')

@section('content')
    <div class="section-padding position-relative px-0">
        <div class="container">
            <div class="row">
                <div class="col-12 col-md-4 mb-4 mb-md-5">
                    <div class="shop-image-container ratio ratio-1x1" style="overflow: hidden; height: 230px;">
                        <img src="{{ getSingleMedia($shop, 'shop_attachment', null) }}"
                            class="img-fluid w-100 h-100 object-fit-cover rounded-3" alt="{{ $shop->shop_name }}"
                            loading="lazy">
                    </div>
                </div>
                <div class="col-12 col-md-8">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-3">
                        <h1 class="h2 mb-2 mb-md-0">{{ $shop->shop_name }}</h1>
                        <div class="text-md-end">
                            <div class="mb-1">
                                <span class="fw-bold text-primary">{{ __('messages.shop_since') }} : </span>
                                <span
                                    class="fw-light">{{ date("$date_time->date_format", strtotime($shop->provider->created_at)) }}</span>
                            </div>
                            <div>
                                <span class="fw-bold text-primary">{{ __('messages.shop_managed_by') }} : </span>
                                <span class="fw-light">{{ $shop->provider->display_name }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <div class="bg-light p-3 rounded h-100">
                                <p class="mb-0"><i
                                        class="fas fa-phone me-2 text-primary"></i>{{ $shop->contact_country_code }}
                                    {{ $shop->contact_number }}</p>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="bg-light p-3 rounded h-100">
                                <p class="mb-0 text-break"><i
                                        class="fas fa-envelope me-2 text-primary"></i>{{ $shop->email }}</p>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="bg-light p-3 rounded h-100">
                                <p class="mb-0">
                                    <i class="fas fa-clock me-2 text-primary"></i>
                                    {{ \Carbon\Carbon::parse($shop->shop_start_time)->format('g:i A') }} -
                                    {{ \Carbon\Carbon::parse($shop->shop_end_time)->format('g:i A') }}
                                </p>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="bg-light p-3 rounded h-100">
                                <p class="mb-0"><i
                                        class="fas fa-map-marker-alt me-2 text-primary"></i>{{ $shop->address }},
                                    {{ $shop->city->name }}, {{ $shop->state->name }},{{ $shop->country->name }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Map - Only shown on larger screens -->
                {{-- @if (!empty($shop->lat) && !empty($shop->long))
                <div class="col-12 mt-4 d-none d-lg-block">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0">
                            <div style="height: 400px;" class="ratio ratio-16x9">
                                <iframe
                                    src="https://maps.google.com/maps?q={{ $shop->lat }},{{ $shop->long }}&hl=es;z=14&output=embed"
                                    allowfullscreen
                                    loading="lazy">
                                </iframe>
                            </div>
                        </div>
                    </div>
                </div>
                @endif --}}
            </div>
            <div class="row">
                <div class="col-12 mt-lg-4 mt-3 mb-4">
                    <h4 class="mb-3">{{ __('landingpage.about_provider') }}</h4>
                    <div class="p-3 p-lg-4 border rounded-3 about-provider-box">
                        <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3 gap-lg-4">
                            <div class="flex-shrink-0 mx-auto mx-lg-0" style="width: 100%; max-width: 260px;">
                                <img src="{{ getSingleMedia($shop->provider, 'profile_image', null) }}" alt="provider-user"
                                    class="img-fluid object-fit-cover rounded-3 w-100"
                                    style="height: 280px; object-position: center;">
                            </div>
                            <div class="flex-grow-1">
                                <div class="mb-3 pb-3 border-bottom">
                                    <a href="{{ route('provider.detail', $shop->provider->id ?? '') }}"
                                        class="text-decoration-none">
                                        <h4 class="mb-1 text-capitalize">
                                            {{ $shop->provider->display_name ?? __('messages.provider_name_not_available') }}
                                        </h4>
                                    </a>
                                    <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
                                        <div class="star-rating" style="font-size: 0.875rem">
                                            <rating-component :readonly="true" :showrating="false"
                                                :ratingvalue="{{ $shop->provider->providers_service_rating ?? 0 }}" />
                                        </div>
                                        <h5 class="lh-sm mb-0" style="font-size: 0.875rem">
                                            {{ round($shop->provider->providers_service_rating ?? 0, 1) }}</h5>
                                        <a href="{{ route('rating.all', ['provider_id' => $shop->provider->id]) }}"
                                            class="text-decoration-none" style="font-size: 0.875rem">
                                            ({{ $shop->provider->total_service_rating ?? 0 }}
                                            {{ __('messages.reviews') }})
                                        </a>
                                    </div>
                                    @if (!empty($shop->provider->description))
                                        <p class="mb-0 text-muted" style="font-size: 0.875rem">
                                            {{ $shop->provider->description }}</p>
                                    @endif
                                </div>
                                <div class="row g-3">
                                    <div class="mt-3">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <div class="rounded p-3 bg-light">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <i class="fas fa-phone text-primary"></i>
                                                            <span style="font-size: 0.875rem">
                                                                {{ $shop->provider->contact_number }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <div class="rounded p-3 bg-light">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <i class="fas fa-envelope text-primary"></i>
                                                            <span
                                                                style="font-size: 0.875rem">{{ $shop->provider->email }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-4">
                                        <div class="bg-light p-3 rounded h-100">
                                            <h6 class="mb-2 text-primary" style="font-size: 0.875rem">
                                                {{ __('landingpage.member_since') }}:</h6>
                                            <p class="m-0" style="font-size: 0.875rem">
                                                {{ date("$date_time->date_format", strtotime($shop->provider->created_at)) }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-4">
                                        <div class="bg-light p-3 rounded h-100">
                                            <h6 class="mb-2 text-primary" style="font-size: 0.875rem">
                                                {{ __('landingpage.complet_project') }}:</h6>
                                            <p class="m-0" style="font-size: 0.875rem">{{ $completed_services }}
                                                {{ __('landingpage.msg_complete_project') }}</p>
                                        </div>
                                    </div>
                                    @if (!empty($shop->provider->known_languages))
                                        <div class="col-sm-6 col-lg-4">
                                            <div class="bg-light p-3 rounded h-100">
                                                <h6 class="mb-2 text-primary" style="font-size: 0.875rem">
                                                    {{ __('landingpage.languages') }}:</h6>
                                                <p class="m-0" style="font-size: 0.875rem">
                                                    {{ implode(', ', json_decode($shop->provider->known_languages)) }}</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row align-items-center pt-3">
                <div class="col-sm-9">
                    <h4 class="text-capitalize mb-0">{{ __('messages.services') }}</h4>
                </div>
                <div class="col-sm-3 mt-sm-0 mt-5 text-sm-end">
                    <a href="{{ route('service.list', ['shop_id' => $shop->id]) }}">{{ __('messages.view_all') }}</a>
                </div>
                <div class="swiper-container">
                    <div class="swiper-wrapper"
                        style="transform: translate3d(-349.5px, 0px, 0px); transition-duration: 0ms;">
                        @foreach ($shop->services as $service)
                            <div class="swiper-slide" style="width: 319.5px; margin-right: 30px;">
                                <div class="mt-5 justify-content-center service-slide-items-4">
                                    <div class="col">
                                        <div class="service-box-card bg-light rounded-3 mb-5">
                                            <div class="iq-image position-relative">
                                                <a href="{{ route('service.detail', ['id' => $service->id, 'shop_id' => $shop->id]) }}"
                                                    class="service-img">
                                                    <img src="{{ getSingleMedia($service, 'service_attachment', null) }}"
                                                        alt="service"
                                                        class="service-img w-100 object-cover img-fluid rounded-3">
                                                </a>
                                                @php
                                                    $isFavourite = false;
                                                    if (
                                                        auth()->check() &&
                                                        $service->relationLoaded('getUserFavouriteService') &&
                                                        $service->getUserFavouriteService->isNotEmpty()
                                                    ) {
                                                        $isFavourite = $service->getUserFavouriteService->contains(
                                                            'user_id',
                                                            auth()->id(),
                                                        );
                                                    }
                                                @endphp

                                                @if (auth()->check() && auth()->user()->hasRole('user'))
                                                    @if (!$isFavourite)
                                                        <form method="POST" class="favoriteForm">
                                                            @csrf
                                                            <input type="hidden" name="service_id" class="service_id"
                                                                value="{{ $service->id }}"
                                                                data-service-id="{{ $service->id }}">
                                                            @if (!empty(auth()->user()))
                                                                <input type="hidden" name="user_id" id="user_id"
                                                                    value="{{ Auth::user()->id }}">
                                                            @endif
                                                            <button type="button"
                                                                class="btn-link serv-whishlist text-primary save_fav">
                                                                <svg width="12" height="13" viewBox="0 0 12 13"
                                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                                                        d="M1.43593 6.29916C0.899433 4.62416 1.52643 2.70966 3.28493 2.14316C4.20993 1.84466 5.23093 2.02066 5.99993 2.59916C6.72743 2.03666 7.78593 1.84666 8.70993 2.14316C10.4684 2.70966 11.0994 4.62416 10.5634 6.29916C9.72843 8.95416 5.99993 10.9992 5.99993 10.9992C5.99993 10.9992 2.29893 8.98516 1.43593 6.29916Z"
                                                                        stroke="currentColor" stroke-width="1.5"
                                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                                    <path
                                                                        d="M8 3.84998C8.535 4.02298 8.913 4.50048 8.9585 5.06098"
                                                                        stroke="currentColor" stroke-width="1.5"
                                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    @else
                                                        <form method="POST" class="favoriteForm">
                                                            @csrf
                                                            <input type="hidden" name="service_id" class="service_id"
                                                                value="{{ $service->id }}"
                                                                data-service-id="{{ $service->id }}">
                                                            @if (!empty(auth()->user()))
                                                                <input type="hidden" name="user_id" id="user_id"
                                                                    value="{{ Auth::user()->id }}">
                                                            @endif
                                                            <button type="button"
                                                                class="btn-link serv-whishlist text-primary delete_fav">
                                                                <svg width="12" height="13" viewBox="0 0 12 13"
                                                                    fill="currentColor"
                                                                    xmlns="http://www.w3.org/2000/svg">
                                                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                                                        d="M1.43593 6.29916C0.899433 4.62416 1.52643 2.70966 3.28493 2.14316C4.20993 1.84466 5.23093 2.02066 5.99993 2.59916C6.72743 2.03666 7.78593 1.84666 8.70993 2.14316C10.4684 2.70966 11.0994 4.62416 10.5634 6.29916C9.72843 8.95416 5.99993 10.9992 5.99993 10.9992C5.99993 10.9992 2.29893 8.98516 1.43593 6.29916Z"
                                                                        stroke="currentColor" stroke-width="1.5"
                                                                        stroke-linecap="round" stroke-linejoin="round">
                                                                    </path>
                                                                    <path
                                                                        d="M8 3.84998C8.535 4.02298 8.913 4.50048 8.9585 5.06098"
                                                                        stroke="currentColor" stroke-width="1.5"
                                                                        stroke-linecap="round" stroke-linejoin="round">
                                                                    </path>
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    @endif
                                                @else
                                                    <form method="GET" class="favoriteForm"
                                                        action="{{ route('user.login') }}">
                                                        @csrf
                                                        <button type="submit"
                                                            class="btn-link serv-whishlist text-primary">
                                                            <svg width="12" height="13" viewBox="0 0 12 13"
                                                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path fill-rule="evenodd" clip-rule="evenodd"
                                                                    d="M1.43593 6.29916C0.899433 4.62416 1.52643 2.70966 3.28493 2.14316C4.20993 1.84466 5.23093 2.02066 5.99993 2.59916C6.72743 2.03666 7.78593 1.84666 8.70993 2.14316C10.4684 2.70966 11.0994 4.62416 10.5634 6.29916C9.72843 8.95416 5.99993 10.9992 5.99993 10.9992C5.99993 10.9992 2.29893 8.98516 1.43593 6.29916Z"
                                                                    stroke="currentColor" stroke-width="1.5"
                                                                    stroke-linecap="round" stroke-linejoin="round" />
                                                                <path
                                                                    d="M8 3.84998C8.535 4.02298 8.913 4.50048 8.9585 5.06098"
                                                                    stroke="currentColor" stroke-width="1.5"
                                                                    stroke-linecap="round" stroke-linejoin="round" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                            <a href="{{ route('service.detail', $service->id) }}"
                                                class="service-heading mt-4 d-block p-0">
                                                <h5 class="service-title font-size-18 line-count-2">{{ $service->name }}
                                                </h5>
                                            </a>
                                            <ul class="list-inline p-0 mt-1 mb-0 price-content">
                                                <li
                                                    class="text-primary fw-500 d-inline-block position-relative font-size-18">
                                                    <span>{{ getPriceFormat($service->price) }}</span></li>
                                                <li class="d-inline-block fw-500 position-relative service-price">
                                                    ({{ $service->duration }} Min)</li>
                                            </ul>
                                            <div class="mt-3">
                                                <div class="d-flex align-items-center gap-2">
                                                    <img src="{{ getSingleMedia($service->providers, 'profile_image', null) }}"
                                                        alt="provider" class="img-fluid rounded-3 object-cover avatar-24">
                                                    <a href="{{ route('provider.detail', $service->providers->id) }}"
                                                        class="text-decoration-none">
                                                        <span
                                                            class="font-size-14 service-user-name">{{ $service->providers->display_name }}</span>
                                                    </a>
                                                </div>

                                                <div class="d-flex align-items-center flex-wrap gap-2">
                                                    <div class="star-rating">
                                                        <rating-component :readonly="true" :showrating="false"
                                                            :ratingvalue="{{ $service->providers->providers_service_rating ?? 0 }}" />
                                                    </div>
                                                    <h6 class="lh-sm mb-0">
                                                        {{ round($service->providers->providers_service_rating ?? 0, 1) }}
                                                    </h6>
                                                    <a href="{{ route('rating.all', $service->providers->id) }}"
                                                        class="text-decoration-none">
                                                        ({{ $service->providers->total_service_rating ?? 0 }}
                                                        {{ __('messages.reviews') }})
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Swiper
            const swiper = new Swiper('.swiper-container', {
                loop: false,
                speed: 600,
                spaceBetween: 30,
                slidesPerView: 4,
                breakpoints: {
                    0: {
                        slidesPerView: 1
                    },
                    576: {
                        slidesPerView: 2
                    },
                    768: {
                        slidesPerView: 3
                    },
                    1200: {
                        slidesPerView: 4
                    },
                }
            });

            const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Handle Save Favourite
            document.addEventListener('click', function(e) {
                if (e.target.closest('.save_fav')) {
                    e.preventDefault();
                    const button = e.target.closest('.save_fav');
                    const form = button.closest('form');
                    const serviceId = form.querySelector('.service_id').dataset.serviceId;
                    const userId = document.getElementById('user_id').value;
                    fetch(`${baseUrl}/api/save-favourite`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({
                                service_id: serviceId,
                                user_id: userId
                            })
                        })
                        .then(res => res.json())
                        .then(response => {
                            Swal.fire({
                                title: 'Added to Favourites',
                                text: response.message,
                                icon: 'success',
                                iconColor: '#5F60B9',
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    button.classList.remove('save_fav');
                                    button.classList.add('delete_fav');
                                    button.innerHTML = `
                                    <svg width="12" height="13" viewBox="0 0 12 13" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M1.43593 6.29916C0.899433 4.62416 1.52643 2.70966 3.28493 2.14316C4.20993 1.84466 5.23093 2.02066 5.99993 2.59916C6.72743 2.03666 7.78593 1.84666 8.70993 2.14316C10.4684 2.70966 11.0994 4.62416 10.5634 6.29916C9.72843 8.95416 5.99993 10.9992 5.99993 10.9992C5.99993 10.9992 2.29893 8.98516 1.43593 6.29916Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                        <path d="M8 3.84998C8.535 4.02298 8.913 4.50048 8.9585 5.06098" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>`;
                                }
                            });
                        });
                }
            });
            // Handle Delete Favourite
            document.addEventListener('click', function(e) {
                if (e.target.closest('.delete_fav')) {
                    e.preventDefault();
                    const button = e.target.closest('.delete_fav');
                    const form = button.closest('form');
                    const serviceId = form.querySelector('.service_id').dataset.serviceId;
                    const userId = document.getElementById('user_id').value;
                    fetch(`${baseUrl}/api/delete-favourite`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({
                                service_id: serviceId,
                                user_id: userId
                            })
                        })
                        .then(res => res.json())
                        .then(response => {
                            Swal.fire({
                                title: 'Removed from Favourites',
                                text: response.message,
                                icon: 'success',
                                iconColor: '#5F60B9',
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    button.classList.remove('delete_fav');
                                    button.classList.add('save_fav');
                                    button.innerHTML = `
                                    <svg width="12" height="13" viewBox="0 0 12 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M1.43593 6.29916C0.899433 4.62416 1.52643 2.70966 3.28493 2.14316C4.20993 1.84466 5.23093 2.02066 5.99993 2.59916C6.72743 2.03666 7.78593 1.84666 8.70993 2.14316C10.4684 2.70966 11.0994 4.62416 10.5634 6.29916C9.72843 8.95416 5.99993 10.9992 5.99993 10.9992C5.99993 10.9992 2.29893 8.98516 1.43593 6.29916Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M8 3.84998C8.535 4.02298 8.913 4.50048 8.9585 5.06098" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>`;
                                }
                            });
                        });
                }
            });
        });
    </script>

@endsection
