<?php

namespace App\Http\Controllers;

use App\Models\ProductOrder;

class UserProductOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->ensureCustomer();
        $orders = ProductOrder::query()
            ->where('user_id', auth()->id())
            ->withCount('items')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('landing-page.user-product-orders', compact('orders'));
    }

    public function show(ProductOrder $productOrder)
    {
        $this->ensureCustomer();
        abort_unless($productOrder->user_id === auth()->id(), 403);
        $productOrder->load(['items.product']);

        return view('landing-page.user-product-order-detail', compact('productOrder'));
    }

    private function ensureCustomer(): void
    {
        abort_unless(auth()->user()->user_type === 'user', 403);
    }
}
