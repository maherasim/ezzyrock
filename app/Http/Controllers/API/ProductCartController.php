<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCartItem;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

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

        $items = ProductCartItem::query()
            ->where('user_id', $user->id)
            ->with(['product.providers', 'product.category', 'variant.option.attribute'])
            ->get()
            ->map(function ($item) {
                $product = $item->product;
                $variant = $item->variant;
                $unitPrice = (float) ($variant?->price ?? $product?->price ?? 0);
                if ($product && (float) $product->discount > 0) {
                    $unitPrice = $unitPrice - ($unitPrice * (float) $product->discount / 100);
                }

                return [
                    'cart_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => round($unitPrice, 2),
                    'unit_price_format' => getPriceFormat($unitPrice),
                    'line_total' => round($unitPrice * (int) $item->quantity, 2),
                    'line_total_format' => getPriceFormat($unitPrice * (int) $item->quantity),
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
                        'price' => $variant->price,
                        'price_format' => getPriceFormat($variant->price),
                        'stock' => $variant->stock,
                        'max_purchase_qty' => $variant->max_purchase_qty,
                    ] : null,
                ];
            })->values();

        return response()->json([
            'status' => true,
            'data' => $items,
            'cart_count' => ProductCartItem::totalEcommerceQuantityForUser((int) $user->id),
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
}
