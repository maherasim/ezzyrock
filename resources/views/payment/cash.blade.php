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
                        <div class="d-flex justify-content-between align-items-center p-3">
                            <h5 class="fw-bold">{{ $pageTitle ?? trans('messages.list') }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <!-- Tabs -->
            <ul class="nav nav-tabs mb-3" id="cashPaymentTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="services-tab" data-bs-toggle="tab" data-bs-target="#services-pane" type="button" role="tab">
                        {{ __('messages.service') }} {{ __('messages.cash_payments') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders-pane" type="button" role="tab">
                        {{ __('messages.product_order') ?? 'Product Orders' }} {{ __('messages.cash_payments') }}
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="cashPaymentTabsContent">

                {{-- ── TAB 1: Service Cash Payments ── --}}
                <div class="tab-pane fade show active" id="services-pane" role="tabpanel">
                    <div class="row justify-content-between gy-3">
                        <div class="col-md-6 col-lg-4 col-xl-3">
                            <div class="col-md-12">
                                <form action="{{ route('payment.bulk-action') }}" id="quick-action-form"
                                    class="form-disabled d-flex gap-3 align-items-center">
                                    @csrf
                                    @if (auth()->user()->hasAnyRole(['admin']))
                                        <select name="action_type" class="form-select select2" id="quick-action-type"
                                            style="width:auto" disabled>
                                            <option value="">{{ __('messages.no_action') }}</option>
                                            <option value="delete">{{ __('messages.delete') }}</option>
                                        </select>
                                        <div class="select-status d-none quick-action-field" id="change-status-action"
                                            style="width:auto">
                                            <select name="status" class="form-select select2" id="status" style="width:auto">
                                                <option value="1" class="m-2">{{ __('messages.approvecash') }}</option>
                                            </select>
                                        </div>
                                        <button id="quick-action-apply" class="btn btn-primary" data-ajax="true"
                                            data--submit="{{ route('payment.bulk-action') }}" data-datatable="reload"
                                            data-confirmation='true'
                                            data-title="{{ __('cash payment list', ['form' => __('cash payment list')]) }}"
                                            title="{{ __('cash payment list', ['form' => __('cash payment list')]) }}"
                                            data-message='{{ __('Do you want to perform this action?') }}'>
                                            {{ __('messages.apply') }}
                                        </button>
                                    @endif
                                </form>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 col-xl-3">
                            <div class="d-flex align-items-center gap-3 justify-content-end">
                                <div class="datatable-filter ml-auto">
                                    <select name="column_status" id="column_status" class="select2 form-select"
                                        data-filter="select" style="width: 100%">
                                        <option value="">{{ __('messages.all') }}</option>
                                        <option value="advanced_paid">{{ __('messages.advanced_paid') }}</option>
                                        <option value="paid">{{ __('messages.paid') }}</option>
                                        <option value="pending_by_admin">{{ __('messages.pending_by_admin') }}</option>
                                        <option value="approved_by_admin">{{ __('messages.approved_by_admin') }}</option>
                                        <option value="approved_by_provider">{{ __('messages.approved_by_provider') }}</option>
                                        <option value="pending_by_provider">{{ __('messages.pending_by_provider') }}</option>
                                        <option value="send_to_provider">{{ __('messages.send_to_provider') }}</option>
                                        <option value="approved_by_handyman">{{ __('messages.approved_by_handyman') }}</option>
                                    </select>
                                </div>
                                <div class="input-group input-group-search ms-2">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control dt-search" placeholder="Search..."
                                        aria-controls="serviceDataTable">
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="serviceDataTable" class="table table-striped border"></table>
                        </div>
                    </div>
                </div>

                {{-- ── TAB 2: Product Order Cash Payments ── --}}
                <div class="tab-pane fade" id="orders-pane" role="tabpanel">
                    <div class="row justify-content-between gy-3">
                        <div class="col-md-6 col-lg-4 col-xl-3">
                            <div class="input-group input-group-search">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control order-dt-search" placeholder="Search..."
                                    aria-controls="orderDataTable">
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 col-xl-3 d-flex justify-content-end">
                            <select id="order_column_status" class="select2 form-select" style="width: auto">
                                <option value="">{{ __('messages.all') }}</option>
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                        <div class="table-responsive">
                            <table id="orderDataTable" class="table table-striped border"></table>
                        </div>
                    </div>
                </div>

            </div>{{-- end tab-content --}}
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // ── Service Cash Payments DataTable ──
            var serviceTable = $('#serviceDataTable').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                responsive: true,
                dom: '<"row align-items-center"><"table-responsive my-3 mt-3 mb-2 pb-1" rt><"row align-items-center data_table_widgets" <"col-md-6" <"d-flex align-items-center flex-wrap gap-3" l i>><"col-md-6" p>><"clear">',
                ajax: {
                    type: 'GET',
                    url: '{{ route('cash.index_data') }}',
                    data: function (d) {
                        d.search = { value: $('.dt-search').val() };
                        d.filter = { column_status: $('#column_status').val() };
                    }
                },
                columns: [
                    @if (auth()->user()->hasAnyRole(['admin']))
                    {
                        name: 'check', data: 'check',
                        title: '<input type="checkbox" class="form-check-input" name="select_all_table" id="select-all-table" onclick="selectAllTable(this)">',
                        exportable: false, orderable: false, searchable: false
                    },
                    @endif
                    () {
                        data: 'updated_at', name: 'updated_at',
                        title: "{{ __('product.lbl_update_at') }}",
                        orderable: true, visible: false
                    },
                    { data: 'id', name: 'id', title: "{{ __('messages.id') }}" },
                    { data: 'booking_id', name: 'booking_id', title: "{{ __('messages.service') }}" },
                    { data: 'customer_id', name: 'customer_id', title: "{{ __('messages.user') }}", orderable: false },
                    { data: 'datetime', name: 'datetime', title: "{{ __('messages.datetime') }}" },
                    { data: 'history', name: 'history', title: "{{ __('messages.history') }}", orderable: false, searchable: false },
                    { data: 'status', name: 'status', title: "{{ __('messages.status') }}", orderable: false, searchable: false },
                    { data: 'total_amount', name: 'total_amount', title: "{{ __('messages.price') }}" },
                    @if (auth()->user()->hasAnyRole(['admin']))
                    { data: 'action', name: 'action', orderable: false, searchable: false, title: "{{ __('messages.action') }}" }
                    @endif
                    ()
                ],
                order: [
                    @if (auth()->user()->hasAnyRole(['admin'])) [5, 'desc'] @else [4, 'desc'] @endif
                ],
                language: { processing: "{{ __('messages.processing') }}" }
            });

            $('#column_status').change(function () { serviceTable.ajax.reload(); });
            $('.dt-search').on('keyup', function () { serviceTable.ajax.reload(); });

            // ── Product Order Cash Payments DataTable ──
            var orderTable = null;

            // Init on tab show to avoid rendering in hidden element
            $('#orders-tab').on('shown.bs.tab', function () {
                if (orderTable) return; // already init
                orderTable = $('#orderDataTable').DataTable({
                    processing: true,
                    serverSide: true,
                    autoWidth: false,
                    responsive: true,
                    dom: '<"row align-items-center"><"table-responsive my-3 mt-3 mb-2 pb-1" rt><"row align-items-center data_table_widgets" <"col-md-6" <"d-flex align-items-center flex-wrap gap-3" l i>><"col-md-6" p>><"clear">',
                    ajax: {
                        type: 'GET',
                        url: '{{ route('cash.order_index_data') }}',
                        data: function (d) {
                            d.search = { value: $('.order-dt-search').val() };
                            d.filter = { column_status: $('#order_column_status').val() };
                        }
                    },
                    columns: [
                        { data: 'order_number', name: 'order_number', title: 'Order #' },
                        { data: 'products', name: 'products', title: 'Products', orderable: false, searchable: false },
                        { data: 'user_id', name: 'user_id', title: "{{ __('messages.user') }}", orderable: false, searchable: false },
                        { data: 'created_at', name: 'created_at', title: "{{ __('messages.datetime') }}" },
                        { data: 'payment_status', name: 'payment_status', title: "{{ __('messages.status') }}", orderable: false, searchable: false },
                        { data: 'total', name: 'total', title: "{{ __('messages.price') }}" },
                    ],
                    order: [[3, 'desc']],
                    language: { processing: "{{ __('messages.processing') }}" }
                });

                $('#order_column_status').change(function () { orderTable.ajax.reload(); });
                $('.order-dt-search').on('keyup', function () { orderTable.ajax.reload(); });
            });

            // Bulk action helpers (existing)
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
            $('#quick-action-type').change(function () { resetQuickAction(); });

            $(document).on('click', '[data-ajax="true"]', function (e) {
                e.preventDefault();
                const button = $(this);
                if (button.data('confirmation') === 'true') {
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
        });
    </script>

</x-master-layout>
