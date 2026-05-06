{{-- Inner HTML for .product-card-cart (AJAX refresh). Expects $data (Product), optional $cartRow (ProductCartItem). --}}
@php
    $cartRow = $cartRow ?? null;
    $inCartQty = $cartRow && isset($cartRow->quantity) ? (int) $cartRow->quantity : 0;
    $isCustomer = auth()->check() && auth()->user()->user_type === 'user';
    $hasVariants = isset($data->variants) ? $data->variants->where('status', 1)->where('stock', '>', 0)->count() > 0 : false;
@endphp
@if ($hasVariants)
    <a href="{{ route('product.detail', $data->id) }}" class="btn btn-sm btn-outline-primary w-100">View options</a>
@elseif ($isCustomer)
    @if ($inCartQty > 0)
        <div class="product-card-cart__active">
            <form action="{{ route('user.cart.update', $cartRow) }}" method="post" class="d-flex align-items-center gap-2 mb-0 product-listing-qty-form">
                @csrf
                <button type="button" class="btn btn-sm btn-outline-secondary product-listing-qty-btn" data-action="minus" aria-label="Decrease quantity">-</button>
                <input type="number" name="quantity" value="{{ $inCartQty }}" min="1" max="99" class="form-control form-control-sm text-center product-listing-qty-input" style="max-width: 68px;" readonly>
                <button type="button" class="btn btn-sm btn-outline-secondary product-listing-qty-btn" data-action="plus" aria-label="Increase quantity">+</button>
            </form>
            <form action="{{ route('user.cart.remove', $cartRow) }}" method="post" class="d-none product-listing-remove-form">
                @csrf
            </form>
        </div>
    @else
        <form action="{{ route('user.cart.add') }}" method="post" class="d-grid product-listing-add-form">
            @csrf
            <input type="hidden" name="product_id" value="{{ $data->id }}">
            <input type="hidden" name="quantity" value="1">
            <button type="submit" class="btn btn-sm btn-primary">{{ __('messages.add_to_cart') }}</button>
        </form>
    @endif
@else
    <form action="{{ route('user.cart.add.intent') }}" method="post" class="d-grid">
        @csrf
        <input type="hidden" name="product_id" value="{{ $data->id }}">
        <input type="hidden" name="quantity" value="1">
        <button type="submit" class="btn btn-sm btn-primary">{{ __('messages.add_to_cart') }}</button>
    </form>
@endif
