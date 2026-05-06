@extends('landing-page.layouts.default')

@section('content')
<div class="section-padding">
    <div class="container">
        <div class="alert alert-info mb-0">{{ __('messages.processing') }}...</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const gateway = @json($paymentType);
        const completeUrl = @json(route('user.subscription.gateway.complete', $subscription->id));
        const failRedirect = @json(route('user.subscriptions.index'));
        const amount = {{ (float) $subscription->amount }};
        const email = @json(auth()->user()->email ?? '');
        const name = @json(auth()->user()->display_name ?? auth()->user()->first_name ?? 'Customer');
        const currency = 'INR';

        const postComplete = (payload) => {
            fetch(completeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token()),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            }).then(r => r.json())
            .then(data => window.location.href = data.redirect || failRedirect)
            .catch(() => window.location.href = failRedirect);
        };

        if (gateway === 'paystack') {
            const key = @json($gatewayConfig['paystack_public'] ?? '');
            if (!key) return window.location.href = failRedirect;
            const s = document.createElement('script');
            s.src = 'https://js.paystack.co/v1/inline.js';
            s.onload = function () {
                const handler = window.PaystackPop.setup({
                    key: key,
                    email: email || 'customer@example.com',
                    amount: Math.round(amount * 100),
                    currency: currency,
                    ref: 'SUB_PS_' + Date.now() + '_' + @json($subscription->id),
                    callback: function (response) {
                        postComplete({ gateway: 'paystack', transaction_id: response.reference || '', reference: response.reference || '', status: 'success' });
                    },
                    onClose: function () { window.location.href = failRedirect; }
                });
                handler.openIframe();
            };
            document.body.appendChild(s);
            return;
        }

        if (gateway === 'flutterwave') {
            const publicKey = @json($gatewayConfig['flutterwave_public'] ?? '');
            if (!publicKey) return window.location.href = failRedirect;
            const s = document.createElement('script');
            s.src = 'https://checkout.flutterwave.com/v3.js';
            s.onload = function () {
                FlutterwaveCheckout({
                    public_key: publicKey,
                    tx_ref: 'SUB_FLW_' + Date.now() + '_' + @json($subscription->id),
                    amount: amount,
                    currency: currency,
                    payment_options: 'card,banktransfer,ussd',
                    customer: { email: email || 'customer@example.com', name: name },
                    customizations: { title: @json(config('app.name')), description: @json('Subscription ' . $subscription->title) },
                    callback: function (response) {
                        postComplete({
                            gateway: 'flutterwave',
                            transaction_id: String(response.transaction_id || ''),
                            reference: String(response.tx_ref || ''),
                            status: String(response.status || 'success')
                        });
                    },
                    onclose: function () { window.location.href = failRedirect; }
                });
            };
            document.body.appendChild(s);
            return;
        }

        if (gateway === 'paypal') {
            const clientId = @json($gatewayConfig['paypal_client_id'] ?? '');
            if (!clientId) return window.location.href = failRedirect;
            const holder = document.createElement('div');
            holder.id = 'paypal-button-container';
            document.body.appendChild(holder);
            const s = document.createElement('script');
            s.src = 'https://www.paypal.com/sdk/js?client-id=' + encodeURIComponent(clientId) + '&currency=' + encodeURIComponent(currency);
            s.onload = function () {
                if (!window.paypal) return window.location.href = failRedirect;
                window.paypal.Buttons({
                    createOrder: function (data, actions) {
                        return actions.order.create({
                            purchase_units: [{ amount: { value: amount.toFixed(2) }, description: @json('Subscription ' . $subscription->title) }]
                        });
                    },
                    onApprove: function (data, actions) {
                        return actions.order.capture().then(function (details) {
                            postComplete({ gateway: 'paypal', transaction_id: data.orderID || '', reference: (details && details.id) ? details.id : '', status: 'success' });
                        });
                    },
                    onCancel: function () { window.location.href = failRedirect; },
                    onError: function () { window.location.href = failRedirect; }
                }).render('#paypal-button-container');
            };
            document.body.appendChild(s);
            return;
        }

        window.location.href = failRedirect;
    })();
</script>
@endpush
