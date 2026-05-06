@extends('landing-page.layouts.default')

@section('content')
<div class="section-padding">
    <div class="container">
        <div class="mb-4">
            <a href="{{ route('user.product-orders') }}" class="btn btn-link p-0">← {{ __('messages.back') }}</a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <h3 class="text-capitalize mb-2">{{ __('messages.order_detail_products') }}</h3>
        <p class="text-body mb-4">
            <strong>{{ __('messages.order_number') }}:</strong> {{ $productOrder->order_number }}
            &nbsp;·&nbsp;
            <strong>{{ __('messages.order_date') }}:</strong> {{ $productOrder->created_at->format('Y-m-d H:i') }}
        </p>
        @php
            $orderNotes = is_array($productOrder->notes ?? null) ? $productOrder->notes : (json_decode((string) ($productOrder->notes ?? ''), true) ?: []);
            $shipping = $orderNotes['shipping'] ?? null;
        @endphp
        @if(!empty($shipping))
        <div class="alert alert-light border mb-4">
            @if(!empty($shipping['name']))
            <strong>Name:</strong> {{ $shipping['name'] }}<br>
            @endif
            <strong>Shipping Address:</strong>
            {{ $shipping['address'] ?? '' }},
            {{ $shipping['city'] ?? '' }},
            {{ $shipping['state'] ?? '' }} - {{ $shipping['pincode'] ?? '' }}
        </div>
        @endif

        <div class="table-responsive bg-white rounded-3 border">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('messages.product') }}</th>
                        <th>{{ __('messages.price') }}</th>
                        <th>{{ __('messages.quantity') }}</th>
                        <th>{{ __('messages.line_total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($productOrder->items as $line)
                        <tr>
                            <td>
                                @if ($line->product)
                                    <a href="{{ route('product.detail', $line->product_id) }}">{{ $line->product_name }}</a>
                                @else
                                    {{ $line->product_name }}
                                @endif
                            </td>
                            <td>{{ getPriceFormat($line->unit_price) }}</td>
                            <td>{{ $line->quantity }}</td>
                            <td>{{ getPriceFormat($line->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    @if ((float) ($productOrder->tax_total ?? 0) > 0)
                    <tr>
                        <th colspan="3" class="text-end">{{ __('messages.tax') }}</th>
                        <th>{{ getPriceFormat($productOrder->tax_total) }}</th>
                    </tr>
                    @endif
                    <tr>
                        <th colspan="3" class="text-end">{{ __('messages.grand_total') }}</th>
                        <th>{{ getPriceFormat($productOrder->total) }}</th>
                    </tr>
                    @if (!empty($productOrder->payment_type))
                    <tr>
                        <th colspan="3" class="text-end">{{ __('messages.payment_type') }}</th>
                        <th>{{ ucfirst(str_replace('_', ' ', $productOrder->payment_type)) }}</th>
                    </tr>
                    @endif
                    @if (!empty($productOrder->payment_status))
                    <tr>
                        <th colspan="3" class="text-end">{{ __('messages.payment_status') }}</th>
                        <th>{{ ucfirst(str_replace('_', ' ', $productOrder->payment_status)) }}</th>
                    </tr>
                    @endif
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
