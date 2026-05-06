@extends('landing-page.layouts.default')

@section('content')
<div class="section-padding">
    <div class="container">
        <h3 class="text-capitalize mb-4">{{ __('messages.product_orders') }}</h3>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($orders->isEmpty())
            <p class="text-body">{{ __('messages.no_record_found') }}</p>
        @else
            <div class="table-responsive bg-white rounded-3 border">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('messages.order_number') }}</th>
                            <th>{{ __('messages.order_date') }}</th>
                            <th>{{ __('messages.items') }}</th>
                            <th>{{ __('messages.grand_total') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td class="fw-500">{{ $order->order_number }}</td>
                                <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                                <td>{{ $order->items_count }}</td>
                                <td>{{ getPriceFormat($order->total) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('user.product-order.show', $order) }}" class="btn btn-sm btn-outline-primary">{{ __('messages.view') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
@endsection
