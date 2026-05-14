<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\ProductCartItem;
use App\Models\ProductOrder;
use App\Models\ProductOrderItem;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Tax;
use App\Models\Wallet;
use App\Models\WalletHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Razorpay\Api\Api as RazorpayApi;

class ProductCartController extends Controller
{
    public function list(Request $request)
    {
        $user = auth()->user();
        if (!$user || $user->user_type !== 'user') {
            return response()->json(['status' => false, 'message' => 'Only customer accounts can use cart APIs.'], 403);
        }

        if (!Schema::hasTable('product_cart_items')) {
            return response()->json(['status' => false, 'message' => __('messages.cart_unavailable')], 422);
        }

        $cartRows = ProductCartItem::query()
            ->where('user_id', $user->id)
            ->with(['product.providers', 'product.category', 'variant.option.attribute'])
            ->get();

        $items = $cartRows->map(fn ($item) => $this->serializeCartItem($item))->values();
        $summary = $this->buildCartSummary($cartRows);

        return response()->json([
            'status' => true,
            'data' => $items,
            'summary' => $summary,
            'cart_count' => ProductCartItem::totalEcommerceQuantityForUser((int) $user->id),
            'cart_entries' => $cartRows->count(),
            'checkout' => [
                'shipping_required_fields' => [
                    'shipping_name',
                    'shipping_address',
                    'shipping_state',
                    'shipping_city',
                    'shipping_pincode',
                    'shipping_country',
                ],
                'shipping_country_default' => 'India',
                'states' => config('indian_states', []),
                'payment_methods' => $this->paymentMethodsForApi((int) $user->id),
            ],
        ]);
    }

