<?php

namespace App\Http\Controllers\API;

use App\Models\Post;
use App\Models\Category;
use App\Models\UserPlan;
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

    public function getUserPostList(Request $request)
    {
        $userId = auth()->id();
        $query = Post::where('provider_id', $userId)
            ->where('service_type', 'classified')
            ->with(['category', 'subcategory', 'translations'])
            ->orderByDesc('created_at');

        $per_page = $request->get('per_page', config('constant.PER_PAGE_LIMIT', 15));
        $items = $per_page === 'all' ? $query->get() : $query->paginate($per_page);

        if ($per_page === 'all') {
            return response()->json([
                'status' => true,
                'allow_to_create_featured' => $this->canCreateFeaturedPost($userId) ? 'yes' : 'no',
                'data' => \App\Http\Resources\API\PostResource::collection($items),
                'pagination' => null,
            ]);
        }

        return response()->json([
            'status' => true,
            'allow_to_create_featured' => $this->canCreateFeaturedPost($userId) ? 'yes' : 'no',
            'data' => \App\Http\Resources\API\PostResource::collection($items),
            'pagination' => [
                'total_items' => $items->total(),
                'per_page' => $items->perPage(),
                'currentPage' => $items->currentPage(),
                'totalPages' => $items->lastPage(),
            ],
        ]);
    }

    private function canCreateFeaturedPost(int $userId): bool
    {
        $subscription = user_subscriptions_valid_query($userId)
            ->latest('id')
            ->first();

        if (! $subscription) {
            return false;
        }

        $planKind = strtolower(trim((string) ($subscription->plan_type ?? '')));
        $planLimitation = json_decode($subscription->plan_limitation ?? '', true);

        if ((! is_array($planLimitation) || empty($planLimitation)) && ! empty($subscription->plan_id)) {
            $plan = UserPlan::query()->with('planlimit')->find($subscription->plan_id);
            if ($plan) {
                $planKind = strtolower(trim((string) ($plan->plan_type ?? $planKind)));
                $planLimitation = $plan->planlimit->plan_limitation ?? [];
            }
        }

        if ($planKind === 'unlimited') {
            return true;
        }

        $featuredLimit = is_array($planLimitation) ? ($planLimitation['featured_classified'] ?? null) : null;
        if (! is_array($featuredLimit) || ($featuredLimit['is_checked'] ?? 'off') !== 'on') {
            return false;
        }

        $limit = $featuredLimit['limit'] ?? null;
        if ($limit === null || $limit === '') {
            return true;
        }

        $usedCount = Post::query()
            ->where('provider_id', $userId)
            ->where('service_type', 'classified')
            ->where('is_featured', 1)
            ->where('status', 1)
            ->count();

        return $usedCount < (int) $limit;
    }

    public function getPostFormConfig(Request $request)
    {
        $userId = auth()->id();
        $categories = Category::query()
            ->where('module_type', 'classified')
            ->where('status', 1)
            ->orderByDesc('is_featured')
            ->orderBy('id')
            ->get();

        $subcategories = \App\Models\SubCategory::query()
            ->where('status', 1)
            ->whereHas('category', function ($q) {
                 $q->where('module_type', 'classified');
            })
            ->orderByDesc('is_featured')
            ->orderBy('id')
            ->get();

        $zones = \App\Models\ServiceZone::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $post = null;
        $postId = $request->post_id ?? $request->id;
        if (! empty($postId)) {
            $post = Post::query()
                ->where('provider_id', $userId)
                ->where('service_type', 'classified')
                ->with(['providers', 'category', 'subcategory', 'translations', 'zones'])
                ->find($postId);

            if (! $post) {
                return response()->json([
                    'status' => false,
                    'message' => __('messages.record_not_found'),
                ], 404);
            }
        }

        return response()->json([
            'status' => true,
            'data' => [
                'categories' => \App\Http\Resources\API\CategoryResource::collection($categories),
                'subcategories' => \App\Http\Resources\API\SubCategoryResource::collection($subcategories),
                'zones' => $zones,
                'post' => $post ? new \App\Http\Resources\API\PostResource($post) : null,
            ]
        ]);
    }

    public function savePost(Request $request)
    {
        $userId = auth()->id();
        $postId = $request->id;

        $rules = [
            'name' => [
                'required',
                \Illuminate\Validation\Rule::unique('posts', 'name')
                    ->ignore($postId)
                    ->where(function ($q) use ($userId) {
                        return $q->where('provider_id', $userId);
                    }),
            ],
            'category_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('categories', 'id')->where(function ($q) {
                    return $q->where('module_type', 'classified')->where('status', 1);
                }),
            ],
            'subcategory_id' => [
                'nullable',
                \Illuminate\Validation\Rule::exists('sub_categories', 'id')->where(function ($q) use ($request) {
                    return $q->where('category_id', (int) $request->category_id);
                }),
            ],
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0',
            'is_featured' => 'nullable|boolean',
            'service_zones' => 'required|array|min:1',
            'service_zones.*' => ['integer', \Illuminate\Validation\Rule::exists('service_zones', 'id')->where(function ($q) {
                return $q->where('status', true);
            })],
        ];

        if (!$postId) {
            $rules['post_attachment'] = 'required|array|min:1';
            $rules['post_attachment.*'] = 'image|mimes:jpeg,png,jpg,gif|max:10240';
        } else {
            $rules['post_attachment'] = 'nullable|array';
            $rules['post_attachment.*'] = 'image|mimes:jpeg,png,jpg,gif|max:10240';
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $isFeatured = (int) ($request->boolean('is_featured') ? 1 : 0);

        if ($postId) {
            $post = Post::findOrFail($postId);
            if ($post->provider_id !== $userId) {
                return response()->json(['status' => false, 'message' => __('messages.unauthorized')], 403);
            }

            $post->update([
                'name' => $validated['name'],
                'category_id' => $validated['category_id'],
                'subcategory_id' => $validated['subcategory_id'] ?? null,
                'description' => $validated['description'] ?? '',
                'price' => $validated['price'],
                'is_featured' => $isFeatured,
            ]);
            $message = __('messages.update_form', ['form' => __('messages.post')]);
        } else {
            $data = [
                'name' => $validated['name'],
                'category_id' => $validated['category_id'],
                'subcategory_id' => $validated['subcategory_id'] ?? null,
                'description' => $validated['description'] ?? '',
                'price' => $validated['price'],
                'type' => 'fixed',
                'status' => 1,
                'visit_type' => 'on_site',
                'duration' => null,
                'discount' => 0,
                'provider_id' => $userId,
                'added_by' => $userId,
                'service_type' => 'classified',
                'service_request_status' => 'approve',
                'is_service_request' => 0,
                'is_featured' => $isFeatured,
                'is_slot' => 0,
                'is_enable_advance_payment' => 0,
                'advance_payment_amount' => null,
                'slug' => \Illuminate\Support\Str::slug($validated['name']).'-'.\Illuminate\Support\Str::random(4),
            ];

            $post = Post::create($data);
            $message = __('messages.save_form', ['form' => __('messages.post')]);
        }

        if ($request->hasFile('post_attachment')) {
            storeMediaFile($post, $request->file('post_attachment'), 'post_attachment');
        }

        $validZoneIds = \App\Models\ServiceZone::query()
            ->where('status', true)
            ->whereIn('id', (array) ($validated['service_zones'] ?? []))
            ->pluck('id')
            ->all();
        $post->zones()->sync($validZoneIds);

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => new \App\Http\Resources\API\PostResource($post)
        ]);
    }

    public function deletePost(Request $request)
    {
        $userId = auth()->id();
        $post = Post::findOrFail($request->id);
        
        if ($post->provider_id !== $userId) {
            return response()->json(['status' => false, 'message' => __('messages.unauthorized')], 403);
        }
        
        $post->delete();
        
        return response()->json([
            'status' => true,
            'message' => __('messages.delete_form', ['form' => __('messages.post')])
        ]);
    }
}
