@extends('landing-page.layouts.default')

@section('content')
<div class="section-padding">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <h3 class="text-capitalize mb-0">{{ __('messages.my_cart') }}</h3>
            <a href="{{ route('product.list') }}" class="btn btn-link p-0">{{ __('messages.continue_shopping') }}</a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        @if ($items->isEmpty())
            <p class="text-body">{{ __('messages.cart_empty') }}</p>
        @else
            <div class="table-responsive bg-white rounded-3 border">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('messages.product') }}</th>
                            <th>{{ __('messages.price') }}</th>
                            <th>{{ __('messages.quantity') }}</th>
                            <th>{{ __('messages.line_total') }}</th>
                            <th class="text-end">{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $subtotal = 0;
                            $taxTotal = 0;
                        @endphp
                        @foreach ($items as $item)
                            @php
                                $p = $item->product;
                                if (!$p) { continue; }
                                $variant = $item->variant;
                                $unit = (float) ($variant?->price ?? $p->price);
                                if ($p->discount > 0) {
                                    $unit = $unit - ($unit * (float) $p->discount / 100);
                                }
                                $line = round($unit * $item->quantity, 2);
                                $subtotal += $line;
                                $lineTax = 0;
                                $productTaxes = \App\Models\Tax::query()->where('status', 1)->where('module_type', 'ecommerce')->get();
                                foreach ($productTaxes as $tax) {
                                    if ($tax->type === 'percent') {
                                        $lineTax += ($line * (float) $tax->value) / 100;
                                    } else {
                                        $lineTax += (float) $tax->value;
                                    }
                                }
                                $lineTax = round($lineTax, 2);
                                $taxTotal += $lineTax;
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('product.detail', $p->id) }}" class="text-decoration-none d-flex align-items-center gap-2">
                                        <img src="{{ getSingleMedia($p, 'product_attachment', null) }}" alt="" class="rounded-2 object-cover" style="width:56px;height:56px;">
                                        <span>{{ $p->name }}@if($variant && $variant->option) <small class="text-muted d-block">@if(optional($variant->option->attribute)->name){{ $variant->option->attribute->name }}: @endif{{ $variant->option->value }}</small>@endif</span>
                                    </a>
                                </td>
                                <td>{{ getPriceFormat($unit) }}</td>
                                <td style="min-width: 140px;">
                                    <form action="{{ route('user.cart.update', $item) }}" method="post" class="d-flex align-items-center gap-1 cart-qty-form">
                                        @csrf
                                        <button type="button" class="btn btn-sm btn-outline-secondary qty-minus" aria-label="Decrease quantity">-</button>
                                        @php
                                            $rowMax = 99;
                                            if($variant && !empty($variant->max_purchase_qty)) { $rowMax = min($rowMax, (int) $variant->max_purchase_qty); }
                                            elseif(!empty($p->max_purchase_qty)) { $rowMax = min($rowMax, (int) $p->max_purchase_qty); }
                                        @endphp
                                        <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="{{ $rowMax }}" class="form-control form-control-sm text-center qty-input" style="max-width: 64px;" readonly>
                                        <button type="button" class="btn btn-sm btn-outline-secondary qty-plus" aria-label="Increase quantity">+</button>
                                    </form>
                                </td>
                                <td>{{ getPriceFormat($line) }}</td>
                                <td class="text-end">
                                    <form action="{{ route('user.cart.remove', $item) }}" method="post" onsubmit="return confirm('{{ __('messages.delete_msg') }}');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('messages.remove') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">{{ __('messages.Subtotal') }}</th>
                            <th colspan="2">{{ getPriceFormat($subtotal) }}</th>
                        </tr>
                        <tr>
                            <th colspan="3" class="text-end">{{ __('messages.tax') }}</th>
                            <th colspan="2">{{ getPriceFormat($taxTotal) }}</th>
                        </tr>
                        <tr>
                            <th colspan="3" class="text-end">{{ __('messages.grand_total') }}</th>
                            <th colspan="2">{{ getPriceFormat($subtotal + $taxTotal) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="mt-4">
                <form action="{{ route('user.cart.checkout') }}" method="post">
                    @csrf
                    <div class="card border mb-3">
                        <div class="card-body">
                            <h6 class="mb-3">Shipping Information</h6>
                            @php
                                $indianStates = config('indian_states', []);
                                $shipState = old('shipping_state', session('user_state', ''));
                                $shipCity = old('shipping_city', session('user_city', ''));
                            @endphp
                            <input type="hidden" name="shipping_country" value="India">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="shipping_name" class="form-control checkout-required-field" value="{{ old('shipping_name') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Address</label>
                                    <textarea name="shipping_address" class="form-control checkout-required-field" rows="3" required>{{ old('shipping_address') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">State</label>
                                    <select name="shipping_state" class="form-select checkout-required-field" required>
                                        <option value="">{{ __('landingpage.select') }}</option>
                                        @foreach ($indianStates as $st)
                                            <option value="{{ $st }}" @selected($shipState === $st)>{{ $st }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">City</label>
                                    <input type="text" name="shipping_city" class="form-control checkout-required-field" value="{{ $shipCity }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Pincode</label>
                                    <input type="text" name="shipping_pincode" class="form-control checkout-required-field" value="{{ old('shipping_pincode') }}" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    @php
                        $activeGatewayTypes = \App\Models\PaymentGateway::query()
                            ->where('status', 1)
                            ->where('type', '!=', 'razorPayX')
                            ->pluck('type')
                            ->filter()
                            ->values()
                            ->all();
                        $gatewayLabelMap = [
                            'stripe' => __('messages.stripe'),
                            'cash' => __('messages.cash'),
                            'razorPay' => 'Razorpay',
                            'phonepe' => 'PhonePe',
                            'paypal' => 'PayPal',
                            'paystack' => 'Paystack',
                            'flutterwave' => 'Flutterwave',
                            'midtrans' => __('messages.midtrans'),
                            'sadad' => __('messages.sadad'),
                            'cinet' => __('messages.cinet'),
                            'airtel' => __('messages.airtel_money'),
                        ];
                    @endphp
                    <div class="mb-3" id="payment-options-box" style="display:none;">
                        <label class="form-label d-block mb-2">{{ __('messages.payment_method') }}</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="payment_method" id="product_wallet" value="wallet" checked>
                            <label class="form-check-label" for="product_wallet">{{ __('messages.wallet') }}</label>
                        </div>
                        @foreach($activeGatewayTypes as $gatewayType)
                            @if($gatewayType !== 'wallet')
                                @php
                                    $inputId = 'product_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $gatewayType);
                                    $gatewayLabel = $gatewayLabelMap[$gatewayType] ?? ucfirst($gatewayType);
                                @endphp
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="payment_method" id="{{ $inputId }}" value="{{ $gatewayType }}">
                                    <label class="form-check-label" for="{{ $inputId }}">{{ $gatewayLabel }}</label>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <button type="submit" class="btn btn-primary" id="product-checkout-btn" disabled>{{ __('messages.checkout') }}</button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection

@section('after_script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const requiredFields = Array.from(document.querySelectorAll('.checkout-required-field'));
    const paymentBox = document.getElementById('payment-options-box');
    const checkoutBtn = document.getElementById('product-checkout-btn');
    if (!requiredFields.length || !paymentBox || !checkoutBtn) return;

    function toggleCheckoutState() {
        const allFilled = requiredFields.every(function (el) {
            return String(el.value || '').trim() !== '';
        });
        paymentBox.style.display = allFilled ? '' : 'none';
        checkoutBtn.disabled = !allFilled;
    }

    requiredFields.forEach(function (el) {
        el.addEventListener('input', toggleCheckoutState);
        el.addEventListener('change', toggleCheckoutState);
    });
    toggleCheckoutState();

    document.querySelectorAll('.cart-qty-form').forEach(function (form) {
        const input = form.querySelector('.qty-input');
        const minus = form.querySelector('.qty-minus');
        const plus = form.querySelector('.qty-plus');
        if (!input || !minus || !plus) return;

        function submitWith(nextValue) {
            const n = Math.max(1, Math.min(99, nextValue));
            if (Number(input.value) === n) return;
            input.value = n;
            form.submit();
        }

        minus.addEventListener('click', function () {
            submitWith(Number(input.value || 1) - 1);
        });
        plus.addEventListener('click', function () {
            submitWith(Number(input.value || 1) + 1);
        });
    });
});
</script>
@endsection
