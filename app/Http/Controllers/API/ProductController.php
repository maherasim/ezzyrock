<?php

namespace App\Http\Controllers\API;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeOption;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\API\ProductResource;
use App\Http\Resources\API\CategoryResource;
use App\Http\Resources\API\SubCategoryResource;
use App\Models\SubCategory;
use App\Traits\ZoneTrait;
use App\Models\ServiceZone;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use ZoneTrait;

    public function getProductList(Request $request)
    {
        $query = Product::where('service_type', 'ecommerce')
            ->where('service_request_status', 'approve')
            ->with(['providers', 'category', 'subcategory', 'translations', 'variants'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->orderByDesc('is_featured')
            ->latest();

        $category = Category::onlyTrashed()->pluck('id');
        $query->whereNotIn('category_id', $category);

        if (\Schema::hasColumn('products', 'total_stock')) {
            $query->where('total_stock', '>', 0);
        }

        $query->whereHas('providers', function ($query) {
            $query->where('status', 1);
        });

        if (default_earning_type() === 'subscription') {
            $query->whereHas('providers', function ($query) {
                $query->where('status', 1)->where('is_subscribe', 1);
            });
        }

        if (auth()->user() && auth()->user()->hasRole('admin')) {
            $query->withTrashed();
        } elseif (auth()->user() && auth()->user()->hasRole('provider')) {
            $query->where('provider_id', auth()->id());
        } else {
            $query->where('status', 1);
        }
        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }
        if ($request->has('provider_id')) {
            $query->where('provider_id', $request->provider_id);
        }
        if ($request->has('category_id') && $request->category_id != 'null' && $request->category_id != '') {
            $categoryIds = is_array($request->category_id) ? $request->category_id : explode(',', $request->category_id);
            $query->whereIn('category_id', $categoryIds);
        }
        if ($request->has('subcategory_id') && $request->subcategory_id != 'null' && $request->subcategory_id != '') {
            $subcategoryIds = is_array($request->subcategory_id) ? $request->subcategory_id : explode(',', $request->subcategory_id);
            $query->whereIn('subcategory_id', $subcategoryIds);
        }

        if ($request->has('min_price') && $request->min_price !== null && $request->min_price !== '') {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price') && $request->max_price !== null && $request->max_price !== '') {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->has('brand_id') && $request->brand_id !== null && $request->brand_id !== '') {
            $brandIds = is_array($request->brand_id) ? $request->brand_id : explode(',', $request->brand_id);
            // Check if column exists to prevent error if brand_id is not yet added to DB
            if (\Schema::hasColumn('products', 'brand_id')) {
                $query->whereIn('brand_id', $brandIds);
            }
        }

        if ($request->has('city_id') && !empty($request->city_id)) {
            $query->whereHas('providers', function ($query) use ($request) {
                $query->where('city_id', $request->city_id);
            });
        }

        if ($request->has('latitude') && !empty($request->latitude) && $request->has('longitude') && !empty($request->longitude)) {
            $latitude = $request->latitude;
            $longitude = $request->longitude;

            $serviceZone = ServiceZone::all();
            if (count($serviceZone) > 0) {
                try {
                    $matchingZoneIds = $this->getMatchingZonesByLatLng($latitude, $longitude);
                    if (!empty($matchingZoneIds)) {
                        $query->whereHas('productZoneMapping', function ($query) use ($matchingZoneIds) {
                            $query->whereIn('zone_id', $matchingZoneIds);
                        });
                    }
                } catch (\Exception $e) {
                    \Log::error('Product location filtering error: ' . $e->getMessage());
                }
            }
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $per_page = $request->get('per_page', config('constant.PER_PAGE_LIMIT', 15));
        $items = $per_page === 'all' ? $query->get() : $query->paginate($per_page);

        if ($per_page === 'all') {
            $categories = Category::where('status', 1)->where('module_type', Category::MODULE_ECOMMERCE)->withCount('products')->get();
            $subcategories = SubCategory::where('status', 1)->whereHas('category', function($q) {
                $q->where('module_type', Category::MODULE_ECOMMERCE);
            })->withCount('products')->get();

            return response()->json([
                'status' => true,
                'category' => CategoryResource::collection($categories),
                'subcategory' => SubCategoryResource::collection($subcategories),
                'data' => ProductResource::collection($items),
                'pagination' => null,
                'cart_count' => (auth()->check() && auth()->user()->user_type === 'user') 
                    ? \App\Models\ProductCartItem::totalEcommerceQuantityForUser((int) auth()->id()) 
                    : 0,
            ]);
        }

        $categories = Category::where('status', 1)->where('module_type', Category::MODULE_ECOMMERCE)->withCount('products')->get();
        $subcategories = SubCategory::where('status', 1)->whereHas('category', function($q) {
            $q->where('module_type', Category::MODULE_ECOMMERCE);
        })->withCount('products')->get();

        return response()->json([
            'status' => true,
            'category' => CategoryResource::collection($categories),
            'subcategory' => SubCategoryResource::collection($subcategories),
            'data' => ProductResource::collection($items),
            'pagination' => [
                'total_items' => $items->total(),
                'per_page' => $items->perPage(),
                'currentPage' => $items->currentPage(),
                'totalPages' => $items->lastPage(),
            ],
            'cart_count' => (auth()->check() && auth()->user()->user_type === 'user') 
                ? \App\Models\ProductCartItem::totalEcommerceQuantityForUser((int) auth()->id()) 
                : 0,
        ]);
    }

    public function getUserProductList(Request $request)
    {
        $query = Product::query()
            ->where('provider_id', auth()->id())
            ->where('service_type', 'ecommerce')
            ->with(['providers', 'category', 'subcategory', 'translations', 'variants.option.attribute', 'productUnit'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->latest();

        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }
        if ($request->has('service_request_status') && $request->service_request_status !== null && $request->service_request_status !== '') {
            $query->where('service_request_status', $request->service_request_status);
        }
        if ($request->has('category_id') && $request->category_id != 'null' && $request->category_id != '') {
            $categoryIds = is_array($request->category_id) ? $request->category_id : explode(',', $request->category_id);
            $query->whereIn('category_id', $categoryIds);
        }
        if ($request->has('subcategory_id') && $request->subcategory_id != 'null' && $request->subcategory_id != '') {
            $subcategoryIds = is_array($request->subcategory_id) ? $request->subcategory_id : explode(',', $request->subcategory_id);
            $query->whereIn('subcategory_id', $subcategoryIds);
        }
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $per_page = $request->get('per_page', config('constant.PER_PAGE_LIMIT', 15));
        $items = $per_page === 'all' ? $query->get() : $query->paginate($per_page);

        return response()->json([
            'status' => true,
            'data' => ProductResource::collection($items),
            'pagination' => $per_page === 'all' ? null : [
                'total_items' => $items->total(),
                'per_page' => $items->perPage(),
                'currentPage' => $items->currentPage(),
                'totalPages' => $items->lastPage(),
            ],
        ]);
    }

    public function saveProduct(Request $request)
    {
        $userId = auth()->id();
        $productId = $request->id;

        $existingProduct = null;
        if ($productId) {
            $existingProduct = Product::query()
                ->where('service_type', 'ecommerce')
                ->findOrFail($productId);

            if ((int) $existingProduct->provider_id !== $userId && !auth()->user()->hasAnyRole(['admin', 'demo_admin'])) {
                return response()->json(['status' => false, 'message' => __('messages.unauthorized')], 403);
            }
        }

        $providerId = auth()->user()->hasAnyRole(['admin', 'demo_admin']) && $request->filled('provider_id')
            ? (int) $request->provider_id
            : ($existingProduct ? (int) $existingProduct->provider_id : $userId);

        $rules = [
            'id' => 'nullable|integer|exists:products,id',
            'provider_id' => 'nullable|integer|exists:users,id',
            'name' => [
                'required',
                Rule::unique('products', 'name')->ignore($productId)->where(function ($q) use ($providerId) {
                    return $q->where('provider_id', $providerId);
                }),
            ],
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('status', 1)),
            ],
            'subcategory_id' => [
                'nullable',
                Rule::exists('sub_categories', 'id')->where(fn ($q) => $q->where('category_id', (int) $request->category_id)->where('status', 1)),
            ],
            'type' => 'nullable|string|max:50',
            'price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:99',
            'description' => 'nullable|string|max:5000',
            'total_stock' => 'required|integer|min:0',
            'max_purchase_qty' => 'nullable|integer|min:1',
            'product_unit_id' => 'nullable|integer|exists:product_units,id',
            'status' => 'required|in:0,1',
            'is_featured' => 'nullable|boolean',
            'service_zones' => 'required|array|min:1',
            'service_zones.*' => ['integer', Rule::exists('service_zones', 'id')->where(fn ($q) => $q->where('status', true))],
            'shop_ids' => 'nullable|array',
            'shop_ids.*' => 'integer|exists:shops,id',
            'product_attribute_id' => 'nullable|integer|exists:product_attributes,id',
            'variant_labels' => 'nullable|array',
            'variant_labels.*' => 'nullable|string|max:255',
            'variant_price' => 'nullable|array',
            'variant_price.*' => 'nullable|numeric|min:0',
            'variant_stock' => 'nullable|array',
            'variant_stock.*' => 'nullable|integer|min:0',
            'product_attachment' => 'nullable|array',
            'product_attachment.*' => 'image|mimes:jpeg,png,jpg,gif|max:10240',
            'attachment_count' => 'nullable|integer|min:1',
            'seo_enabled' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:200',
            'meta_keywords' => 'nullable|string',
            'seo_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
        ];

        if ($request->filled('attachment_count')) {
            for ($i = 0; $i < (int) $request->attachment_count; $i++) {
                $rules["product_attachment_{$i}"] = 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240';
            }
        }

        if (!$productId && !$request->hasFile('product_attachment') && !$this->hasCountedAttachment($request, 'product_attachment')) {
            $rules['product_attachment_required'] = 'required';
        }

        $validator = Validator::make($request->all(), $rules, [
            'product_attachment_required.required' => 'The product attachment field is required.',
        ]);

        $validator->after(function ($v) use ($request) {
            $labels = array_values(array_filter(array_map('trim', (array) $request->input('variant_labels', []))));
            if (count($labels) === 0) {
                return;
            }
            if (!$request->filled('product_attribute_id')) {
                $v->errors()->add('product_attribute_id', __('messages.product_variant_attribute_required'));
            }
            if (count((array) $request->input('variant_price', [])) !== count($labels) || count((array) $request->input('variant_stock', [])) !== count($labels)) {
                $v->errors()->add('variant_labels', __('messages.product_variant_rows_mismatch'));
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!$existingProduct) {
            $productMsg = plan_limit_user_message(get_provider_plan_limit($providerId, 'ecommerce'), __('messages.products'));
            if ($productMsg !== null) {
                return response()->json(['status' => false, 'message' => $productMsg], 422);
            }
        }

        $isFeatured = $request->boolean('is_featured');
        if ($isFeatured && (!$existingProduct || (int) $existingProduct->is_featured !== 1)) {
            $featuredMsg = plan_limit_user_message(get_provider_plan_limit($providerId, 'featured_ecommerce'), 'Featured products');
            if ($featuredMsg !== null) {
                return response()->json(['status' => false, 'message' => $featuredMsg], 422);
            }
        }

        $data = [
            'name' => $request->name,
            'category_id' => (int) $request->category_id,
            'subcategory_id' => $request->filled('subcategory_id') ? (int) $request->subcategory_id : null,
            'provider_id' => $providerId,
            'type' => $request->input('type', 'fixed'),
            'price' => (float) $request->price,
            'discount' => $request->filled('discount') ? (float) $request->discount : 0,
            'description' => $request->description,
            'total_stock' => (int) $request->total_stock,
            'max_purchase_qty' => $request->filled('max_purchase_qty') ? (int) $request->max_purchase_qty : null,
            'product_unit_id' => $request->filled('product_unit_id') ? (int) $request->product_unit_id : null,
            'status' => (int) $request->status,
            'is_featured' => $isFeatured ? 1 : 0,
            'service_type' => 'ecommerce',
            'visit_type' => $request->input('visit_type', 'on_site'),
            'is_slot' => (int) $request->boolean('is_slot'),
            'is_enable_advance_payment' => (int) $request->boolean('is_enable_advance_payment'),
            'advance_payment_amount' => $request->filled('advance_payment_amount') ? (float) $request->advance_payment_amount : null,
            'seo_enabled' => $request->boolean('seo_enabled'),
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'meta_keywords' => $request->meta_keywords,
            'slug' => $request->filled('meta_title') ? Str::slug($request->meta_title) : Str::slug($request->name),
        ];

        if (!$existingProduct) {
            $data['added_by'] = $userId;
            $data['is_service_request'] = auth()->user()->hasAnyRole(['admin', 'demo_admin']) ? 0 : 1;
            $data['service_request_status'] = auth()->user()->hasAnyRole(['admin', 'demo_admin']) ? 'approve' : 'pending';
        }

        $product = Product::query()->updateOrCreate(['id' => $productId], $data);

        $validZoneIds = ServiceZone::query()
            ->where('status', true)
            ->whereIn('id', (array) $request->service_zones)
            ->pluck('id')
            ->all();
        $product->zones()->sync($validZoneIds);

        if ($request->has('shop_ids')) {
            $product->shops()->sync(array_filter(array_map('intval', (array) $request->shop_ids)));
        }

        $this->syncVariants($product, $request);

        $attachments = $this->uploadedProductAttachments($request);
        if (!empty($attachments)) {
            storeMediaFile($product, $attachments, 'product_attachment');
        }
        if ($request->hasFile('seo_image')) {
            storeMediaFile($product, $request->file('seo_image'), 'seo_image');
        }

        $product->load(['providers', 'category', 'subcategory', 'translations', 'variants.option.attribute', 'productUnit']);

        return response()->json([
            'status' => true,
            'message' => $existingProduct
                ? __('messages.update_form', ['form' => __('messages.product')])
                : __('messages.save_form', ['form' => __('messages.product')]),
            'data' => new ProductResource($product),
        ]);
    }

    public function deleteProduct(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:products,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $product = Product::query()
            ->where('service_type', 'ecommerce')
            ->findOrFail($request->id);

        if ((int) $product->provider_id !== auth()->id() && !auth()->user()->hasAnyRole(['admin', 'demo_admin'])) {
            return response()->json(['status' => false, 'message' => __('messages.unauthorized')], 403);
        }

        $product->delete();

        return response()->json([
            'status' => true,
            'message' => __('messages.msg_deleted', ['name' => __('messages.product')]),
        ]);
    }

    private function syncVariants(Product $product, Request $request): void
    {
        $attributeId = (int) $request->input('product_attribute_id', 0);
        $variantLabels = array_values(array_filter(array_map('trim', (array) $request->input('variant_labels', []))));

        if ($attributeId <= 0 || count($variantLabels) === 0) {
            $product->attributeOptions()->sync([]);
            ProductVariant::query()->where('product_id', $product->id)->delete();
            return;
        }

        $attribute = ProductAttribute::query()->where('id', $attributeId)->where('status', true)->first();
        if (!$attribute) {
            return;
        }

        $optionIds = [];
        foreach ($variantLabels as $label) {
            $option = ProductAttributeOption::withTrashed()
                ->where('product_attribute_id', $attributeId)
                ->where('value', $label)
                ->first();

            if ($option) {
                if ($option->trashed()) {
                    $option->restore();
                }
                if (!$option->status) {
                    $option->status = true;
                    $option->save();
                }
            } else {
                $option = ProductAttributeOption::query()->create([
                    'product_attribute_id' => $attributeId,
                    'value' => $label,
                    'status' => true,
                ]);
            }
            $optionIds[] = $option->id;
        }

        $options = ProductAttributeOption::query()->whereIn('id', $optionIds)->get()->keyBy('id');
        $syncPayload = [];
        foreach ($optionIds as $optionId) {
            $syncPayload[$optionId] = ['product_attribute_id' => $attributeId];
        }
        $product->attributeOptions()->sync($syncPayload);

        $variantPrices = (array) $request->input('variant_price', []);
        $variantStocks = (array) $request->input('variant_stock', []);
        foreach ($optionIds as $index => $optionId) {
            if (!$options->get($optionId)) {
                continue;
            }
            ProductVariant::query()->updateOrCreate(
                ['product_id' => $product->id, 'product_attribute_option_id' => $optionId],
                [
                    'price' => (float) ($variantPrices[$index] ?? $product->price ?? 0),
                    'stock' => max((int) ($variantStocks[$index] ?? 0), 0),
                    'max_purchase_qty' => null,
                    'status' => true,
                ]
            );
        }

        ProductVariant::query()
            ->where('product_id', $product->id)
            ->whereNotIn('product_attribute_option_id', $optionIds)
            ->delete();

        $activeVariants = ProductVariant::query()
            ->where('product_id', $product->id)
            ->where('status', true)
            ->get();

        if ($activeVariants->isNotEmpty()) {
            $product->price = (float) $activeVariants->min('price');
            $product->total_stock = (int) $activeVariants->sum('stock');
            $product->save();
        }
    }

    private function uploadedProductAttachments(Request $request): array
    {
        $files = [];
        if ($request->hasFile('product_attachment')) {
            $uploaded = $request->file('product_attachment');
            $files = is_array($uploaded) ? $uploaded : [$uploaded];
        }

        if ($request->filled('attachment_count')) {
            for ($i = 0; $i < (int) $request->attachment_count; $i++) {
                $key = "product_attachment_{$i}";
                if ($request->hasFile($key)) {
                    $files[] = $request->file($key);
                }
            }
        }

        return $files;
    }

    private function hasCountedAttachment(Request $request, string $prefix): bool
    {
        if (!$request->filled('attachment_count')) {
            return false;
        }

        for ($i = 0; $i < (int) $request->attachment_count; $i++) {
            if ($request->hasFile("{$prefix}_{$i}")) {
                return true;
            }
        }

        return false;
    }

    public function getProductDetail(Request $request)
    {
        $id = $request->product_id ?? $request->id;
        $product = Product::with(['providers', 'category', 'subcategory', 'translations', 'zones', 'shops', 'variants.option.attribute', 'productUnit'])->find($id);
        if (!$product) {
            return response()->json(['status' => false, 'message' => __('messages.record_not_found')], 404);
        }

        $activeVariants = $product->variants
            ->where('status', true)
            ->where('stock', '>', 0)
            ->values()
            ->map(function ($variant) use ($product) {
                $maxPurchaseQty = (int) ($variant->max_purchase_qty ?: ($product->max_purchase_qty ?: 99));
                $maxAllowedQty = max(1, min(99, (int) $variant->stock, $maxPurchaseQty));

                return [
                    'id' => $variant->id,
                    'product_variant_id' => $variant->id,
                    'product_attribute_option_id' => $variant->product_attribute_option_id,
                    'option_value' => optional($variant->option)->value,
                    'attribute_name' => optional(optional($variant->option)->attribute)->name,
                    'label' => trim((optional(optional($variant->option)->attribute)->name ? optional(optional($variant->option)->attribute)->name . ': ' : '') . (optional($variant->option)->value ?? ('Option #' . $variant->id))),
                    'price' => $variant->price,
                    'price_format' => getPriceFormat($variant->price),
                    'stock' => $variant->stock,
                    'max_purchase_qty' => $variant->max_purchase_qty,
                    'max_allowed_quantity' => $maxAllowedQty,
                    'is_available' => $maxAllowedQty > 0,
                    'status' => $variant->status,
                ];
            });

        $maxAllowed = min(99, (int) ($product->total_stock ?? 99));
        if (!empty($product->max_purchase_qty)) {
            $maxAllowed = min($maxAllowed, (int) $product->max_purchase_qty);
        }

        $productData = (new ProductResource($product))->toArray($request);
        $productData['total_stock'] = (int) ($product->total_stock ?? 0);
        $productData['max_purchase_qty'] = $product->max_purchase_qty;
        $productData['max_allowed_quantity'] = max(0, $maxAllowed);
        $productData['requires_variant_selection'] = $activeVariants->count() > 0;
        $productData['variant_attribute_name'] = $activeVariants->first()['attribute_name'] ?? null;
        $productData['variants'] = $activeVariants;
        $productData['product_unit_id'] = $product->product_unit_id;
        $productData['product_unit_name'] = optional($product->productUnit)->name;

        return response()->json([
            'status' => true,
            'data' => $productData,
            'variants' => $activeVariants,
            'has_variants' => $activeVariants->count() > 0,
        ]);
    }

    public function productstatus(Request $request)
    {
        $product = Product::withTrashed()->find($request->id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        if ($request->request_status === 'delete') {
            $product->delete();
            return response()->json(['message' => 'Product deleted successfully'], 200);
        }
        if ($request->request_status === 'restore') {
            $product->restore();
            return response()->json(['message' => 'Product restored successfully'], 200);
        }
        if (in_array($request->request_status, ['0', '1'])) {
            $product->status = (int) $request->request_status;
            $product->save();
            return response()->json(['message' => 'Product status updated'], 200);
        }
        return response()->json(['message' => 'Invalid request'], 400);
    }
}
