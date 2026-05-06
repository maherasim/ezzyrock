<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCartItem;
use App\Models\ProductVariant;
use App\Models\Tax;
use App\Models\Country;
use App\Models\PaymentGateway;
use App\Models\Setting;
use App\Models\Wallet;
use App\Models\WalletHistory;
use Stripe\StripeClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Razorpay\Api\Api as RazorpayApi;

class UserProductCartController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['addIntentGuest']);
    }

    public function addIntentGuest(Request $request)
    {
        if (! Schema::hasTable('product_cart_items')) {
            return back()->withErrors(__('messages.cart_unavailable'));
        }
        if (auth()->check()) {
            return $this->add($request);
        }
        $request->validate([
            'product_id' => 'required|integer',
            'product_variant_id' => 'nullable|integer',
            'quantity' => 'nullable|integer|min:1|max:99',
        ]);
        $product = $this->purchasableProduct((int) $request->product_id);
        if (! $product) {
            return back()->withErrors(__('messages.product_not_found'));
        }
        session([
            'pending_cart_add' => [
                'product_id' => (int) $request->product_id,
                'product_variant_id' => $request->filled('product_variant_id') ? (int) $request->product_variant_id : null,
                'quantity' => (int) ($request->quantity ?? 1),
            ],
        ]);

        return redirect()->route('user.login');
    }

    public function index()
    {
        $this->ensureCustomer();
        if (! Schema::hasTable('product_cart_items')) {
            return redirect()->route('frontend.index')->withErrors(__('messages.cart_unavailable'));
        }
        $items = ProductCartItem::query()
            ->where('user_id', auth()->id())
            ->whereHas('product', function ($q) {
                $q->where('service_type', 'ecommerce')
                    ->where('status', 1)
                    ->where('service_request_status', 'approve');
            })
            ->with(['product.providers', 'product.translations', 'variant.option.attribute'])
            ->get();

        return view('landing-page.user-cart', compact('items'));
    }

    public function add(Request $request)
    {
        $this->ensureCustomer();
        $wantJson = $this->wantsListingCartJson($request);
        if (! Schema::hasTable('product_cart_items')) {
            return $wantJson
                ? response()->json(['ok' => false, 'message' => __('messages.cart_unavailable')], 422)
                : back()->withErrors(__('messages.cart_unavailable'));
        }
        $validator = \Validator::make($request->all(), [
            'product_id' => 'required|integer',
            'product_variant_id' => 'nullable|integer',
            'quantity' => 'nullable|integer|min:1|max:99',
        ]);
        if ($validator->fails()) {
            return $wantJson
                ? response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422)
                : back()->withErrors($validator);
        }
        $product = $this->purchasableProduct((int) $request->product_id);
        if (! $product) {
            return $wantJson
                ? response()->json(['ok' => false, 'message' => __('messages.product_not_found')], 422)
                : back()->withErrors(__('messages.product_not_found'));
        }
        $variant = $this->resolveVariant($product, $request->input('product_variant_id'));
        if ($variant === false) {
            $msg = 'Please select a valid product option.';

            return $wantJson
                ? response()->json(['ok' => false, 'message' => $msg], 422)
                : back()->withErrors($msg);
        }
        $qty = (int) ($request->quantity ?? 1);
        $row = ProductCartItem::query()->firstOrNew([
            'user_id' => auth()->id(),
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
        ]);
        $newQuantity = ($row->exists ? (int) $row->quantity : 0) + $qty;
        $limitError = $this->validateCartQuantityLimit($product, $newQuantity, $variant);
        if ($limitError !== null) {
            return $wantJson
                ? response()->json(['ok' => false, 'message' => $limitError], 422)
                : back()->withErrors($limitError);
        }
        $row->quantity = $newQuantity;
        $row->save();

        if ($wantJson) {
            return response()->json([
                'ok' => true,
                'html' => $this->renderProductCardCartInnerHtml($product->id),
                'cart_count' => ProductCartItem::totalEcommerceQuantityForUser((int) auth()->id()),
                'message' => __('messages.added_to_cart'),
            ]);
        }

        return back()->with('success', __('messages.added_to_cart'));
    }

    public function update(Request $request, ProductCartItem $cartItem)
    {
        $this->ensureCustomer();
        $wantJson = $this->wantsListingCartJson($request);
        if (! Schema::hasTable('product_cart_items')) {
            return $wantJson
                ? response()->json(['ok' => false, 'message' => __('messages.cart_unavailable')], 422)
                : back()->withErrors(__('messages.cart_unavailable'));
        }
        $this->authorizeCartItem($cartItem);
        $validator = \Validator::make($request->all(), ['quantity' => 'required|integer|min:1|max:99']);
        if ($validator->fails()) {
            return $wantJson
                ? response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422)
                : back()->withErrors($validator);
        }
        $newQuantity = (int) $request->quantity;
        $limitError = $this->validateCartQuantityLimit($cartItem->product, $newQuantity, $cartItem->variant);
        if ($limitError !== null) {
            return $wantJson
                ? response()->json(['ok' => false, 'message' => $limitError], 422)
                : back()->withErrors($limitError);
        }
        $cartItem->update(['quantity' => $newQuantity]);
        $productId = (int) $cartItem->product_id;

        if ($wantJson) {
            return response()->json([
                'ok' => true,
                'html' => $this->renderProductCardCartInnerHtml($productId),
                'cart_count' => ProductCartItem::totalEcommerceQuantityForUser((int) auth()->id()),
                'message' => __('messages.cart_updated'),
            ]);
        }

        return back()->with('success', __('messages.cart_updated'));
    }

    public function remove(Request $request, ProductCartItem $cartItem)
    {
        $this->ensureCustomer();
        $wantJson = $this->wantsListingCartJson($request);
        if (! Schema::hasTable('product_cart_items')) {
            return $wantJson
                ? response()->json(['ok' => false, 'message' => __('messages.cart_unavailable')], 422)
                : back()->withErrors(__('messages.cart_unavailable'));
        }
        $this->authorizeCartItem($cartItem);
        $productId = (int) $cartItem->product_id;
        $cartItem->delete();

        if ($wantJson) {
            return response()->json([
                'ok' => true,
                'html' => $this->renderProductCardCartInnerHtml($productId),
                'cart_count' => ProductCartItem::totalEcommerceQuantityForUser((int) auth()->id()),
                'message' => __('messages.removed_from_cart'),
            ]);
        }

        return back()->with('success', __('messages.removed_from_cart'));
    }

    public function checkout(Request $request)
    {
        $this->ensureCustomer();
        if (! Schema::hasTable('product_cart_items')) {
            return redirect()->route('frontend.index')->withErrors(__('messages.cart_unavailable'));
        }
        $allowedPaymentMethods = $this->getAllowedPaymentMethods();
        $indianStates = config('indian_states', []);
        $request->validate([
            'payment_method' => 'required|string|in:' . implode(',', $allowedPaymentMethods),
            'shipping_name' => 'required|string|max:120',
            'shipping_address' => 'required|string|max:255',
            'shipping_city' => 'required|string|max:120',
            'shipping_state' => ['required', 'string', 'max:120', Rule::in($indianStates)],
            'shipping_pincode' => 'required|string|max:20',
            'shipping_country' => 'nullable|string|max:80',
        ]);
        $userId = auth()->id();
        $items = ProductCartItem::query()->where('user_id', $userId)->with(['product', 'variant.option.attribute'])->get();
        if ($items->isEmpty()) {
            return redirect()->route('user.cart')->withErrors(__('messages.cart_empty'));
        }
        $paymentMethod = (string) $request->payment_method;

        $shippingData = [
            'name' => (string) $request->shipping_name,
            'address' => (string) $request->shipping_address,
            'city' => (string) $request->shipping_city,
            'state' => (string) $request->shipping_state,
            'pincode' => (string) $request->shipping_pincode,
            'country' => (string) ($request->shipping_country ?: 'India'),
        ];

        return DB::transaction(function () use ($items, $userId, $paymentMethod, $shippingData) {
            $subtotal = 0;
            $taxTotal = 0;
            $orderLines = [];
            $taxDetail = [];
            foreach ($items as $item) {
                $p = $item->product;
                if (! $p || ! $this->isPurchasable($p)) {
                    continue;
                }
                $stockCheckMessage = $this->validateCartQuantityLimit($p, (int) $item->quantity, $item->variant);
                if ($stockCheckMessage !== null) {
                    return redirect()->route('user.cart')->withErrors($stockCheckMessage);
                }
                $unit = (float) ($item->variant?->price ?? $p->price);
                if ($p->discount > 0) {
                    $unit = $unit - ($unit * (float) $p->discount / 100);
                }
                $line = round($unit * $item->quantity, 2);
                $lineTaxData = $this->calculateLineTax($line);
                $lineTax = $lineTaxData['line_tax'];
                $subtotal += $line;
                $taxTotal += $lineTax;
                foreach ($lineTaxData['applied'] as $taxRow) {
                    $key = $taxRow['title'].'|'.$taxRow['type'].'|'.$taxRow['value'];
                    if (! isset($taxDetail[$key])) {
                        $taxDetail[$key] = $taxRow;
                    } else {
                        $taxDetail[$key]['amount'] = round((float) $taxDetail[$key]['amount'] + (float) $taxRow['amount'], 2);
                    }
                }
                $orderLines[] = [
                    'product' => $p,
                    'variant' => $item->variant,
                    'variant_label' => $item->variant?->option?->value,
                    'quantity' => $item->quantity,
                    'unit_price' => round($unit, 2),
                    'line_total' => $line,
                ];
            }
            if ($orderLines === []) {
                ProductCartItem::query()->where('user_id', $userId)->delete();

                return redirect()->route('user.cart')->withErrors(__('messages.cart_empty'));
            }

            $taxTotal = round($taxTotal, 2);
            $grandTotal = round($subtotal + $taxTotal, 2);

            $orderNumber = 'PO-'.strtoupper(bin2hex(random_bytes(4)));
            $orderPayload = [
                'order_number' => $orderNumber,
                'user_id' => $userId,
                'status' => 'pending',
                'subtotal' => round($subtotal, 2),
                'total' => $grandTotal,
                'notes' => json_encode(['shipping' => $shippingData]),
            ];
            if (Schema::hasColumn('product_orders', 'tax_total')) {
                $orderPayload['tax_total'] = $taxTotal;
            }
            if (Schema::hasColumn('product_orders', 'payment_type')) {
                $orderPayload['payment_type'] = $paymentMethod;
            }
            if (Schema::hasColumn('product_orders', 'payment_status')) {
                $orderPayload['payment_status'] = 'failed';
            }
            if (Schema::hasColumn('product_orders', 'tax_detail')) {
                $orderPayload['tax_detail'] = array_values($taxDetail);
            }
            $order = \App\Models\ProductOrder::query()->create($orderPayload);
            foreach ($orderLines as $line) {
                $p = $line['product'];
                $variant = $line['variant'] ?? null;
                if ($variant && Schema::hasColumn('product_variants', 'stock')) {
                    $affectedVariant = ProductVariant::query()
                        ->where('id', $variant->id)
                        ->where('stock', '>=', (int) $line['quantity'])
                        ->decrement('stock', (int) $line['quantity']);
                    if (! $affectedVariant) {
                        return redirect()->route('user.cart')->withErrors('Product option stock changed. Please update cart.');
                    }
                }
                if (Schema::hasColumn('products', 'total_stock')) {
                    $affected = Product::query()
                        ->where('id', $p->id)
                        ->where('total_stock', '>=', (int) $line['quantity'])
                        ->decrement('total_stock', (int) $line['quantity']);
                    if (! $affected) {
                        return redirect()->route('user.cart')->withErrors(__('messages.product_not_found'));
                    }
                }
                \App\Models\ProductOrderItem::query()->create([
                    'product_order_id' => $order->id,
                    'product_id' => $p->id,
                    'product_variant_id' => $variant?->id,
                    'product_name' => $p->name,
                    'variant_label' => $line['variant_label'] ?? null,
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ]);
            }

            if ($paymentMethod === 'wallet') {
                $wallet = Wallet::query()->where('user_id', $userId)->first();
                $walletAmount = $wallet ? (float) $wallet->amount : 0.0;
                if ($walletAmount < $grandTotal) {
                    return redirect()->route('user.cart')->withErrors(__('messages.wallent_balance_error'));
                }
                $wallet->amount = round($walletAmount - $grandTotal, 2);
                $wallet->save();
                if (Schema::hasColumn('product_orders', 'status')) {
                    $order->status = 'confirmed';
                }
                if (Schema::hasColumn('product_orders', 'payment_status')) {
                    $order->payment_status = 'paid';
                }
                $order->save();
                WalletHistory::query()->create([
                    'datetime' => now(),
                    'user_id' => $userId,
                    'activity_type' => 'paid_with_wallet',
                    'activity_message' => __('messages.paid_with_wallet', ['Value' => $order->order_number]),
                    'activity_data' => json_encode([
                        'order_number' => $order->order_number,
                        'order_type' => 'product',
                        'subtotal' => round($subtotal, 2),
                        'tax_total' => $taxTotal,
                        'total' => $grandTotal,
                    ]),
                ]);
                ProductCartItem::query()->where('user_id', $userId)->delete();

                return redirect()
                    ->route('user.product-order.show', $order)
                    ->with('success', __('messages.order_placed'));
            }

            if ($paymentMethod === 'cash') {
                if (Schema::hasColumn('product_orders', 'payment_status')) {
                    $order->payment_status = 'pending';
                }
                if (Schema::hasColumn('product_orders', 'status')) {
                    $order->status = 'pending';
                }
                $order->save();

                ProductCartItem::query()->where('user_id', $userId)->delete();

                return redirect()
                    ->route('user.product-order.show', $order)
                    ->with('success', __('messages.payment_message', ['status' => __('messages.pending')]));
            }

            if ($paymentMethod === 'razorPay') {
                $razorpay = PaymentGateway::query()->where('type', 'razorPay')->where('status', 1)->first();
                $gatewayData = json_decode((string) ($razorpay->value ?? '{}'), true);
                $razorKey = $gatewayData['razor_key'] ?? null;
                $razorSecret = $gatewayData['razor_secret'] ?? null;
                if (empty($razorKey) || empty($razorSecret)) {
                    return redirect()->route('user.cart')->withErrors(__('messages.something_wrong'));
                }

                $api = new RazorpayApi($razorKey, $razorSecret);
                $razorOrder = $api->order->create([
                    'receipt' => (string) $order->order_number,
                    'amount' => (int) round($grandTotal * 100),
                    'currency' => $this->getCurrencyCode(),
                ]);

                if (Schema::hasColumn('product_orders', 'other_transaction_detail')) {
                    $order->other_transaction_detail = json_encode([
                        'gateway' => 'razorPay',
                        'razorpay_order_id' => (string) $razorOrder['id'],
                    ]);
                    $order->save();
                }
                ProductCartItem::query()->where('user_id', $userId)->delete();

                return redirect()->route('user.product.razorpay.checkout', $order->id);
            }

            if ($paymentMethod === 'phonepe') {
                $phonepe = PaymentGateway::query()->where('type', 'phonepe')->where('status', 1)->first();
                $phoneConfig = json_decode((string) ($phonepe->value ?? '{}'), true);
                $merchantId = $phoneConfig['merchant_id'] ?? null;
                $saltKey = $phoneConfig['salt_key'] ?? null;
                $saltIndex = $phoneConfig['salt_index'] ?? 1;
                if (empty($merchantId) || empty($saltKey)) {
                    return redirect()->route('user.cart')->withErrors(__('messages.something_wrong'));
                }

                $apiPath = '/pg/v1/pay';
                $merchantTransactionId = 'POPP' . time() . $order->id;
                $payload = [
                    'merchantId' => $merchantId,
                    'merchantTransactionId' => $merchantTransactionId,
                    'amount' => (int) round($grandTotal * 100),
                    'redirectUrl' => url('/api/product-phonepe/callback?order_id=' . $order->id),
                    'redirectMode' => 'POST',
                    'callbackUrl' => url('/api/product-phonepe/callback?order_id=' . $order->id),
                    'paymentInstrument' => [
                        'type' => 'PAY_PAGE',
                    ],
                ];
                $base64Payload = base64_encode(json_encode($payload));
                $checksum = $this->generatePhonePeChecksum($base64Payload, $apiPath, (string) $saltKey, (string) $saltIndex);

                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'X-VERIFY' => $checksum,
                    'X-MERCHANT-ID' => (string) $merchantId,
                ])->post($this->getPhonePeBaseUrl((int) ($phonepe->is_test ?? 1)) . $apiPath, [
                    'request' => $base64Payload,
                ]);

                $json = $response->json();
                $payUrl = data_get($json, 'data.instrumentResponse.redirectInfo.url');
                if (! $response->successful() || empty($payUrl)) {
                    Log::error('Product PhonePe initiate failed', ['response' => $json]);

                    return redirect()->route('user.cart')->withErrors(__('messages.something_wrong'));
                }
                if (Schema::hasColumn('product_orders', 'other_transaction_detail')) {
                    $order->other_transaction_detail = json_encode([
                        'gateway' => 'phonepe',
                        'merchant_transaction_id' => $merchantTransactionId,
                    ]);
                    $order->save();
                }
                ProductCartItem::query()->where('user_id', $userId)->delete();

                return redirect()->away((string) $payUrl);
            }

            if (in_array($paymentMethod, ['paypal', 'paystack', 'flutterwave', 'midtrans', 'sadad', 'cinet', 'airtel'], true)) {
                $gateway = PaymentGateway::query()->where('type', $paymentMethod)->where('status', 1)->first();
                if (! $gateway) {
                    return redirect()->route('user.cart')->withErrors(__('messages.something_wrong'));
                }
                $cfg = $this->getGatewayConfig($gateway);
                if ($paymentMethod === 'paypal' && empty($cfg['paypal_client_id'])) {
                    return redirect()->route('user.cart')->withErrors(__('messages.something_wrong'));
                }
                if ($paymentMethod === 'paystack' && empty($cfg['paystack_public'])) {
                    return redirect()->route('user.cart')->withErrors(__('messages.something_wrong'));
                }
                if ($paymentMethod === 'flutterwave' && empty($cfg['flutterwave_public'])) {
                    return redirect()->route('user.cart')->withErrors(__('messages.something_wrong'));
                }

                if (Schema::hasColumn('product_orders', 'other_transaction_detail')) {
                    $order->other_transaction_detail = json_encode([
                        'gateway' => $paymentMethod,
                    ]);
                    $order->save();
                }
                ProductCartItem::query()->where('user_id', $userId)->delete();

                return redirect()->route('user.product.gateway.checkout', $order->id);
            }

            $stripeKeyData = getPaymentMethodkey('stripe');
            $stripeSecret = is_array($stripeKeyData) ? ($stripeKeyData['stripe_key'] ?? null) : null;
            if (empty($stripeSecret)) {
                return redirect()->route('user.cart')->withErrors(__('messages.stripe_details'));
            }

            $currencyCode = $this->getCurrencyCode();
            $stripe = new StripeClient($stripeSecret);
            $checkoutSession = $stripe->checkout->sessions->create([
                'success_url' => url('/save-product-stripe-payment/' . $order->id),
                'cancel_url' => route('user.cart'),
                'payment_method_types' => ['card'],
                'billing_address_collection' => 'required',
                'line_items' => [[
                    'price_data' => [
                        'currency' => $currencyCode,
                        'product_data' => [
                            'name' => 'Product Order ' . $order->order_number,
                        ],
                        'unit_amount' => (int) round($grandTotal * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
            ]);

            if (Schema::hasColumn('product_orders', 'other_transaction_detail')) {
                $order->other_transaction_detail = (string) $checkoutSession->id;
                $order->save();
            }
            ProductCartItem::query()->where('user_id', $userId)->delete();

            return redirect($checkoutSession->url);
        });
    }

    public function saveStripePayment(int $id)
    {
        $order = \App\Models\ProductOrder::query()->findOrFail($id);
        $sessionId = (string) ($order->other_transaction_detail ?? '');
        if ($sessionId === '') {
            return redirect()->route('user.product-order.show', $order)->withErrors(__('messages.something_wrong'));
        }

        $sessionObject = getstripePaymnetId($sessionId, 'stripe');
        if (!empty($sessionObject['payment_intent']) && ($sessionObject['payment_status'] ?? '') === 'paid') {
            if (Schema::hasColumn('product_orders', 'txn_id')) {
                $order->txn_id = (string) $sessionObject['payment_intent'];
            }
            if (Schema::hasColumn('product_orders', 'payment_status')) {
                $order->payment_status = 'paid';
            }
            if (Schema::hasColumn('product_orders', 'status')) {
                $order->status = 'confirmed';
            }
            $order->save();

            return redirect()->route('user.product-order.show', $order)->with('success', __('messages.order_placed'));
        }

        return redirect()->route('user.product-order.show', $order)->withErrors(__('messages.payment_message', ['status' => __('messages.failed')]));
    }

    public function razorpayCheckoutPage(int $id)
    {
        $order = \App\Models\ProductOrder::query()->where('user_id', auth()->id())->findOrFail($id);
        $gateway = PaymentGateway::query()->where('type', 'razorPay')->where('status', 1)->first();
        $gatewayData = json_decode((string) ($gateway->value ?? '{}'), true);
        $razorKey = $gatewayData['razor_key'] ?? null;
        if (empty($razorKey)) {
            return redirect()->route('user.product-order.show', $order)->withErrors(__('messages.something_wrong'));
        }
        $meta = json_decode((string) ($order->other_transaction_detail ?? '{}'), true);
        $razorOrderId = $meta['razorpay_order_id'] ?? null;
        if (empty($razorOrderId)) {
            return redirect()->route('user.product-order.show', $order)->withErrors(__('messages.something_wrong'));
        }

        return view('landing-page.product-razorpay-checkout', compact('order', 'razorKey', 'razorOrderId'));
    }

    public function verifyRazorpayPayment(Request $request, int $id)
    {
        $order = \App\Models\ProductOrder::query()->where('user_id', auth()->id())->findOrFail($id);
        $request->validate([
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $gateway = PaymentGateway::query()->where('type', 'razorPay')->where('status', 1)->first();
        $gatewayData = json_decode((string) ($gateway->value ?? '{}'), true);
        $razorSecret = $gatewayData['razor_secret'] ?? null;
        if (empty($razorSecret)) {
            return response()->json(['status' => false, 'message' => __('messages.something_wrong')], 422);
        }

        $meta = json_decode((string) ($order->other_transaction_detail ?? '{}'), true);
        if (($meta['razorpay_order_id'] ?? '') !== (string) $request->razorpay_order_id) {
            return response()->json(['status' => false, 'message' => __('messages.something_wrong')], 422);
        }

        $signatureData = $request->razorpay_order_id . '|' . $request->razorpay_payment_id;
        $expectedSignature = hash_hmac('sha256', $signatureData, (string) $razorSecret);
        if (! hash_equals($expectedSignature, (string) $request->razorpay_signature)) {
            return response()->json(['status' => false, 'message' => __('messages.payment_message', ['status' => __('messages.failed')])], 422);
        }

        $this->markOrderPaid($order, 'razorPay', (string) $request->razorpay_payment_id);

        return response()->json([
            'status' => true,
            'redirect' => route('user.product-order.show', $order),
            'message' => __('messages.order_placed'),
        ]);
    }

    public function productPhonePeCallback(Request $request)
    {
        $orderId = (int) ($request->query('order_id') ?? data_get($request->all(), 'data.order_id'));
        $order = \App\Models\ProductOrder::query()->find($orderId);
        if (! $order) {
            return response()->json(['status' => false, 'message' => 'Order not found'], 404);
        }

        $data = $request->all();
        $isSuccess = (data_get($data, 'code') === 'PAYMENT_SUCCESS');
        if ($isSuccess) {
            $txnId = (string) (data_get($data, 'data.transactionId') ?? data_get($data, 'transactionId') ?? '');
            $this->markOrderPaid($order, 'phonepe', $txnId);

            return redirect()->route('user.product-order.show', $order)->with('success', __('messages.order_placed'));
        }

        if (Schema::hasColumn('product_orders', 'payment_status')) {
            $order->payment_status = 'failed';
            $order->save();
        }

        return redirect()->route('user.product-order.show', $order)->withErrors(__('messages.payment_message', ['status' => __('messages.failed')]));
    }

    public function gatewayCheckoutPage(int $id)
    {
        $order = \App\Models\ProductOrder::query()->where('user_id', auth()->id())->findOrFail($id);
        $paymentType = (string) ($order->payment_type ?? '');
        abort_unless(in_array($paymentType, ['paypal', 'paystack', 'flutterwave', 'midtrans', 'sadad', 'cinet', 'airtel'], true), 404);

        $gateway = PaymentGateway::query()->where('type', $paymentType)->where('status', 1)->first();
        abort_unless($gateway, 404);
        $gatewayConfig = $this->getGatewayConfig($gateway);

        return view('landing-page.product-gateway-checkout', compact('order', 'paymentType', 'gatewayConfig'));
    }

    public function completeGatewayPayment(Request $request, int $id)
    {
        $order = \App\Models\ProductOrder::query()->where('user_id', auth()->id())->findOrFail($id);
        $gatewayTypes = array_filter($this->getAllowedPaymentMethods(), fn ($type) => ! in_array($type, ['wallet', 'cash', 'stripe', 'razorPay', 'phonepe'], true));
        $request->validate([
            'gateway' => 'required|in:' . implode(',', $gatewayTypes),
            'transaction_id' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'status' => 'required|string',
        ]);

        $status = strtolower((string) $request->status);
        if (! in_array($status, ['success', 'succeeded', 'completed', 'paid'], true)) {
            if (Schema::hasColumn('product_orders', 'payment_status')) {
                $order->payment_status = 'failed';
                $order->save();
            }

            return response()->json([
                'status' => false,
                'message' => __('messages.payment_message', ['status' => __('messages.failed')]),
                'redirect' => route('user.product-order.show', $order),
            ], 422);
        }

        $txnId = (string) ($request->transaction_id ?: $request->reference ?: ('TXN-' . time()));
        $this->markOrderPaid($order, (string) $request->gateway, $txnId);

        return response()->json([
            'status' => true,
            'message' => __('messages.order_placed'),
            'redirect' => route('user.product-order.show', $order),
        ]);
    }

    private function authorizeCartItem(ProductCartItem $cartItem): void
    {
        abort_unless($cartItem->user_id === auth()->id(), 403);
    }

    private function ensureCustomer(): void
    {
        abort_unless(auth()->user()->user_type === 'user', 403);
    }

    private function purchasableProduct(int $id): ?Product
    {
        $q = Product::query()
            ->where('id', $id)
            ->where('service_type', 'ecommerce')
            ->where('status', 1)
            ->where('service_request_status', 'approve');
        if (Schema::hasColumn('products', 'total_stock')) {
            $q->where('total_stock', '>', 0);
        }
        if (function_exists('default_earning_type') && default_earning_type() === 'subscription') {
            $q->whereHas('providers', function ($a) {
                $a->where('status', 1)->where('is_subscribe', 1);
            });
        }

        return $q->first();
    }

    private function isPurchasable(Product $p): bool
    {
        return $this->purchasableProduct((int) $p->id) !== null;
    }

    private function validateCartQuantityLimit(?Product $product, int $quantity, ?ProductVariant $variant = null): ?string
    {
        if (! $product) {
            return __('messages.product_not_found');
        }
        if ($variant) {
            if (!$variant->status) {
                return 'Selected product option is inactive.';
            }
            if (!empty($variant->max_purchase_qty) && $quantity > (int) $variant->max_purchase_qty) {
                return 'Maximum purchase quantity allowed for this option is ' . (int) $variant->max_purchase_qty . '.';
            }
            if ($quantity > (int) $variant->stock) {
                return 'Only ' . max((int) $variant->stock, 0) . ' item(s) available for selected option.';
            }
        }
        if (Schema::hasColumn('products', 'max_purchase_qty') && !empty($product->max_purchase_qty) && $quantity > (int) $product->max_purchase_qty) {
            return 'Maximum purchase quantity allowed is ' . (int) $product->max_purchase_qty . '.';
        }
        if (Schema::hasColumn('products', 'total_stock') && $quantity > (int) $product->total_stock) {
            return 'Only ' . max((int) $product->total_stock, 0) . ' item(s) in stock.';
        }
        return null;
    }

    private function resolveVariant(Product $product, $variantId)
    {
        $hasVariants = ProductVariant::query()
            ->where('product_id', $product->id)
            ->where('status', true)
            ->exists();
        if (!$hasVariants) {
            return null;
        }
        if (!$variantId) {
            return false;
        }
        return ProductVariant::query()
            ->where('product_id', $product->id)
            ->where('id', (int) $variantId)
            ->where('status', true)
            ->first();
    }

    private function calculateLineTax(float $lineSubtotal): array
    {
        if ($lineSubtotal <= 0) {
            return ['line_tax' => 0.0, 'applied' => []];
        }

        $lineTax = 0.0;
        $applied = [];
        $taxes = Tax::query()
            ->where('status', 1)
            ->where('module_type', 'ecommerce')
            ->get();

        foreach ($taxes as $tax) {
            $amount = 0.0;
            if ($tax->type === 'percent') {
                $amount = ($lineSubtotal * (float) $tax->value) / 100;
            } else {
                $amount = (float) $tax->value;
            }
            $amount = round($amount, 2);
            $lineTax += $amount;
            $applied[] = [
                'title' => (string) $tax->title,
                'type' => (string) $tax->type,
                'value' => (float) $tax->value,
                'amount' => $amount,
            ];
        }

        return ['line_tax' => round($lineTax, 2), 'applied' => $applied];
    }

    private function getCurrencyCode(): string
    {
        $sitesetup = Setting::query()->where('type', 'site-setup')->where('key', 'site-setup')->first();
        $setupData = $sitesetup ? json_decode((string) $sitesetup->value, true) : [];
        $countryId = $setupData['default_currency'] ?? null;
        $country = $countryId ? Country::query()->find($countryId) : null;

        return (string) ($country->currency_code ?? 'USD');
    }

    private function markOrderPaid(\App\Models\ProductOrder $order, string $paymentType, string $txnId): void
    {
        if (Schema::hasColumn('product_orders', 'payment_type')) {
            $order->payment_type = $paymentType;
        }
        if (Schema::hasColumn('product_orders', 'payment_status')) {
            $order->payment_status = 'paid';
        }
        if (Schema::hasColumn('product_orders', 'status')) {
            $order->status = 'confirmed';
        }
        if (Schema::hasColumn('product_orders', 'txn_id')) {
            $order->txn_id = $txnId;
        }
        $order->save();
    }

    private function generatePhonePeChecksum(string $payload, string $apiPath, string $saltKey, string $saltIndex = '1'): string
    {
        $hash = hash('sha256', $payload . $apiPath . $saltKey);

        return $hash . '###' . $saltIndex;
    }

    private function getPhonePeBaseUrl(int $isTest): string
    {
        return $isTest === 0
            ? 'https://api.phonepe.com/apis/pg'
            : 'https://api-preprod.phonepe.com/apis/pg-sandbox';
    }

    private function getGatewayConfig(PaymentGateway $gateway): array
    {
        $payload = $gateway->is_test == 1 ? $gateway->value : $gateway->live_value;

        return json_decode((string) $payload, true) ?? [];
    }

    private function getAllowedPaymentMethods(): array
    {
        $gatewayTypes = PaymentGateway::query()
            ->where('status', 1)
            ->where('type', '!=', 'razorPayX')
            ->pluck('type')
            ->filter()
            ->map(fn ($type) => (string) $type)
            ->values()
            ->all();

        return array_values(array_unique(array_merge(['wallet'], $gatewayTypes)));
    }

    private function wantsListingCartJson(Request $request): bool
    {
        return $request->ajax();
    }

    /**
     * HTML fragment for product listing cards (inside .product-card-cart).
     */
    private function renderProductCardCartInnerHtml(int $productId): string
    {
        if (! Schema::hasTable('product_cart_items')) {
            return '';
        }
        $product = Product::query()
            ->where('service_type', 'ecommerce')
            ->where('status', 1)
            ->where('service_request_status', 'approve')
            ->with(['variants', 'providers'])
            ->find($productId);
        if (! $product) {
            return '';
        }
        $cartRow = null;
        if (auth()->check() && auth()->user()->user_type === 'user') {
            $cartRow = ProductCartItem::query()
                ->where('user_id', auth()->id())
                ->where('product_id', $productId)
                ->first();
        }

        return view('product.partials.card-cart-inner', ['data' => $product, 'cartRow' => $cartRow])->render();
    }
}
