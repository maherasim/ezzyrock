<x-master-layout>

    <head>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    </head>

    <div class="container-fluid">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row mb-3">
            <div class="col-lg-12">
                <div class="card card-block card-stretch border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 pb-0 pt-3 px-3">
                        <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                            <i class="fas fa-code-branch text-primary"></i>
                            {{ __('messages.add_new_attribute') }}
                        </h5>
                    </div>
                    <div class="card-body pt-2">
                        <form id="add-attribute-form" method="post" action="{{ route('product-attributes.store') }}">
                            @csrf
                            <div class="row align-items-end">
                                <div class="col-md-8 col-lg-6">
                                    <label class="form-label" for="attribute-name">
                                        {{ __('messages.attribute_name_label') }} <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="attribute-name" name="name"
                                        placeholder="{{ __('messages.attribute_name_placeholder') }}" required
                                        maxlength="120" autocomplete="off">
                                </div>
                                <div class="col-md-4 col-lg-6 mt-3 mt-md-0">
                                    <div class="d-flex justify-content-md-end gap-2">
                                        <button type="reset" class="btn btn-light border" id="add-attribute-reset">
                                            {{ __('messages.reset_button') }}
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            {{ __('messages.submit') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                            <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                                {{ __('messages.attribute_list') }}
                                <span class="badge bg-secondary">{{ $attributes->count() }}</span>
                            </h5>
                            <div class="input-group input-group-sm ms-auto" style="max-width: 280px;">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control dt-search"
                                    placeholder="{{ __('messages.attribute_search_placeholder') }}"
                                    aria-label="{{ __('messages.search') }}">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="attribute-datatable" class="table table-striped table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 72px;">Sl</th>
                                        <th style="width: 88px;">ID</th>
                                        <th>{{ __('messages.attribute_name_label') }}</th>
                                        <th style="width: 120px;">{{ __('messages.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($attributes as $attribute)
                                        <tr>
                                            <td></td>
                                            <td>{{ $attribute->id }}</td>
                                            <td>{{ $attribute->name }}</td>
                                            <td>
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-primary btn-icon"
                                                        data-bs-toggle="modal" data-bs-target="#editAttributeModal"
                                                        data-id="{{ $attribute->id }}"
                                                        data-name="{{ $attribute->name }}"
                                                        title="{{ __('messages.edit') }}">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="post"
                                                        action="{{ route('product-attributes.destroy', $attribute) }}"
                                                        class="d-inline"
                                                        onsubmit="return confirm(@json(__('messages.delete_attribute_confirm')));">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger btn-icon"
                                                            title="{{ __('messages.delete') }}">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editAttributeModal" tabindex="-1" aria-labelledby="editAttributeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="edit-attribute-form" method="post" action="">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editAttributeModalLabel">{{ __('messages.edit_attribute') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label" for="edit-attribute-name">
                            {{ __('messages.attribute_name_label') }} <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="edit-attribute-name" name="name" required
                            maxlength="120" autocomplete="off">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                            {{ __('messages.close') }}
                        </button>
                        <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .btn-icon {
            width: 2.25rem;
            height: 2.25rem;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    </style>

    @php
        $attributeUpdateUrlTemplate = str_replace(
            '999999998',
            '__ID__',
            route('product-attributes.update', ['productAttribute' => 999999998])
        );
    @endphp

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const updateUrlTemplate = @json($attributeUpdateUrlTemplate);
            const attrTable = $('#attribute-datatable').DataTable({
                autoWidth: false,
                responsive: true,
                paging: true,
                pageLength: 25,
                order: [
                    [1, 'asc']
                ],
                dom: 'rt<"row align-items-center mt-2"<"col-md-6"l><"col-md-6"p>>',
                columnDefs: [{
                    targets: 0,
                    orderable: false,
                    searchable: false,
                }, {
                    targets: 3,
                    orderable: false,
                    searchable: false,
                }],
                drawCallback: function() {
                    const api = this.api();
                    const start = api.page.info().start;
                    api.column(0, {
                        page: 'current'
                    }).nodes().each(function(cell, i) {
                        cell.innerHTML = start + i + 1;
                    });
                }
            });

            window.renderedDataTable = attrTable;

            $('.dt-search').on('keyup', function() {
                attrTable.search(this.value).draw();
            });

            const editModal = document.getElementById('editAttributeModal');
            editModal.addEventListener('show.bs.modal', function(event) {
                const btn = event.relatedTarget;
                if (!btn) return;
                const id = btn.getAttribute('data-id');
                const name = btn.getAttribute('data-name') || '';
                document.getElementById('edit-attribute-name').value = name;
                document.getElementById('edit-attribute-form').action = updateUrlTemplate.replace('__ID__', id);
            });

            document.getElementById('add-attribute-reset').addEventListener('click', function() {
                document.getElementById('add-attribute-form').reset();
            });
        });
    </script>
</x-master-layout>
