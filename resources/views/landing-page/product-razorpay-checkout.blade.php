@extends('landing-page.layouts.default')

@section('content')
<div class="section-padding">
    <div class="container">
        <div class="alert alert-info mb-0">
            {{ __('messages.processing') }}...
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    (function () {
        const options = {
            key: @json($razorKey),
            amount: {{ (int) round(((float) $order->total) * 100) }},
            currency: @json($currencyCode ?? 'INR'),
            name: @json(config('app.name')),
            description: @json('Product Order ' . $order->order_number),
            order_id: @json($razorOrderId),
            handler: function (response) {
                fetch(@json(route('user.product.razorpay.verify', $order->id)), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token()),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        razorpay_payment_id: response.razorpay_payment_id,
                        razorpay_order_id: response.razorpay_order_id,
                        razorpay_signature: response.razorpay_signature
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status && data.redirect) {
                        window.location.href = data.redirect;
                        return;
                    }
                    window.location.href = @json(route('user.product-order.show', $order->id));
                })
                .catch(() => {
                    window.location.href = @json(route('user.product-order.show', $order->id));
                });
            },
            modal: {
                ondismiss: function () {
                    window.location.href = @json(route('user.product-order.show', $order->id));
                }
            },
            prefill: {
                name: @json(auth()->user()->display_name ?? auth()->user()->first_name ?? ''),
                email: @json(auth()->user()->email ?? ''),
                contact: @json(auth()->user()->contact_number ?? '')
            },
            theme: { color: '#0d6efd' }
        };

        const rzp = new Razorpay(options);
        rzp.on('payment.failed', function () {
            window.location.href = @json(route('user.product-order.show', $order->id));
        });
        rzp.open();
    })();
</script>
@endpush
