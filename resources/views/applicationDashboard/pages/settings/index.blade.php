@extends('layouts.app')
@section('title', 'Application Settings')

@section('content')
    <section class="content-header">
        <h1>@lang('lang_v1.application_settings')</h1>
    </section>

    <section class="content">
        @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
        <div class="box-tools">
            <button class="btn btn-primary" id="createSettingButton" data-toggle="modal"
                data-target="#createSettingModal">{{ __('lang_v1.add_new_settings') }}
            </button>
        </div>
        @endslot

        <div class="table-responsive">
            <table class="table mt-4" id="settings_table">
                <thead>
                    <tr>
                        <th>{{ __('lang_v1.key') }}</th>
                        <th>{{ __('lang_v1.type') }}</th>
                        <th>{{ __('lang_v1.actions') }}</th>
                    </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>
        @endcomponent
    </section>

    <!-- Create Setting Modal -->
    <div class="modal fade" id="createSettingModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="createSettingForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('lang_v1.add_new_settings') }}</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="createKey">{{ __('lang_v1.key') }}</label>
                            <input type="text" name="key" id="createKey" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="createType">{{ __('lang_v1.type') }}</label>
                            <select name="type" id="createType" class="form-control" required>
                                <option value="string">String</option>
                                <option value="boolean">Boolean</option>
                                <option value="text">Text</option>
                                <option value="integer">Integer</option>
                                <option value="float">Float</option>
                                <option value="json">JSON</option>
                            </select>
                        </div>
                        <div class="form-group" id="createValueGroup">
                            <label for="createValue">{{ __('lang_v1.value') }}</label>
                            <input type="text" name="value" id="createValue" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Setting Modal -->
    <div class="modal fade" id="editSettingModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editSettingForm">
                    @csrf
                    <input type="hidden" id="editSettingId">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('lang_v1.edit_setting') }}</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="editKey">{{ __('lang_v1.key') }}</label>
                            <input type="text" name="key" id="editKey" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="editType">{{ __('lang_v1.type') }}</label>
                            <select name="type" id="editType" class="form-control" required>
                                <option value="string">String</option>
                                <option value="boolean">Boolean</option>
                                <option value="text">Text</option>
                                <option value="integer">Integer</option>
                                <option value="float">Float</option>
                                <option value="json">JSON</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="editValue">{{ __('lang_v1.value') }}</label>
                            <input type="text" name="value" id="editValue" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Setting Modal -->
    <div class="modal fade" id="viewSettingModal" tabindex="-1" role="dialog" aria-labelledby="viewSettingModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewSettingModalLabel">{{ __('lang_v1.view_setting') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>
                        <strong>{{ __('lang_v1.key') }}:</strong>
                        <span id="viewKey"></span>
                    </p>
                    <p><strong>{{ __('lang_v1.type') }}:</strong>
                        <span id="viewType"></span>
                    </p>
                    <p><strong>{{ __('lang_v1.value') }}:</strong></p>
                    <div id="viewValue" class="border p-3"></div> <!-- Display HTML content -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


@endsection

@section('javascript')
    <script>
        var settingsTable; // Declare it globally

        $(document).ready(function () {

            tinymce.init({
                selector: 'textarea#createValue',
                height: 250
            });

            function updateTinyMCE() {
    tinymce.triggerSave();
}

            settingsTable = $('#settings_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('application_settings.index') }}",
                columns: [
                    { data: 'key', name: 'key' },
                    { data: 'type', name: 'type' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ]
            });
            $('#createSettingForm').submit(function (e) {
                e.preventDefault();
                updateTinyMCE(); // Ensure TinyMCE content is saved
                $.ajax({
                    url: "{{ route('application_settings.store') }}",
                    method: "POST",
                    data: $(this).serialize(),
                    success: function (response) {
                        $('#settings_table tbody').append(response.html);
                        $('#createSettingModal').modal('hide');
                        toastr.success('Setting added successfully!');
                        $('#createSettingForm')[0].reset();
                        settingsTable.ajax.reload(); // Reload table data
                    },
                    error: function (xhr) {
                        toastr.error('Failed to add setting.');
                    }
                });
            });

            $('#editSettingForm').submit(function (e) {
                e.preventDefault();
                updateTinyMCE(); // Ensure TinyMCE content is saved
                let id = $('#editSettingId').val();
                $.ajax({
                    url: `/applicationDashboard/settings/update/${id}`,
                    method: "POST",
                    data: $(this).serialize(),
                    success: function (response) {
                        $(`#setting-${id}`).replaceWith(response.html);
                        $('#editSettingModal').modal('hide');
                        toastr.success('Setting updated successfully!');
                        settingsTable.ajax.reload(); // Reload table data
                    },
                    error: function (xhr) {
                        toastr.error('Failed to update setting.');
                    }
                });
            });
        });

        function editSetting(id) {
            $.get(`/application_settings/${id}/edit`, function (setting) {
                $('#editSettingId').val(setting.id);
                $('#editKey').val(setting.key);
                $('#editType').val(setting.type);
                $('#editValue').val(setting.value);
            });
        }

        function deleteSetting(id) {
            if (confirm('Are you sure?')) {
                $.ajax({
                    url: `/applicationDashboard/settings/destroy/${id}`,
                    method: "POST",
                    data: { _token: "{{ csrf_token() }}" },
                    success: function () {
                        $(`#setting-${id}`).remove();
                        toastr.success('Setting deleted successfully!');
                        settingsTable.ajax.reload(); // Reload table data

                    },
                    error: function () {
                        toastr.error('Failed to delete setting.');
                    }
                });
            }
        }

        // Function to handle input change based on type
        // Function to handle input change based on type
        function handleInputChange(type, inputSelector, value = '') {
            let inputField = $(inputSelector);
            let inputId = inputField.attr('id');

            // Remove TinyMCE instance if it exists
            if (tinymce.get(inputId)) {
                tinymce.get(inputId).remove();
            }

            let newElement = '';

            if (type === 'boolean') {
                newElement = `
                <select name="value" id="${inputId}" class="form-control">
                    <option value="true" ${value == 'true' ? 'selected' : ''}>True</option>
                    <option value="false" ${value == 'false' ? 'selected' : ''}>False</option>
                </select>
            `;
            } else if (type === 'text' || type === 'json') {
                newElement = `
                <textarea name="value" id="${inputId}" class="form-control" rows="4">${value}</textarea>
            `;
            } else if (type === 'integer' || type === 'float') {
                newElement = `
                <input type="number" step="${type === 'float' ? '0.01' : '1'}" 
                    name="value" id="${inputId}" class="form-control" value="${value}">
            `;
            } else {
                newElement = `
                <input type="text" name="value" id="${inputId}" class="form-control" value="${value}">
            `;
            }

            // Replace existing element and set the new one
            inputField.replaceWith(newElement);

            // If it's a textarea, reinitialize TinyMCE
            if (type === 'text' || type === 'json') {
                setTimeout(() => {
                    tinymce.init({
                        selector: `#${inputId}`,
                        height: 250
                    });
                }, 100);
            }
        }

        // Handle input change in Create Modal
        $('#createType').change(function () {
            handleInputChange($(this).val(), '#createValue');
        });

        // Handle input change in Edit Modal
        $('#editType').change(function () {
            handleInputChange($(this).val(), '#editValue');
        });

        // Pre-fill edit modal dynamically
        function editSetting(id) {
            $.get(`/applicationDashboard/settings/show/${id}`, function (response) {
                const setting = response.data;

                $('#editSettingId').val(setting.id);
                $('#editKey').val(setting.key);
                $('#editType').val(setting.type);

                // Readonly keys logic
                const readonlyKeys = [
                    'policy', 'terms', 'contact', 'order_message_today', 'order_message_tomorrow',
                    'order_shipping_cost_status', 'refund_order_shipping_cost_status',
                    'transfer_order_shipping_cost_status', 'customer_service_phone', 'customer_service_whatsapp'
                ];
                $('#editKey').prop('readonly', readonlyKeys.includes(setting.key));

                // Call handleInputChange with the correct value
                handleInputChange(setting.type, '#editValue', setting.value);
            });
        }

        function viewSetting(id) {
            $.ajax({
                url: '/applicationDashboard/settings/show/' + id,
                method: 'GET',
                success: function (response) {
                    const setting = response.data;
                    $('#viewKey').text(setting.key);
                    $('#viewType').text(setting.type);
                    $('#viewValue').html(setting.value); // Display HTML content

                    // Show the view modal
                    $('#viewSettingModal').modal('show');
                },
                error: function () {
                    alert('Unable to fetch the setting details.');
                }
            });
        }


    </script>
@endsection