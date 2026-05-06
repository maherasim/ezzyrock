<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="fw-bold">{{ $pageTitle ?? __('messages.list') }}</h5>
                            <a href="{{ route('post.index') }}"
                                class="float-end btn btn-sm btn-primary">
                                <i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}
                            </a>
                            @if ($auth_user->can('post list'))
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        {{ html()->form('POST', route('post.store'))->attribute('enctype', 'multipart/form-data')->attribute('data-toggle', 'validator')->id('post')->open() }}
                        {{ html()->hidden('id', $postdata->id ?? null) }}

                        @include('partials._language_toggale')
                        @foreach($language_array as $language)
                        <div id="form-language-{{ $language['id'] }}" class="language-form" style="display: {{ $language['id'] == app()->getLocale() ? 'block' : 'none' }};">
                            <div class="row">
                                @foreach(['name' => __('messages.name'), 'description' => __('messages.description')] as $field => $label)
                                <div class="form-group col-md-{{ $field === 'name' ? '4' : '12' }}">
                                    {{ html()->label($label . ($field === 'name' ? ' <span class="text-danger">*</span>' : ''), $field)->class('form-control-label language-label') }}
                                    @php
                                        $value = $language['id'] == 'en'
                                            ? $postdata ? $postdata->translate($field, 'en') : ''
                                            : ($postdata ? $postdata->translate($field, $language['id']) : '');
                                        $name = $language['id'] == 'en' ? $field : "translations[{$language['id']}][$field]";
                                    @endphp

                            @if($field === 'name')
    {{ html()->text($name, $value)
        ->placeholder($label)
        ->class('form-control')
        ->attribute('title', 'Please enter alphabetic characters and spaces only')
        ->attribute('data-required', 'true') }}
@elseif($field === 'description')
    {{ html()->textarea($name, $value)
        ->class('form-control textarea description-field')
        ->attribute('maxlength', 250)
        ->rows(3)
        ->placeholder($label)
        ->attribute('data-lang', $language['id']) }}

    <small class="text-muted">
        <span class="char-count" id="char-count-{{ $language['id'] }}">{{ strlen($value ?? '') }}</span>/250
    </small>
