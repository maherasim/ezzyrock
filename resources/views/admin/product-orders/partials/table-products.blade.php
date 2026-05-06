@php
    $first = $order->items->first();
    $product = $first?->product;
@endphp
@if ($product)
    <div class="d-flex gap-3 align-items-center">
        <img src="{{ getSingleMedia($product, 'product_attachment', null) }}" alt="" class="avatar avatar-40 rounded-pill">
        <div class="text-start">
            <p class="m-0">{{ $first->product_name ?? $product->name }}</p>
            @if ($order->items->count() > 1)
                <span class="small text-muted">+{{ $order->items->count() - 1 }} {{ __('messages.items') }}</span>
            @endif
        </div>
    </div>
@elseif ($first)
    <div class="d-flex gap-3 align-items-center">
        <img src="{{ asset('images/default.png') }}" alt="" class="avatar avatar-40 rounded-pill">
        <div class="text-start">
            <p class="m-0">{{ $first->product_name ?? '—' }}</p>
            @if ($order->items->count() > 1)
                <span class="small text-muted">+{{ $order->items->count() - 1 }} {{ __('messages.items') }}</span>
            @endif
        </div>
    </div>
@else
    <div class="align-items-center">
        <h6 class="text-center m-0">—</h6>
    </div>
@endif
