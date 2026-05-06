<?php $auth_user = authSession(); ?>
{{ html()->form('DELETE', route('product-review.destroy', $row->id))->attribute('data--submit', 'productreview' . $row->id)->open() }}
<div class="d-flex justify-content-end align-items-center">
    @if(!$row->trashed())
    <a class="me-2 text-danger" href="javascript:void(0)" data--submit="productreview{{$row->id}}"
        data--confirmation='true' data-title="Delete review"
        title="Delete review"
        data-message='{{ __("messages.delete_msg") }}'>
        <i class="far fa-trash-alt"></i>
    </a>
    @endif
</div>
{{ html()->form()->close() }}
