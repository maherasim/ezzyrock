@extends('landing-page.layouts.default')

@section('content')
@php
    $product = $productData['product_detail'];
    $provider = $productData['provider'];
    $reviews = $productData['reviews'] ?? collect();
    $reviewCount = $productData['review_count'] ?? 0;
    $averageRating = $productData['average_rating'] ?? 0;
    $myReview = $productData['my_review'] ?? null;
    $subtotal = isset($product->discount) && $product->discount > 0
        ? $product->price - ($product->price * $product->discount / 100)
        : $product->price;
    $taxRows = $productData['tax_rows'] ?? [];
    $taxTotal = $productData['tax_total'] ?? 0;
    $grandTotal = $productData['grand_total'] ?? $subtotal;
@endphp
<div class="section-padding service-detail">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 pe-xxl-5">
                <h3 class="text-capitalize mb-2">{{ $product->name }}</h3>
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <ul class="service-meta-list list-inline m-0 d-flex align-items-center flex-wrap">
                        @if(!empty($product->duration))
                            @php
                                $durationParts = explode(':', $product->duration);
                                $hours = isset($durationParts[0]) ? intval($durationParts[0]) : 0;
                                $minutes = isset($durationParts[1]) ? intval($durationParts[1]) : 0;
                            @endphp
                            @if($hours > 0 || $minutes > 0)
                                <li>
                                    <h6 class="text-body">
                                        ({{ $hours }} hrs @if($minutes > 0) {{ $minutes }} min @endif)
                                    </h6>
                                </li>
                            @endif
                        @endif
                    </ul>
                    @if($provider)
                    <div>
                        <span class="text-capitalize">{{ __('landingpage.created_by') }}: </span>
                        <a class="d-inline-block text-capitalize m-0"
                            href="{{ route('provider.detail', $provider->id) }}">{{ $provider->display_name }}</a>
                    </div>
                    @endif
                </div>
                @if(!empty($product->attchments) && count($product->attchments) > 0)
                    <div class="mt-5">
                        <section-thumbnail-section
                            :attachments="{{ json_encode($product->attchments) }}"></section-thumbnail-section>
                    </div>
                @else
                    <img src="{{ $product->product_image ?? asset('images/default.png') }}" alt=""
                        class="img-fluid object-cover rounded-3 mt-4 w-100" />
                @endif
                @if(!empty($product->description))
                    <div class="mt-5 pt-lg-5 pt-3">
                        <h5 class="mb-3">About Product</h5>
                        <p class="m-0">{{ $product->description }}</p>
                    </div>
                @endif

                @if($provider)
                <div class="mt-5 pt-lg-5 pt-3">
                    <h5 class="mb-3">{{ __('landingpage.about_provider') }}</h5>
                    <div class="p-5 border rounded-3 about-provider-box">
                        <div class="mb-4 pb-4 border-bottom d-flex align-items-sm-center align-items-start flex-sm-row flex-column gap-5">
                            <div class="flex-shrink-0 provider-image-container">
                                <img src="{{ $provider->profile_image ?? asset('images/default.png') }}" alt="provider-user"
                                    class="img-fluid w-100">
                            </div>
                            <div>
                                <a href="{{ route('provider.detail', $provider->id) }}">
                                    <h5 class="text-capitalize mb-1">{{ $provider->display_name }}</h5>
                                </a>
                                @if($date_time && isset($provider->created_at))
                                    <p class="mt-3 mb-0">
                                        {{ __('landingpage.member_since') }}: {{ date($date_time->date_format ?? 'Y-m-d', strtotime($provider->created_at)) }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @if($product->price > 0)
                <div class="mt-5 pt-lg-5 pt-3">
                    <h5 class="mb-3 text-capitalize">{{ __('landingpage.order_detail') }}</h5>
                    <div class="p-5 border rounded-3">
                        <h6 class="mb-1">{{ __('messages.product') }}</h6>
                        <p class="m-0 text-capitalize">{{ $product->name }}</p>
                        <div class="mt-5 border-top">
                            <div class="table-responsive">
                                <table class="table mb-5">
                                    <tbody>
                                        <tr>
                                            <td class="ps-0 py-2">
                                                <label class="text-capitalize"><h6>{{ __('messages.price') }}</h6></label>
                                            </td>
                                            <td class="pe-0 py-2 text-end">
                                                <h6 class="text-primary">+{{ getPriceFormat($product->price) }}</h6>
                                            </td>
                                        </tr>
                                        @if(!empty($product->discount) && $product->discount > 0)
                                        <tr>
                                            <td class="ps-0 py-2">
                                                <label class="text-capitalize">
                                                    <h6>{{ __('messages.discount') }} <span class="text-success">({{ $product->discount }}% Off)</span></h6>
                                                </label>
                                            </td>
                                            <td class="pe-0 py-2 text-end">
                                                <span class="text-success">-{{ getPriceFormat(($product->price * $product->discount) / 100) }}</span>
                                            </td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <td class="ps-0 py-2">
                                                <h6 class="text-capitalize m-0">{{ __('messages.Subtotal') }}</h6>
                                            </td>
                                            <td class="pe-0 py-2 text-end">
                                                <h6 class="text-primary">{{ getPriceFormat($subtotal) }}</h6>
                                            </td>
                                        </tr>
                                        @if(!empty($taxRows))
                                        <tr>
                                            <td class="ps-0 py-2">
                                                <h6 class="text-capitalize m-0">{{ __('messages.tax') }}</h6>
                                            </td>
                                            <td class="pe-0 py-2 text-end">
                                                <h6 class="text-primary">{{ getPriceFormat($taxTotal) }}</h6>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="ps-0 py-2">
                                                <h6 class="text-capitalize m-0">{{ __('messages.grand_total') }}</h6>
                                            </td>
                                            <td class="pe-0 py-2 text-end">
                                                <h6 class="text-primary">{{ getPriceFormat($grandTotal) }}</h6>
                                            </td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <div class="mt-5 pt-lg-5 pt-3">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="m-0">{{ __('messages.reviews') }}</h5>
                        <div class="small text-muted">
                            {{ number_format((float) $averageRating, 1) }}/5 ({{ $reviewCount }})
                        </div>
                    </div>

                    @auth
                        @if(auth()->user()->user_type === 'user')
                            <form action="{{ route('product.review.store', $product->id) }}" method="post" class="p-4 border rounded-3 mb-4">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">{{ __('messages.rating') }}</label>
                                        <select name="rating" class="form-select" required>
                                            @for($i = 5; $i >= 1; $i--)
                                                <option value="{{ $i }}" {{ (int) old('rating', optional($myReview)->rating) === $i ? 'selected' : '' }}>
                                                    {{ $i }} Star{{ $i > 1 ? 's' : '' }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="col-md-9">
                                        <label class="form-label">{{ __('messages.review') }}</label>
                                        <textarea name="comment" class="form-control" rows="3" maxlength="1000" placeholder="Write your review...">{{ old('comment', optional($myReview)->comment) }}</textarea>
                                    </div>
                                </div>
                                <div class="mt-3 text-end">
                                    <button type="submit" class="btn btn-primary">{{ __('messages.submit') }}</button>
                                </div>
                            </form>
                        @endif
                    @else
                        <div class="p-3 border rounded-3 mb-4">
                            <a href="{{ route('user.login') }}" class="btn-link">{{ __('messages.login') }}</a> to add your review.
                        </div>
                    @endauth

                    @if($reviews->isNotEmpty())
                        <ul class="comment-list list-inline m-0">
                            @foreach($reviews as $review)
                                <li class="comment mb-4 pb-4 border-bottom">
                                    <div class="comment-box">
                                        <div class="d-flex align-items-sm-center align-items-start flex-sm-row flex-column justify-content-between gap-3">
                                            <div class="d-inline-flex align-items-sm-center align-items-start flex-sm-row flex-column gap-3">
                                                <div class="user-image flex-shrink-0">
                                                    <img src="{{ optional($review->user)->profile_image ?? asset('images/default.png') }}" class="avatar-70 object-cover rounded-circle" alt="comment-user" />
                                                </div>
                                                <div class="comment-user-info">
                                                    <h6 class="font-size-18 text-capitalize mb-2">
                                                        {{ optional($review->user)->display_name ?? optional($review->user)->email ?? __('messages.user') }}
                                                    </h6>
                                                    <span class="text-primary">
                                                        <rating-component :readonly=true :showrating="false" :ratingvalue="{{ (float) $review->rating }}" />
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="date text-capitalize">
                                                {{ optional($review->created_at)->format('Y-m-d') }}
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <p class="commnet-content m-0">{{ $review->comment ?: '-' }}</p>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="p-3 border rounded-3 text-muted">No reviews yet.</div>
                    @endif
                </div>
            </div>
            <div class="col-lg-4">
                <div class="rounded-3 border p-4 position-sticky" style="top: 100px;">
                    <h5 class="mb-3 text-capitalize">{{ $product->name }}</h5>
                    @if($product->price == 0)
                        <p class="text-primary fw-500 font-size-18" id="product-live-price">Free</p>
                    @else
                        <p class="text-primary fw-500 font-size-18" id="product-live-price">
                            {{ getPriceFormat($grandTotal) }}
                            @if(!empty($product->discount) && $product->discount > 0)
                                <span class="text-success">({{ $product->discount }}% off)</span>
                            @endif
                        </p>
                        @if(!empty($taxRows))
                            <p class="mb-0 small text-body">{{ __('messages.tax') }}: {{ getPriceFormat($taxTotal) }}</p>
                        @endif
                    @endif
                    @if($provider)
                        <div class="d-flex align-items-center gap-2 mt-3">
                            <img src="{{ $provider->profile_image ?? asset('images/default.png') }}" alt="" class="img-fluid rounded-3 object-cover avatar-24">
                            <a href="{{ route('provider.detail', $provider->id) }}" class="font-size-14">{{ $provider->display_name }}</a>
                        </div>
                    @endif
                    @if($product->relationLoaded('productUnit') && $product->productUnit)
                        <p class="mb-0 mt-2 small text-muted">{{ __('messages.product_unit') }}: <span class="text-body fw-500">{{ $product->productUnit->name }}</span></p>
                    @endif
                    @php
                        $maxAllowed = min(99, (int) ($product->total_stock ?? 99));
                        if (!empty($product->max_purchase_qty)) {
                            $maxAllowed = min($maxAllowed, (int) $product->max_purchase_qty);
                        }
                        $activeVariants = collect($product->variants ?? [])->filter(function ($v) {
                            return (int) $v->status === 1 && (int) $v->stock > 0;
                        })->values();
                        $variantAttributeName = $activeVariants->first()?->option?->attribute?->name;
                    @endphp
                    @if(($product->total_stock ?? 0) > 0 && $maxAllowed > 0)
                        <form action="{{ auth()->check() && auth()->user()->user_type === 'user' ? route('user.cart.add') : route('user.cart.add.intent') }}" method="post" class="mt-4 d-grid gap-2">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            @if($activeVariants->isNotEmpty())
                                <div>
                                    <label class="small mb-1 d-block">{{ $variantAttributeName ? $variantAttributeName . ': ' : '' }}{{ __('messages.select_name', ['select' => __('messages.variant_table_variant')]) }}</label>
                                    <select name="product_variant_id" id="product_variant_id" class="form-select form-select-sm" required>
                                        <option value="">Choose</option>
                                        @foreach($activeVariants as $variant)
                                            <option
                                                value="{{ $variant->id }}"
                                                data-price-display="{{ getPriceFormat($variant->price) }}"
                                                data-max-qty="{{ max(1, min(99, (int) $variant->stock, (int) ($variant->max_purchase_qty ?: ($product->max_purchase_qty ?: 99)))) }}"
                                            >
                                                {{ $variant->option->value ?? ('Option #' . $variant->id) }} - {{ getPriceFormat($variant->price) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="d-flex align-items-center gap-2">
                                <label class="small mb-0 text-nowrap">{{ __('messages.quantity') }}</label>
                                <input type="number" name="quantity" id="detail_quantity" value="1" min="1" max="{{ $maxAllowed }}" class="form-control form-control-sm" style="max-width: 80px;">
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('messages.add_to_cart') }}</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('bottom_script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const variantSelect = document.getElementById('product_variant_id');
    const priceNode = document.getElementById('product-live-price');
    const qtyInput = document.getElementById('detail_quantity');
    if (!variantSelect || !priceNode) return;

    const basePriceHtml = priceNode.innerHTML;
    variantSelect.addEventListener('change', function () {
        const selected = variantSelect.options[variantSelect.selectedIndex];
        if (!selected || !selected.value) {
            priceNode.innerHTML = basePriceHtml;
            if (qtyInput) qtyInput.max = "{{ $maxAllowed }}";
            return;
        }
        const variantPrice = selected.getAttribute('data-price-display');
        const maxQty = selected.getAttribute('data-max-qty');
        if (variantPrice) {
            priceNode.innerHTML = variantPrice;
        }
        if (qtyInput && maxQty) {
            qtyInput.max = maxQty;
            if (parseInt(qtyInput.value || '1', 10) > parseInt(maxQty, 10)) {
                qtyInput.value = maxQty;
            }
        }
    });
});
</script>
@endsection
