{{ html()->form('DELETE', route('free-post-settings.destroy', $freePostSetting->id))->attribute('data--submit', 'free_post_setting' . $freePostSetting->id)->open() }}
<div class="d-flex justify-content-end align-items-center">
    @if(auth()->user()->hasAnyRole(['admin']))
        <a class="me-3" href="{{ route('free-post-settings.index', ['id' => $freePostSetting->id]) }}" title="{{ __('messages.edit') }}">
            <i class="far fa-edit text-primary"></i>
        </a>
        <a class="me-3" href="{{ route('free-post-settings.destroy', $freePostSetting->id) }}" data--submit="free_post_setting{{ $freePostSetting->id }}"
            data--confirmation="true"
            data--ajax="true"
            data-datatable="reload"
            data-title="Delete Free Post Setting"
            title="Delete Free Post Setting"
            data-message="{{ __('messages.delete_msg') }}">
            <i class="far fa-trash-alt text-danger"></i>
        </a>
    @endif
</div>
{{ html()->form()->close() }}
