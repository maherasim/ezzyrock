@extends('landing-page.layouts.default')

@section('content')
<div class="section-padding">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <h3 class="text-capitalize mb-0">Post {{ __('messages.subscription') }}</h3>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        @if($activeSubscription)
            <div class="alert alert-info mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <strong>Active:</strong> {{ $activeSubscription->title }}
                        ({{ ucfirst($activeSubscription->type) }}) -
                        {{ getPriceFormat($activeSubscription->amount) }}
                        <div class="small mt-1">
                            {{ $activeSubscription->start_at }} to {{ $activeSubscription->end_at }}
                        </div>
                    </div>
                    <form action="{{ route('user.subscriptions.cancel', $activeSubscription) }}" method="post"
                          onsubmit="return confirm('{{ __('messages.delete_msg') }}');">
                        @csrf
                        <button class="btn btn-sm btn-outline-danger" type="submit">Cancel</button>
                    </form>
                </div>
            </div>
        @endif

        <div class="row g-3 mb-4">
            @forelse($plans as $plan)
                @php
                    $limits = $plan->planlimit->plan_limitation ?? [];
                    if (is_string($limits)) {
                        $limits = json_decode($limits, true) ?: [];
                    }
                    $regularKey = 'classified';
                    $featuredKey = 'featured_classified';
                    $regularLimit = $limits[$regularKey]['limit'] ?? null;
                    $featuredLimit = $limits[$featuredKey]['limit'] ?? null;
                @endphp
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title mb-1">{{ $plan->title }}</h5>
                            <div class="text-body mb-2">{{ ucfirst($plan->type) }} · {{ getPriceFormat($plan->amount) }}</div>
                            <div class="small text-muted mb-2">{{ $plan->description }}</div>
                            <ul class="small ps-3 mb-3">
                                <li>Posts limit: {{ $regularLimit ?? 'Unlimited' }}</li>
                                <li>Featured posts: {{ $featuredLimit ?? 'Unlimited' }}</li>
                            </ul>
                            <form action="{{ route('user.subscriptions.store') }}" method="post" class="mt-auto">
                                @csrf
                                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                @php
                                    $stripeEnabled = \App\Models\PaymentGateway::query()->where('type', 'stripe')->where('status', 1)->exists();
                                    $razorpayEnabled = \App\Models\PaymentGateway::query()->where('type', 'razorPay')->where('status', 1)->exists();
                                    $phonepeEnabled = \App\Models\PaymentGateway::query()->where('type', 'phonepe')->where('status', 1)->exists();
                                    $paypalEnabled = \App\Models\PaymentGateway::query()->where('type', 'paypal')->where('status', 1)->exists();
                                    $paystackEnabled = \App\Models\PaymentGateway::query()->where('type', 'paystack')->where('status', 1)->exists();
                                    $flutterwaveEnabled = \App\Models\PaymentGateway::query()->where('type', 'flutterwave')->where('status', 1)->exists();
                                @endphp
                                <div class="mb-2">
                                    <select name="payment_method" class="form-select form-select-sm" required>
                                        @if($stripeEnabled)<option value="stripe">Stripe</option>@endif
                                        @if($razorpayEnabled)<option value="razorPay">Razorpay</option>@endif
                                        @if($phonepeEnabled)<option value="phonepe">PhonePe</option>@endif
                                        @if($paypalEnabled)<option value="paypal">PayPal</option>@endif
                                        @if($paystackEnabled)<option value="paystack">Paystack</option>@endif
                                        @if($flutterwaveEnabled)<option value="flutterwave">Flutterwave</option>@endif
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Subscribe</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-warning mb-0">{{ __('messages.no_record', ['form' => __('messages.plan')]) }}</div>
                </div>
            @endforelse
        </div>

        <div class="table-responsive bg-white rounded-3 border">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('messages.plan') }}</th>
                        <th>{{ __('messages.amount') }}</th>
                        <th>{{ __('messages.status') }}</th>
                        <th>{{ __('messages.start_at') }}</th>
                        <th>{{ __('messages.end_at') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $row)
                        <tr>
                            <td>{{ $row->title }}</td>
                            <td>{{ getPriceFormat($row->amount) }}</td>
                            <td>{{ ucfirst($row->status) }}</td>
                            <td>{{ $row->start_at }}</td>
                            <td>{{ $row->end_at }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">{{ __('messages.no_record', ['form' => __('messages.subscription')]) }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
