<x-master-layout>
    <head>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    </head>

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="fw-bold">{{ $pageTitle ?? 'Free Post Settings' }}</h5>
                            @if(!empty($freePostSetting->id))
                                <a href="{{ route('free-post-settings.index') }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-plus-circle"></i> Add New
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        {{ html()->form('POST', route('free-post-settings.store'))->attribute('data-toggle', 'validator')->id('free_post_setting')->open() }}
                        {{ html()->hidden('id', $freePostSetting->id ?? '') }}

                        <div class="row">
                            <div class="form-group col-md-4">
                                {{ html()->label('Title <span class="text-danger">*</span>', 'title')->class('form-control-label') }}
                                {{ html()->text('title', $freePostSetting->title ?? '')->placeholder('Example: Initial Free Posts')->class('form-control')->required() }}
                            </div>

                            <div class="form-group col-md-4">
                                {{ html()->label('Free Posts <span class="text-danger">*</span>', 'free_posts')->class('form-control-label') }}
                                {{ html()->number('free_posts', $freePostSetting->free_posts ?? 0)->placeholder('Free Posts')->class('form-control')->attribute('min', 0)->attribute('step', 1)->required() }}
                            </div>

                            <div class="form-group col-md-4">
                                {{ html()->label('Status <span class="text-danger">*</span>', 'status')->class('form-control-label') }}
                                {{ html()->select('status', ['1' => __('messages.active'), '0' => __('messages.inactive')], (string) ($freePostSetting->status ?? 1))->id('status')->class('form-select select2js')->required() }}
                            </div>

                            <div class="form-group col-md-12">
                                {{ html()->label(__('messages.description'), 'description')->class('form-control-label') }}
                                {{ html()->textarea('description', $freePostSetting->description ?? '')->class('form-control textarea')->rows(2)->placeholder(__('messages.description')) }}
                            </div>
                        </div>

                        {{ html()->submit(!empty($freePostSetting->id) ? __('messages.update') : __('messages.save'))->class('btn btn-md btn-primary float-end') }}
                        {{ html()->form()->close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row justify-content-between gy-3">
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <form action="{{ route('free-post-settings.bulk-action') }}" id="quick-action-form" class="form-disabled d-flex gap-3 align-items-center">
                        @csrf
                        <select name="action_type" class="form-select select2" id="quick-action-type" style="width:100%" disabled>
                            <option value="">{{ __('messages.no_action') }}</option>
                            <option value="change-status">{{ __('messages.status') }}</option>
                            <option value="delete">{{ __('messages.delete') }}</option>
                        </select>

                        <div class="select-status d-none quick-action-field" id="change-status-action" style="width:100%">
                            <select name="status" class="form-select select2" id="quick_status" style="width:100%">
                                <option value="1">{{ __('messages.active') }}</option>
                                <option value="0">{{ __('messages.inactive') }}</option>
                            </select>
                        </div>

                        <button id="quick-action-apply" class="btn btn-primary" data-ajax="true" data--submit="{{ route('free-post-settings.bulk-action') }}" data-datatable="reload" data-confirmation="true" data-message="{{ trans('messages.do_you_want_to_perform_this_action') }}" disabled>
                            {{ __('messages.apply') }}
                        </button>
                    </form>
                </div>

                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="d-flex align-items-center gap-3 justify-content-end">
                        <div class="datatable-filter ml-auto">
                            <select name="column_status" id="column_status" class="select2 form-select" data-filter="select" style="width: 100%">
                                <option value="">{{ __('messages.all') }}</option>
                                <option value="0">{{ __('messages.inactive') }}</option>
                                <option value="1">{{ __('messages.active') }}</option>
                            </select>
                        </div>
                        <div class="input-group input-group-search ms-2">
                            <span class="input-group-text" id="addon-wrapping"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control dt-search" placeholder="Search..." aria-label="Search" aria-describedby="addon-wrapping" aria-controls="dataTableBuilder">
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="datatable" class="table table-striped border"></table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.renderedDataTable = $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                responsive: true,
                dom: '<"row align-items-center"><"table-responsive my-3 mt-3 mb-2 pb-1" rt><"row align-items-center data_table_widgets" <"col-md-6" <"d-flex align-items-center flex-wrap gap-3" l i>><"col-md-6" p>><"clear">',
                ajax: {
                    type: 'GET',
                    url: '{{ route('free-post-settings.index_data') }}',
                    data: function(d) {
                        d.search = { value: $('.dt-search').val() };
                        d.filter = { column_status: $('#column_status').val() };
                    },
                },
                columns: [
                    {
                        name: 'check',
                        data: 'check',
                        title: '<input type="checkbox" class="form-check-input" name="select_all_table" id="select-all-table" onclick="selectAllTable(this)">',
                        exportable: false,
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'updated_at',
                        name: 'updated_at',
                        title: "{{ __('product.lbl_update_at') }}",
                        orderable: true,
                        visible: false,
                    },
                    { data: 'title', name: 'title', title: 'Title' },
                    { data: 'free_posts', name: 'free_posts', title: 'Free Posts' },
                    { data: 'description', name: 'description', title: "{{ __('messages.description') }}" },
                    { data: 'status', name: 'status', title: "{{ __('messages.status') }}" },
                    { data: 'action', name: 'action', orderable: false, searchable: false, title: "{{ __('messages.action') }}" },
                ],
                order: [[1, 'desc']],
                language: {
                    processing: "{{ __('messages.processing') }}"
                }
            });
        });

        function resetQuickAction() {
            const actionValue = $('#quick-action-type').val();
            if (actionValue != '') {
                $('#quick-action-apply').removeAttr('disabled');
                if (actionValue == 'change-status') {
                    $('.quick-action-field').addClass('d-none');
                    $('#change-status-action').removeClass('d-none');
                } else {
                    $('.quick-action-field').addClass('d-none');
                }
            } else {
                $('#quick-action-apply').attr('disabled', true);
                $('.quick-action-field').addClass('d-none');
            }
        }

        $('#quick-action-type').change(function() {
            resetQuickAction();
        });

        $(document).on('click', '[data-ajax="true"]', function(e) {
            e.preventDefault();
            const button = $(this);
            const confirmation = button.data('confirmation');

            if (confirmation === true || confirmation === 'true') {
                if (confirm(button.data('message'))) {
                    const form = button.closest('form');
                    form.attr('action', button.data('submit'));
                    form.submit();
                }
            } else {
                const form = button.closest('form');
                form.attr('action', button.data('submit'));
                form.submit();
            }
        });
    </script>
</x-master-layout>
