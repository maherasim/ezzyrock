@extends('landing-page.layouts.default')

@section('after_head')
    @include('landing-page.partials._category-list-six-styles')
@endsection

@section('content')
<div class="section-padding">
    <div class="container">
        @isset($viewAll)
            {{-- Full list for one section --}}
            <div class="mb-4">
                <a href="{{ route('category.list') }}" class="btn btn-link text-decoration-none p-0 mb-2 d-inline-flex align-items-center gap-1">
                    <span aria-hidden="true">←</span> {{ __('messages.back') }}
                </a>
                <div class="iq-title-box mb-0">
                    @if ($viewAll === 'service')
                        <h2 class="text-capitalize h4 mb-0">{{ __('messages.services') }} — {{ __('messages.view_all') }}</h2>
                    @elseif ($viewAll === 'ecommerce')
                        <h2 class="text-capitalize h4 mb-0">{{ __('messages.product_categories') }} — {{ __('messages.view_all') }}</h2>
                    @else
                        <h2 class="text-capitalize h4 mb-0">{{ __('messages.classified_categories') }} — {{ __('messages.view_all') }}</h2>
                    @endif
                </div>
            </div>
            @if ($sectionCategories->isEmpty())
                <p class="text-body">{{ __('messages.no_record_found') }}</p>
            @else
                {{-- Same 8-across compact grid as landing category-three-modules (view_all=service|ecommerce|classified) --}}
                <div class="landing-category-compact-grid category-list-six-item-wrap">
                    @foreach ($sectionCategories as $data)
                        <div class="category-list-six-item">
                            @include('category.datatable-card', [
                                'type' => $viewAll === 'service' ? 'service' : ($viewAll === 'ecommerce' ? 'ecommerce' : 'classified'),
                                'compact' => true,
                            ])
                        </div>
                    @endforeach
                </div>
            @endif
        @else
            @include('landing-page.partials.category-three-modules')
        @endisset
    </div>
</div>
@endsection
