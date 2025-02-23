@extends('layouts.app')
@section('title', 'ApplicationNotifications')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.notifications')
        <small>@lang('lang_v1.manage_your_notifications')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
    @can('notifications.create')
        @slot('tool')
        <div class="box-tools">
            <button type="button" class="btn btn-block btn-primary btn-modal add_notifications_button"
                data-href="{{action('ApplicationDashboard\ApplicationNotificationsController@create')}}"
                data-container=".notifications_modal">
                <i class="fa fa-plus"></i> @lang('messages.add')</button>
        </div>
        @endslot
    @endcan
    @can('notifications.view')
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="notifications_table">
                <thead>
                    <tr>
                        <th>@lang('lang_v1.id')</th>
                        <th>@lang('lang_v1.title')</th>
                        <th>@lang('lang_v1.message')</th>
                        <th>@lang('lang_v1.type')</th>
                        <th>@lang('lang_v1.client')</th>
                        <th>@lang('lang_v1.actions')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcan
    @endcomponent

    <div class="modal fade notifications_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->

@stop
@section('javascript')
    <script>


        //Brand table
        var notifications_table = $('#notifications_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ action("ApplicationDashboard\ApplicationNotificationsController@index") }}',
            columnDefs: [
                {
                    targets: 2,
                    orderable: false,
                    searchable: false,
                },
            ],
            columns: [
                { data: 'id', name: 'id' },
                { data: 'title', name: 'title' },
                { data: 'body', name: 'body' },
                { data: 'type', name: 'type' },
                {data:'client',name: 'client'},
                { data: 'action', name: 'action', orderable: false, searchable: false },

            ]
        });


        $(document).on('click', 'button.add_notifications_button', function () {
            $('div.notifications_modal').load($(this).data('href'), function () {
                $(this).modal('show');

                // Initialize Select2 on the `client_id` select element
                $('#client_id').select2({
                    placeholder: "@lang('lang_v1.select_client_id')",
                    allowClear: true,
                    width: '100%' // Optional: Ensures the dropdown width matches the container
                });
            });
        });


        $(document).on('submit', 'form#notifications_add_form', function (e) {
            e.preventDefault();
            var form = $(this)[0];
            var formData = new FormData(form);

            $.ajax({
                method: 'POST',
                url: $(form).attr('action'),
                data: formData,
                processData: false,  // Required for FormData
                contentType: false,  // Required for FormData
                dataType: 'json',
                beforeSend: function (xhr) {
                    __disable_submit_button($(form).find('button[type="submit"]'));
                },
                success: function (result) {
                    if (result.success == true) {
                        $('div.notifications_modal').modal('hide');
                        toastr.success(result.message);
                        notifications_table.ajax.reload();
                    } else {
                        console.log(result)
                        toastr.error(result.msg);
                    }
                },
                error: function (xhr) {
                    console.log(xhr.responseText);

                    let response = JSON.parse(xhr.responseText);
                    if (response.errors) {
                        // Collect all error messages in an array
                        let errorMessages = Object.values(response.errors).flat();

                        // Show each error message using toastr
                        errorMessages.forEach(message => {
                            toastr.error(message);
                        });
                    } else {
                        toastr.error(response.message || 'An error occurred');
                    }
                }

            });
        });





        $(document).on('click', 'button.delete_notifications_button', function () {
            var href = $(this).data('href');

            swal({
                title: LANG.sure,
                text: LANG.confirm_delete_notifications,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        method: 'DELETE',
                        url: href,
                        dataType: 'json',
                        success: function (result) {
                            if (result.success) {
                                toastr.success(result.msg);
                                notifications_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                        error: function (xhr) {
                            toastr.error(xhr.responseText || 'An error occurred');
                        }
                    });
                }
            });
        });

        // Handle module type change
        $(document).on('change', '#type', function () {
            const selectedType = $(this).val(); // Get the selected value
            const moduleIdContainer = $('#client_id_container'); // Container for client_id select
            const moduleIdSelect = $('#client_id'); // The client_id select element

            // Initialize Select2 on the `client_id` select element
            $('#client_id').select2({
                placeholder: "@lang('lang_v1.select_client_id')",
                allowClear: true,
                width: '100%' // Optional: Ensures the dropdown width matches the container
            });

            // Clear previous options
            moduleIdSelect.empty().append('<option value="">@lang("lang_v1.select_client_id")</option>');

            if (selectedType === 'client') {
                // Show the module ID container
                moduleIdContainer.show();

                // Show a loading indicator while fetching data
                moduleIdSelect.append('<option value="" disabled>Loading...</option>');

                // AJAX call to fetch module data
                $.ajax({
                    url: `/get_clients`, // Backend API endpoint
                    method: 'GET',
                    success: function (response) {
                        // Remove the loading option
                        moduleIdSelect.empty().append('<option value="">@lang("lang_v1.select_client_id")</option>');

                        // Populate options with fetched data
                        $.each(response, function (key, item) {
                            moduleIdSelect.append(
                                `<option value="${item.id}">${item.contact.name}</option>`
                            );
                        });
                    },
                    error: function () {
                        alert('Failed to fetch data. Please try again.');
                    },
                });
            } else {
                // Hide the container if no valid type is selected
                moduleIdContainer.hide();
            }
        });



    </script>
@endsection