    public function checkout(Request $request)
    {
        $user = auth()->user();
        if (!$user || $user->user_type !== 'user') {
            return response()->json(['status' => false, 'message' => 'Only customer accounts can use cart APIs.'], 403);
        }

        if (!Schema::hasTable('product_cart_items')) {
            return response()->json(['status' => false, 'message' => __('messages.cart_unavailable')], 422);
        }

        $allowedPaymentMethods = $this->getAllowedPaymentMethods();
        $indianStates = config('indian_states', []);
        $validated = $request->validate([
            'payment_method' => 'required|string|in:' . implode(',', $allowedPaymentMethods),
            'shipping_name' => 'required|string|max:120',
            'shipping_address' => 'required|string|max:255',
            'shipping_city' => 'required|string|max:120',
            'shipping_state' => ['required', 'string', 'max:120', Rule::in($indianStates)],
            'shipping_pincode' => 'required|string|max:20',
            'shipping_country' => 'nullable|string|max:80',
        ]);

        $cartRows = ProductCartItem::query()
            ->where('user_id', $user->id)
            ->with(['product', 'variant.option.attribute'])
            ->get();

        if ($cartRows->isEmpty()) {
            return response()->json(['status' => false, 'message' => __('messages.cart_empty')], 422);
        }

        $shippingData = [
            'name' => (string) $validated['shipping_name'],
            'address' => (string) $validated['shipping_address'],
            'city' => (string) $validated['shipping_city'],
            'state' => (string) $validated['shipping_state'],
            'pincode' => (string) $validated['shipping_pincode'],
            'country' => (string) ($validated['shipping_country'] ?? 'India'),
        ];

        try {
            return DB::transaction(function () use ($cartRows, $user, $validated, $shippingData) {
            $prepared = $this->prepareOrderLines($cartRows);
            if (!$prepared['status']) {
                if (($prepared['clear_cart'] ?? false) === true) {
                    ProductCartItem::query()->where('user_id', $user->id)->delete();
                }

                return response()->json(['status' => false, 'message' => $prepared['message']], 422);
            }

            $paymentMethod = (string) $validated['payment_method'];
            $order = $this->createProductOrder(
                (int) $user->id,
                $paymentMethod,
                $shippingData,
                $prepared
            );

            foreach ($prepared['lines'] as $line) {
                $stockError = $this->decrementLineStock($line);
                if ($stockError !== null) {
                    throw new \RuntimeException($stockError);
                }

                ProductOrderItem::query()->create([
                    'product_order_id' => $order->id,
                    'product_id' => $line['product']->id,
                    'product_variant_id' => $line['variant']?->id,
                    'product_name' => $line['product']->name,
                    'variant_label' => $line['variant_label'],
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ]);
            }

            if ($paymentMethod === 'wallet') {
                $wallet = Wallet::query()->where('user_id', $user->id)->first();
                $walletAmount = $wallet ? (float) $wallet->amount : 0.0;
                if (!$wallet || $walletAmount < (float) $prepared['grand_total']) {
                    throw new \RuntimeException(__('messages.wallent_balance_error'));
                }

                $wallet->amount = round($walletAmount - (float) $prepared['grand_total'], 2);
                $wallet->save();
                $this->markOrderPaid($order, 'wallet', 'WALLET-' . $order->id);
                WalletHistory::query()->create([
                    'datetime' => now(),
                    'user_id' => $user->id,
                    'activity_type' => 'paid_with_wallet',
                    'activity_message' => __('messages.paid_with_wallet', ['Value' => $order->order_number]),
                    'activity_data' => json_encode([
                        'order_number' => $order->order_number,
                        'order_type' => 'product',
                        'subtotal' => $prepared['subtotal'],
                        'tax_total' => $prepared['tax_total'],
                        'total' => $prepared['grand_total'],
                    ]),
                ]);

                ProductCartItem::query()->where('user_id', $user->id)->delete();

                return response()->json([
                    'status' => true,
                    'message' => __('messages.order_placed'),
                    'data' => $this->serializeOrder($order->fresh('items')),
                    'payment_action' => ['type' => 'none'],
                    'cart_count' => 0,
                ]);
            }

            if ($paymentMethod === 'cash') {
                $this->setOrderStatus($order, 'pending', 'pending');
                ProductCartItem::query()->where('user_id', $user->id)->delete();

                return response()->json([
                    'status' => true,
                    'message' => __('messages.payment_message', ['status' => __('messages.pending')]),
                    'data' => $this->serializeOrder($order->fresh('items')),
                    'payment_action' => ['type' => 'cash_on_delivery'],
                    'cart_count' => 0,
                ]);
            }

            if ($paymentMethod === 'razorPay') {
                $gateway = PaymentGateway::query()->where('type', 'razorPay')->where('status', 1)->first();
                if (!$gateway) {
                    throw new \RuntimeException(__('messages.something_wrong'));
                }
                $gatewayData = $this->getGatewayConfig($gateway);
                $razorKey = $gatewayData['razor_key'] ?? null;
                $razorSecret = $gatewayData['razor_secret'] ?? null;
                if (empty($razorKey) || empty($razorSecret)) {
                    throw new \RuntimeException(__('messages.something_wrong'));
                }

                $api = new RazorpayApi($razorKey, $razorSecret);
                $razorOrder = $api->order->create([
                    'receipt' => (string) $order->order_number,
                    'amount' => (int) round((float) $prepared['grand_total'] * 100),
                    'currency' => $this->getCurrencyCode(),
                ]);

                $this->setOrderMeta($order, [
                    'gateway' => 'razorPay',
                    'razorpay_order_id' => (string) $razorOrder['id'],
                    'razorpay_is_test' => (int) $gateway->is_test,
                    'razorpay_key' => (string) $razorKey,
                ]);
                ProductCartItem::query()->where('user_id', $user->id)->delete();

                return response()->json([
                    'status' => true,
                    'message' => 'Order created. Complete Razorpay payment.',
                    'data' => $this->serializeOrder($order->fresh('items')),
                    'payment_action' => [
                        'type' => 'razorpay',
                        'razorpay_key' => $razorKey,
                        'razorpay_order_id' => (string) $razorOrder['id'],
                        'amount' => (int) round((float) $prepared['grand_total'] * 100),
                        'currency' => $this->getCurrencyCode(),
                        'verify_endpoint' => url('/api/product-razorpay-verify'),
                    ],
                    'cart_count' => 0,
                ]);
            }

            ProductCartItem::query()->where('user_id', $user->id)->delete();

                return response()->json([
                    'status' => true,
                    'message' => 'Order created. Complete payment in app and call product-payment-complete.',
                    'data' => $this->serializeOrder($order->fresh('items')),
                    'payment_action' => [
                        'type' => 'client_gateway',
                        'gateway' => $paymentMethod,
                        'amount' => (float) $prepared['grand_total'],
                        'amount_format' => getPriceFormat($prepared['grand_total']),
                        'currency' => $this->getCurrencyCode(),
                        'complete_endpoint' => url('/api/product-payment-complete'),
                    ],
                    'cart_count' => 0,
                ]);
            });
        } catch (\RuntimeException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function verifyRazorpay(Request $request)
    {
        $user = auth()->user();
        if (!$user || $user->user_type !== 'user') {
            return response()->json(['status' => false, 'message' => 'Only customer accounts can use cart APIs.'], 403);
        }

        $validated = $request->validate([
            'order_id' => 'required|integer',
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $order = ProductOrder::query()->where('user_id', $user->id)->find($validated['order_id']);
        if (!$order) {
            return response()->json(['status' => false, 'message' => __('messages.record_not_found')], 404);
        }

        $gateway = PaymentGateway::query()->where('type', 'razorPay')->where('status', 1)->first();
        if (!$gateway) {
            return response()->json(['status' => false, 'message' => __('messages.something_wrong')], 422);
        }
        $meta = json_decode((string) ($order->other_transaction_detail ?? '{}'), true);
        $gatewayData = $this->getGatewayConfig($gateway, $meta['razorpay_is_test'] ?? null);
        $razorSecret = $gatewayData['razor_secret'] ?? null;
        if (empty($razorSecret)) {
            return response()->json(['status' => false, 'message' => __('messages.something_wrong')], 422);
        }
        if (($meta['razorpay_order_id'] ?? '') !== (string) $validated['razorpay_order_id']) {
            return response()->json(['status' => false, 'message' => __('messages.something_wrong')], 422);
        }

        $signatureData = $validated['razorpay_order_id'] . '|' . $validated['razorpay_payment_id'];
        $expectedSignature = hash_hmac('sha256', $signatureData, (string) $razorSecret);
        if (!hash_equals($expectedSignature, (string) $validated['razorpay_signature'])) {
            return response()->json(['status' => false, 'message' => __('messages.payment_message', ['status' => __('messages.failed')])], 422);
        }

        $this->markOrderPaid($order, 'razorPay', (string) $validated['razorpay_payment_id']);

        return response()->json([
            'status' => true,
            'message' => __('messages.order_placed'),
            'data' => $this->serializeOrder($order->fresh('items')),
        ]);
    }

    public function completePayment(Request $request)
    {
        $user = auth()->user();
        if (!$user || $user->user_type !== 'user') {
            return response()->json(['status' => false, 'message' => 'Only customer accounts can use cart APIs.'], 403);
        }

        $allowed = array_filter($this->getAllowedPaymentMethods(), fn ($type) => !in_array($type, ['wallet', 'cash', 'razorPay'], true));
        $validated = $request->validate([
            'order_id' => 'required|integer',
            'gateway' => 'required|string|in:' . implode(',', $allowed),
            'status' => 'required|string',
            'transaction_id' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
        ]);

        $order = ProductOrder::query()->where('user_id', $user->id)->find($validated['order_id']);
        if (!$order) {
            return response()->json(['status' => false, 'message' => __('messages.record_not_found')], 404);
        }

        $status = strtolower((string) $validated['status']);
        if (!in_array($status, ['success', 'succeeded', 'completed', 'paid'], true)) {
            $this->setOrderStatus($order, (string) ($order->status ?? 'pending'), 'failed');

            return response()->json([
                'status' => false,
                'message' => __('messages.payment_message', ['status' => __('messages.failed')]),
                'data' => $this->serializeOrder($order->fresh('items')),
            ], 422);
        }

        $txnId = (string) (($validated['transaction_id'] ?? null) ?: ($validated['reference'] ?? null) ?: ('TXN-' . time()));
        $this->markOrderPaid($order, (string) $validated['gateway'], $txnId);

        return response()->json([
            'status' => true,
            'message' => __('messages.order_placed'),
            'data' => $this->serializeOrder($order->fresh('items')),
        ]);
    }

    public function add(Request $request)
    {
        $user = auth()->user();
        if (!$user || $user->user_type !== 'user') {
            return response()->json(['status' => false, 'message' => 'Only customer accounts can use cart APIs.'], 403);
        }

        if (!Schema::hasTable('product_cart_items')) {
            return response()->json(['status' => false, 'message' => __('messages.cart_unavailable')], 422);
        }

        $validated = $request->validate([
            'product_id' => 'required|integer',
            'product_variant_id' => 'nullable|integer',
            'quantity' => 'nullable|integer|min:1|max:99',
        ]);

        $product = $this->purchasableProduct((int) $validated['product_id']);
        if (!$product) {
            return response()->json(['status' => false, 'message' => __('messages.product_not_found')], 422);
        }

        $variant = $this->resolveVariant($product, $validated['product_variant_id'] ?? null);
        if ($variant === false) {
            return response()->json(['status' => false, 'message' => 'Please select a valid product option.'], 422);
        }

        $qty = (int) ($validated['quantity'] ?? 1);
        $row = ProductCartItem::query()->firstOrNew([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
        ]);
        $newQuantity = ($row->exists ? (int) $row->quantity : 0) + $qty;

        $limitError = $this->validateCartQuantityLimit($product, $newQuantity, $variant);
        if ($limitError !== null) {
            return response()->json(['status' => false, 'message' => $limitError], 422);
        }

        $row->quantity = $newQuantity;
        $row->save();

        return response()->json([
            'status' => true,
            'message' => __('messages.added_to_cart'),
            'cart_item_id' => $row->id,
            'cart_count' => ProductCartItem::totalEcommerceQuantityForUser((int) $user->id),
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        if (!$user || $user->user_type !== 'user') {
            return response()->json(['status' => false, 'message' => 'Only customer accounts can use cart APIs.'], 403);
        }

        $validated = $request->validate([
            'cart_item_id' => 'required|integer',
            'quantity' => 'required|integer|min:1|max:99',
        ]);

        $item = ProductCartItem::query()->where('user_id', $user->id)->where('id', $validated['cart_item_id'])->first();
        if (!$item) {
            return response()->json(['status' => false, 'message' => __('messages.record_not_found')], 404);
        }

        $limitError = $this->validateCartQuantityLimit($item->product, (int) $validated['quantity'], $item->variant);
        if ($limitError !== null) {
            return response()->json(['status' => false, 'message' => $limitError], 422);
        }

        $item->quantity = (int) $validated['quantity'];
        $item->save();

        return response()->json([
            'status' => true,
            'message' => __('messages.cart_updated'),
            'cart_count' => ProductCartItem::totalEcommerceQuantityForUser((int) $user->id),
        ]);
    }

    public function remove(Request $request)
    {
        $user = auth()->user();
        if (!$user || $user->user_type !== 'user') {
            return response()->json(['status' => false, 'message' => 'Only customer accounts can use cart APIs.'], 403);
        }

        $validated = $request->validate([
            'cart_item_id' => 'required|integer',
        ]);

        $item = ProductCartItem::query()->where('user_id', $user->id)->where('id', $validated['cart_item_id'])->first();
        if (!$item) {
            return response()->json(['status' => false, 'message' => __('messages.record_not_found')], 404);
        }

        $item->delete();

        return response()->json([
            'status' => true,
            'message' => __('messages.removed_from_cart'),
            'cart_count' => ProductCartItem::totalEcommerceQuantityForUser((int) $user->id),
        ]);
    }

    private function purchasableProduct(int $id): ?Product
    {
        $q = Product::query()
            ->with(['providers'])
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

    private function validateCartQuantityLimit(?Product $product, int $quantity, ?ProductVariant $variant = null): ?string
    {
        if (!$product) {
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

    private function serializeCartItem(ProductCartItem $item): array
    {
        $product = $item->product;
        $variant = $item->variant;
        $unitPrice = $this->unitPrice($product, $variant);
        $lineTotal = round($unitPrice * (int) $item->quantity, 2);
        $maxAllowed = 99;
        if ($variant) {
            $maxAllowed = min($maxAllowed, (int) $variant->stock);
            if (!empty($variant->max_purchase_qty)) {
                $maxAllowed = min($maxAllowed, (int) $variant->max_purchase_qty);
            }
        } elseif ($product) {
            if (Schema::hasColumn('products', 'total_stock')) {
                $maxAllowed = min($maxAllowed, (int) $product->total_stock);
            }
            if (Schema::hasColumn('products', 'max_purchase_qty') && !empty($product->max_purchase_qty)) {
                $maxAllowed = min($maxAllowed, (int) $product->max_purchase_qty);
            }
        }

        return [
            'cart_item_id' => $item->id,
            'product_id' => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
            'quantity' => (int) $item->quantity,
            'max_allowed_quantity' => max(0, $maxAllowed),
            'unit_price' => round($unitPrice, 2),
            'unit_price_format' => getPriceFormat($unitPrice),
            'line_total' => $lineTotal,
            'line_total_format' => getPriceFormat($lineTotal),
            'product' => $product ? [
                'id' => $product->id,
                'name' => $product->name,
                'provider_name' => optional($product->providers)->display_name,
                'provider_image' => optional($product->providers)->login_type != null
                    ? optional($product->providers)->social_image
                    : getSingleMedia(optional($product->providers), 'profile_image', null),
                'product_image' => getSingleMedia($product, 'product_attachment', null),
            ] : null,
            'variant' => $variant ? [
                'id' => $variant->id,
                'option_value' => optional($variant->option)->value,
                'attribute_name' => optional(optional($variant->option)->attribute)->name,
                'label' => trim((optional(optional($variant->option)->attribute)->name ? optional(optional($variant->option)->attribute)->name . ': ' : '') . (optional($variant->option)->value ?? '')),
                'price' => $variant->price,
                'price_format' => getPriceFormat($variant->price),
                'stock' => $variant->stock,
                'max_purchase_qty' => $variant->max_purchase_qty,
            ] : null,
        ];
    }

    private function unitPrice(?Product $product, ?ProductVariant $variant = null): float
    {
        $unitPrice = (float) ($variant?->price ?? $product?->price ?? 0);
        if ($product && (float) $product->discount > 0) {
            $unitPrice = $unitPrice - ($unitPrice * (float) $product->discount / 100);
        }

        return round($unitPrice, 2);
    }

    private function buildCartSummary($items): array
    {
        $subtotal = 0.0;
        $taxTotal = 0.0;
        $taxDetail = [];

        foreach ($items as $item) {
            $product = $item->product;
            if (!$product) {
                continue;
            }

            $lineTotal = round($this->unitPrice($product, $item->variant) * (int) $item->quantity, 2);
            $lineTaxData = $this->calculateLineTax($lineTotal);
            $subtotal += $lineTotal;
            $taxTotal += $lineTaxData['line_tax'];

            foreach ($lineTaxData['applied'] as $taxRow) {
                $key = $taxRow['title'] . '|' . $taxRow['type'] . '|' . $taxRow['value'];
                if (!isset($taxDetail[$key])) {
                    $taxDetail[$key] = $taxRow;
                } else {
                    $taxDetail[$key]['amount'] = round((float) $taxDetail[$key]['amount'] + (float) $taxRow['amount'], 2);
                }
            }
        }

        $subtotal = round($subtotal, 2);
        $taxTotal = round($taxTotal, 2);
        $grandTotal = round($subtotal + $taxTotal, 2);

        return [
            'subtotal' => $subtotal,
            'subtotal_format' => getPriceFormat($subtotal),
            'tax_total' => $taxTotal,
            'tax_total_format' => getPriceFormat($taxTotal),
            'grand_total' => $grandTotal,
            'grand_total_format' => getPriceFormat($grandTotal),
            'tax_detail' => array_values($taxDetail),
        ];
    }

    private function prepareOrderLines($items): array
    {
        $subtotal = 0.0;
        $taxTotal = 0.0;
        $orderLines = [];
        $taxDetail = [];

        foreach ($items as $item) {
            $product = $item->product;
            if (!$product || !$this->purchasableProduct((int) $product->id)) {
                continue;
            }

            $stockCheckMessage = $this->validateCartQuantityLimit($product, (int) $item->quantity, $item->variant);
            if ($stockCheckMessage !== null) {
                return ['status' => false, 'message' => $stockCheckMessage];
            }

            $lineTotal = round($this->unitPrice($product, $item->variant) * (int) $item->quantity, 2);
            $lineTaxData = $this->calculateLineTax($lineTotal);
            $subtotal += $lineTotal;
            $taxTotal += $lineTaxData['line_tax'];

            foreach ($lineTaxData['applied'] as $taxRow) {
                $key = $taxRow['title'] . '|' . $taxRow['type'] . '|' . $taxRow['value'];
                if (!isset($taxDetail[$key])) {
                    $taxDetail[$key] = $taxRow;
                } else {
                    $taxDetail[$key]['amount'] = round((float) $taxDetail[$key]['amount'] + (float) $taxRow['amount'], 2);
                }
            }

            $orderLines[] = [
                'product' => $product,
                'variant' => $item->variant,
                'variant_label' => $item->variant?->option?->value,
                'quantity' => (int) $item->quantity,
                'unit_price' => $this->unitPrice($product, $item->variant),
                'line_total' => $lineTotal,
            ];
        }

        if ($orderLines === []) {
            return ['status' => false, 'message' => __('messages.cart_empty'), 'clear_cart' => true];
        }

        $subtotal = round($subtotal, 2);
        $taxTotal = round($taxTotal, 2);

        return [
            'status' => true,
            'lines' => $orderLines,
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'grand_total' => round($subtotal + $taxTotal, 2),
            'tax_detail' => array_values($taxDetail),
        ];
    }

    private function createProductOrder(int $userId, string $paymentMethod, array $shippingData, array $prepared): ProductOrder
    {
        $payload = [
            'order_number' => 'PO-' . strtoupper(bin2hex(random_bytes(4))),
            'user_id' => $userId,
            'status' => 'pending',
            'subtotal' => $prepared['subtotal'],
            'total' => $prepared['grand_total'],
            'notes' => json_encode(['shipping' => $shippingData]),
        ];
        if (Schema::hasColumn('product_orders', 'tax_total')) {
            $payload['tax_total'] = $prepared['tax_total'];
        }
        if (Schema::hasColumn('product_orders', 'payment_type')) {
            $payload['payment_type'] = $paymentMethod;
        }
        if (Schema::hasColumn('product_orders', 'payment_status')) {
            $payload['payment_status'] = 'failed';
        }
        if (Schema::hasColumn('product_orders', 'tax_detail')) {
            $payload['tax_detail'] = $prepared['tax_detail'];
        }

        return ProductOrder::query()->create($payload);
    }

    private function decrementLineStock(array $line): ?string
    {
        $variant = $line['variant'] ?? null;
        if ($variant && Schema::hasColumn('product_variants', 'stock')) {
            $affectedVariant = ProductVariant::query()
                ->where('id', $variant->id)
                ->where('stock', '>=', (int) $line['quantity'])
                ->decrement('stock', (int) $line['quantity']);
            if (!$affectedVariant) {
                return 'Product option stock changed. Please update cart.';
            }
        }

        $product = $line['product'];
        if (Schema::hasColumn('products', 'total_stock')) {
            $affected = Product::query()
                ->where('id', $product->id)
                ->where('total_stock', '>=', (int) $line['quantity'])
                ->decrement('total_stock', (int) $line['quantity']);
            if (!$affected) {
                return __('messages.product_not_found');
            }
        }

        return null;
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
            $amount = $tax->type === 'percent'
                ? ($lineSubtotal * (float) $tax->value) / 100
                : (float) $tax->value;
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

    private function getGatewayConfig(PaymentGateway $gateway, ?int $isTest = null): array
    {
        $useTest = $isTest ?? (int) $gateway->is_test;
        $payload = $useTest === 1 ? $gateway->value : $gateway->live_value;

        return json_decode((string) $payload, true) ?? [];
    }

    private function paymentMethodsForApi(int $userId): array
    {
        $walletAmount = (float) (Wallet::query()->where('user_id', $userId)->value('amount') ?? 0);
        $methods = [[
            'type' => 'wallet',
            'title' => __('messages.wallet'),
            'is_online' => false,
            'balance' => $walletAmount,
            'balance_format' => getPriceFormat($walletAmount),
        ]];

        $gateways = PaymentGateway::query()
            ->where('status', 1)
            ->where('type', '!=', 'razorPayX')
            ->get();

        foreach ($gateways as $gateway) {
            $methods[] = [
                'type' => (string) $gateway->type,
                'title' => (string) ($gateway->title ?: ucfirst((string) $gateway->type)),
                'is_online' => !in_array($gateway->type, ['cash'], true),
                'is_test' => (int) $gateway->is_test,
            ];
        }

        return $methods;
    }

    private function getCurrencyCode(): string
    {
        $sitesetup = Setting::query()->where('type', 'site-setup')->where('key', 'site-setup')->first();
        $setupData = $sitesetup ? json_decode((string) $sitesetup->value, true) : [];
        $countryId = $setupData['default_currency'] ?? null;
        $country = $countryId ? Country::query()->find($countryId) : null;

        return (string) ($country->currency_code ?? 'USD');
    }

    private function setOrderStatus(ProductOrder $order, string $status, string $paymentStatus): void
    {
        if (Schema::hasColumn('product_orders', 'status')) {
            $order->status = $status;
        }
        if (Schema::hasColumn('product_orders', 'payment_status')) {
            $order->payment_status = $paymentStatus;
        }
        $order->save();
    }

    private function setOrderMeta(ProductOrder $order, array $meta): void
    {
        if (Schema::hasColumn('product_orders', 'other_transaction_detail')) {
            $order->other_transaction_detail = json_encode($meta);
            $order->save();
        }
    }

    private function markOrderPaid(ProductOrder $order, string $paymentType, string $txnId): void
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

    private function serializeOrder(ProductOrder $order): array
    {
        $shipping = json_decode((string) ($order->notes ?? '{}'), true);

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_type' => $order->payment_type ?? null,
            'payment_status' => $order->payment_status ?? null,
            'txn_id' => $order->txn_id ?? null,
            'subtotal' => (float) $order->subtotal,
            'subtotal_format' => getPriceFormat($order->subtotal),
            'tax_total' => (float) ($order->tax_total ?? 0),
            'tax_total_format' => getPriceFormat($order->tax_total ?? 0),
            'total' => (float) $order->total,
            'total_format' => getPriceFormat($order->total),
            'shipping' => $shipping['shipping'] ?? null,
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'product_name' => $item->product_name,
                'variant_label' => $item->variant_label,
                'unit_price' => (float) $item->unit_price,
                'unit_price_format' => getPriceFormat($item->unit_price),
                'quantity' => (int) $item->quantity,
                'line_total' => (float) $item->line_total,
                'line_total_format' => getPriceFormat($item->line_total),
            ])->values(),
        ];
    }
}
