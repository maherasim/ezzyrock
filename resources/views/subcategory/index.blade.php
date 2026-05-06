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
                            <div>
                                <h5 class="fw-bold mb-0">{{ $pageTitle ?? trans('messages.list') }}</h5>
                                @if (!empty($moduleContext))
                                    <span class="badge {{ $moduleContext === 'ecommerce' ? 'bg-info' : ($moduleContext === 'classified' ? 'bg-secondary' : 'bg-primary') }} mt-1">{{ __('messages.category_module') }}:
                                        @if ($moduleContext === 'ecommerce') {{ __('messages.ecommerce') }}
                                        @elseif ($moduleContext === 'classified') {{ __('messages.classifieds') }}
                                        @else {{ __('messages.services') }}
                                        @endif
                                    </span>
                                @endif
                            </div>
                            @if ($auth_user->can('subcategory add'))
                            <div class="d-flex flex-wrap gap-1 justify-content-end align-items-center">
                                @if (!empty($moduleContext))
                                    <a href="{{ route('subcategory.create', ['module' => $moduleContext]) }}" class="btn btn-sm btn-primary"><i class="fa fa-plus-circle"></i> {{ __('messages.new') }}</a>
                                    <a href="{{ route('subcategory.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('messages.all') }}</a>
                                @else
                                    <a href="{{ route('subcategory.create', ['module' => 'service']) }}" class="btn btn-sm btn-primary"><i class="fa fa-plus-circle"></i> {{ __('messages.services') }}</a>
                                    <a href="{{ route('subcategory.create', ['module' => 'ecommerce']) }}" class="btn btn-sm btn-info text-white"><i class="fa fa-plus-circle"></i> {{ __('messages.ecommerce') }}</a>
                                    <a href="{{ route('subcategory.create', ['module' => 'classified']) }}" class="btn btn-sm btn-secondary"><i class="fa fa-plus-circle"></i> {{ __('messages.classifieds') }}</a>
                                @endif
                            </div>
                            @endif
                        </div>
                        {{-- {{ $dataTable->table(['class' => 'table w-100'],false) }} --}}
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="row justify-content-between gy-3">
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="col-md-12">
                            <form action="{{ route('sub-bulk-action') }}" id="quick-action-form"
                                class="form-disabled d-flex gap-3 align-items-center">
                                @csrf
                                <select name="action_type" class="form-select select2" id="quick-action-type"
                                    style="width:100%" disabled>
                                    <option value="">{{ __('messages.no_action') }}</option>
                                    <option value="change-status">{{ __('messages.status') }}</option>
                                    <option value="change-featured">{{ __('messages.featured') }}</option>
                                    <option value="delete">{{ __('messages.delete') }}</option>
                                    <option value="restore">{{ __('messages.restore') }}</option>
                                    <option value="permanently-delete">{{ __('messages.permanent_dlt') }}</option>
                                </select>

                                <div class="select-status d-none quick-action-field" id="change-status-action"
                                    style="width:100%">
                                    <select name="status" class="form-select select2" id="status">
                                        <option value="1">{{ __('messages.active') }}</option>
                                        <option value="0">{{ __('messages.inactive') }}</option>
                                    </select>
                                </div>
                                <div class="select-status d-none quick-action-featured" id="change-featured-action"
                                    style="width:100%">
                                    <select name="is_featured" class="form-select select2" id="is_featured">
                                        <option value="1">{{ __('messages.yes') }}</option>
                                        <option value="0">{{ __('messages.no') }}</option>
                                    </select>
                                </div>
                                <button id="quick-action-apply" class="btn btn-primary" data-ajax="true"
                                    data--submit="{{ route('sub-bulk-action') }}" data-datatable="reload"
                                    data-confirmation='true'
                                    data-title="{{ __('subcategory', ['form' => __('subcategory')]) }}"
                                    title="{{ __('subcategory', ['form' => __('subcategory')]) }}" data-message='{{ __('
                                    Do you want to perform this action??') }}'>{{ __('messages.apply') }}</button>
                        </div>

                        </form>
                    </div>
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="d-flex align-items-center gap-3 justify-content-end">
                            <div class="d-flex justify-content-end gap-3">
                                <div class="datatable-filter ml-auto d-flex flex-wrap gap-2 align-items-center">
                                    @if (empty($moduleContext))
                                    <select name="filter_module_type" id="filter_module_type" class="select2 form-select" style="min-width: 10rem">
                                        <option value="">{{ __('messages.all') }} — {{ __('messages.category_module') }}</option>
                                        <option value="service">{{ __('messages.services') }}</option>
                                        <option value="ecommerce">{{ __('messages.ecommerce') }}</option>
                                        <option value="classified">{{ __('messages.classifieds') }}</option>
                                    </select>
                                    @else
                                    <input type="hidden" id="filter_module_type" value="{{ $moduleContext }}">
                                    @endif
                                    <select name="column_status" id="column_status" class="select2 form-select"
                                        data-filter="select" style="width: 100%">
                                        <option value="">{{ __('messages.all') }}</option>
                                        <option value="0" {{ $filter['status']=='0' ? 'selected' : '' }}>
                                            {{ __('messages.inactive') }}</option>
                                        <option value="1" {{ $filter['status']=='1' ? 'selected' : '' }}>
                                            {{ __('messages.active') }}</option>
                                    </select>
                                </div>
                                <div class="input-group input-group-search ms-2">
                                    <span class="input-group-text" id="addon-wrapping"><i
                                            class="fas fa-search"></i></span>
                                    <input type="text" class="form-control dt-search" placeholder="Search..."
                                        aria-label="Search" aria-describedby="addon-wrapping"
                                        aria-controls="dataTableBuilder">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="datatable" class="table table-striped border">

                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {

            window.renderedDataTable = $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                responsive: true,
                dom: '<"row align-items-center"><"table-responsive my-3 mt-3 mb-2 pb-1" rt><"row align-items-center data_table_widgets" <"col-md-6" <"d-flex align-items-center flex-wrap gap-3" l i>><"col-md-6" p>><"clear">',
                ajax: {
                    "type": "GET",
                    "url": '{{ route('subcategory.sub-index-data') }}',
                    "data": function(d) {
                        d.search = {
                            value: $('.dt-search').val()
                        };
                        d.filter = {
                            column_status: $('#column_status').val(),
                            module_type: $('#filter_module_type').val() || ''
                        }
                    },
                },

                columns: [{
                        name: 'check',
                        data: 'check',
                        title: '<input type="checkbox" class="form-check-input" name="select_all_table" id="select-all-table" data-type="subcategory" onclick="selectAllTable(this)">',
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
                    {
                        data: 'name',
                        name: 'name',
                        title: "{{ __('messages.name') }}"
                    },
                    {
                        data: 'section',
                        name: 'section',
                        title: "{{ __('messages.category_module') }}",
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'category_id',
                        name: 'category_id',
                        title: "{{ __('messages.category') }}"
                    },
                    {
                        data: 'is_featured',
                        name: 'is_featured',
                        title: "{{ __('messages.featured') }}"
                    },
                    {
                        data: 'status',
                        name: 'status',
                        title: "{{ __('messages.status') }}"
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        title: "{{ __('messages.action') }}",
                        className: 'text-end'
                    }

                ],
                order: [
                    [1, 'desc']
                ],
                language: {
                    processing: "{{ __('messages.processing') }}" // Set your custom processing text
                }
            });
            $('#filter_module_type, #column_status').on('change', function () {
                if (window.renderedDataTable) {
                    window.renderedDataTable.ajax.reload();
                }
            });
        });

        function resetQuickAction() {
            const actionValue = $('#quick-action-type').val();
            console.log(actionValue)
            if (actionValue != '') {
                $('#quick-action-apply').removeAttr('disabled');

                if (actionValue == 'change-status') {
                    $('.quick-action-field').addClass('d-none');
                    $('#change-status-action').removeClass('d-none');
                } else {
                    $('.quick-action-field').addClass('d-none');
                }
                if (actionValue == 'change-featured') {
                    $('.quick-action-featured').addClass('d-none');
                    $('#change-featured-action').removeClass('d-none');
                } else {
                    $('.quick-action-featured').addClass('d-none');
                }
            } else {
                $('#quick-action-apply').attr('disabled', true);
                $('.quick-action-field').addClass('d-none');
                $('.quick-action-featured').addClass('d-none');
            }
        }
        $('#quick-action-type').change(function() {
            resetQuickAction()
        });

        $(document).on('click', '[data-ajax="true"]', function(e) {
            e.preventDefault();
            const button = $(this);
            const confirmation = button.data('confirmation');

            if (confirmation === 'true') {
                const message = button.data('message');
                if (confirm(message)) {
                    const submitUrl = button.data('submit');
                    const form = button.closest('form');
                    form.attr('action', submitUrl);
                    form.submit();
                }
            } else {
                const submitUrl = button.data('submit');
                const form = button.closest('form');
                form.attr('action', submitUrl);
                form.submit();
            }
        });
    </script>

</x-master-layout>
