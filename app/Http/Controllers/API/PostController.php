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
            ->with(['providers', 'category', 'subcategory', 'translations'])
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

        return response()->json([
            'status' => true,
            'data' => $items,
            'pagination' => $per_page !== 'all' && method_exists($items, 'total') ? [
                'total_items' => $items->total(),
                'per_page' => $items->perPage(),
                'currentPage' => $items->currentPage(),
                'totalPages' => $items->lastPage(),
            ] : null,
        ]);
    }

    public function getPostDetail(Request $request)
    {
        $id = $request->post_id ?? $request->id;
        $post = Post::with(['providers', 'category', 'subcategory', 'translations', 'zones', 'shops'])->find($id);
        if (!$post) {
            return response()->json(['status' => false, 'message' => __('messages.record_not_found')], 404);
        }
        return response()->json(['status' => true, 'data' => $post]);
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
