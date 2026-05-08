<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ProductOrder;
use Illuminate\Http\Request;

class ProductOrderController extends Controller
{
    public function list(Request $request)
    {
        $user = auth()->user();
        if (!$user || $user->user_type !== 'user') {
            return response()->json(['status' => false, 'message' => 'Only customer accounts can view product orders.'], 403);
        }

        $orders = ProductOrder::query()
            ->where('user_id', $user->id)
            ->withCount('items');

        if ($request->filled('status')) {
            $orders->whereIn('status', explode(',', (string) $request->status));
        }

        if ($request->filled('payment_status')) {
            $orders->whereIn('payment_status', explode(',', (string) $request->payment_status));
        }

        if ($request->filled('search')) {
            $search = (string) $request->search;
            $orders->where(function ($query) use ($search) {
                $query->where('id', 'LIKE', "%{$search}%")
                    ->orWhere('order_number', 'LIKE', "%{$search}%");
            });
        }

        $perPage = config('constant.PER_PAGE_LIMIT', 15);
        if ($request->filled('per_page')) {
            if ($request->per_page === 'all') {
                $perPage = max(1, $orders->count());
            } elseif (is_numeric($request->per_page)) {
                $perPage = (int) $request->per_page;
            }
        }

        $orderBy = strtolower((string) $request->get('orderby', 'desc')) === 'asc' ? 'asc' : 'desc';
        $orders = $orders->orderBy('created_at', $orderBy)->paginate($perPage);

        return response()->json([
            'status' => true,
            'pagination' => [
                'total_items' => $orders->total(),
                'per_page' => $orders->perPage(),
                'currentPage' => $orders->currentPage(),
                'totalPages' => $orders->lastPage(),
                'from' => $orders->firstItem(),
                'to' => $orders->lastItem(),
                'next_page' => $orders->nextPageUrl(),
                'previous_page' => $orders->previousPageUrl(),
            ],
            'data' => $orders->getCollection()
                ->map(fn (ProductOrder $order) => $this->serializeOrderListItem($order))
                ->values(),
        ]);
    }

    public function detail(Request $request, $id = null)
    {
        $user = auth()->user();
        if (!$user || $user->user_type !== 'user') {
            return response()->json(['status' => false, 'message' => 'Only customer accounts can view product orders.'], 403);
        }

        $orderId = $id ?: $request->get('order_id', $request->get('id'));
        $order = ProductOrder::query()
            ->where('user_id', $user->id)
            ->with(['items.product', 'items.variant.option.attribute'])
            ->find($orderId);

        if (!$order) {
            return response()->json(['status' => false, 'message' => __('messages.record_not_found')], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $this->serializeOrderDetail($order),
        ]);
    }

    private function serializeOrderListItem(ProductOrder $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'order_date' => optional($order->created_at)->format('Y-m-d H:i'),
            'created_at' => optional($order->created_at)->toISOString(),
            'status' => $order->status,
            'payment_type' => $order->payment_type,
            'payment_status' => $order->payment_status,
            'items_count' => (int) ($order->items_count ?? 0),
            'subtotal' => (float) $order->subtotal,
            'subtotal_format' => getPriceFormat($order->subtotal),
            'tax_total' => (float) ($order->tax_total ?? 0),
            'tax_total_format' => getPriceFormat($order->tax_total ?? 0),
            'total' => (float) $order->total,
            'total_format' => getPriceFormat($order->total),
            'detail_url' => url('/api/my-product-orders/' . $order->id),
        ];
    }

    private function serializeOrderDetail(ProductOrder $order): array
    {
        $notes = $this->decodeJson($order->notes);

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'order_date' => optional($order->created_at)->format('Y-m-d H:i'),
            'created_at' => optional($order->created_at)->toISOString(),
            'status' => $order->status,
            'payment_type' => $order->payment_type,
            'payment_status' => $order->payment_status,
            'txn_id' => $order->txn_id,
            'subtotal' => (float) $order->subtotal,
            'subtotal_format' => getPriceFormat($order->subtotal),
            'tax_total' => (float) ($order->tax_total ?? 0),
            'tax_total_format' => getPriceFormat($order->tax_total ?? 0),
            'total' => (float) $order->total,
            'total_format' => getPriceFormat($order->total),
            'tax_detail' => $order->tax_detail,
            'shipping' => $notes['shipping'] ?? null,
            'items_count' => $order->items->count(),
            'items' => $order->items
                ->map(fn ($item) => $this->serializeOrderItem($item))
                ->values(),
        ];
    }

    private function serializeOrderItem($item): array
    {
        $product = $item->product;

        return [
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
            'product' => $product ? [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'image' => getSingleMedia($product, 'product_attachment', null),
                'detail_url' => url('/product-detail/' . $product->id),
            ] : null,
        ];
    }

    private function decodeJson($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) ($value ?? ''), true);

        return is_array($decoded) ? $decoded : [];
    }
}
