<?php $auth_user = authSession(); ?>
{{ html()->form('DELETE', route('product.destroy', $data->id))->attribute('data--submit', 'product'.$data->id)->open() }}
<div class="d-flex justify-content-end align-items-center">
    @if(!$data->trashed())
        @if($auth_user->can('product delete'))
        <a class="me-2" href="{{ route('product.destroy', $data->id) }}" data--submit="product{{$data->id}}" data--confirmation='true' data--ajax="true" data-datatable="reload" data-title="{{ __('messages.delete_form_title',['form'=> __('messages.product') ]) }}" title="{{ __('messages.delete_form_title',['form'=> __('messages.product') ]) }}" data-message='{{ __("messages.delete_msg") }}'>
            <i class="far fa-trash-alt text-danger"></i>
        </a>
        @endif
    @endif
    @if(auth()->user()->hasAnyRole(['admin','provider']) && $data->trashed())
        <a href="{{ route('product.action', ['id' => $data->id, 'type' => 'restore']) }}" title="{{ __('messages.restore_form_title', ['form' => __('messages.product')]) }}" data--submit="confirm_form" data--confirmation="true" data--ajax="true" data-title="{{ __('messages.restore_form_title', ['form' => __('messages.product')]) }}" data-message="{{ __('messages.restore_msg') }}" data-datatable="reload" class="me-2">
            <i class="fas fa-redo text-primary"></i>
        </a>
        <a href="{{ route('product.action',['id' => $data->id, 'type' => 'forcedelete']) }}" title="{{ __('messages.forcedelete_form_title',['form' => __('messages.product') ]) }}" data--submit="confirm_form" data--confirmation='true' data--ajax='true' data-title="{{ __('messages.forcedelete_form_title',['form'=> __('messages.product') ]) }}" data-message='{{ __("messages.forcedelete_msg") }}' data-datatable="reload" class="me-2">
            <i class="far fa-trash-alt text-danger"></i>
        </a>
    @endif
</div>
{{ html()->form()->close() }}
