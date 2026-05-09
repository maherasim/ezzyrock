<?php

namespace App\Http\Controllers\API;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\API\ProductResource;
use App\Http\Resources\API\CategoryResource;
use App\Http\Resources\API\SubCategoryResource;
use App\Models\SubCategory;
use App\Traits\ZoneTrait;
use App\Models\ServiceZone;

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
            $categories = Category::where('status', 1)->where('module_type', Category::MODULE_ECOMMERCE)->get();
            $subcategories = SubCategory::where('status', 1)->whereHas('category', function($q) {
                $q->where('module_type', Category::MODULE_ECOMMERCE);
            })->get();

            return response()->json([
                'status' => true,
                'category' => CategoryResource::collection($categories),
                'subcategory' => SubCategoryResource::collection($subcategories),
                'data' => ProductResource::collection($items),
                'pagination' => null,
            ]);
        }

        $categories = Category::where('status', 1)->where('module_type', Category::MODULE_ECOMMERCE)->get();
        $subcategories = SubCategory::where('status', 1)->whereHas('category', function($q) {
            $q->where('module_type', Category::MODULE_ECOMMERCE);
        })->get();

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
        ]);
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
