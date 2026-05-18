<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ProductOrderActivity;
use App\Models\ProductOrderAssignment;
use App\Models\ProductOrder;
use App\Models\ProductOrderLiveLocation;
use App\Models\ProductOrderProof;
use App\Models\User;
use App\Notifications\CommonNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductOrderController extends Controller
{
    private array $providerStatuses = ['pending', 'accept', 'assigned', 'on_going', 'delivered', 'completed', 'cancelled', 'rejected', 'confirmed'];

    public function list(Request $request)
    {
        $user = auth()->user();
        if (!$user || $user->user_type !== 'user') {
            return response()->json(['status' => false, 'message' => 'Only customer accounts can view product orders.'], 403);
        }

        $orders = ProductOrder::query()
            ->where('user_id', $user->id)
            ->with(['items.product'])
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

        $orderBy = 'desc';
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
        if ($user && in_array($user->user_type, ['provider', 'handyman'], true)) {
            return $this->providerDetail($request, $id);
        }

        if (!$user || $user->user_type !== 'user') {
            return response()->json(['status' => false, 'message' => 'Only customer accounts can view product orders.'], 403);
        }

        $orderId = $id ?: $request->get('order_id', $request->get('id'));
        $order = ProductOrder::query()
            ->where('user_id', $user->id)
            ->with([
                'items.product.providers.getServiceRating',
                'items.product.shops',
                'items.variant.option.attribute',
                'assignments.handyman.handymantype',
                'assignments.handyman.handymanRating',
            ])
            ->find($orderId);

        if (!$order) {
            return response()->json(['status' => false, 'message' => __('messages.record_not_found')], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $this->serializeOrderDetail($order),
        ]);
    }

    public function providerList(Request $request)
    {
        $user = auth()->user();
        if (!$user || !in_array($user->user_type, ['provider', 'handyman', 'user'], true)) {
            return response()->json(['status' => false, 'message' => __('messages.unauthorized')], 403);
        }

        $orders = ProductOrder::query()
            ->with([
                'user',
                'items.product.providers',
                'items.product.shops',
                'items.variant.option.attribute',
                'assignments.handyman.handymantype',
            ])
            ->withCount('items');

        if ($user->user_type === 'provider') {
            $orders->whereHas('items.product', fn ($q) => $q->where('provider_id', $user->id));
        } elseif ($user->user_type === 'handyman') {
            $orders->whereHas('assignments', fn ($q) => $q->where('handyman_id', $user->id));
        } else {
            $orders->where('user_id', $user->id);
        }

        if ($request->filled('status')) {
            $orders->whereIn('status', $this->csv($request->status));
        }
        if ($request->filled('payment_status')) {
            $orders->whereIn('payment_status', $this->csv($request->payment_status));
        }
        if ($request->filled('payment_type')) {
            $orders->whereIn('payment_type', $this->csv($request->payment_type));
        }
        if ($request->filled('date_from')) {
            $orders->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $orders->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('customer_id')) {
            $orders->whereIn('user_id', $this->csv($request->customer_id));
        }
        if ($request->filled('handyman_id')) {
            $orders->whereHas('assignments', fn ($q) => $q->whereIn('handyman_id', $this->csv($request->handyman_id)));
        }
        if ($request->filled('shop_id')) {
            $orders->whereHas('items.product.shops', fn ($q) => $q->whereIn('shops.id', $this->csv($request->shop_id)));
        }
        if ($request->filled('search')) {
            $search = (string) $request->search;
            $orders->where(function ($q) use ($search) {
                $q->where('id', 'LIKE', "%{$search}%")
                    ->orWhere('order_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('items', fn ($iq) => $iq->where('product_name', 'LIKE', "%{$search}%"))
                    ->orWhereHas('user', fn ($uq) => $uq->where('display_name', 'LIKE', "%{$search}%")->orWhere('email', 'LIKE', "%{$search}%"));
            });
        }

        $totalQuery = clone $orders;
        $totalEarning = (float) $totalQuery->get()->sum(fn (ProductOrder $order) => $this->providerOrderAmount($order, $user));
        $breakdown = $totalQuery->get()->reduce(function ($carry, ProductOrder $order) use ($user) {
            $amount = $this->providerOrderAmount($order, $user);
            $type = (string) ($order->payment_type ?? 'online');
            if ($type === 'cash') {
                $carry['cash'] += $amount;
            } elseif ($type === 'wallet') {
                $carry['wallet'] += $amount;
            } else {
                $carry['online'] += $amount;
            }
            return $carry;
        }, ['cash' => 0, 'online' => 0, 'wallet' => 0]);

        $perPage = $this->perPage($request, $orders);
        $orders = $orders->orderByDesc('id')->paginate($perPage);

        return response()->json([
            'status' => true,
            'data' => $orders->getCollection()->map(fn (ProductOrder $order) => $this->serializeProviderOrderListItem($order, $user))->values(),
            'total_earning' => (string) round($totalEarning, 2),
            'payment_breakdown' => [
                'cash' => round($breakdown['cash'], 2),
                'online' => round($breakdown['online'], 2),
                'wallet' => round($breakdown['wallet'], 2),
            ],
            'pagination' => [
                'total_items' => $orders->total(),
                'per_page' => $orders->perPage(),
                'currentPage' => $orders->currentPage(),
                'totalPages' => $orders->lastPage(),
            ],
        ]);
    }

    public function providerDetail(Request $request, $id = null)
    {
        $orderId = $id ?: $request->get('order_id', $request->get('id'));
        $order = ProductOrder::query()
            ->with([
                'user',
                'items.product.providers',
                'items.product.shops',
                'items.variant.option.attribute',
                'assignments.handyman.handymantype',
                'activities',
                'liveLocation',
                'proofs',
            ])
            ->find($orderId);

        if (!$order) {
            return response()->json(['status' => false, 'message' => __('messages.record_not_found')], 404);
        }
        if (!$this->canAccessProviderOrder($order, auth()->user())) {
            return response()->json(['status' => false, 'message' => __('messages.unauthorized')], 403);
        }

        return response()->json([
            'status' => true,
            'data' => $this->serializeProviderOrderDetail($order, auth()->user()),
        ]);
    }

    public function updateProviderOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:product_orders,id',
           
            'reason' => 'nullable|string|max:1000',
            'delivery_status' => 'nullable|string|max:1000',
            'payment_status' => 'nullable|string|max:64',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $order = $this->findAccessibleProviderOrder((int) $request->id);
        if (!$order) {
            return response()->json(['status' => false, 'message' => __('messages.record_not_found')], 404);
        }

        $order->status = $request->delivery_status;
        if (Schema::hasColumn('product_orders', 'delivery_status')) {
            $order->delivery_status = $request->delivery_status;
        }
        if ($request->filled('payment_status') && Schema::hasColumn('product_orders', 'payment_status')) {
            $order->payment_status = $request->payment_status;
        }
        $this->mergeOrderNote($order, ['last_reason' => $request->reason]);
        $order->save();
        $this->recordActivity($order, $request->delivery_status, $this->statusLabel($request->delivery_status), ['reason' => $request->reason]);
        $this->sendProductOrderNotification($order->fresh(['items.product.providers', 'assignments.handyman', 'user']), 'update_booking_status', 'Product order status has been updated successfully');

        return response()->json([
            'status' => true,
            'message' => 'Product order status has been updated successfully',
        ]);
    }

    public function assignProviderOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:product_orders,id',
            'handyman_id' => 'required|array|min:1',
            'handyman_id.*' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $order = $this->findAccessibleProviderOrder((int) $request->id, true);
        if (!$order) {
            return response()->json(['status' => false, 'message' => __('messages.record_not_found')], 404);
        }

        $providerId = auth()->id();
        $ids = array_values(array_unique(array_map('intval', (array) $request->handyman_id)));
        foreach ($ids as $assignedId) {
            if ($assignedId === $providerId) {
                continue;
            }
            $validHandyman = User::query()
                ->where('id', $assignedId)
                ->where('user_type', 'handyman')
                ->where('provider_id', $providerId)
                ->exists();
            if (!$validHandyman) {
                return response()->json(['status' => false, 'message' => 'Selected delivery boy is invalid.'], 422);
            }
        }

        $order->assignments()->delete();
        foreach ($ids as $assignedId) {
            ProductOrderAssignment::query()->create([
                'product_order_id' => $order->id,
                'handyman_id' => $assignedId,
            ]);
        }
        $order->status = 'assigned';
        if (Schema::hasColumn('product_orders', 'delivery_status')) {
            $order->delivery_status = 'assigned';
        }
        $order->save();
        $this->recordActivity($order, 'assigned', 'Delivery boy has been assigned successfully', ['handyman_id' => $ids]);
        $this->sendProductOrderNotification($order->fresh(['items.product.providers', 'assignments.handyman', 'user']), 'assigned_booking', 'Delivery boy has been assigned successfully');

        return response()->json([
            'status' => true,
            'message' => 'Delivery boy has been assigned successfully',
        ]);
    }

    public function updateProviderOrderLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:product_orders,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $order = $this->findAccessibleProviderOrder((int) $request->id);
        if (!$order || !$this->isAssignedActor($order, auth()->id())) {
            return response()->json(['status' => false, 'message' => __('messages.unauthorized')], 403);
        }

        $location = ProductOrderLiveLocation::query()->updateOrCreate(
            ['product_order_id' => $order->id],
            [
                'user_id' => auth()->id(),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]
        );

        $data = $this->serializeLocation($location);

        return response()->json([
            'status' => true,
            'message' => 'Location updated successfully',
            'data' => $data,
        ]);
    }

    public function providerOrderLocation(Request $request)
    {
        $orderId = $request->get('id', $request->get('order_id'));
        $order = $this->findAccessibleProviderOrder((int) $orderId);
        if (!$order) {
            return response()->json(['status' => false, 'message' => __('messages.record_not_found')], 404);
        }

        $location = ProductOrderLiveLocation::query()->where('product_order_id', $order->id)->first();
        if (!$location) {
            return response()->json(['status' => false, 'message' => 'Live location not found for this product order.'], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $this->serializeLocation($location),
        ]);
    }

    public function saveProviderOrderProof(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:product_orders,id',
            'description' => 'nullable|string|max:2000',
            'attachment_count' => 'nullable|integer|min:1',
        ]);

        if ($request->filled('attachment_count')) {
            for ($i = 0; $i < (int) $request->attachment_count; $i++) {
                $validator->addRules(["proof_attachment_{$i}" => 'nullable|file|mimes:jpeg,png,jpg,gif|max:10240']);
            }
        }

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $order = $this->findAccessibleProviderOrder((int) $request->id);
        if (!$order || !$this->isAssignedActor($order, auth()->id())) {
            return response()->json(['status' => false, 'message' => __('messages.unauthorized')], 403);
        }

        $proof = ProductOrderProof::query()->create([
            'product_order_id' => $order->id,
            'user_id' => auth()->id(),
            'description' => $request->description,
        ]);

        $files = [];
        if ($request->filled('attachment_count')) {
            for ($i = 0; $i < (int) $request->attachment_count; $i++) {
                $key = "proof_attachment_{$i}";
                if ($request->hasFile($key)) {
                    $files[] = $request->file($key);
                }
            }
        }
        if (!empty($files)) {
            storeMediaFile($proof, $files, 'proof_attachment');
        }
        $this->recordActivity($order, 'proof_uploaded', 'Delivery proof has been saved successfully');
        $this->sendProductOrderNotification($order->fresh(['items.product.providers', 'assignments.handyman', 'user']), 'update_booking_status', 'Delivery proof has been saved successfully');

        return response()->json([
            'status' => true,
            'message' => 'Delivery proof has been saved successfully',
        ]);
    }

    public function confirmProviderOrderPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:product_orders,id',
            'payment_status' => 'required|string|max:64',
            'remarks' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $order = $this->findAccessibleProviderOrder((int) $request->id, true);
        if (!$order) {
            return response()->json(['status' => false, 'message' => __('messages.record_not_found')], 404);
        }

        if (Schema::hasColumn('product_orders', 'payment_status')) {
            $order->payment_status = $request->payment_status;
        }
        $this->mergeOrderNote($order, ['payment_remarks' => $request->remarks]);
        $order->save();
        $this->recordActivity($order, 'payment_confirmed', 'Payment has been confirmed successfully', ['payment_status' => $request->payment_status, 'remarks' => $request->remarks]);
        $this->sendProductOrderNotification($order->fresh(['items.product.providers', 'assignments.handyman', 'user']), 'payment_message_status', 'Payment has been confirmed successfully', [
            'payment_status' => $request->payment_status,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Payment has been confirmed successfully',
        ]);
    }

    private function serializeOrderListItem(ProductOrder $order): array
    {
        $firstItem = $order->items->first();
        $productImage = $firstItem && $firstItem->product ? getSingleMedia($firstItem->product, 'product_attachment', null) : null;

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'order_date' => optional($order->created_at)->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
            'created_at' => optional($order->created_at)->setTimezone('Asia/Kolkata')->toISOString(),
            'status' => $order->status,
            'product_image' => $productImage,
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
        $provider = $order->items
            ->map(fn ($item) => $item->product?->providers)
            ->filter()
            ->first();
        $assignment = $order->assignments->first();

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'order_date' => optional($order->created_at)->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
            'created_at' => optional($order->created_at)->setTimezone('Asia/Kolkata')->toISOString(),
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
            'provider' => $provider ? $this->serializeProductOrderProvider($provider) : null,
            'delivery_boy' => $assignment?->handyman ? $this->serializeProductOrderDeliveryBoy($assignment->handyman) : null,
            'items_count' => $order->items->count(),
            'items' => $order->items
                ->map(fn ($item) => $this->serializeOrderItem($item))
                ->values(),
        ];
    }

    private function serializeProductOrderProvider(User $user): array
    {
        $rating = $user->relationLoaded('getServiceRating')
            ? $user->getServiceRating->avg('rating')
            : $user->getServiceRating()->avg('rating');

        return [
            'id' => $user->id,
            'display_name' => $user->display_name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'contact_number' => $user->contact_number,
            'profile_image' => $this->userImage($user),
            'address' => $user->address,
            'uid' => $user->uid,
            'providers_service_rating' => (float) number_format(max((float) ($rating ?? 0), 0), 2),
            'is_verify_provider' => (int) verify_provider_document($user->id),
        ];
    }

    private function serializeProductOrderDeliveryBoy(User $user): array
    {
        $rating = $user->relationLoaded('handymanRating')
            ? $user->handymanRating->avg('rating')
            : $user->handymanRating()->avg('rating');

        return [
            'id' => $user->id,
            'display_name' => $user->display_name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'contact_number' => $user->contact_number,
            'profile_image' => $this->userImage($user),
            'address' => $user->address,
            'uid' => $user->uid,
            'handyman_rating' => (float) number_format(max((float) ($rating ?? 0), 0), 2),
            'is_verified' => (int) ($user->is_verified ?? 0),
            'is_available' => (bool) $user->is_available,
            'is_handyman_available' => (bool) $user->is_available,
            'handyman_type' => optional($user->handymantype)->name,
            'designation' => $user->designation,
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

    private function serializeProviderOrderListItem(ProductOrder $order, User $actor): array
    {
        $items = $this->providerItems($order, $actor);
        $firstItem = $items->first();
        $firstProduct = $firstItem?->product;
        $shop = $firstProduct?->shops?->first();
        $assignment = $order->assignments->first();
        $customer = $order->user;
        $shipping = $this->shippingData($order);

        return [
            'id' => $order->id,
            'order_code' => $order->order_number,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'status_label' => $this->statusLabel((string) $order->status),
            'delivery_status' => $this->orderColumnValue($order, 'delivery_status'),
            'delivery_status_label' => $this->statusLabel((string) ($this->orderColumnValue($order, 'delivery_status') ?? '')),
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_type,
            'payment_type' => $order->payment_type,
            'total_amount' => $this->providerOrderAmount($order, $actor),
            'total_amount_format' => getPriceFormat($this->providerOrderAmount($order, $actor)),
            'date' => optional($order->created_at)->format('Y-m-d H:i:s'),
            'customer_id' => $customer?->id,
            'customer_name' => $customer?->display_name,
            'customer_image' => $this->userImage($customer),
            'customer_phone' => $customer?->contact_number,
            'delivery_address' => $shipping['address'] ?? null,
            'delivery_latitude' => $shipping['latitude'] ?? null,
            'delivery_longitude' => $shipping['longitude'] ?? null,
            'shop_id' => $shop?->id,
            'shop_name' => $shop?->shop_name,
            'handyman_id' => $assignment?->handyman_id,
            'delivery_boy' => $assignment?->handyman ? $this->serializeDeliveryBoy($assignment->handyman) : null,
            'product_image' => $firstProduct ? getSingleMedia($firstProduct, 'product_attachment', null) : null,
            'product_count' => $items->count(),
            'items' => $items->map(fn ($item) => $this->serializeProviderListItem($item))->values(),
        ];
    }

    private function serializeProviderOrderDetail(ProductOrder $order, User $actor): array
    {
        $items = $this->providerItems($order, $actor);
        $provider = $this->providerForOrder($order, $actor);
        $firstProduct = $items->first()?->product;
        $shop = $firstProduct?->shops?->first();
        $assignment = $order->assignments->first();
        $shipping = $this->shippingData($order);
        $location = $order->liveLocation;

        return [
            'id' => $order->id,
            'order_code' => $order->order_number,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'status_label' => $this->statusLabel((string) $order->status),
            'delivery_status' => $this->orderColumnValue($order, 'delivery_status'),
            'delivery_status_label' => $this->statusLabel((string) ($this->orderColumnValue($order, 'delivery_status') ?? '')),
            'date' => optional($order->created_at)->format('Y-m-d H:i:s'),
            'description' => $shipping['notes'] ?? null,
            'payment_id' => null,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_type,
            'payment_type' => $order->payment_type,
            'txn_id' => $order->txn_id,
            'subtotal' => (float) $order->subtotal,
            'discount' => 0,
            'tax' => (float) ($order->tax_total ?? 0),
            'delivery_charge' => 0,
            'total_amount' => $this->providerOrderAmount($order, $actor),
            'total_amount_format' => getPriceFormat($this->providerOrderAmount($order, $actor)),
            'provider' => $provider ? $this->serializeUserLite($provider) : null,
            'customer' => $order->user ? $this->serializeUserLite($order->user, true) : null,
            'delivery_address' => [
                'id' => null,
                'address' => $shipping['address'] ?? null,
                'latitude' => $shipping['latitude'] ?? null,
                'longitude' => $shipping['longitude'] ?? null,
            ],
            'shop' => $shop ? [
                'id' => $shop->id,
                'name' => $shop->shop_name,
                'address' => $shop->address,
                'latitude' => $shop->lat,
                'longitude' => $shop->long,
                'image' => getSingleMedia($shop, 'shop_attachment', null),
            ] : null,
            'delivery_boy' => $assignment?->handyman ? $this->serializeDeliveryBoy($assignment->handyman) : null,
            'items' => $items->map(fn ($item) => $this->serializeProviderDetailItem($item))->values(),
            'activity' => $order->activities->sortBy('id')->map(fn ($activity) => [
                'id' => $activity->id,
                'order_id' => $activity->product_order_id,
                'activity_type' => $activity->activity_type,
                'activity_message' => $activity->activity_message,
                'datetime' => optional($activity->datetime ?? $activity->created_at)->format('Y-m-d H:i:s'),
                'created_by' => $activity->created_by,
            ])->values(),
            'proof' => $order->proofs->flatMap(function ($proof) {
                $media = $proof->getMedia('proof_attachment');
                if ($media->isEmpty()) {
                    return [[
                        'id' => $proof->id,
                        'url' => null,
                        'description' => $proof->description,
                        'created_at' => optional($proof->created_at)->format('Y-m-d H:i:s'),
                    ]];
                }
                return $media->map(fn ($file) => [
                    'id' => $proof->id,
                    'url' => $file->getUrl(),
                    'description' => $proof->description,
                    'created_at' => optional($proof->created_at)->format('Y-m-d H:i:s'),
                ]);
            })->values(),
            'latest_location' => $location ? [
                'latitude' => (string) $location->latitude,
                'longitude' => (string) $location->longitude,
                'datetime' => optional($location->updated_at)->format('Y-m-d H:i:s'),
            ] : null,
        ];
    }

    private function serializeProviderListItem($item): array
    {
        $product = $item->product;

        return [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'name' => $item->product_name,
            'image' => $product ? getSingleMedia($product, 'product_attachment', null) : null,
            'quantity' => (int) $item->quantity,
            'price' => (float) $item->unit_price,
            'price_format' => getPriceFormat($item->unit_price),
            'variant_label' => $item->variant_label,
        ];
    }

    private function serializeProviderDetailItem($item): array
    {
        $product = $item->product;

        return [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'name' => $item->product_name,
            'description' => $product?->description,
            'image' => $product ? getSingleMedia($product, 'product_attachment', null) : null,
            'attachments' => $product ? getAttachments($product->getMedia('product_attachment')) : [],
            'quantity' => (int) $item->quantity,
            'price' => (float) $item->unit_price,
            'price_format' => getPriceFormat($item->unit_price),
            'total' => (float) $item->line_total,
            'variant_id' => $item->product_variant_id,
            'variant_label' => $item->variant_label,
        ];
    }

    private function findAccessibleProviderOrder(int $id, bool $providerOnly = false): ?ProductOrder
    {
        $order = ProductOrder::query()
            ->with(['items.product.providers', 'items.product.shops', 'assignments.handyman', 'user'])
            ->find($id);

        if (!$order) {
            return null;
        }
        if ($providerOnly && auth()->user()?->user_type !== 'provider') {
            return null;
        }

        return $this->canAccessProviderOrder($order, auth()->user()) ? $order : null;
    }

    private function canAccessProviderOrder(ProductOrder $order, ?User $user): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->user_type === 'provider') {
            return $order->items->contains(fn ($item) => (int) optional($item->product)->provider_id === (int) $user->id);
        }
        if ($user->user_type === 'handyman') {
            return $order->assignments->contains(fn ($assignment) => (int) $assignment->handyman_id === (int) $user->id);
        }

        return $user->hasAnyRole(['admin', 'demo_admin']);
    }

    private function isAssignedActor(ProductOrder $order, int $userId): bool
    {
        if (auth()->user()?->user_type === 'provider' && $order->items->contains(fn ($item) => (int) optional($item->product)->provider_id === $userId)) {
            return true;
        }

        return $order->assignments->contains(fn ($assignment) => (int) $assignment->handyman_id === $userId);
    }

    private function providerItems(ProductOrder $order, User $actor)
    {
        if ($actor->user_type === 'provider') {
            return $order->items->filter(fn ($item) => (int) optional($item->product)->provider_id === (int) $actor->id)->values();
        }

        return $order->items->values();
    }

    private function providerForOrder(ProductOrder $order, User $actor): ?User
    {
        if ($actor->user_type === 'provider') {
            return $actor;
        }

        return $order->items->first()?->product?->providers;
    }

    private function providerOrderAmount(ProductOrder $order, User $actor): float
    {
        $items = $this->providerItems($order, $actor);
        if ($items->isEmpty()) {
            return (float) $order->total;
        }

        return round((float) $items->sum(fn ($item) => (float) $item->line_total), 2);
    }

    private function shippingData(ProductOrder $order): array
    {
        return $this->decodeJson($order->notes)['shipping'] ?? [];
    }

    private function recordActivity(ProductOrder $order, string $type, string $message, array $data = []): void
    {
        ProductOrderActivity::query()->create([
            'product_order_id' => $order->id,
            'activity_type' => $type,
            'activity_message' => $message,
            'activity_data' => $data ? json_encode($data) : null,
            'created_by' => auth()->id(),
            'datetime' => now(),
        ]);
    }

    private function sendProductOrderNotification(ProductOrder $order, string $templateType, string $message, array $extra = []): void
    {
        $order->loadMissing(['items.product.providers', 'assignments.handyman', 'user']);
        $providers = $order->items
            ->map(fn ($item) => $item->product?->providers)
            ->filter()
            ->unique('id')
            ->values();
        $handymen = $order->assignments
            ->map(fn ($assignment) => $assignment->handyman)
            ->filter()
            ->unique('id')
            ->values();
        $customer = $order->user;
        $actor = auth()->user();
        $providerName = optional($providers->first())->display_name;
        $handymanName = $handymen->pluck('display_name')->filter()->join(', ');

        $recipients = collect();
        if ($customer) {
            $recipients->push($customer);
        }
        $providers->each(fn ($provider) => $recipients->push($provider));
        $handymen->each(fn ($handyman) => $recipients->push($handyman));

        $recipients
            ->filter()
            ->unique('id')
            ->each(function (User $recipient) use ($order, $templateType, $message, $extra, $customer, $providerName, $handymanName, $actor) {
                try {
                    $recipient->notify(new CommonNotification($templateType, array_merge([
                        'person_id' => $recipient->id,
                        'user_type' => $recipient->user_type,
                        'type' => 'product_order',
                        'message' => $message,
                        'booking_id' => $order->id,
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'product_order_id' => $order->id,
                        'booking_status' => $this->statusLabel((string) $order->status),
                        'old_status' => '',
                        'payment_status' => $order->payment_status ?? ($extra['payment_status'] ?? ''),
                        'payment_type' => $order->payment_type ?? '',
                        'pay_amount' => getPriceFormat($order->total ?? 0),
                        'customer_name' => $customer?->display_name ?? '',
                        'user_name' => $customer?->display_name ?? '',
                        'user_email' => $customer?->email ?? '',
                        'user_contact' => $customer?->contact_number ?? '',
                        'provider_name' => $providerName ?? '',
                        'handyman_name' => $handymanName,
                        'assignee_name' => $handymanName,
                        'booking_services_name' => 'Product Order',
                        'service_name' => 'Product Order',
                        'booking_date' => optional($order->created_at)->format('Y-m-d') ?? '',
                        'booking_time' => optional($order->created_at)->format('H:i:s') ?? '',
                        'venue_address' => $this->shippingData($order)['address'] ?? '',
                        'check_booking_type' => 'product_order',
                        'logged_in_user_role' => $actor?->user_type ? ucfirst($actor->user_type) : '',
                    ], $extra)));
                } catch (\Throwable $e) {
                    Log::error('Product order notification failed', [
                        'order_id' => $order->id,
                        'template_type' => $templateType,
                        'recipient_id' => $recipient->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });
    }

    private function mergeOrderNote(ProductOrder $order, array $payload): void
    {
        $notes = $this->decodeJson($order->notes);
        foreach ($payload as $key => $value) {
            if ($value !== null && $value !== '') {
                $notes[$key] = $value;
            }
        }
        $order->notes = json_encode($notes);
    }

    private function serializeLocation(ProductOrderLiveLocation $location): array
    {
        return [
            'order_id' => $location->product_order_id,
            'latitude' => (string) $location->latitude,
            'longitude' => (string) $location->longitude,
            'datetime' => optional($location->updated_at)->format('Y-m-d H:i:s'),
        ];
    }

    private function serializeUserLite(User $user, bool $includeEmail = false): array
    {
        $data = [
            'id' => $user->id,
            'display_name' => $user->display_name,
            'profile_image' => $this->userImage($user),
            'phone' => $user->contact_number,
        ];
        if ($includeEmail) {
            $data['email'] = $user->email;
        }

        return $data;
    }

    private function serializeDeliveryBoy(User $user): array
    {
        return [
            'id' => $user->id,
            'display_name' => $user->display_name,
            'profile_image' => $this->userImage($user),
            'phone' => $user->contact_number,
            'is_available' => (bool) $user->is_available,
            'is_handyman_available' => (bool) $user->is_available,
            'handyman_type' => optional($user->handymantype)->name,
            'designation' => $user->designation,
        ];
    }

    private function userImage(?User $user): ?string
    {
        if (!$user) {
            return null;
        }

        return $user->login_type != null ? $user->social_image : getSingleMedia($user, 'profile_image', null);
    }

    private function statusLabel(string $status): string
    {
        if ($status === '') {
            return '';
        }

        return ucwords(str_replace('_', ' ', $status));
    }

    private function orderColumnValue(ProductOrder $order, string $column)
    {
        return Schema::hasColumn('product_orders', $column) ? $order->{$column} : null;
    }

    private function csv($value): array
    {
        return is_array($value) ? $value : array_filter(explode(',', (string) $value), fn ($item) => $item !== '');
    }

    private function perPage(Request $request, $query): int
    {
        if ($request->per_page === 'all') {
            return max(1, (int) $query->count());
        }

        return $request->filled('per_page') && is_numeric($request->per_page)
            ? max(1, (int) $request->per_page)
            : (int) config('constant.PER_PAGE_LIMIT', 15);
    }

    private function validationError($validator)
    {
        return response()->json([
            'status' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422);
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
