@php
    $cardType = $type ?? 'service';
    $href = route('category.detail', $data->id);
    $compact = ! empty($compact ?? false);
@endphp

<a href="{{ $href }}">
    <div class="card text-center circle-clip-effect rounded-3 {{ $compact ? 'border-0 bg-transparent shadow-none landing-category-tile-card' : 'bg-light' }}">
        <div class="card-body category-card {{ $compact ? 'py-2 px-1' : '' }}">
            <div class="img-bg d-inline-block rounded-3 {{ $compact ? 'landing-category-tile-img-bg' : '' }}">
            <img src="{{ getSingleMedia($data,'category_image', null) }}" alt="icon" class="img-fluid {{ $compact ? 'landing-category-tile-img' : 'avatar-70' }}">
        </div>
        <h5 class="categories-name text-capitalize line-count-1 {{ $compact ? 'mt-2 mb-0 small fw-semibold' : 'mt-4 mb-2' }}">{{ $data->name }}</h5>
        @unless($compact)
        <p class="categories-desc mb-0 text-capitalize line-count-2">{{ $data->description }}</p>
        @endunless
    </div>
</div>
</a>
