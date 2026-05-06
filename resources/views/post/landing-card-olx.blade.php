@php
    use Illuminate\Support\Str;
    $detailUrl = route('post.detail', $data->id);
    $loc = '';
    if ($data->providers) {
        $cityName = optional($data->providers->city)->name;
        $loc = $cityName ? strtoupper($cityName) : strtoupper(Str::limit((string) ($data->providers->address ?? ''), 36));
    }
    if ($loc === '') {
        $loc = strtoupper(__('landingpage.location_na'));
    }
    $posted = '';
    if ($data->created_at) {
        if ($data->created_at->isToday()) {
            $posted = __('landingpage.posted_today');
        } elseif ($data->created_at->isYesterday()) {
            $posted = __('landingpage.posted_yesterday');
        } else {
            $posted = $data->created_at->format('M d');
        }
    }
@endphp
<div class="olx-post-card h-100">
    <a href="{{ $detailUrl }}" class="olx-post-card__link text-decoration-none text-body d-block h-100">
        <div class="olx-post-card__media position-relative rounded-2 overflow-hidden bg-white border">
            @if (!empty($data->is_featured))
                <span class="olx-post-card__featured">{{ __('landingpage.featured') }}</span>
            @endif
            <span class="olx-post-card__fav" onclick="event.preventDefault();" title="{{ __('landingpage.save') }}" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            </span>
            <img src="{{ getSingleMedia($data, 'post_attachment', null) }}" alt=""
                class="olx-post-card__img w-100 object-cover">
        </div>
        <div class="olx-post-card__body pt-2 pb-1 px-0">
            <div class="olx-post-card__price fw-bold">
                @if (($data->price ?? 0) == 0)
                    {{ __('messages.free') }}
                @else
                    {{ getPriceFormat($data->price) }}
                @endif
            </div>
            <div class="olx-post-card__title line-count-2 small text-muted mt-1">{{ $data->name ?? '-' }}</div>
            <div class="olx-post-card__footer d-flex justify-content-between align-items-center mt-2 pt-1 border-top small">
                <span class="olx-post-card__loc text-uppercase text-secondary text-truncate me-2" style="font-size: 11px; letter-spacing: 0.02em;">{{ $loc }}</span>
                <span class="olx-post-card__date text-secondary flex-shrink-0" style="font-size: 11px;">{{ $posted }}</span>
            </div>
        </div>
    </a>
</div>
