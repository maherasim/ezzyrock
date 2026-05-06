<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class ProductReviewController extends Controller
{
    public function store(Request $request, int $productId)
    {
        abort_unless(auth()->check() && auth()->user()->user_type === 'user', 403);

        $product = Product::query()
            ->where('id', $productId)
            ->where('service_type', 'ecommerce')
            ->where('status', 1)
            ->where('service_request_status', 'approve')
            ->firstOrFail();

        $data = $request->validate([
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        ProductReview::query()->updateOrCreate(
            [
                'product_id' => $product->id,
                'user_id' => auth()->id(),
            ],
            [
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
                'status' => 1,
            ]
        );

        return redirect()->route('product.detail', $product->id)->withSuccess(__('messages.save_form', ['form' => __('messages.review')]));
    }

    public function index()
    {
        $pageTitle = trans('messages.list_form_title', ['form' => 'Product reviews']);
        $auth_user = authSession();
        $assets = ['datatable'];

        return view('product-review.index', compact('pageTitle', 'auth_user', 'assets'));
    }

    public function index_data(DataTables $datatable)
    {
        $query = ProductReview::query()->with(['product', 'user']);
        if (auth()->user()->hasAnyRole(['admin'])) {
            $query->withTrashed();
        }

        return $datatable->eloquent($query)
            ->editColumn('product_id', function ($row) {
                return optional($row->product)->name ?? '-';
            })
            ->filterColumn('product_id', function ($query, $keyword) {
                $query->whereHas('product', function ($q) use ($keyword) {
                    $q->where('name', 'like', '%' . $keyword . '%');
                });
            })
            ->editColumn('user_id', function ($row) {
                return optional($row->user)->display_name ?? optional($row->user)->email ?? '-';
            })
            ->filterColumn('user_id', function ($query, $keyword) {
                $query->whereHas('user', function ($q) use ($keyword) {
                    $q->where('display_name', 'like', '%' . $keyword . '%')
                        ->orWhere('email', 'like', '%' . $keyword . '%');
                });
            })
            ->editColumn('rating', function ($row) {
                return number_format((float) $row->rating, 1) . ' <i class="ri-star-line"></i>';
            })
            ->addColumn('action', function ($row) {
                return view('product-review.action', compact('row'))->render();
            })
            ->addIndexColumn()
            ->rawColumns(['rating', 'action'])
            ->toJson();
    }

    public function destroy(int $id)
    {
        if (demoUserPermission()) {
            return redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }

        $review = ProductReview::query()->findOrFail($id);
        $review->delete();

        return redirect()->back()->withSuccess(__('messages.msg_deleted', ['name' => __('messages.review')]));
    }
}
