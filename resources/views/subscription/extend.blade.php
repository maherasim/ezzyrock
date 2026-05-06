<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="fw-bold">{{ __('messages.update_form_title', ['form' => __('messages.plan')]) }} - {{ $targetUser->display_name }}</h5>
                            <a href="{{ url()->previous() }}" class="float-end btn btn-sm btn-primary"><i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.subscription.extend.store') }}">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $targetUser->id }}">

                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label class="form-control-label">{{ __('messages.user') }}</label>
                                    <input class="form-control" value="{{ $targetUser->display_name }} ({{ $targetUser->email }})" disabled>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-control-label">{{ __('messages.user_type') }}</label>
                                    <input class="form-control" value="{{ ucfirst($targetUser->user_type) }}" disabled>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-control-label">{{ __('messages.category') }}</label>
                                    <select id="module-select" name="module" class="form-select" required>
                                        @if($targetUser->user_type === 'provider')
                                            <option value="service" {{ $module === 'service' ? 'selected' : '' }}>Service</option>
                                            <option value="ecommerce" {{ $module === 'ecommerce' ? 'selected' : '' }}>Ecommerce</option>
                                        @else
                                            <option value="classified" {{ $module === 'classified' ? 'selected' : '' }}>Classified</option>
                                        @endif
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="form-group col-md-8">
                                    <label class="form-control-label">{{ __('messages.plan') }} <span class="text-danger">*</span></label>
                                    <select id="plan-select" name="plan_id" class="form-select" required>
                                        @if (empty($activePlanId))
                                            <option value="" selected disabled>— {{ __('messages.select_name', ['select' => __('messages.plan')]) }} —</option>
                                        @endif
                                        @foreach($plans as $plan)
                                            <option value="{{ $plan->id }}" @selected(!empty($activePlanId) && (int) $activePlanId === (int) $plan->id)>
                                                {{ $plan->title }} — {{ ucfirst($plan->type) }} — {{ getPriceFormat($plan->amount ?? 0) }}
                                                @if(!empty($activePlanId) && (int) $activePlanId === (int) $plan->id) (current)
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @if (!empty($activeForModule))
                                        <p class="small text-muted mt-2 mb-0">Active for this module: <strong>{{ $activeForModule->title }}</strong> until {{ $activeForModule->end_at }}. Choose a plan above to replace or extend.</p>
                                    @endif
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-control-label">{{ __('messages.payment_type') }}</label>
                                    <input type="text" name="payment_type" class="form-control" value="manual" placeholder="manual / cash / bank transfer">
                                </div>
                            </div>

                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label class="form-control-label">{{ __('messages.description') }}</label>
                                    <textarea name="notes" class="form-control" rows="3" placeholder="Optional admin note"></textarea>
                                </div>
                            </div>

                            @if ($plans->isEmpty())
                                <p class="text-warning small mb-2">No active plans exist for this module in Plan List. Create plans with the matching module first.</p>
                            @endif
                            <button type="submit" class="btn btn-primary" @if($plans->isEmpty()) disabled @endif>{{ __('messages.save') }}</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Active — {{ ucfirst($module) }}</h6>
                    </div>
                    <div class="card-body">
                        @forelse($activeSubscriptions as $sub)
                            <div class="border rounded p-2 mb-2">
                                <div><strong>{{ $sub->title }}</strong></div>
                                <div class="small text-muted">{{ ucfirst($sub->type) }}</div>
                                <div class="small">{{ $sub->start_at }} to {{ $sub->end_at }}</div>
                            </div>
                        @empty
                            <p class="mb-0 text-muted">No active subscription for this module.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        (function () {
            var sel = document.getElementById('module-select');
            if (!sel) return;
            sel.addEventListener('change', function () {
                var base = @json(route('admin.subscription.extend', ['user_id' => $targetUser->id]));
                var sep = base.indexOf('?') === -1 ? '?' : '&';
                window.location.href = base + sep + 'module=' + encodeURIComponent(sel.value);
            });
        })();
    </script>
</x-master-layout>
