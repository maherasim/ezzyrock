<x-master-layout>
    <?php $auth_user = authSession(); ?>

    <head>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
        <style>
            .iti {
                width: 100% !important;
            }

            .iti input {
                width: 100% !important;
            }
            .image-preview-container {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-top: 15px;
            }
            .image-wrapper {
                position: relative;
                width: 150px;
                height: 150px;
                border: 1px solid #ddd;
                border-radius: 4px;
                overflow: hidden;
            }
            .image-wrapper img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .remove-btn {
                position: absolute;
                top: 5px;
                right: 5px;
                background: rgba(255, 0, 0, 0.7);
                color: white;
                border: none;
                border-radius: 50%;
                width: 25px;
                height: 25px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
            }
        </style>
    </head>

    <div class="container-fluid">
        <!-- Header -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch mb-4">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="fw-bold mb-0">{{ isset($shop) ? __('messages.update_shop_details') : __('messages.add_new_shop') }}</h5>
                            <a href="{{ route('shop.index') }}" class="float-end btn btn-sm btn-primary">
                               <i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="card">
            <div class="card-body">
              <form method="POST" action="{{ $url }}" id="shop" enctype="multipart/form-data" data-toggle="validator">
                    @csrf
                    <input type="hidden" name="removed_images" id="removed_images" value="">
                    <input type="hidden" name="existing_images" id="existing_images" value="{{ isset($shop) ? $shop->getMedia('shop_attachment')->pluck('id')->implode(',') : '' }}">

                    <div class="row">
                        <div class="form-group col-md-4 mb-3">
                            <label for="shop_name" class="form-label">{{ __('messages.shop_name') }}<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="shop_name" id="shop_name" value="{{ old('shop_name', $shop->shop_name ?? '') }}" placeholder="{{ __('messages.shop_name_placeholder') }}" required autofocus>
                            <small class="help-block with-errors text-danger"></small>
                        </div>

                        @if($auth_user->hasRole('admin') || $auth_user->hasRole('demo_admin'))
                            <div class="form-group col-md-4 mb-3">
                                <label for="provider_id" class="form-label">{{ __('messages.providers') }} <span class="text-danger">*</span></label>
                                <select name="provider_id" id="provider" value="{{old('provider_id', $shop->provider_id ?? '')}}" class="select2js form-group" required>
                                    <option value="" disabled selected>{{ __('messages.shop_provider_placeholder') }}</option>
                                </select>
                                <small class="help-block with-errors text-danger"></small>
                            </div>
                        @else
                            <input type="hidden" name="provider_id" value="{{ $auth_user->id }}">
                        @endif

                        <div class="form-group col-md-4 mb-3">
                            <label for="service_ids" class="form-label">{{ __('messages.shop_services') }} <span class="text-danger">*</span></label>
                            <select name="service_ids[]" id="service_ids" class="select2js form-group" multiple data-placeholder="{{ __('messages.shop_services_placeholder') }}" required></select>
                            <small class="help-block with-errors text-danger"></small>
                        </div>

                        <div class="form-group col-md-4 mb-3">
                            <label for="registration_number" class="form-label">{{ __('messages.shop_registration_number') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="registration_number" id="registration_number"
                                value="{{ old('registration_number', $shop->registration_number ?? '') }}" placeholder="{{ __('messages.shop_registration_number_placeholder') }}" required>
                            <small id="reg-error" class="help-block with-errors text-danger"></small>
                        </div>

                        <div class="form-group col-md-4 mb-3">
                            <label for="email" class="form-label">{{ __('messages.shop_email') }} <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" id="email"
                                value="{{ old('email', $shop->email ?? '') }}" placeholder="{{ __('messages.shop_email_placeholder') }}" pattern="[^@]+@[^@]+\.[a-zA-Z]{2,}" required>
                            <small id="email-error" class="help-block with-errors text-danger"></small>
                        </div>

                        <div class="form-group col-md-4 mb-3">
                            <label for="contact_number" class="form-label">{{ __('messages.shop_contact_number') }} <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="contact_number" placeholder="{{ __('messages.shop_contact_number_placeholder') }}" required>
                            <input type="hidden" name="contact_number" id="contact_number_full" value="{{ old('contact_number', $shop->contact_number ?? '') }}">
                            <input type="hidden" name="contact_country_code" id="contact_country_code" value="{{ old('contact_country_code', $shop->contact_country_code ?? '') }}" required>
                            <small class="help-block with-errors text-danger"></small>
                        </div>

                        <div class="form-group col-md-4 mb-3">
                            <label for="shop_start_time" class="form-label">{{ __('messages.shop_start_time') }} <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" name="shop_start_time" id="shop_start_time"
                                value="{{ old('shop_start_time', isset($shop) ? \Carbon\Carbon::parse($shop->shop_start_time)->format('H:i') : '') }}" placeholder="Select shop start time" required>
                            <small class="help-block with-errors text-danger"></small>
                        </div>

                        <div class="form-group col-md-4 mb-3">
                            <label for="shop_end_time" class="form-label">{{ __('messages.shop_end_time') }} <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" name="shop_end_time" id="shop_end_time"
                                value="{{ old('shop_end_time', isset($shop) ? \Carbon\Carbon::parse($shop->shop_end_time)->format('H:i') : '') }}" placeholder="Select shop end time" required>
                            <small class="help-block with-errors text-danger contact-error"></small>
                        </div>

                        <div class="form-group col-md-4 mb-3">
                            <label for="lat" class="form-label">{{ __('messages.shop_lat') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="lat" id="lat"
                                value="{{ old('lat', $shop->lat ?? '') }}" required
                                pattern="^-?\d{1,2}(\.\d+)?$"
                                title="Enter a valid latitude (-90 to 90)"
                                placeholder="e.g. 12.3456">
                            <small class="help-block with-errors text-danger"></small>
                        </div>

                        <div class="form-group col-md-4 mb-3">
                            <label for="long" class="form-label">{{ __('messages.shop_long') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="long" id="long"
                                value="{{ old('long', $shop->long ?? '') }}" required
                                pattern="^-?\d{1,3}(\.\d+)?$"
                                title="Enter a valid longitude (-180 to 180)"
                                placeholder="e.g. 77.1234">
                            <small class="help-block with-errors text-danger"></small>
                        </div>

                        <div class="form-group col-md-4 mb-3">
                            <label for="address" class="form-label">{{ __('messages.shop_address') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="address" id="address"
                                value="{{ old('address', $shop->address ?? '') }}" placeholder="{{ __('messages.shop_address_placeholder') }}" required>
                            <small class="help-block with-errors text-danger"></small>
                        </div>

                        <div class="form-group col-md-4 mb-3">
                            <label for="country" class="form-label">{{ __('messages.shop_country') }} <span class="text-danger">*</span></label>
                            <select name="country_id" id="country" class="select2js form-group">
                                <option value="" disabled selected>{{ __('messages.shop_country_placeholder') }}</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}" data="{{ $country->code }}"
                                        {{ old('country_id', $shop->country_id ?? '') == $country->id ? 'selected' : '' }}>
                                        {{ $country->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="help-block with-errors text-danger"></small>
                        </div>

                        <div class="form-group col-md-4 mb-3">
                            <label for="state" class="form-label">{{ __('messages.shop_state') }} <span class="text-danger">*</span></label>
                            <select name="state_id" id="state" class="select2js form-group" required>
                                <option value="" disabled selected>{{ __('messages.shop_state_placeholder') }}</option>
                            </select>
                            <small class="help-block with-errors text-danger"></small>
                        </div>

                        <div class="form-group col-md-4 mb-3">
                            <label for="city" class="form-label">{{ __('messages.shop_city') }} <span class="text-danger">*</span></label>
                            <select name="city_id" id="city" class="select2js form-group" required>
                                <option value="" disabled selected>{{ __('messages.shop_city_placeholder') }}</option>
                            </select>
                            <small class="help-block with-errors text-danger"></small>
                        </div>

                        <div class="form-group col-md-2 mb-3">
                           <label for="is_active" class="form-label">{{ __('messages.shop_status') }} <span class="text-danger">*</span></label>
                            <select name="is_active" id="is_active" class="select2js form-control" required>
                                <option value="" disabled selected>{{ __('messages.shop_status_placeholder') }}</option>
                                <option value="1" {{ old('is_active', $shop->is_active ?? 1) == 1 ? 'selected' : '' }}>{{ __('messages.active') }}</option>
                                <option value="0" {{ old('is_active', $shop->is_active ?? 1) == 0 ? 'selected' : '' }}>{{ __('messages.inactive') }}</option>
                            </select>
                            <small class="help-block with-errors text-danger"></small>
                        </div>

                        <div class="form-group col-md-4 mb-3">
                            <label for="shop_attachment" class="form-label">{{ __('messages.shop_image') }} <span class="text-danger">*</span></label>
                            <input type="file" name="shop_attachment[]" class="form-control" id="shop_attachment" placeholder="{{ __('messages.shop_image_placeholder') }}" multiple accept="image/*" {{ isset($shop) ? '' : 'required' }}>
                            <small class="help-block with-errors text-danger"></small>
                            <div class="image-preview-container" id="shop_attachment_preview">
                                @if(isset($shop) && $shop->getMedia('shop_attachment'))
                                    @foreach($shop->getMedia('shop_attachment') as $media)
                                        <div class="image-wrapper">
                                            <img src="{{ $media->getUrl() }}" class="existing-image" data-id="{{ $media->id }}">
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                    <button type="submit" id="submit-btn" class="btn btn-md btn-primary float-end">{{ __('messages.save') }}</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            var input = document.querySelector("#contact_number");
            var iti = window.intlTelInput(input, {
                initialCountry: "in",
                formatOnDisplay: false,
                separateDialCode: true,
                utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js"
            });

            // Prefill in edit mode
            var oldFullNumber = $('#contact_number_full').val();
            if (oldFullNumber) {
                iti.setNumber(oldFullNumber.replace(/\s+/g, ''));
            }

            function updateHiddenFields() {
                var fullNumberE164 = iti.getNumber(intlTelInputUtils.numberFormat.E164);
                if (!fullNumberE164) return null;
                var dialCode = iti.getSelectedCountryData().dialCode;
                var nationalNumber = fullNumberE164.replace('+' + dialCode, '');
                var formattedNumber = '+' + dialCode + ' ' + nationalNumber;
                $('#contact_country_code').val(dialCode);
                $('#contact_number_full').val(formattedNumber);
                return formattedNumber;
            }

            $(input).on('input blur countrychange', updateHiddenFields);

            var debounceTimers = {};

            function debounceCheck(field, value, inputEl) {
                clearTimeout(debounceTimers[field]);
                debounceTimers[field] = setTimeout(function () {
                    checkUniqueField(field, value, inputEl);
                }, 500);
            }

            // Event handlers
            $("#contact_number").on("input blur countrychange", function () {
                var inputEl = $(this);
                var number = updateHiddenFields();
                if (!number) return;
                debounceCheck("contact_number", number, inputEl);
            });

            $("#email").on("input", function () {
                debounceCheck("email", $(this).val().trim(), $(this));
            });

            $("#registration_number").on("input", function () {
                debounceCheck("registration_number", $(this).val().trim(), $(this));
            });

            function checkUniqueField(field, value, inputEl) {
                $.ajax({
                    url: "{{ route('shop.checkRegistration') }}",
                    method: "POST",
                    data: { _token: "{{ csrf_token() }}", field: field, value: value },
                    success: function(response) {
                        var errorMsg = inputEl.closest(".form-group").find(".help-block.with-errors");

                        if (!errorMsg.length) {
                            errorMsg = $('<div class="help-block with-errors"></div>').appendTo(inputEl.closest(".form-group"));
                        }

                        if (!response.status) {
                            errorMsg.html('<ul class="list-unstyled mt-3"><li>' + response.message + '</li></ul>');
                        } else {
                            errorMsg.html('');
                        }
                    }
                });
            }

            // Validate on submit, but don't disable button
            $('#user-form').on('submit', function (e) {
                if (!iti.isValidNumber()) {
                    e.preventDefault();
                    $('.contact-error').text('Please enter a valid mobile number.').show();
                } else {
                    $('.contact-error').hide();
                    updateHiddenFields();
                }
            });
        });

        $(document).ready(function () {
            // Select2 Initialization
            $('#service_ids').select2({ placeholder: 'Select Services', width: '100%' });
            $('.select2js').select2({ width: '100%' });

            // Load Provider & Services (Admin)
            @if($auth_user->hasRole('admin') || $auth_user->hasRole('demo_admin'))
                $.get('{{ route("shop.get_providers") }}', function (data) {
                    const selectedProviderId = "{{ old('provider_id', $shop->provider_id ?? '') }}";
                    const $providerSelect = $('#provider').empty().append('<option value="" disabled selected>Select Provider</option>');

                    $.each(data, function (_, provider) {
                        const selected = provider.id == selectedProviderId ? 'selected' : '';
                        $providerSelect.append(`<option value="${provider.id}" ${selected}>${provider.first_name} ${provider.last_name}</option>`);
                    });

                    if (selectedProviderId) {
                        $providerSelect.val(selectedProviderId).trigger('change');
                    }
                });

                $('#provider').on('change', function () {
                    const providerId = $(this).val();
                    $('#service_ids').html('<option disabled selected>Loading...</option>');

                    $.get(`{{ url('shop/get-services') }}/${providerId}`, function (services) {
                        const selectedServices = @json(old('service_ids', isset($shop) ? $shop->services->pluck('id') : []));
                        $('#service_ids').empty();
                        $.each(services, function (_, service) {
                            const selected = selectedServices.includes(service.id) ? 'selected' : '';
                            $('#service_ids').append(`<option value="${service.id}" ${selected}>${service.name}</option>`);
                        });
                        $('#service_ids').trigger('change');
                    });
                });
            @endif

            // Load Services (Provider)
            @if($auth_user->hasRole('provider'))
                const providerId = "{{ $auth_user->id }}";
                const selectedServices = @json(old('service_ids', isset($shop) ? $shop->services->pluck('id') : []));
                $('#service_ids').html('<option disabled selected>Loading...</option>');

                $.get(`{{ url('shop/get-services') }}/${providerId}`, function (services) {
                    $('#service_ids').empty();
                    $.each(services, function (_, service) {
                        const selected = selectedServices.includes(service.id) ? 'selected' : '';
                        $('#service_ids').append(`<option value="${service.id}" ${selected}>${service.name}</option>`);
                    });
                    $('#service_ids').trigger('change');
                });
            @endif

            // Country/State/City Dropdowns
            $('#country').on('change', function () {
                const countryId = $(this).val();
                $('#state').html('<option>Loading...</option>');
                $('#city').html('<option selected disabled>Select City</option>');

                $.get(`{{ url('shop/get-states') }}/${countryId}`, function (states) {
                    $('#state').html('<option selected disabled>Select State</option>');
                    $.each(states, (id, name) => {
                        $('#state').append(`<option value="${id}">${name}</option>`);
                    });
                });
            });

            $('#state').on('change', function () {
                const stateId = $(this).val();
                $('#city').html('<option>Loading...</option>');

                $.get(`{{ url('shop/get-cities') }}/${stateId}`, function (cities) {
                    $('#city').html('<option selected disabled>Select City</option>');
                    $.each(cities, (id, name) => {
                        $('#city').append(`<option value="${id}">${name}</option>`);
                    });
                });
            });

            @if(isset($shop))
                const countryId = '{{ $shop->country_id }}';
                const stateId = '{{ $shop->state_id }}';
                const cityId = '{{ $shop->city_id }}';

                $('#country').val(countryId).trigger('change');

                setTimeout(() => {
                    $.get(`{{ url('shop/get-states') }}/${countryId}`, function (states) {
                        $('#state').empty();
                        $.each(states, function (id, name) {
                            const selected = id == stateId ? 'selected' : '';
                            $('#state').append(`<option value="${id}" ${selected}>${name}</option>`);
                        });

                        $.get(`{{ url('shop/get-cities') }}/${stateId}`, function (cities) {
                            $('#city').empty();
                            $.each(cities, function (id, name) {
                                const selected = id == cityId ? 'selected' : '';
                                $('#city').append(`<option value="${id}" ${selected}>${name}</option>`);
                            });
                        });
                    });
                }, 500);
            @endif

            // Image Preview & Removal
            let removedImages = [];

            $(document).on('click', '.remove-image', function () {
                const imageId = $(this).data('id');
                removedImages.push(imageId);
                $('#removed_images').val(removedImages.join(','));
                $(this).closest('.image-wrapper').remove();
            });

            $('#shop_attachment').on('change', function () {
                const preview = $('#shop_attachment_preview');
                const files = this.files;

                if (files.length > 0) {
                    const existingImages = $('#existing_images').val().split(',');
                    removedImages = [...existingImages];
                    $('#removed_images').val(removedImages.join(','));

                    preview.find('.image-wrapper').remove();

                    Array.from(files).forEach(file => {
                        if (file.type.match('image.*')) {
                            const reader = new FileReader();
                            reader.onload = e => {
                                preview.append(`
                                    <div class="image-wrapper">
                                        <img src="${e.target.result}" class="new-image">
                                        <button type="button" class="remove-btn remove-new-image">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                `);
                            };
                            reader.readAsDataURL(file);
                        }
                    });
                }
            });

            $(document).on('click', '.remove-new-image', function () {
                $(this).closest('.image-wrapper').remove();
                $('#shop_attachment').val('');
                removedImages = [];

                @if(isset($shop) && $shop->getMedia('shop_attachment'))
                    const existingImages = $('#existing_images').val().split(',');
                    const preview = $('#shop_attachment_preview');
                    preview.empty();
                    @foreach($shop->getMedia('shop_attachment') as $media)
                        if (!removedImages.includes("{{ $media->id }}")) {
                            preview.append(`
                                <div class="image-wrapper">
                                    <img src="{{ $media->getUrl() }}" class="existing-image" data-id="{{ $media->id }}">
                                </div>
                            `);
                        }
                    @endforeach
                @endif
            });
        });

    document.addEventListener('DOMContentLoaded', function () {
        const shopNameInput = document.getElementById('shop_name');
            shopNameInput.addEventListener('input', function () {
                this.value = this.value.replace(/[^A-Za-z\s]/g, '');
        });

        const contact_number_input = document.getElementById('contact_number');
        contact_number_input.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        function restrictLatLong(input, isLat = true) {
            input.addEventListener('input', function () {
                // allow digits, decimal point, minus sign
                this.value = this.value.replace(/[^0-9\.\-]/g, '');

                // prevent multiple dots or minus signs
                if ((this.value.match(/\./g) || []).length > 1) {
                    this.value = this.value.substring(0, this.value.length - 1);
                }
                if ((this.value.match(/\-/g) || []).length > 1) {
                    this.value = this.value.substring(0, this.value.length - 1);
                }

                // limit range live
                let num = parseFloat(this.value);
                if (!isNaN(num)) {
                    if (isLat && (num < -90 || num > 90)) this.value = '';
                    if (!isLat && (num < -180 || num > 180)) this.value = '';
                }
            });
        }

        restrictLatLong(document.getElementById('lat'), true);  // latitude
        restrictLatLong(document.getElementById('long'), false); // longitude
    });
    </script>
</x-master-layout>
