@if ($provider)
    <a href="{{ route('provider_info', $provider->id) }}">
        <div class="d-flex gap-3 align-items-center">
            <img src="{{ getSingleMedia($provider, 'profile_image', null) }}" alt="" class="avatar avatar-40 rounded-pill">
            <div class="text-start">
                <h6 class="m-0">{{ $provider->display_name ?? trim(($provider->first_name ?? '') . ' ' . ($provider->last_name ?? '')) ?: '—' }}</h6>
                <span>{{ $provider->email ?? '—' }}</span>
            </div>
        </div>
    </a>
@else
    <div class="align-items-center">
        <h6 class="text-center m-0">—</h6>
    </div>
@endif
