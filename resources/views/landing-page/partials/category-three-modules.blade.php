{{-- Services, Product categories, Classified categories (same grid as /category-list). Pass excludeServiceCategories on landing. --}}
@php
    $categoryBlocks = [
        [__('messages.services'), $serviceCategories, 'service', $serviceTotal ?? 0],
        [__('messages.product_categories'), $ecommerceCategories, 'ecommerce', $ecommerceTotal ?? 0],
        [__('messages.classified_categories'), $classifiedCategories, 'classified', $classifiedTotal ?? 0],
    ];
    if (!empty($excludeServiceCategories)) {
        $categoryBlocks = array_values(array_filter($categoryBlocks, function ($row) {
            return ($row[2] ?? '') !== 'service';
        }));
    }
@endphp
@foreach ($categoryBlocks as $block)
    @php [$title, $cats, $type, $total] = $block; @endphp
    <div class="mb-3 {{ ! empty($categoryBlocksExtraGap) && ! $loop->first ? 'landing-category-modules-gap' : '' }}">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <div class="iq-title-box mb-0">
                <h3 class="text-capitalize line-count-1 mb-0">{{ $title }}</h3>
            </div>
            @if ($total > 0)
                <a href="{{ route('category.list', ['view_all' => $type]) }}" class="btn btn-link p-0 flex-shrink-0 text-capitalize font-size-14">
                    {{ __('messages.view_all') }}
                </a>
            @endif
        </div>
        @if ($cats->isEmpty())
            <p class="text-body mb-0">{{ __('messages.no_record_found') }}</p>
        @else
            <div class="landing-category-compact-grid category-list-six-item-wrap">
                @foreach ($cats as $data)
                    <div class="category-list-six-item">
                        @include('category.datatable-card', ['type' => $type, 'compact' => true])
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endforeach
