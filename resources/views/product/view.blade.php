<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="fw-bold">{{ $pageTitle ?? __('messages.product') }}</h5>
                            <a href="{{ route('product.index') }}" class="btn btn-sm btn-primary"><i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}</a>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>{{ __('messages.name') }}:</strong> {{ $product->name }}</p>
                                <p><strong>{{ __('messages.description') }}:</strong> {{ $product->description ?? '-' }}</p>
                                <p><strong>{{ __('messages.category') }}:</strong> {{ optional($product->category)->name ?? '-' }}</p>
                                <p><strong>{{ __('messages.subcategory') }}:</strong> {{ optional($product->subcategory)->name ?? '-' }}</p>
                                <p><strong>{{ __('messages.price') }}:</strong> {{ getPriceFormat($product->price) }} ({{ $product->type }})</p>
                                <p><strong>{{ __('messages.status') }}:</strong> {{ $product->status ? __('messages.active') : __('messages.inactive') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>
