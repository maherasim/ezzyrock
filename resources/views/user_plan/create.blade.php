<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="fw-bold">{{ $pageTitle ?? trans('messages.add_form_title', ['form' => trans('User Plan')]) }}</h5>
                            <a href="{{ route('user_plans.index') }}" class=" float-end btn btn-sm btn-primary"><i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        {{ html()->form('POST', route('user_plans.store'))->attribute('enctype', 'multipart/form-data')->attribute('data-toggle', 'validator')->id('user_plan')->open() }}
                        {{ html()->hidden('id', $plan->id ?? '') }}

                        <div class="row">
                            <div class="form-group col-md-4">
                                {{ html()->label(trans('messages.title') . ' <span class="text-danger">*</span>', 'title')->class('form-control-label') }}
                                {{ html()->text('title', $plan->title ?? '')->placeholder(trans('messages.title'))->class('form-control')->required() }}
                            </div>

                            <div class="form-group col-md-4">
                                {{ html()->label(trans('messages.type') . ' <span class="text-danger">*</span>', 'type')->class('form-control-label') }}
                                {{ html()->select('type', ['weekly' => __('messages.weekly'), 'monthly' => __('messages.monthly'), 'yearly' => __('messages.yearly')], $plan->type ?? '')->id('type')->class('form-select select2js')->required() }}
                            </div>

                            <div class="form-group col-md-4">
                                {{ html()->label(trans('messages.duration') . ' <span class="text-danger">*</span>', 'duration')->class('form-control-label') }}
                                {{ html()->select('duration', array_combine(range(1, 12), range(1, 12)), $plan->duration ?? 1)->id('duration')->class('form-select select2js')->required() }}
                            </div>

                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.amount') . ' <span class="text-danger">*</span>', 'amount')->class('form-control-label') }}
                                {{ html()->number('amount', $plan->amount ?? 0)->placeholder(__('messages.amount'))->class('form-control')->required()->attribute('step', 'any')->attribute('min', 0) }}
                            </div>

                            <div class="form-group col-md-4">
                                {{ html()->label(trans('messages.status') . ' <span class="text-danger">*</span>', 'status')->class('form-control-label') }}
                                {{ html()->select('status', ['1' => __('messages.active'), '0' => __('messages.inactive')], $plan->status ?? 1)->id('status')->class('form-select select2js')->required() }}
                            </div>

                            <div class="form-group col-md-4">
                                {{ html()->label(trans('messages.plan_limitation') . ' <span class="text-danger">*</span>', 'plan_type')->class('form-control-label') }}
                                <select class="form-select select2js" id="plan_limitation" name="plan_type">
                                    @foreach($plan_type as $value)
                                        <option value="{{ $value->value }}" data-type="{{ $value->value }}" {{ ($plan->plan_type ?? 'limited') == $value->value ? 'selected' : '' }}>{{ $value->label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        @if($is_in_app_purchase_enable)
                        <div class="row">
                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.playstore_identifier') . ' <span class="text-danger">*</span>', 'playstore_identifier')->class('form-control-label') }}
                                {{ html()->text('playstore_identifier', $plan->playstore_identifier ?? '')->placeholder(__('messages.playstore_identifier'))->class('form-control')->required() }}
                            </div>

                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.appstore_identifier') . ' <span class="text-danger">*</span>', 'appstore_identifier')->class('form-control-label') }}
                                {{ html()->text('appstore_identifier', $plan->appstore_identifier ?? '')->placeholder(__('messages.appstore_identifier'))->class('form-control')->required() }}
                            </div>
                        </div>
                        @endif

                        <div class="row">
                            <div class="form-group col-md-12">
                                {{ html()->label(__('messages.description'), 'description')->class('form-control-label') }}
                                {{ html()->textarea('description', $plan->description ?? '')->class('form-control textarea')->rows(3)->placeholder(__('messages.description')) }}
                            </div>
                        </div>

                        <div>
                            @php
                                $planValue = $plan->planlimit->plan_limitation ?? [];
                                $isChecked = $planValue['featured_classified']['is_checked'] ?? 'off';
                                $limitValue = $planValue['featured_classified']['limit'] ?? null;
                            @endphp

                            <div class="row show-checklist">
                                <div class="form-group col-md-6">
                                    <div class="custom-control custom-checkbox custom-control-inline">
                                        {{ html()->checkbox("plan_limitation[featured_classified][is_checked]", $isChecked === 'on', 'on')->class('custom-control-input checklist')->id("enable_featured_classified")->attribute('onClick', "showCheckLimitData('enable_featured_classified')") }}
                                        <label class="custom-control-label" for="enable_featured_classified">{{ __('messages.plan_limitations', ['keyword' => __('messages.featured_classified')]) }}</label>
                                    </div>
                                </div>
                                <div class="col-md-6 d-none enable_featured_classified" id="show-limit-0">
                                    <div class="form-group">
                                        {{ html()->label(__('messages.limit'), 'service_limit')->class('form-control-label') }}
                                        {{ html()->number("plan_limitation[featured_classified][limit]", $limitValue)->placeholder(__('messages.plan_limitations', ['keyword' => __('messages.featured_classified')]))->class('form-control')->attribute('min', 0)->attribute('step', 'any') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row d-none show_trial_period">
                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.trial_period'), 'trial_period')->class('form-control-label') }}
                                {{ html()->number('trial_period', $plan->trial_period ?? 0)->placeholder(__('messages.trial_period'))->class('form-control')->attribute('min', 0)->attribute('step', 'any') }}
                            </div>
                        </div>

                        {{ html()->submit(trans('messages.save_form', ['form' => trans('User Plan')]))->class('btn btn-md btn-primary float-end') }}
                        {{ html()->form()->close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @section('bottom_script')
    <script type="text/javascript">
        (function($) {
            $(document).ready(function() {
                $(".checklist:checkbox").each(function() {
                    if ($(this).is(":checked")) {
                        showCheckLimitData($(this).attr("id"));
                    }
                });

                var value = $("#plan_limitation option:selected").attr('data-type');
                showLimitCheckbox(value);

                $(document).on('change', '#plan_limitation', function() {
                    var data = $("#plan_limitation option:selected").attr('data-type');
                    showLimitCheckbox(data);
                });

                function showLimitCheckbox(type) {
                    if (type === 'limited') {
                        $('.show-checklist').removeClass('d-none');
                    } else {
                        $('.show-checklist').addClass('d-none');
                    }
                    if (type === 'free') {
                        $('.show_trial_period').removeClass('d-none');
                    } else {
                        $('.show_trial_period').addClass('d-none');
                    }
                }
            });
        })(jQuery);
    </script>
    @endsection
</x-master-layout>