@endif

                                    <small class="help-block with-errors text-danger"></small>
                                </div>
                                @endforeach

                                <!-- Category Selection -->
                                <div class="form-group col-md-4">
                                    {{ html()->label(__('messages.select_name', ['select' => __('messages.category')]) . ' <span class="text-danger">*</span>', 'category_id')->class('form-control-label') }}
                                    <select name="category_id"
                                            id="category_id_{{ $language['id'] }}"
                                            class="form-select select2js-category"
                                            data-select2-type="category"
                                            data-selected-id="{{ $postdata->category_id ?? '' }}"
                                            data-language-id="{{ $language['id'] }}"
                                            data-ajax--url="{{ route('ajax-list', ['type' => 'category', 'language_id' => $language['id'], 'module_type' => 'classified']) }}"
                                            data-placeholder="{{ __('messages.select_name', ['select' => __('messages.category')]) }}">
                                        </select>
                                        <small class="help-block with-errors text-danger"></small>
                                    </div>

                                    <!-- SubCategory Selection -->
                                    <div class="form-group col-md-4">
                                        {{ html()->label(__('messages.select_name', ['select' => __('messages.subcategory')]), 'category_id')->class('form-control-label') }}
                                        <select name="subcategory_id" id="subcategory_id_{{ $language['id'] }}"
                                            class="form-select select2js-subcategory subcategory_id"
                                            data-select2-type="subcategory"
                                            data-selected-id="{{ $postdata->subcategory_id ?? '' }}"
                                            data-language-id="{{ $language['id'] }}"
                                            data-ajax--url="{{ route('ajax-list', ['type' => 'subcategory', 'category_id' => $postdata->category_id ?? '', 'language_id' => $language['id']]) }}"
                                            data-placeholder="{{ __('messages.select_name', ['select' => __('messages.subcategory')]) }}">
                                        </select>
                                        <small class="help-block with-errors text-danger"></small>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <div class="row">
                            <!-- <div class="form-group col-md-4">
                                {{ html()->label(__('messages.name') . ' <span class="text-danger">*</span>', 'name')->class('form-control-label') }}
                                {{ html()->text('name', $postdata->name)->placeholder(__('messages.name'))->class('form-control')->attributes(['title' => 'Please enter alphabetic characters and spaces only']) }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>

                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.select_name', ['select' => __('messages.category')]) . ' <span class="text-danger">*</span>', 'name')->class('form-control-label') }}
                                <br />
                                {{ html()->select(
                                        'category_id',
                                        [optional($postdata->category)->id => optional($postdata->category)->name],
                                        optional($postdata->category)->id,
                                    )->class('select2js form-group category')->required()->id('category_id')->attribute('data-placeholder', __('messages.select_name', ['select' => __('messages.category')]))->attribute('data-ajax--url', route('ajax-list', ['type' => 'category', 'module_type' => 'classified'])) }}

                            </div>
                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.select_name', ['select' => __('messages.subcategory')]), 'subcategory_id')->class('form-control-label') }}
                                <br />
                                {{ html()->select('subcategory_id', [])->class('select2js form-group subcategory_id')->attribute('data-placeholder', __('messages.select_name', ['select' => __('messages.subcategory')])) }}
                            </div> -->

                            <input type="hidden" name="provider_id" id="provider_id"
                                value="{{ $postdata->provider_id ?? auth()->id() }}">
                            @php
                                $defaultVisitType = old('visit_type', $postdata->visit_type ?? '');
                                if ($defaultVisitType === '') {
                                    $defaultVisitType = array_key_first($visittype ?? []) ?: 'ON_SITE';
                                }
                            @endphp
                            <input type="hidden" name="type" value="{{ old('type', $postdata->type ?? 'fixed') }}">
                            <input type="hidden" name="discount" value="{{ old('discount', $postdata->discount ?? 0) }}">
                            <input type="hidden" name="duration" value="{{ old('duration', $postdata->duration ?? '') }}">
                            <input type="hidden" name="visit_type" id="visit_type" value="{{ $defaultVisitType }}">

                            <!-- Zone Selection -->
                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.select_name', ['select' => __('messages.zone')]) . ' <span class="text-danger">*</span>', 'name')->class('form-control-label') }}
                                <br />
                                {{ html()->select('service_zones[]', [], old('service_zones', $selectedZones ?? []))->class('select2js form-group zone_id')->id('service_zones')->multiple()->required()->attribute('data-placeholder', __('messages.select_name', ['select' => __('messages.zone')])) }}
                            </div>

                            <div class="form-group col-md-4" id="price_div">
                                {{ html()->label(__('messages.price') . ' <span class="text-danger">*</span>', 'price')->class('form-control-label') }}
                                {{ html()->text('price', old('price', $postdata->price))->attributes(['min' => 1, 'step' => 'any', 'pattern' => '^\\d+(\\.\\d{1,2})?$'])->placeholder(__('messages.price'))->class('form-control')->required()->id('price') }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>

                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.status') . ' <span class="text-danger">*</span>', 'status')->class('form-control-label') }}
                                {{ html()->select('status', ['1' => __('messages.active'), '0' => __('messages.inactive')], $postdata->status)->class('form-select select2js')->required() }}
                            </div>

                            <!-- File Input -->
                        <div class="form-group col-md-4">
                            <label class="form-control-label" for="post_attachment">
                                {{ __('messages.image') }} <span class="text-danger">*</span>
                            </label>
                            <div class="custom-file">
                                <input type="file"
                                    name="post_attachment[]"
                                    class="custom-file-input"
                                    id="post_attachment_input"
                                    onchange="previewServiceImage(event)"
                                    accept="image/*"
                                    @if(!getMediaFileExit($postdata, 'post_attachment')) required @endif
                                     multiple>
                                <label class="custom-file-label upload-label" id="post_attachment_label">
                                    {{ $postdata && getMediaFileExit($postdata, 'post_attachment')
                                        ? $postdata->getFirstMedia('post_attachment')->file_name
                                        : __('messages.choose_file',['file' => __('messages.image')]) }}
                                </label>
                            </div>
                        </div>
                        <div id="post_attachment_preview_container" class="d-flex flex-wrap">
                            @if(getMediaFileExit($postdata, 'post_attachment'))
                                @foreach($postdata->getMedia('post_attachment') as $media)
                                    <div class="col-md-2 mb-2">
                                        <div class="image-preview-container ">
                                            <img id="post_attachment_preview_{{$media->id}}" src="{{ $media->getUrl() }}"
                                                alt="Image preview"
                                                class="attachment-image mt-1"
                                                style="width:150px;  {{ $media->getUrl()  ? '' : 'display:none;' }}">
                                            <a class="text-danger remove-file" id="removeButton"
                                                    href="{{ route('remove.file', ['id' => $media->id, 'type' => 'post_attachment']) }}"
                                                    data--submit="confirm_form" data--confirmation='true'
                                                    data--ajax="true" data-toggle="tooltip"
                                                    title='{{ __("messages.remove_file_title" , ["name" =>  __("messages.attachments") ] ) }}'
                                                    data-title='{{ __("messages.remove_file_title" , ["name" =>  __("messages.attachments") ] ) }}'
                                                    data-message='{{ __("messages.remove_file_msg") }}'
                                                    style="{{ $media->getUrl() ? 'display: inline;' : 'display: none;' }}" >
                                                    <i class="ri-close-circle-line"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        <!-- Preview + Remove -->
                        {{-- <div class="col-md-2 mb-2">
                            <div class="image-preview-container">
                                <img id="post_attachment_preview"
                                    src="{{ getMediaFileExit($postdata, 'post_attachment')
                                        ? getSingleMedia($postdata, 'post_attachment') : '' }}"
                                    alt="Image preview"
                                    class="attachment-image mt-1"
                                    style="width:150px; {{ getMediaFileExit($postdata, 'post_attachment') ? '' : 'display:none;' }}">
                                <a class="text-danger remove-file"
                                id="removeServiceAttachmentBtn"
                                href="javascript:void(0);"
                                style="{{ getMediaFileExit($postdata, 'post_attachment') ? 'display:inline;' : 'display:none;' }}">
                                    <i class="ri-close-circle-line"></i>
                                </a>
                            </div>
                        </div> --}}



                        </div>

                        <!-- SEO Enable/Disable Switch -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <!-- @php
                                            $seoEnabled = !empty($postdata->meta_title)
                                                || !empty($postdata->meta_description)
                                                || !empty($postdata->meta_keywords)
                                                || !empty($postdata->slug)
                                        @endphp -->
                                        {{ html()->checkbox('seo_enabled', $postdata->seo_enabled)->class('custom-control-input')->id('seo_enabled') }}
                                        <label class="custom-control-label" for="seo_enabled">{{ __('messages.set_seo') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SEO Fields Section for this language -->
                        <!-- SEO Fields Section for this language -->
                        <div id="seo_fields_section" style="{{ isset($postdata->seo_enable) && $postdata->seo_enable ? '' : 'display:none;' }}">
                            <div class="row">
                                <div class="form-group col-md-6 mb-3">
                                    {{ html()->label(__('messages.seo_image'), 'seo_image')->class('form-control-label') }}
                                    <div class="custom-file">
                                    @php
                                        $seoImageUrl = (isset($postdata->id) && getMediaFileExit($postdata, 'seo_image')) ? $postdata->getFirstMediaUrl('seo_image') : '';
                                        $seoImageHas = !empty($seoImageUrl) ? '1' : '0';
                                    @endphp
                                    <input type="file" name="seo_image" class="custom-file-input" id="seo_image"
                                        accept=".jpg,.jpeg,.png"
                                        onchange="previewSeoImage(event)"
                                        data-has-image="{{ $seoImageHas }}"

                                        >
                                        <label class="custom-file-label upload-label">{{ __('messages.choose_file', ['file' => __('messages.seo_image')]) }}</label>
                                    </div>
                                    <small id="seo_image_error" class="text-danger"></small> <!-- Error message container -->
                                    <small class="text-muted d-block mt-1">{{ __('messages.only_jpg_png_jpeg_allowed') }}</small> <!-- Note for allowed image types -->


                                    <img id="seo_image_preview" src="{{ $seoImageUrl }}" alt="SEO Image Preview" style="max-width: 100px; margin-top: 10px; @if(empty($seoImageUrl)) display: none; @endif" />
                                </div>
                                @foreach ($language_array as $language)
                                    <div id="seo-form-language-{{ $language['id'] }}" class="language-form" style="display: {{ $language['id'] == app()->getLocale() ? 'block' : 'none' }};">

                                        {{-- Meta Title --}}
                                        <div class="form-group col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                {{ html()->label(__('messages.meta_title') . ' <span class="text-danger">*</span>')->class('form-control-label language-label') }}
                                                <span class="text-muted" style="font-size: 12px;">
                                                    @php
                                                        $metaTitleVal = $language['id'] === 'en'
                                                            ? ($postdata->meta_title ?? '')
                                                            : ($postdata->translate('meta_title', $language['id']) ?? '');
                                                    @endphp
                                                    <span id="meta-title-count-{{ $language['id'] }}">{{ strlen((string) $metaTitleVal) }}</span>/100
                                                </span>
                                            </div>
                                            @php
                                                $metaTitleName = $language['id'] === 'en'
                                                    ? 'meta_title'
                                                    : "translations[{$language['id']}][meta_title]";
                                            @endphp
                                            <input
                                                type="text"
                                                name="{{ $metaTitleName }}"
                                                id="meta_title_{{ $language['id'] }}"
                                                class="form-control"
                                                maxlength="100"
                                                placeholder="{{ __('messages.enter_meta_title') }}"
                                                value="{{ $metaTitleVal }}"
                                                data-lang="{{ $language['id'] }}"
                                                data-required="true"
                                            >
                                            <small class="help-block with-errors text-danger"></small>
                                        </div>

                                        {{-- Meta Keywords --}}
                                        <div class="form-group col-md-6 mb-3">
                                            {{ html()->label(__('messages.meta_keywords') . ' <span class="text-danger">*</span>', "meta_keywords_{$language['id']}")->class('form-control-label language-label') }}
                                            @php
                                                $metaKeywordsVal = $language['id'] === 'en'
                                                    ? (is_array($postdata->meta_keywords) ? implode(',', $postdata->meta_keywords) : ($postdata->meta_keywords ?? ''))
                                                    : ($postdata->translate('meta_keywords', $language['id']) ?? '');

                                                $metaKeywordsName = $language['id'] === 'en'
                                                    ? 'meta_keywords'
                                                    : "translations[{$language['id']}][meta_keywords]";
                                            @endphp
                                            <input
                                                type="text"
                                                name="{{ $metaKeywordsName }}"
                                                id="meta_keywords_{{ $language['id'] }}"
                                                class="form-control tagify-input"
                                                value="{{ $metaKeywordsVal }}"
                                                placeholder="{{ __('messages.type_and_press_enter') }}"
                                                data-lang="{{ $language['id'] }}"
                                                data-required="true"
                                            >
                                            <small class="help-block with-errors text-danger"></small>
                                            <small class="text-muted">{{ __('messages.type_and_press_enter') }}</small>
                                        </div>

                                        {{-- Meta Description --}}
                                        <div class="form-group col-12 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                {{ html()->label(__('messages.meta_description') . ' <span class="text-danger">*</span>', "meta_description_{$language['id']}")->class('form-control-label language-label') }}
                                                <span class="text-muted" style="font-size: 12px;">
                                                    @php
                                                        $metaDescVal = $language['id'] === 'en'
                                                            ? ($postdata->meta_description ?? '')
                                                            : ($postdata->translate('meta_description', $language['id']) ?? '');
                                                    @endphp
                                                    <span id="meta-desc-count-{{ $language['id'] }}">{{ strlen((string) $metaDescVal) }}</span>/200
                                                </span>
                                            </div>
                                            @php
                                                $metaDescName = $language['id'] === 'en'
                                                    ? 'meta_description'
                                                    : "translations[{$language['id']}][meta_description]";
                                            @endphp
                                            <textarea
                                                name="{{ $metaDescName }}"
                                                id="meta_description_{{ $language['id'] }}"
                                                class="form-control"
                                                rows="4"
                                                maxlength="200"
                                                placeholder="{{ __('messages.enter_meta_description') }}"
                                                style="min-height: 120px; resize: vertical;"
                                                data-lang="{{ $language['id'] }}"
                                                data-required="true"
                                            >{{ $metaDescVal }}</textarea>
                                            <small class="help-block with-errors text-danger"></small>
                                        </div>

                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="row">
                            <!-- <div class="form-group col-md-12">
                                    {{ html()->label(__('messages.description'), 'description')->class('form-control-label') }}
                                    {{ html()->textarea('description', $postdata->description)->class('form-control textarea')->rows(3)->placeholder(__('messages.description')) }}
                                </div> -->
                            <div class="form-group col-md-3">
                                <div class="custom-control custom-switch">
                                    {{ html()->checkbox('is_featured', $postdata->is_featured)->class('custom-control-input')->id('is_featured') }}
                                    <label class="custom-control-label"
                                        for="is_featured">{{ __('messages.set_as_featured') }}</label>
                                </div>
                            </div>
                            <!-- @if (!empty($digitalservicedata) && $digitalservicedata->value == 1)
<div class="form-group col-md-3">
                                <div class="custom-control custom-switch">
                                    {{ Form::checkbox('digital_service', $postdata->digital_service, null, ['class' => 'custom-control-input', 'id' => 'digital_service']) }}
                                    <label class="custom-control-label"
                                        for="digital_service">{{ __('messages.digital_service') }}</label>
                                </div>
                            </div>
@endif -->
                            @if (isset($postdata->service_request_status) &&
                                    $postdata->service_request_status == 'reject' &&
                                    !empty($postdata->reject_reason))
                                <div class="form-group col-md-12 d-flex align-items-center">
                                    <label class="form-control-label mb-0 me-2 text-danger" for="reason">
                                        {{ __('messages.reason') }}:
                                    </label>
                                    <span>{{ $postdata->reject_reason }}</span>
                                </div>
                            @endif
                        </div>


                        @if (auth()->user()->hasAnyRole(['admin', 'demo_admin']) &&
                                isset($postdata) &&
                                $postdata->is_service_request == 1 &&
                                (is_null($postdata->service_request_status) || $postdata->service_request_status == 'pending'))
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-sm btn-light text-dark float-end"
                                    onclick="showRejectionConfirmation('{{ $postdata->id }}', 'rejected')">Reject</button>
                                <button type="button" class="btn btn-sm btn-primary float-end me-3"
                                    onclick="showApprovalConfirmation('{{ $postdata->id }}', 'approved')">Approve</button>
                            </div>
                        @elseif(auth()->user()->hasAnyRole(['admin', 'demo_admin']) &&
                                isset($postdata->is_service_request) &&
                                ($postdata->is_service_request == 1 || is_null($postdata->is_service_request)) &&
                                $postdata->service_request_status == 'reject')
                        @else
                            {{ html()->submit(__('messages.save'))->class('btn btn-md btn-primary float-end')->id('saveButton') }}
                        @endif
                        {{ html()->form()->close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @php
        $addrMappings = $postdata->id
            ? $postdata->providerPostAddress
            : collect();
        $data = $addrMappings->pluck('provider_address_id')->filter()->implode(',');
    @endphp
    @section('bottom_script')
        <script type="text/javascript">

        $(document).on('change', 'input[name="post_attachment[]"]', function () {
            if (this.files.length > 0) {
                $('input[name="post_attachment[]"]').removeAttr('required');
                $('#saveButton').prop('disabled', false);
            }
        });
            function preview() {
                var fileInput = event.target;
                var previewElement = document.getElementById('post_attachment_preview');
                if (fileInput.files && fileInput.files[0]) {
                    previewElement.src = URL.createObjectURL(fileInput.files[0]);
                    previewElement.style.display = 'block';
                } else {
                    previewElement.style.display = 'none';
                }
            }
            function previewSeoImage(event) {
                const preview = document.getElementById('seo_image_preview');
                const file = event.target.files[0];
                if (preview && file) {
                    preview.src = URL.createObjectURL(file);
                    preview.style.display = 'block';
                }
            }
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof renderedDataTable === 'undefined') {
                    renderedDataTable = $('#datatable').DataTable();
                }

                var initialProviderId = document.getElementById('provider_id').value;
                selectprovider({
                    value: initialProviderId
                });

                  const textareas = document.querySelectorAll('.description-field');

        textareas.forEach(function (textarea) {
            textarea.addEventListener('input', function () {
                const langId = textarea.getAttribute('data-lang');
                const countSpan = document.getElementById('char-count-' + langId);

                if (countSpan) {
                    countSpan.textContent = textarea.value.length;
                }
            });
        });


                 const addLink = document.getElementById('add_provider_address_link');

    if (addLink) {
        addLink.addEventListener('click', function(event) {
            event.preventDefault();

            const providerId = document.getElementById('provider_id').value;
            let providerAddressCreateUrl = "{{ route('provideraddress.create', ['provideraddress' => '']) }}";

            providerAddressCreateUrl = providerAddressCreateUrl.replace('provideraddress=',
                'provideraddress=' + providerId);

            window.location.href = providerAddressCreateUrl;
        });
    }

            });

            function updateServiceStatus(serviceId, status, reason = '') {
                $.ajax({
                    url: '{{ route('post.updateStatus') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: serviceId,
                        status: status,
                        reason: reason
                    },
                    success: function(response) {
                        if (response.success) {
                            if (status === 'approved') {
                                window.location.href = '{{ route('post.index') }}';
                            } else {
                                var badge = '<span class="badge badge-danger">Rejected</span>';
                                var row = $('#datatable-row-' + serviceId);
                                row.find('.service-status').html(badge);
                                window.location.href = '{{ route('post.index') }}';
                                renderedDataTable.ajax.reload();
                            }
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred while updating the status.',
                                icon: 'error',
                                confirmButtonText: 'Try Again'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while processing the request.',
                            icon: 'error',
                            confirmButtonText: 'Try Again'
                        });
                    }
                });
            }

            function showApprovalConfirmation(serviceId, status) {
                Swal.fire({
                    icon: 'success',
                    title: '',
                    html: '<span style="color: #333; font-weight: 550; font-size: 20px;">' +
                        '{{ __('messages.are_you_sure_you_want_to') }} ' +
                        (status === "approved" ?
                            '{{ __('messages.approve_this_service_into_list') }}' :
                            '{{ __('messages.reject_this_service_into_list') }}') +
                        '</span>',
                    showCancelButton: true,
                    cancelButtonText: '<span style="color: black; font-weight: 500;">{{ __('messages.cancel') }}</span>', // Black text, medium weight
                    confirmButtonText: '{{ __('messages.approve') }}',
                    confirmButtonColor: '#6366F1',
                    cancelButtonColor: '#E5E7EB',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        updateServiceStatus(serviceId, status);
                    }
                });
            }

            function showRejectionConfirmation(serviceId) {
                Swal.fire({
                    title: `<h2 style="font-size: 20px; font-weight: bold; margin-bottom: 15px;">{{ __('messages.reject_service_confirmation_title') }}</h2>`,
                    text: '{{ __('messages.provide_rejection_reason') }}',
                    html: `
                    <div style="text-align: left; margin-top: 5px; background-color: #f0f0f0; padding: 20px; border-radius: 10px;">
                        <label for="reject-reason" style="font-size: 14px; font-weight: bold; display: block; margin-bottom: 5px;">
                            Provide the reason for rejection
                        </label>
                        <textarea id="reject-reason" placeholder="e.g. Insufficient details"
                            style="width: 100%; height: 100px; background-color: #ffffff; border: 1px solid #ccc;
                            border-radius: 8px; padding: 10px; font-size: 14px; resize: none;"></textarea>
                    </div>
                    `,
                    icon: 'error',
                    inputAttributes: {
                        'aria-label': '{{ __('messages.rejection_reason_aria') }}'
                    },
                    showCancelButton: true,
                    confirmButtonText: '<span style="font-size: 14px; font-weight: bold;">{{ __('messages.reject') }}</span>',
                    cancelButtonText: '<span style="font-size: 14px; font-weight: bold; color: black;">{{ __('messages.cancel') }}</span>',
                    cancelButtonColor: '#f0f0f0',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        var rejectionReason = document.getElementById('reject-reason').value;
                        if (rejectionReason.trim() !== "") {
                            updateServiceStatus(serviceId, 'rejected', rejectionReason);
                        } else {
                            Swal.fire({
                                title: '{{ __('messages.error') }}',
                                text: '{{ __('messages.rejection_reason_required') }}',
                                icon: 'error',
                                confirmButtonText: '{{ __('messages.okay') }}'
                            });
                        }
                    }
                });

            }

            function selectprovider(selectElement) {
                var providerId = selectElement && selectElement.value !== undefined ? selectElement.value : $('#provider_id').val();
                var zoneDropdown = $('#service_zones');

                // Classified posts: list all active zones (matches user panel). for_classified_post skips provider_zone_mappings filter.
                $.ajax({
                    url: "{{ route('ajax-list', ['type' => 'zone']) }}",
                    data: {
                        provider_id: providerId || '',
                        for_classified_post: 1
                    },
                    success: function(result) {
                        zoneDropdown.empty();

                        if (result.results && result.results.length > 0) {
                            $.each(result.results, function(index, item) {
                                var option = new Option(item.text, item.id, false, false);
                                zoneDropdown.append(option);
                            });
                        }

                        if (zoneDropdown.hasClass('select2-hidden-accessible')) {
                            zoneDropdown.select2('destroy');
                        }
                        zoneDropdown.select2({
                            width: '100%',
                            placeholder: "{{ trans('messages.select_name', ['select' => trans('messages.zone')]) }}",
                            allowClear: true
                        });

                        @if (isset($selectedZones) && !empty($selectedZones))
                            var selectedZones = @json($selectedZones);
                            if (selectedZones && selectedZones.length > 0) {
                                zoneDropdown.val(selectedZones).trigger('change');
                            }
                        @endif
                    }
                });
            }

            // Initialize Select2 for service zones on page load
            // $(document).ready(function() {
            //     // Initialize the zone dropdown
            //     $('#service_zones').select2({
            //         width: '100%',
            //         placeholder: "{{ trans('messages.select_name', ['select' => trans('messages.zone')]) }}",
            //     });

            //     // Initialize provider dropdown with Select2
            //     $('#provider_id').select2({
            //         width: '100%',
            //         placeholder: "{{ trans('messages.select_name', ['select' => trans('messages.provider')]) }}",
            //         allowClear: true
            //     });

            //     // Always call selectprovider on load
            //     var initialProviderId = $('#provider_id').val();
            //     if (initialProviderId) {
            //         selectprovider({
            //             value: initialProviderId
            //         });
            //     }
            // });

            // Initialize Select2 for service zones on page load
