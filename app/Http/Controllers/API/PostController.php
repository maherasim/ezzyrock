<?php

namespace App\Http\Controllers\API;

use App\Models\Post;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PostController extends Controller
{
    public function getPostList(Request $request)
    {
        $query = Post::where('service_type', 'classified')
            ->with(['providers', 'category', 'subcategory', 'translations']);

        if ($request->has('sort_by')) {
            if ($request->sort_by === 'price_low_high') {
                $query->orderBy('price', 'asc');
            } elseif ($request->sort_by === 'price_high_low') {
                $query->orderBy('price', 'desc');
            } elseif ($request->sort_by === 'newest') {
                $query->orderBy('created_at', 'desc');
            } else {
                $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $category = Category::onlyTrashed()->pluck('id');
        $query->whereNotIn('category_id', $category);

        if (auth()->user() && auth()->user()->hasRole('admin')) {
            $query->withTrashed();
        } elseif (auth()->user() && auth()->user()->hasRole('provider')) {
            $query->where('provider_id', auth()->id());
        } else {
            $query->where('status', 1);
            $query->whereHas('providers', function ($q) {
                $q->where('status', 1);
            });
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

        if ($request->has('city_id') && !empty($request->city_id)) {
            $query->whereHas('providers', function ($q) use ($request) {
                $q->where('city_id', $request->city_id);
            });
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

        $categoriesData = Category::where('status', 1)
            ->where('module_type', Category::MODULE_CLASSIFIED)
            ->withCount('posts')
            ->get()
            ->map(function ($category) {
                $subcategories = \App\Models\SubCategory::where('status', 1)
                    ->where('category_id', $category->id)
                    ->withCount('posts')
                    ->get()
                    ->map(function ($subcategory) {
                        return [
                            'id' => $subcategory->id,
                            'name' => $subcategory->name,
                            'category_id' => $subcategory->category_id,
                            'subcategory_image' => getSingleMedia($subcategory, 'subcategory_image', null),
                            'posts_count' => $subcategory->posts_count ?? 0,
                        ];
                    });

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'color' => $category->color,
                    'category_image' => getSingleMedia($category, 'category_image', null),
                    'posts_count' => $category->posts_count ?? 0,
                    'subcategories' => $subcategories,
                ];
            });

        if ($per_page === 'all') {
            return response()->json([
                'status' => true,
                'category' => $categoriesData,
                'data' => \App\Http\Resources\API\PostResource::collection($items),
                'pagination' => null,
            ]);
        }

        return response()->json([
            'status' => true,
            'category' => $categoriesData,
            'data' => \App\Http\Resources\API\PostResource::collection($items),
            'pagination' => [
                'total_items' => $items->total(),
                'per_page' => $items->perPage(),
                'currentPage' => $items->currentPage(),
                'totalPages' => $items->lastPage(),
            ],
        ]);
    }

    public function getPostDetail(Request $request)
    {
        $id = $request->post_id ?? $request->id;
        $post = Post::with(['providers', 'category', 'subcategory', 'translations', 'zones', 'shops'])->find($id);
        if (!$post) {
            return response()->json(['status' => false, 'message' => __('messages.record_not_found')], 404);
        }
        
        $postData = (new \App\Http\Resources\API\PostResource($post))->toArray($request);

        return response()->json([
            'status' => true, 
            'data' => $postData
        ]);
    }

    public function poststatus(Request $request)
    {
        $post = Post::withTrashed()->find($request->id);
        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }
        if ($request->request_status === 'delete') {
            $post->delete();
            return response()->json(['message' => 'Post deleted successfully'], 200);
        }
        if ($request->request_status === 'restore') {
            $post->restore();
            return response()->json(['message' => 'Post restored successfully'], 200);
        }
        if (in_array($request->request_status, ['0', '1'])) {
            $post->status = (int) $request->request_status;
            $post->save();
            return response()->json(['message' => 'Post status updated'], 200);
        }
        return response()->json(['message' => 'Invalid request'], 400);
    }
}
