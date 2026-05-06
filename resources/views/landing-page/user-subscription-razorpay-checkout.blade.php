@extends('landing-page.layouts.default')

@section('content')
<div class="section-padding">
    <div class="container">
        <div class="alert alert-info mb-0">{{ __('messages.processing') }}...</div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    (function () {
        const options = {
            key: @json($razorKey),
            amount: {{ (int) round(((float) $subscription->amount) * 100) }},
            currency: 'INR',
            name: @json(config('app.name')),
            description: @json('Subscription ' . $subscription->title),
            order_id: @json($razorOrderId),
            handler: function (response) {
                fetch(@json(route('user.subscription.razorpay.verify', $subscription->id)), {
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
                }).then(r => r.json())
                .then(data => window.location.href = data.redirect || @json(route('user.subscriptions.index')))
                .catch(() => window.location.href = @json(route('user.subscriptions.index')));
            },
            modal: {
                ondismiss: function () {
                    window.location.href = @json(route('user.subscriptions.index'));
                }
            }
        };
        (new Razorpay(options)).open();
    })();
</script>
@endpush