$(document).ready(function() {
    // Initialize the zone dropdown
    $('#service_zones').select2({
        width: '100%',
        placeholder: "{{ trans('messages.select_name', ['select' => trans('messages.zone')]) }}",
        allowClear: true  // <-- Add this
    });

    // Load zones for classified post (all active zones; not limited to provider_zone_mappings)
    var initialProviderId = $('#provider_id').val();
    selectprovider({ value: initialProviderId || '' });
});


            // Preview selected image
function previewServiceImage(event) {
    const fileInput = event.target;
    const previewContainer = document.getElementById("post_attachment_preview_container");
    const errorBlock = fileInput.parentElement.querySelector('.help-block.with-errors.text-danger');
    const uploadLabel = document.getElementById('post_attachment_label');

    // Clear previously added previews (but keep DB images already rendered in Blade)
    const newPreviews = previewContainer.querySelectorAll(".new-upload");
    newPreviews.forEach(el => el.remove());

    // Reset error message
    if (errorBlock) errorBlock.textContent = '';

    if (fileInput.files) {
        // Allowed file types
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        let hasInvalidFiles = false;
        let errorMessage = '';

        // Check each file
        for (let i = 0; i < fileInput.files.length; i++) {
            const file = fileInput.files[i];

            // Check file size (10MB = 10 * 1024 * 1024 bytes)
            if (file.size > 10 * 1024 * 1024) {
                errorMessage = 'Each image must be less than 10MB.';
                hasInvalidFiles = true;
                break;
            }

            // Check file type
            if (!allowedTypes.includes(file.type)) {
                errorMessage = 'Please upload a valid image in .jpg , .png , .gif or .jpeg format';
                hasInvalidFiles = true;
                break;
            }
        }

        // If any file is invalid, clear the input and show error
        if (hasInvalidFiles) {
            fileInput.value = '';
            $('input[name="post_attachment[]"]').attr("required", true);
            if (errorBlock) {
                errorBlock.textContent = errorMessage;
            } else {
                // Use Snackbar instead of alert
                Snackbar.show({
                    text: errorMessage,
                    pos: 'bottom-center',
                    backgroundColor: '#dc3545',
                    actionTextColor: 'white'
                });
            }
            if (uploadLabel) {
                uploadLabel.textContent = '{{ __('messages.choose_file', ['file' => __('messages.image')]) }}';
            }
            return;
        }

        $('input[name="post_attachment[]"]').removeAttr('required');
        if (uploadLabel) {
            uploadLabel.textContent = fileInput.files.length > 1
                ? `${fileInput.files.length} files selected`
                : fileInput.files[0].name;
        }

        // Process valid files
        Array.from(fileInput.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function (e) {
                // Create wrapper
                const col = document.createElement("div");
                col.classList.add("col-md-2", "mb-2", "new-upload");

                // Create preview container
                const container = document.createElement("div");
                container.classList.add("image-preview-container");

                // Image element
                const img = document.createElement("img");
                img.src = e.target.result;
                img.classList.add("attachment-image", "mt-1");
                img.style.width = "150px";

                // Remove button
                const removeBtn = document.createElement("a");
                removeBtn.href = "javascript:void(0)";
                removeBtn.classList.add("text-danger", "remove-file");
                removeBtn.innerHTML = '<i class="ri-close-circle-line"></i>';
                removeBtn.onclick = function () {
                    col.remove();

                // If user removes everything, also clear file input
                    if (
                        previewContainer.querySelectorAll(".new-upload").length === 0
                    ) {
                        fileInput.value = "";
                        $('input[name="post_attachment[]"]').attr("required", true);
                        if (uploadLabel) {
                            uploadLabel.textContent = '{{ __('messages.choose_file', ['file' => __('messages.image')]) }}';
                        }
                    }
                };

                // Append
                container.appendChild(img);
                container.appendChild(removeBtn);
                col.appendChild(container);
                previewContainer.appendChild(col);
            };

            reader.readAsDataURL(file);
        });
    }
}



            (function($) {
                "use strict";
                $(document).ready(function() {
                    var provider_id = "{{ isset($postdata->provider_id) ? $postdata->provider_id : '' }}";
                    var provider_address_id = "{{ isset($data) ? $data : [] }}";

                    var category_id = "{{ isset($postdata->category_id) ? $postdata->category_id : '' }}";
                    var subcategory_id =
                        "{{ isset($postdata->subcategory_id) ? $postdata->subcategory_id : '' }}";

                    providerAddress(provider_id, provider_address_id)
                    getSubCategory(category_id, subcategory_id)

                    $(document).on('change', '#provider_id', function() {
                        var provider_id = $(this).val();
                        $('#provider_address_id').empty();
                        providerAddress(provider_id, provider_address_id);
                    })
                    $(document).on('change', '#category_id', function() {
                        var category_id = $(this).val();
                        $('#subcategory_id').empty();
                        getSubCategory(category_id, subcategory_id);
                    })


                    $('.galary').each(function(index, value) {
                        let galleryClass = $(value).attr('data-gallery');
                        $(galleryClass).magnificPopup({
                            delegate: 'a#attachment_files',
                            type: 'image',
                            gallery: {
                                enabled: true,
                                navigateByImgClick: true,
                                preload: [0,
                                    1
                                ] // Will preload 0 - before current, and 1 after the current image
                            },
                            callbacks: {
                                elementParse: function(item) {
                                    if (item.el[0].className.includes('video')) {
                                        item.type = 'iframe',
                                            item.iframe = {
                                                markup: '<div class="mfp-iframe-scaler">' +
                                                    '<div class="mfp-close"></div>' +
                                                    '<iframe class="mfp-iframe" frameborder="0" allowfullscreen></iframe>' +
                                                    '<div class="mfp-title">Some caption</div>' +
                                                    '</div>'
                                            }
                                    } else {
                                        item.type = 'image',
                                            item.tLoading = 'Loading image #%curr%...',
                                            item.mainClass = 'mfp-img-mobile',
                                            item.image = {
                                                tError: '<a href="%url%">The image #%curr%</a> could not be loaded.'
                                            }
                                    }
                                }
                            }
                        })
                    })
                })

                function providerAddress(provider_id, provider_address_id = "") {
                    var provider_address_route =
                        "{{ route('ajax-list', ['type' => 'provider_address', 'provider_id' => '']) }}" + provider_id;
                    provider_address_route = provider_address_route.replace('amp;', '');

                    $.ajax({
                        url: provider_address_route,
                        success: function(result) {
                            $('#provider_address_id').select2({
                                width: '100%',
                                placeholder: "{{ trans('messages.select_name', ['select' => trans('messages.provider_address')]) }}",
                                data: result.results
                            });
                            if (provider_address_id != "") {
                                $('#provider_address_id').val(provider_address_id.split(',')).trigger('change');
                            }
                        }
                    });
                }

                function getSubCategory(category_id, subcategory_id = "") {
                    var get_subcategory_list =
                        "{{ route('ajax-list', ['type' => 'subcategory_list', 'category_id' => '']) }}" + category_id;
                    get_subcategory_list = get_subcategory_list.replace('amp;', '');

                    $.ajax({
                        url: get_subcategory_list,
                        success: function(result) {
                            $('#subcategory_id').select2({
                                width: '100%',
                                placeholder: "{{ trans('messages.select_name', ['select' => trans('messages.subcategory')]) }}",
                                data: result.results
                            });
                            if (subcategory_id != "") {
                                $('#subcategory_id').val(subcategory_id).trigger('change');
                            }
                        }
                    });
                }
            })(jQuery);

            document.addEventListener('DOMContentLoaded', function() {
                checkImage();
            });

            function checkImage() {
                var id = @json($postdata->id);
                var route = "{{ route('check-image', ':id') }}";
                route = route.replace(':id', id);
                var type = 'service';

                $.ajax({
                    url: route,
                    type: 'GET',
                    data: {
                        type: type,
                    },
                    success: function(result) {
                        var attachments = result.results;

                        if (attachments && attachments.length === 0) {
                            $('input[name="post_attachment[]"]').attr('required', 'required');
                        } else {
                            $('input[name="post_attachment[]"]').removeAttr('required');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                    }
                });
            }



            //     $(document).ready(function () {
            //     // Function to initialize Select2 for a given element
            //     function initializeSelect2($element) {
            //         const selectedId = $element.data('selected-id'); // Get the preselected ID
            //         const ajaxUrl = $element.data('ajax--url');
            //         const placeholder = $element.data('placeholder');

            //         $element.select2({
            //             placeholder: placeholder,
            //             ajax: {
            //                 url: ajaxUrl,
            //                 dataType: 'json',
            //                 delay: 250,
            //                 data: function (params) {
            //                     return {
            //                         q: params.term, // Search term
            //                     };
            //                 },
            //                 processResults: function (data) {
            //                     return {
            //                         results: data.map(function (item) {
            //                             return { id: item.id, text: item.text };
            //                         }),
            //                     };
            //                 },
            //                 cache: true,
            //             },
            //         });

            //         // Preselect the value during edit
            //         if (selectedId) {
            //             $.ajax({
            //                 url: ajaxUrl, // Fetch the preselected item
            //                 data: { id: selectedId },
            //                 dataType: 'json',
            //                 success: function (response) {
            //                     const selectedItem = response.find(item => item.id == selectedId);
            //                     if (selectedItem) {
            //                         // Create and append the selected option
            //                         const option = new Option(selectedItem.text, selectedItem.id, true, true);
            //                         $element.append(option).trigger('change');
            //                     }
            //                 },
            //                 error: function () {
            //                     console.error('Failed to fetch selected item for:', selectedId);
            //                 },
            //             });
            //         }
            //     }
            //     function synchronizeDropdowns(type, selectedId) {
            //         $(`.select2js-${type}`).each(function () {
            //             const $dropdown = $(this);

            //             // Fetch the translated value for the selected ID
            //             $.ajax({
            //                 url: $dropdown.data('ajax--url'),
            //                 data: { id: selectedId },
            //                 dataType: 'json',
            //                 success: function (response) {
            //                     const translatedItem = response.find(item => item.id == selectedId);
            //                     if (translatedItem) {
            //                         const option = new Option(translatedItem.text, translatedItem.id, true, true);
            //                         $dropdown.empty().append(option).trigger('change');
            //                     }
            //                 },
            //             });
            //         });
            //     }
            //     // Function to update subcategory dropdown based on category selection
            //     function updateSubcategoryDropdown($categoryDropdown, $subcategoryDropdown) {
            //     // Ensure a single change listener
            //     $categoryDropdown.off('change').on('change', function () {
            //         const categoryId = $(this).val();

            //         if (!categoryId) {
            //             $subcategoryDropdown.empty().trigger('change'); // Clear subcategory
            //             return;
            //         }

            //         const subcategoryAjaxUrl = $subcategoryDropdown
            //             .data('ajax--url')
            //             .replace(/category_id=[^&]*/, `category_id=${categoryId}`);

            //         // Safely destroy Select2 instance if initialized
            //         if ($subcategoryDropdown.hasClass('select2-hidden-accessible')) {
            //             $subcategoryDropdown.select2('destroy');
            //         }

            //         $subcategoryDropdown.empty(); // Clear current options

            //         // Update the AJAX URL dynamically
            //         $subcategoryDropdown.data('ajax--url', subcategoryAjaxUrl);

            //         // Reinitialize Select2 with the new URL
            //         initializeSelect2($subcategoryDropdown);
            //     });
            // }


            //     // Initialize Select2 for all category and subcategory dropdowns
            //     $('.select2js-category').each(function () {
            //         const $categoryDropdown = $(this);
            //         console.log("Dropdown data-selected-id:", $categoryDropdown.data('selected-id'));

            //         const languageId = $categoryDropdown.data('language-id');
            //         const $subcategoryDropdown = $(`#subcategory_id_${languageId}`);

            //         // Initialize subcategory dropdown first to avoid empty state issues
            //         updateSubcategoryDropdown($categoryDropdown, $subcategoryDropdown);

            //         // Then initialize the category dropdown
            //         initializeSelect2($categoryDropdown);
            //     });
            //     // Listen for changes and synchronize all dropdowns of the same type
            //     $('[data-select2-type]').on('select2:select', function (e) {
            //         const $dropdown = $(this);
            //         const selectedId = e.params.data.id;
            //         const type = $dropdown.data('select2-type');

            //         synchronizeDropdowns(type, selectedId);
            //     });


            //     // Handle language toggle
            //     $('.language-toggle').on('click', function () {
            //         const languageId = $(this).data('language-id');
            //         $('.language-form').hide();
            //         $(`#form-language-${languageId}`).show();
            //     });
            // });
        </script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css">
        <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
        <script>

document.addEventListener('DOMContentLoaded', function () {
    // Character counter for all languages
    @foreach ($language_array as $language)
        const metaTitleInput{{ $language['id'] }} = document.getElementById('meta_title_{{ $language["id"] }}');
        const metaDescInput{{ $language['id'] }} = document.getElementById('meta_description_{{ $language["id"] }}');

        if (metaTitleInput{{ $language['id'] }}) {
            metaTitleInput{{ $language['id'] }}.addEventListener('input', function () {
                document.getElementById('meta-title-count-{{ $language["id"] }}').textContent = this.value.length;
            });
        }

        if (metaDescInput{{ $language['id'] }}) {
            metaDescInput{{ $language['id'] }}.addEventListener('input', function () {
                document.getElementById('meta-desc-count-{{ $language["id"] }}').textContent = this.value.length;
            });
        }
    @endforeach
});
    document.addEventListener('DOMContentLoaded', function() {
        var input = document.querySelector('input[name=meta_keywords]');
        if (input) {
            new Tagify(input, {
                delimiters: ",",
                whitelist: [],
                dropdown: { enabled: 0 },
                originalInputValueFormat: valuesArr => JSON.stringify(valuesArr.map(item => item.value))
            });
        }

        // SEO Enable/Disable Switch functionality
        var seoEnabledSwitch = document.getElementById('seo_enabled');
        var seoFieldsSection = document.getElementById('seo_fields_section');
        var metaTitle = document.getElementById('meta_title');
        var metaTitleCount = document.getElementById('meta-title-count');
        var metaDesc = document.getElementById('meta_description');
        var metaDescCount = document.getElementById('meta-desc-count');
        var metaKeywords = document.getElementById('meta_keywords');
        var seoImage = document.querySelector('input[name="seo_image"]');

        function toggleSeoFields() {
            if (seoEnabledSwitch.checked) {
                seoFieldsSection.style.display = 'block';
                    var seoImageInput = document.querySelector('input[name="seo_image"]');
                    if (seoImageInput.getAttribute('data-has-image') == '0') {
                        seoImage.setAttribute('required', 'required');
                    }else{
                        seoImage.removeAttribute('required');
                    }
                    // Do not restore old data, keep fields as is (empty if just toggled on)
            } else {
                seoFieldsSection.style.display = 'none';
                // Clear SEO fields when disabling
                if (metaTitle) {
                    metaTitle.value = '';
                    if (metaTitleCount) metaTitleCount.textContent = '0';
                }
                if (metaDesc) {
                    metaDesc.value = '';
                    if (metaDescCount) metaDescCount.textContent = '0';
                }
                if (metaKeywords) {
                    metaKeywords.value = '';
                    if (metaKeywords.tagify) metaKeywords.tagify.removeAllTags();
                }
                if (seoImage) {
                    seoImage.value = '';
                    var seoImagePreview = document.getElementById('seo_image_preview');
                    if (seoImagePreview) {
                        seoImagePreview.src = '';
                        seoImagePreview.style.display = 'none';
                    }
                    seoImage.removeAttribute('required');
                }
            }
        }

        // Initial state: show/hide and populate fields based on backend data
        if (seoEnabledSwitch) {
            if (seoEnabledSwitch.checked) {
                seoFieldsSection.style.display = 'block';
                var seoImageInput = document.querySelector('input[name="seo_image"]');
                    if (seoImageInput.getAttribute('data-has-image') == '0') {
                        seoImage.setAttribute('required', 'required');
                    }else{
                        seoImage.removeAttribute('required');
                    }
                // The Blade template will have already populated the fields with $postdata values
            } else {
                seoFieldsSection.style.display = 'none';
                // Clear fields (in case of browser autofill)
                if (metaTitle) metaTitle.value = '';
                if (metaDesc) metaDesc.value = '';
                if (metaKeywords) {
                    metaKeywords.value = '';
                    if (metaKeywords.tagify) metaKeywords.tagify.removeAllTags();
                }
                if (seoImage) {
                    seoImage.value = '';
                    var seoImagePreview = document.getElementById('seo_image_preview');
                    if (seoImagePreview) {
                        seoImagePreview.src = '';
                        seoImagePreview.style.display = 'none';
                    }
                    seoImage.removeAttribute('required');
                }
                if (metaTitleCount) metaTitleCount.textContent = '0';
                if (metaDescCount) metaDescCount.textContent = '0';
            }
            // Add event listener
            seoEnabledSwitch.addEventListener('change', toggleSeoFields);
        }
    });

document.addEventListener('DOMContentLoaded', function() {
    // SEO Image validation
    const seoImageInput = document.querySelector('input[name="seo_image"]');
    const seoImageError = document.getElementById('seo_image_error');
    if (seoImageInput) {
        seoImageInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                if (!allowedTypes.includes(file.type)) {
                    event.target.value = '';
                    seoImageError.textContent = 'Only JPG, JPEG, and PNG files are allowed.';
                    document.getElementById('seo_image_preview').style.display = 'none'; // Hide preview on error
                    seoImageInput.setAttribute('data-has-image', '0');
                } else {
                    seoImageError.textContent = '';
                }
            } else {
                seoImageInput.setAttribute('data-has-image', seoImageInput.value ? '1' : '0');
                seoImageError.textContent = '';
            }
        });
    }
    // Category Image validation
    const categoryImageInput = document.querySelector('input[name="category_image"]');
    const categoryImageError = document.getElementById('category_image_error');
    if (categoryImageInput) {
        categoryImageInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                if (!allowedTypes.includes(file.type)) {
                    event.target.value = '';
                    categoryImageError.textContent = 'Please upload a valid image in .jpg , .png , .gif or .jpeg format';
                    document.getElementById('category_image_preview').style.display = 'none'; // Hide preview on error
                } else {
                    categoryImageError.textContent = '';
                }
            } else {
                categoryImageError.textContent = '';
            }
        });
    }
    // Prevent form submit if file type error exists
    const form = document.getElementById('category-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (categoryImageError.textContent || seoImageError.textContent) {
                e.preventDefault();
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // 10MB in bytes
    const MAX_SIZE = 10 * 1024 * 1024;

    // Category Image validation (10MB limit)
    const categoryImageInput = document.querySelector('input[name="category_image"]');
    if (categoryImageInput) {
        categoryImageInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            const errorBlock = document.getElementById('category_image_error');
            if (file) {
                if (file.size > MAX_SIZE) {
                    event.target.value = '';
                    if (errorBlock) {
                        errorBlock.textContent = '{{ __("messages.image_size_must_be_less_than_10mb") }}';
                    } else {
                        alert('{{ __("messages.image_size_must_be_less_than_10mb") }}');
                    }
                    var preview = document.getElementById('category_image_preview');
                    if (preview) preview.style.display = 'none';
                } else {
                    if (errorBlock) errorBlock.textContent = '';
                }
            } else {
                if (errorBlock) errorBlock.textContent = '';
            }
        });
    }

    // SEO Image validation (10MB limit)
    const seoImageInput = document.querySelector('input[name="seo_image"]');
    if (seoImageInput) {
        seoImageInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            const errorBlock = document.getElementById('seo_image_error');
            if (file) {
                if (file.size > MAX_SIZE) {
                    event.target.value = '';
                    if (errorBlock) {
                        errorBlock.textContent = 'Image size must be less than 10MB.';
                    } else {
                        alert('Image size must be less than 10MB.');
                    }
                    var preview = document.getElementById('seo_image_preview');
                    if (preview) preview.style.display = 'none';
                    seoImageInput.setAttribute('data-has-image', '0');
                } else {
                    if (errorBlock) errorBlock.textContent = '';
                    seoImageInput.setAttribute('data-has-image', '1');
                }
            } else {
                seoImageInput.setAttribute('data-has-image', '1');
                if (errorBlock) errorBlock.textContent = '';

            }
        });
    }


});
</script>
        <script type="text/javascript">

    // Service Attachment validation (10MB limit per file and file type validation)
    const serviceAttachmentInputs = document.querySelectorAll('input[name="post_attachment[]"]');
    serviceAttachmentInputs.forEach(function(input) {
        input.addEventListener('change', function(event) {
            const files = event.target.files;
            const errorBlock = input.parentElement.querySelector('.help-block.with-errors.text-danger');
            let tooLarge = false;
            let invalidType = false;

            // Allowed file types
            const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];

            for (let i = 0; i < files.length; i++) {
                // Check file size (10MB = 10 * 1024 * 1024 bytes)
                if (files[i].size > 10 * 1024 * 1024) {
                    tooLarge = true;
                    break;
                }

                // Check file type
                if (!allowedTypes.includes(files[i].type)) {
                    invalidType = true;
                    break;
                }
            }

            if (tooLarge) {
                event.target.value = '';
                if (errorBlock) {
                    errorBlock.textContent = 'Each image must be less than 10MB.';
                } else {
                    // Use Snackbar instead of alert
                    Snackbar.show({
                        text: 'Each image must be less than 10MB.',
                        pos: 'bottom-center',
                        backgroundColor: '#dc3545',
                        actionTextColor: 'white'
                    });
                }
                // Clear preview
                const previewContainer = document.getElementById("post_attachment_preview_container");
                if (previewContainer) {
                    const newPreviews = previewContainer.querySelectorAll(".new-upload");
                    newPreviews.forEach(el => el.remove());
                }
            } else if (invalidType) {
                event.target.value = '';
                if (errorBlock) {
                    errorBlock.textContent = 'Please upload a valid image in .jpg , .png , .gif or .jpeg format';
                } else {
                    // Use Snackbar instead of alert
                    Snackbar.show({
                        text: 'Please upload a valid image in .jpg , .png , .gif or .jpeg format',
                        pos: 'bottom-center',
                        backgroundColor: '#dc3545',
                        actionTextColor: 'white'
                    });
                }
                // Clear preview
                const previewContainer = document.getElementById("post_attachment_preview_container");
                if (previewContainer) {
                    const newPreviews = previewContainer.querySelectorAll(".new-upload");
                    newPreviews.forEach(el => el.remove());
                }
            } else {
                if (errorBlock) errorBlock.textContent = '';
            }
        });
    });

    $(document).ready(function () {
        $('.select2js').select2({ width: '100%' });
    });
</script>
@endsection
</x-master-layout>