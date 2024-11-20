@extends('layouts.app')
@section('title', 'Application Settings')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.application_settings')</h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.application_settings')])
    @slot('tool')
    <div class="box-tools">
        <button class="btn btn-primary" id="createSettingButton" data-toggle="modal"
            data-target="#createSettingModal">{{ __('lang_v1.add_new_settings')}}</button>
    </div>
    @endslot
    <div class="container">
        <div class="table-responsive">
            <table class="table mt-4" id="settings_table">
                <thead>
                    <tr>
                        <th>{{ __('lang_v1.key')}}</th>
                        <th>{{ __('lang_v1.type')}}</th>
                        <th>{{ __('lang_v1.actions')}}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($settings as $setting)
                        <tr>
                            <td>{{ $setting->key }}</td>
                            <td>{{ $setting->type }}</td>
                            <!-- <td>{!! $setting->value !!}</td> -->
                            <td>
                                <button class="btn btn-info" onclick="viewSetting({{ $setting->id }})">
                                    {{ __('lang_v1.view') }}
                                </button>
                                <button class="btn btn-warning" data-toggle="modal"
                                        data-target="#editSettingModal" onclick="editSetting({{ $setting->id }})">
                                    {{ __('lang_v1.edit') }}
                                </button>
                                <form action="{{ route('application_settings.destroy', $setting->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">
                                        {{ __('lang_v1.delete') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endcomponent
</section>

<!-- Create Setting Modal -->
<div class="modal fade" id="createSettingModal" tabindex="-1" role="dialog" aria-labelledby="createSettingModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('application_settings.store') }}" id="createSettingForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="createSettingModalLabel">{{ __('lang_v1.add_new_settings')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="key">{{ __('lang_v1.key')}}</label>
                        <input type="text" name="key" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="type">{{ __('lang_v1.type')}}</label>
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
                        <label for="value">{{ __('lang_v1.value')}}</label>
                        <input type="text" name="value" id="createValue" class="form-control" required>
                    </div>
                    <div class="form-group" id="createBooleanOptions" style="display:none;">
                        <label for="booleanValue">Boolean Value</label>
                        <div>
                            <input type="radio" name="value" value="true">
                            True
                            <input type="radio" name="value" value="false">
                            False
                        </div>
                    </div>
                    <div class="form-group" id="createTextareaGroup" style="display:none;">
                        <label for="value">Text Value</label>
                        <!-- {!! Form::textarea('value', null, ['class' => 'form-control']); !!} -->

                        <textarea name="value" id="createTextarea" class="form-control" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        data-dismiss="modal">{{ __('lang_v1.close')}}</button>
                    <button type="submit" class="btn btn-primary">{{ __('lang_v1.save')}}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Setting Modal -->
<div class="modal fade" id="editSettingModal" tabindex="-1" role="dialog" aria-labelledby="editSettingModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" id="editSettingForm" action="" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editSettingModalLabel">{{ __('lang_v1.edit_setting')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="key">Key</label>
                        <input type="text" name="key" id="editKey" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select name="type" id="editType" class="form-control" required>
                            <option value="string">String</option>
                            <option value="boolean">Boolean</option>
                            <option value="text">Text</option>
                            <option value="integer">Integer</option>
                            <option value="float">Float</option>
                            <option value="json">JSON</option>
                        </select>
                    </div>
                    <div class="form-group" id="editValueGroup">
                        <label for="value">Value</label>
                        <input type="text" name="value" id="editValue" class="form-control" required>
                    </div>
                    <div class="form-group" id="editBooleanOptions" style="display:none;">
                        <label for="booleanValue">Boolean Value</label>
                        <div>
                            <input type="radio" name="value" id="editValueTrue" value="true"> True
                            <input type="radio" name="value" id="editValueFalse" value="false"> False
                        </div>
                    </div>
                    <div class="form-group" id="editTextareaGroup" style="display:none;">
                        <label for="value">Text Value</label>

                        <textarea name="value" id="editTextarea" class="form-control" rows="4"></textarea>
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
                <p><strong>{{ __('lang_v1.key') }}:</strong> <span id="viewKey"></span></p>
                <p><strong>{{ __('lang_v1.type') }}:</strong> <span id="viewType"></span></p>
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
@if (session('success'))
    <script>
        toastr.success("{{ session('success') }}");
    </script>
@endif

@if ($errors->any())
    <script>
        @foreach ($errors->all() as $error)
            toastr.error("{{ $error }}");
        @endforeach
    </script>
@endif
<script>

    var settings_table = $('#settings_table').DataTable({
    });
    // Handle the input dynamically based on the selected type in the Create Modal
    $('#createType').change(function () {
        var type = $(this).val();
        toggleInputFields(type, 'create');
    });

    // Handle the input dynamically based on the selected type in the Edit Modal
    $('#editType').change(function () {
        var type = $(this).val();
        toggleInputFields(type, 'edit');
    });

    function toggleInputFields(type, prefix) {
        const valueGroup = `#${prefix}ValueGroup`;
        const booleanOptions = `#${prefix}BooleanOptions`;
        const textareaGroup = `#${prefix}TextareaGroup`;
        const valueInput = `#${prefix}Value`;
        const textareaInput = `#${prefix}Textarea`;

        // Reset attributes to ensure correct accessibility
        $(valueInput).removeAttr('name required').prop('inert', false);
        $(textareaInput).removeAttr('name required').prop('inert', false);
        $('input[name="booleanValue"]').removeAttr('name').prop('inert', false);

        if (type === 'boolean') {
            $(valueGroup).hide().prop('inert', true);
            $(booleanOptions).show();
            $(textareaGroup).hide().prop('inert', true);
            $('input[name="booleanValue"]').attr('name', 'value');
        } else if (type === 'text') {
            $(valueGroup).hide().prop('inert', true);
            $(booleanOptions).hide();
            $(textareaGroup).show().prop('inert', false);
            $(textareaInput).attr('name', 'value');
            $(textareaGroup).show();
        } else {
            $(valueGroup).show().prop('inert', false);
            $(booleanOptions).hide().prop('inert', true);
            $(textareaGroup).hide().prop('inert', true);
            $(valueInput).attr('name', 'value').prop('required', true);
        }
    }

    tinymce.init({
        selector: 'textarea',
    });

    // Initialize TinyMCE only when textarea is visible
    $('#createSettingModal').on('shown.bs.modal', function () {
        var type = $('#createType').val();
        toggleInputFields(type, 'create');  // Ensure fields are set correctly on modal show

    });
    $('#createSettingModal').on('hidden.bs.modal', function () {
        tinymce.remove('textarea#createTextarea');
    });


    // Form submission event
    $('#editSettingForm, #createSettingForm').submit(function (e) {
        const formPrefix = $(this).attr('id') === 'editSettingForm' ? 'edit' : 'create';
        const type = $(`#${formPrefix}Type`).val();

        // Only process value for visible fields
        if (type === 'boolean') {
            const booleanValue = $(`#${formPrefix}ValueTrue`).prop('checked') ? 'true' : 'false';
            $(`input[name="value"]`).val(booleanValue);
        } else if (type === 'text') {
            const textValue = $(`#${formPrefix}Textarea`).val().trim();
            $(`#${formPrefix}Textarea`).val(textValue || null);  // Ensure valid text input
        } else {
            const inputValue = $(`#${formPrefix}Value`).val().trim();
            $(`#${formPrefix}Value`).val(inputValue || null);  // Ensure valid input for other types
        }
    });


    $('#createSettingModal').on('show.bs.modal', function () {
        $(this).removeAttr('aria-hidden');  // Remove aria-hidden when showing the modal
    }).on('hide.bs.modal', function () {
        $(this).attr('aria-hidden', 'true');  // Reapply aria-hidden when hiding the modal
    });



    $('#editSettingModal').on('show.bs.modal', function () {
        $(this).removeAttr('aria-hidden');  // Remove aria-hidden when showing the modal
    }).on('hide.bs.modal', function () {
        $(this).attr('aria-hidden', 'true');  // Reapply aria-hidden when hiding the modal
    });

    function editSetting(id) {
        $.ajax({
            url: '/applicationDashboard/settings/show/' + id,
            method: 'GET',
            success: function (response) {
                var setting = response.data;
                var url = "{{ route('application_settings.update', ':id') }}".replace(':id', id);
                $('#editSettingForm').attr('action', url);
                $('#editKey').val(setting.key);
                $('#editType').val(setting.type).trigger('change');

                // Check if the key is in the predefined array
                const readonlyKeys = ['privacy_policy', 'terms_conditions', 'contact_us'
                ,'order_message_today','order_message_tomorrow'];
                if (readonlyKeys.includes(setting.key)) {
                    $('#editKey').prop('readonly', true);
                    $('#editType').prop('disabled', true);
                } else {
                    $('#editKey').prop('readonly', false);
                    $('#editType').prop('disabled', false);
                }

                if (setting.type === 'boolean') {
                    if (setting.value === 'true') {
                        $('#editValueTrue').prop('checked', true);
                    } else {
                        $('#editValueFalse').prop('checked', true);
                    }
                } else if (setting.type === 'text') {
                    $('#editTextareaGroup').show();
                    tinymce.get("editTextarea").setContent(setting.value);
                } else {
                    $('#editValue').val(setting.value);
                    $('#editTextareaGroup').hide();
                }

                $('#editSettingModal').modal('show');
            },
            error: function () {
                alert('Unable to fetch the setting details.');
            }
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