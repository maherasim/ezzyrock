<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1C1F34; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #F6F7F9; text-align: left; }
        th, td { padding: 10px 12px; border-bottom: 1px solid #E5E7EB; }
        .muted { color: #6C757D; }
        .right { text-align: right; }
        .box { background: #F6F7F9; padding: 12px; }
        .mb { margin-bottom: 18px; }
    </style>
</head>
<body>
@php
    $customer = $order->user;
    $provider = $order->items->map(fn ($item) => optional($item->product)->providers)->filter()->first();
    $shop = $order->items->map(fn ($item) => optional($item->product)->shops?->first())->filter()->first();
@endphp
    <div class="mb" style="border-bottom: 1px solid #D1D5DB; padding-bottom: 14px;">
        <div style="float: left;">
            <h2 style="margin: 0;">{{ env('APP_NAME') }}</h2>
            <div class="muted">Product Invoice</div>
        </div>
        <div class="right">
            <div><span class="muted">{{ __('messages.invoice_date') }}:</span> {{ optional($order->created_at)->format('Y-m-d') }}</div>
            <div><span class="muted">{{ __('messages.invoice_id') }}:</span> #{{ $order->order_number ?: $order->id }}</div>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div class="box mb">
        <strong>{{ __('messages.payment_info') }}:</strong>
        <span class="muted">{{ __('messages.payment_method') }}:</span> {{ ucfirst((string) $order->payment_type) ?: '-' }}
        <span class="muted" style="margin-left: 20px;">{{ __('messages.payment_status') }}:</span> {{ ucfirst((string) $order->payment_status) ?: '-' }}
    </div>

    <table class="mb">
        <tr>
            <td style="width: 33%; vertical-align: top;">
                <strong>{{ __('messages.customer') }}</strong><br>
                {{ optional($customer)->display_name ?: '-' }}<br>
                <span class="muted">{{ optional($customer)->email ?: '-' }}</span><br>
                <span class="muted">{{ optional($customer)->contact_number ?: '-' }}</span>
            </td>
            <td style="width: 33%; vertical-align: top;">
                <strong>{{ __('messages.provider') }}</strong><br>
                {{ optional($provider)->display_name ?: '-' }}<br>
                <span class="muted">{{ optional($provider)->email ?: '-' }}</span><br>
                <span class="muted">{{ optional($provider)->contact_number ?: '-' }}</span>
            </td>
            <td style="width: 34%; vertical-align: top;">
                <strong>{{ __('messages.shop') }}</strong><br>
                {{ optional($shop)->shop_name ?: '-' }}<br>
                <span class="muted">{{ optional($shop)->address ?: ($shipping['address'] ?? '-') }}</span>
            </td>
        </tr>
    </table>

    <table class="mb">
        <thead>
            <tr>
                <th>{{ __('messages.product') }}</th>
                <th>{{ __('messages.Price') }}</th>
                <th class="right">{{ __('messages.Qty') }}</th>
                <th class="right">{{ __('messages.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>
                        {{ $item->product_name }}
                        @if($item->variant_label)
                            <br><span class="muted">{{ $item->variant_label }}</span>
                        @endif
                    </td>
                    <td>{{ getPriceFormat($item->unit_price) }}</td>
                    <td class="right">{{ (int) $item->quantity }}</td>
                    <td class="right">{{ getPriceFormat($item->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table>
        <tr>
            <td class="right muted">{{ __('messages.sub_total') }}</td>
            <td class="right" style="width: 180px;">{{ getPriceFormat($charges['subtotal']) }}</td>
        </tr>
        @if($charges['shipping_charge'] > 0)
            <tr>
                <td class="right muted">Shipping / Delivery Charge</td>
                <td class="right">{{ getPriceFormat($charges['shipping_charge']) }}</td>
            </tr>
        @endif
        @if($charges['tax_total'] > 0)
            <tr>
                <td class="right muted">{{ __('messages.Tax') }}</td>
                <td class="right">{{ getPriceFormat($charges['tax_total']) }}</td>
            </tr>
        @endif
        <tr>
            <td class="right"><strong>{{ __('messages.grand_total') }}</strong></td>
            <td class="right"><strong>{{ getPriceFormat($charges['grand_total']) }}</strong></td>
        </tr>
    </table>
</body>
</html>
