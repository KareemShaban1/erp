@extends('layouts.app')
@section('title', 'Banner')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.banners')
        <small>@lang('lang_v1.manage_your_banners')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
    @can('lang_v1.create')
        @slot('tool')
        <div class="box-tools">
            <button type="button" class="btn btn-block btn-primary btn-modal add_banner_button"
                data-href="{{action('ApplicationDashboard\BannerController@create')}}" data-container=".banners_modal">
                <i class="fa fa-plus"></i> @lang('messages.add')</button>
        </div>
        @endslot
    @endcan
    @can('lang_v1.view')
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="banners_table">
                <thead>
                    <tr>
                        <th>@lang('lang_v1.id')</th>
                        <th>@lang('lang_v1.name')</th>
                        <th>@lang('lang_v1.image')</th>
                        <th>@lang('lang_v1.active')</th>
                        <th>@lang('lang_v1.actions')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcan
    @endcomponent

    <div class="modal fade banners_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->

@stop
@section('javascript')
<script>

    
    //Brand table
    var banners_table = $('#banners_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ action("ApplicationDashboard\BannerController@index") }}',
        columnDefs: [
            {
                targets: 2,
                orderable: false,
                searchable: false,
            },
        ],
        columns: [
            { data: 'id', name: 'id' },
            { data: 'name', name: 'name' },
            { data: 'image', name: 'image' },
            { data: 'active', name: 'active' },
            { data: 'action', name: 'action', orderable: false, searchable: false },

        ]
    });


    $(document).on('click', 'button.add_banner_button', function () {
    $('div.banners_modal').load($(this).data('href'), function () {
        $(this).modal('show');

         // Initialize Select2 on the `module_id` select element
         $('#module_id').select2({
            placeholder: "@lang('lang_v1.select_module_id')",
            allowClear: true,
            width: '100%' // Optional: Ensures the dropdown width matches the container
        });
    });
});


    $(document).on('submit', 'form#banner_add_form', function (e) {
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
                    $('div.banners_modal').modal('hide');
                    toastr.success(result.msg);
                    banners_table.ajax.reload();
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


    $(document).on('click', 'button.edit_banner_button', function () {
    var href = $(this).data('href');
    $('div.banners_modal').load(href, function () {
        $(this).modal('show');

         // Initialize Select2 on the `module_id` select element
        $('#module_id').select2({
            placeholder: "@lang('lang_v1.select_module_id')",
            allowClear: true,
            width: '100%' // Optional: Ensures the dropdown width matches the container
        });

        // Function to fetch module IDs based on type
        function fetchModuleIds(selectedType, selectedModuleId = null) {
            const moduleIdSelect = $('#module_id'); // The module_id select element
            const moduleIdContainer = $('#module_id_container'); // Container for module_id select

            moduleIdSelect.empty().append('<option value="">@lang("lang_v1.select_module_id")</option>');

            if (selectedType) {
                // Show the module ID container
                moduleIdContainer.show();

                // Show a loading indicator while fetching data
                moduleIdSelect.append('<option value="" disabled>Loading...</option>');

                let type;
                switch (selectedType) {
                    case 'category':
                        type = 'categories';
                        break;
                    case 'product':
                        type = 'products';
                        break;
                    default:
                        type = 'categories';
                }

                // AJAX call to fetch module data
                $.ajax({
                    url: `/banners/${type}`, // Backend API endpoint
                    method: 'GET',
                    success: function (response) {
                        moduleIdSelect.empty().append('<option value="">@lang("lang_v1.select_module_id")</option>');
                        // Populate options with fetched data
                        $.each(response, function (key, item) {
                            moduleIdSelect.append(
                                `<option value="${item.id}" ${item.id == selectedModuleId ? 'selected' : ''}>${item.name}</option>`
                            );
                        });
                    },
                    error: function () {
                        alert('Failed to fetch data. Please try again.');
                    },
                });
            } else {
                moduleIdContainer.hide();
            }
        }

        // Pre-load data for edit
        const moduleType = $('#module_type').val();
        const moduleId = $('#module_id').data('selected-id'); // Assume `data-selected-id` is passed from the backend with current value

        fetchModuleIds(moduleType, moduleId);

        // Handle `module_type` change dynamically
        $('#module_type').on('change', function () {
            const selectedType = $(this).val();
            fetchModuleIds(selectedType);
        });

        // Submit the form via AJAX
        $('form#banner_edit_form').submit(function (e) {
            e.preventDefault();
            var form = $(this);
            let formData = new FormData(this);

            $.ajax({
                method: 'POST',
                url: form.attr('action'),
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function (xhr) {
                    __disable_submit_button(form.find('button[type="submit"]'));
                },
                success: function (result) {
                    if (result.success) {
                        $('div.banners_modal').modal('hide');
                        toastr.success(result.msg);
                        banners_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                },
                error: function (xhr) {
                    let response = JSON.parse(xhr.responseText);
                    if (response.errors) {
                        let errorMessages = Object.values(response.errors).flat();
                        errorMessages.forEach(message => toastr.error(message));
                    } else {
                        toastr.error(response.message || 'An error occurred');
                    }
                },
            });
        });
    });
});


    $(document).on('click', 'button.delete_banner_button', function () {
        var href = $(this).data('href');

        swal({
            title: LANG.sure,
            text: LANG.confirm_delete_banner,
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
                            banners_table.ajax.reload();
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
     $(document).on('change', '#module_type', function () {
        const selectedType = $(this).val(); // Get the selected value
        const moduleIdContainer = $('#module_id_container'); // Container for module_id select
        const moduleIdSelect = $('#module_id'); // The module_id select element

                 // Initialize Select2 on the `module_id` select element
                 $('#module_id').select2({
            placeholder: "@lang('lang_v1.select_module_id')",
            allowClear: true,
            width: '100%' // Optional: Ensures the dropdown width matches the container
        });

        // Clear previous options
        moduleIdSelect.empty().append('<option value="">@lang("lang_v1.select_module_id")</option>');

        if (selectedType) {
            // Show the module ID container
            moduleIdContainer.show();

            // Show a loading indicator while fetching data
            moduleIdSelect.append('<option value="" disabled>Loading...</option>');

            let type;
            switch (selectedType) {
                case 'category':
                    type = 'categories';
                    break;
                case 'product':
                    type = 'products';
                    break;
                default:
                        type = 'categories';
                        }
            // AJAX call to fetch module data
            $.ajax({
                url: `/banners/${type}`, // Backend API endpoint
                method: 'GET',
                success: function (response) {
                    // Remove the loading option
                    moduleIdSelect.empty().append('<option value="">@lang("lang_v1.select_module_id")</option>');

                    // Populate options with fetched data
                    $.each(response, function (key, item) {
                        moduleIdSelect.append(
                            `<option value="${item.id}">${item.name}</option>`
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