@extends('landing-page.layouts.default')

@section('content')
<div class="section-padding">
    <div class="container">
        <div id="product-gateway-processing" class="alert alert-info mb-0">
            {{ __('messages.processing') }}...
        </div>
        <div id="product-gateway-manual" class="card border d-none">
            <div class="card-body">
                <h5 class="mb-2" id="manual-gateway-title"></h5>
                <p class="text-muted mb-3">Complete payment in your selected gateway, then confirm here.</p>
                <div class="mb-3">
                    <label for="manual-transaction-id" class="form-label">Transaction ID / Reference</label>
                    <input id="manual-transaction-id" type="text" class="form-control" maxlength="255" placeholder="Enter gateway transaction ID">
                </div>
                <div class="d-flex gap-2">
                    <button type="button" id="manual-complete-btn" class="btn btn-primary">I have paid</button>
                    <a href="{{ route('user.product-order.show', $order) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const gateway = @json($paymentType);
        const completeUrl = @json(route('user.product.gateway.complete', $order->id));
        const failRedirect = @json(route('user.product-order.show', $order));
        const amount = {{ (float) $order->total }};
        const email = @json(auth()->user()->email ?? '');
        const name = @json(auth()->user()->display_name ?? auth()->user()->first_name ?? 'Customer');
        const currency = @json('INR');

        const postComplete = (payload) => {
            fetch(completeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token()),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                window.location.href = data.redirect || failRedirect;
            })
            .catch(() => {
                window.location.href = failRedirect;
            });
        };
        const showManualFallback = () => {
            const processing = document.getElementById('product-gateway-processing');
            const manual = document.getElementById('product-gateway-manual');
            const title = document.getElementById('manual-gateway-title');
            const transactionInput = document.getElementById('manual-transaction-id');
            const completeBtn = document.getElementById('manual-complete-btn');
            if (!processing || !manual || !title || !transactionInput || !completeBtn) {
                window.location.href = failRedirect;
                return;
            }
            const labels = {
                midtrans: 'Midtrans',
                sadad: 'SADAD',
                cinet: 'Cinet',
                airtel: 'Airtel Money',
            };
            title.textContent = 'Complete payment via ' + (labels[gateway] || gateway);
            processing.classList.add('d-none');
            manual.classList.remove('d-none');
            completeBtn.addEventListener('click', function () {
                const txn = String(transactionInput.value || '').trim();
                if (!txn) {
                    transactionInput.focus();
                    return;
                }
                completeBtn.disabled = true;
                postComplete({
                    gateway: gateway,
                    transaction_id: txn,
                    reference: txn,
                    status: 'success'
                });
            });
        };

        if (gateway === 'paystack') {
            const key = @json($gatewayConfig['paystack_public'] ?? '');
            if (!key) {
                window.location.href = failRedirect;
                return;
            }
            const s = document.createElement('script');
            s.src = 'https://js.paystack.co/v1/inline.js';
            s.onload = function () {
                const handler = window.PaystackPop.setup({
                    key: key,
                    email: email || 'customer@example.com',
                    amount: Math.round(amount * 100),
                    currency: currency || 'NGN',
                    ref: 'POPS_' + Date.now() + '_' + @json($order->id),
                    callback: function (response) {
                        postComplete({
                            gateway: 'paystack',
                            transaction_id: response.reference || '',
                            reference: response.reference || '',
                            status: 'success'
                        });
                    },
                    onClose: function () {
                        window.location.href = failRedirect;
                    }
                });
                handler.openIframe();
            };
            document.body.appendChild(s);
            return;
        }

        if (gateway === 'flutterwave') {
            const publicKey = @json($gatewayConfig['flutterwave_public'] ?? '');
            if (!publicKey) {
                window.location.href = failRedirect;
                return;
            }
            const s = document.createElement('script');
            s.src = 'https://checkout.flutterwave.com/v3.js';
            s.onload = function () {
                FlutterwaveCheckout({
                    public_key: publicKey,
                    tx_ref: 'POFLW_' + Date.now() + '_' + @json($order->id),
                    amount: amount,
                    currency: currency || 'INR',
                    payment_options: 'card,banktransfer,ussd',
                    customer: {
                        email: email || 'customer@example.com',
                        name: name
                    },
                    customizations: {
                        title: @json(config('app.name')),
                        description: @json('Product Order ' . $order->order_number),
                    },
                    callback: function (response) {
                        postComplete({
                            gateway: 'flutterwave',
                            transaction_id: String(response.transaction_id || ''),
                            reference: String(response.tx_ref || ''),
                            status: String(response.status || 'success')
                        });
                    },
                    onclose: function () {
                        window.location.href = failRedirect;
                    }
                });
            };
            document.body.appendChild(s);
            return;
        }

        if (gateway === 'paypal') {
            const clientId = @json($gatewayConfig['paypal_client_id'] ?? '');
            if (!clientId) {
                window.location.href = failRedirect;
                return;
            }

            const holder = document.createElement('div');
            holder.id = 'paypal-button-container';
            document.body.appendChild(holder);

            const s = document.createElement('script');
            s.src = 'https://www.paypal.com/sdk/js?client-id=' + encodeURIComponent(clientId) + '&currency=' + encodeURIComponent(currency || 'USD');
            s.onload = function () {
                if (!window.paypal) {
                    window.location.href = failRedirect;
                    return;
                }
                window.paypal.Buttons({
                    createOrder: function (data, actions) {
                        return actions.order.create({
                            purchase_units: [{
                                amount: { value: amount.toFixed(2) },
                                description: @json('Product Order ' . $order->order_number)
                            }]
                        });
                    },
                    onApprove: function (data, actions) {
                        return actions.order.capture().then(function (details) {
                            postComplete({
                                gateway: 'paypal',
                                transaction_id: data.orderID || '',
                                reference: (details && details.id) ? details.id : '',
                                status: 'success'
                            });
                        });
                    },
                    onCancel: function () {
                        window.location.href = failRedirect;
                    },
                    onError: function () {
                        window.location.href = failRedirect;
                    }
                }).render('#paypal-button-container');
            };
            document.body.appendChild(s);
            return;
        }

        if (['midtrans', 'sadad', 'cinet', 'airtel'].includes(gateway)) {
            showManualFallback();
            return;
        }

        window.location.href = failRedirect;
    })();
</script>
@endpush
