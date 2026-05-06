<?php

namespace App\Http\Controllers;

use App\Models\ProductOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AdminProductOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('product list'), 403);
        if (! Schema::hasTable('product_orders')) {
            abort(404);
        }
        $q = ProductOrder::query()
            ->with([
                'user',
                'items' => static fn ($iq) => $iq->orderBy('id'),
                'items.product' => static fn ($pq) => $pq->withTrashed()->with('providers'),
            ])
            ->orderByDesc('id');
        if ($request->filled('user_id')) {
            $q->where('user_id', (int) $request->user_id);
        }
        if ($request->filled('status')) {
            $q->where('status', (string) $request->status);
        }
        $orders = $q->paginate(20)->withQueryString();

        return view('admin.product-orders.index', compact('orders'));
    }

    public function show(ProductOrder $productOrder)
    {
        abort_unless(auth()->user()->can('product list'), 403);
        if (! Schema::hasTable('product_orders')) {
            abort(404);
        }
        $productOrder->load(['user', 'items.product']);

        $orderStatusLabels = $this->orderStatusLabelsForOrder($productOrder);
        $paymentStatusLabels = $this->paymentStatusLabelsForOrder($productOrder);

        return view('admin.product-orders.show', compact('productOrder', 'orderStatusLabels', 'paymentStatusLabels'));
    }

    public function update(Request $request, ProductOrder $productOrder)
    {
        abort_unless(auth()->user()->can('product list'), 403);
        if (demoUserPermission()) {
            return redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        if (! Schema::hasTable('product_orders')) {
            abort(404);
        }
        $orderAllowed = array_keys($this->orderStatusLabelsForOrder($productOrder));
        $rules = [
            'status' => ['required', 'string', 'max:32', Rule::in($orderAllowed)],
        ];
        if (Schema::hasColumn('product_orders', 'payment_status')) {
            $paymentAllowed = array_keys($this->paymentStatusLabelsForOrder($productOrder));
            $rules['payment_status'] = ['required', 'string', 'max:32', Rule::in($paymentAllowed)];
        }
        $validated = $request->validate($rules);

        $productOrder->status = (string) $validated['status'];
        if (Schema::hasColumn('product_orders', 'payment_status')) {
            $productOrder->payment_status = (string) ($validated['payment_status'] ?? 'pending');
        }
        $productOrder->save();

        return redirect()
            ->route('admin.product-orders.show', $productOrder)
            ->with('success', __('messages.update_form', ['form' => 'Product order']));
    }

    /**
     * @return array<string, string> value => label
     */
    private function orderStatusLabelsForOrder(ProductOrder $order): array
    {
        $labels = [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'cancelled' => 'Cancelled',
        ];
        $current = (string) ($order->status ?? 'pending');
        if ($current !== '' && ! array_key_exists($current, $labels)) {
            $labels = [$current => $current . ' (legacy)'] + $labels;
        }

        return $labels;
    }

    /**
     * @return array<string, string> value => label
     */
    private function paymentStatusLabelsForOrder(ProductOrder $order): array
    {
        $labels = [
            'pending' => 'Pending',
            'paid' => 'Paid',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
        ];
        $current = (string) ($order->payment_status ?? '');
        if ($current !== '' && ! array_key_exists($current, $labels)) {
            $labels = [$current => $current . ' (legacy)'] + $labels;
        }

        return $labels;
    }
}
