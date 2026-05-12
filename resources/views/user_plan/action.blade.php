<?php
    $auth_user= authSession();
?>
{{ html()->form('DELETE', route('user_plans.destroy', $plan->id))->attribute('data--submit', 'user_plan'.$plan->id)->open() }}
<div class="d-flex justify-content-end align-items-center">
    @if(auth()->user()->hasAnyRole(['admin']))
        <a class="me-3" href="{{ route('user_plans.destroy', $plan->id) }}" data--submit="user_plan{{$plan->id}}"
            data--confirmation='true'
            data--ajax="true"
            data-datatable="reload"
            data-title="{{ __('messages.delete_form_title',['form'=>  __('messages.user_plan') ]) }}"
            title="{{ __('messages.delete_form_title',['form'=>  __('messages.user_plan') ]) }}"
            data-message='{{ __("messages.delete_msg") }}'>
            <i class="far fa-trash-alt text-danger"></i>
        </a>
    @endif
</div>
{{ html()->form()->close() }}