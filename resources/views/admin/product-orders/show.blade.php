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
                            <h5 class="fw-bold mb-0">Order {{ $productOrder->order_number }}</h5>
                            <a href="{{ route('admin.product-orders.index') }}" class="btn btn-sm btn-primary"><i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header">Items</div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Variant</th>
                                        <th class="text-end">Unit</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Line</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($productOrder->items as $line)
                                        <tr>
                                            <td>{{ $line->product_name }}</td>
                                            <td>{{ $line->variant_label ?: '—' }}</td>
                                            <td class="text-end">{{ getPriceFormat($line->unit_price) }}</td>
                                            <td class="text-end">{{ $line->quantity }}</td>
                                            <td class="text-end">{{ getPriceFormat($line->line_total) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header">Summary</div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Customer:</strong>
                            @if($productOrder->user)
                                {{ $productOrder->user->display_name ?? $productOrder->user->email }} (#{{ $productOrder->user_id }})
                            @else
                                #{{ $productOrder->user_id }}
                            @endif
                        </p>
                        <p class="mb-1"><strong>Subtotal:</strong> {{ getPriceFormat($productOrder->subtotal ?? 0) }}</p>
                        @if(isset($productOrder->tax_total))
                            <p class="mb-1"><strong>Tax:</strong> {{ getPriceFormat($productOrder->tax_total) }}</p>
                        @endif
                        <p class="mb-1"><strong>Total:</strong> {{ getPriceFormat($productOrder->total ?? 0) }}</p>
                        <p class="mb-1"><strong>Payment:</strong> {{ $productOrder->payment_type ?? '—' }} / {{ $productOrder->payment_status ?? '—' }}</p>
                        @if($productOrder->txn_id)
                            <p class="mb-0 small text-muted">Txn: {{ $productOrder->txn_id }}</p>
                        @endif
                    </div>
                </div>
                @php
                    $shipping = null;
                    if (!empty($productOrder->notes)) {
                        $decoded = json_decode($productOrder->notes, true);
                        $shipping = is_array($decoded) ? ($decoded['shipping'] ?? null) : null;
                    }
                @endphp
                @if(is_array($shipping))
                    <div class="card mb-3">
                        <div class="card-header">Shipping</div>
                        <div class="card-body small">
                            <p class="mb-1">{{ $shipping['name'] ?? '' }}</p>
                            <p class="mb-0">{{ $shipping['address'] ?? '' }}, {{ $shipping['city'] ?? '' }}, {{ $shipping['state'] ?? '' }} {{ $shipping['pincode'] ?? '' }}</p>
                            <p class="mb-0">{{ $shipping['country'] ?? '' }}</p>
                        </div>
                    </div>
                @endif
                <div class="card">
                    <div class="card-header">Update status</div>
                    <div class="card-body">
                        <form method="post" action="{{ route('admin.product-orders.update', $productOrder) }}">
                            @csrf
                            @method('PUT')
                            @php
                                $currentOrderStatus = (string) ($productOrder->status ?? 'pending');
                            @endphp
                            <div class="mb-2">
                                <label class="form-label">Order status</label>
                                <select name="status" class="form-select form-select-sm" required>
                                    @foreach($orderStatusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($currentOrderStatus === (string) $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @if(\Illuminate\Support\Facades\Schema::hasColumn('product_orders', 'payment_status'))
                                @php
                                    $currentPayment = (string) ($productOrder->payment_status ?? 'pending');
                                @endphp
                                <div class="mb-3">
                                    <label class="form-label">Payment status</label>
                                    <select name="payment_status" class="form-select form-select-sm" required>
                                        @foreach($paymentStatusLabels as $value => $label)
                                            <option value="{{ $value }}" @selected($currentPayment === (string) $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('messages.save') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>
