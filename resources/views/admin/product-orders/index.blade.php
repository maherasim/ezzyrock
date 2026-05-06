<x-master-layout>
    <div class="container-fluid">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="fw-bold mb-0">{{ __('messages.products') }} — Orders</h5>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <form method="get" class="row g-2 align-items-end mb-3">
                            <div class="col-md-3">
                                <label class="form-label small mb-0">User ID</label>
                                <input type="number" name="user_id" class="form-control form-control-sm" value="{{ request('user_id') }}" placeholder="Filter by user">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-0">Status</label>
                                <input type="text" name="status" class="form-control form-control-sm" value="{{ request('status') }}" placeholder="e.g. confirmed">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                                <a href="{{ route('admin.product-orders.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                            </div>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Order #</th>
                                        <th>{{ __('messages.products') }}</th>
                                        <th>{{ __('messages.customer') }}</th>
                                        <th>{{ __('messages.provider') }}</th>
                                        <th>{{ __('messages.total_amount') }}</th>
                                        <th>{{ __('messages.status') }}</th>
                                        <th>{{ __('messages.payment_status') }}</th>
                                        <th>{{ __('messages.date') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($orders as $order)
                                        @php
                                            $firstItem = $order->items->first();
                                            $providerUser = $firstItem?->product?->providers;
                                        @endphp
                                        <tr>
                                            <td>
                                                <a class="btn-link btn-link-hover" href="{{ route('admin.product-orders.show', $order) }}">#{{ $order->id }}</a>
                                            </td>
                                            <td>{{ $order->order_number }}</td>
                                            <td>@include('admin.product-orders.partials.table-products', ['order' => $order])</td>
                                            <td>@include('admin.product-orders.partials.table-customer', ['user' => $order->user])</td>
                                            <td>@include('admin.product-orders.partials.table-provider', ['provider' => $providerUser])</td>
                                            <td>{{ getPriceFormat($order->total ?? 0) }}</td>
                                            <td>{{ $order->status }}</td>
                                            <td>{{ $order->payment_status ?? '—' }}</td>
                                            <td>{{ $order->created_at }}</td>
                                            <td>
                                                <a href="{{ route('admin.product-orders.show', $order) }}" class="btn btn-sm btn-outline-primary">View</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center text-muted">No orders found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $orders->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>
