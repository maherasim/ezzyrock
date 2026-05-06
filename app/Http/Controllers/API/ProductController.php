<?php

namespace App\Http\Controllers\API;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\API\ProductResource;

class ProductController extends Controller
{
    public function getProductList(Request $request)
    {
        $query = Product::where('service_type', 'ecommerce')
            ->with(['providers', 'category', 'subcategory', 'translations', 'variants'])
            ->orderBy('created_at', 'desc');

        $category = Category::onlyTrashed()->pluck('id');
        $query->whereNotIn('category_id', $category);

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
        if ($request->has('category_id') && $request->category_id != 'null') {
            $query->where('category_id', $request->category_id);
        }
        if ($request->has('subcategory_id') && $request->subcategory_id != '') {
            $query->whereIn('subcategory_id', explode(',', $request->subcategory_id));
        }

        $per_page = $request->get('per_page', config('constant.PER_PAGE_LIMIT', 15));
        $items = $per_page === 'all' ? $query->get() : $query->paginate($per_page);

        if ($per_page === 'all') {
            return response()->json([
                'status' => true,
                'data' => ProductResource::collection($items),
                'pagination' => null,
            ]);
        }

        return response()->json([
            'status' => true,
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
            ->map(function ($variant) {
                return [
                    'id' => $variant->id,
                    'product_attribute_option_id' => $variant->product_attribute_option_id,
                    'option_value' => optional($variant->option)->value,
                    'attribute_name' => optional(optional($variant->option)->attribute)->name,
                    'price' => $variant->price,
                    'price_format' => getPriceFormat($variant->price),
                    'stock' => $variant->stock,
                    'max_purchase_qty' => $variant->max_purchase_qty,
                    'status' => $variant->status,
                ];
            });

        return response()->json([
            'status' => true,
            'data' => new ProductResource($product),
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
