@extends('layouts.app')
@section('title', 'delivery')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.deliveries')
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])

    @can('lang_v1.view')
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="deliveries_table">
                <thead>
                    <tr>
                        <th>@lang('lang_v1.id')</th>
                        <th>@lang('lang_v1.delivery_name')</th>
                        <th>@lang('lang_v1.email_address')</th>
                        <th>@lang('lang_v1.location')</th>
                        <th>@lang('lang_v1.balance')</th>
                        <th>@lang('lang_v1.actions')</th>


                    </tr>
                </thead>
            </table>
        </div>
    @endcan
    @endcomponent

    <div class="modal fade deliveries_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->

@stop
@section('javascript')
<script>
    //Brand table
    var deliveries_table = $('#deliveries_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ action("ApplicationDashboard\DeliveryController@allDeliveries") }}',
        columnDefs: [
            {
                targets: 1,
                orderable: false,
                searchable: false,
            },
        ],
        columns: [
            { data: 'id', name: 'id' },
            { data: 'delivery_name', name: 'contact.name' },
            { data: 'email_address', name: 'email_address' },
            { data: 'location', name: 'location' },
            { data: 'contact.balance', name: 'contact.balance' },
            { data: 'action', name: 'action', orderable: false, searchable: false }


            ]
    });

    $(document).on('submit', 'form#delivery_add_form', function (e) {
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
                console.log(result)
                if (result.success == true) {
                    $('div.deliveries_modal').modal('hide');
                    toastr.success(result.msg);
                    deliveries_table.ajax.reload();
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


    $(document).on('click', 'button.edit_delivery_button', function () {
        var href = $(this).data('href');
        $('div.deliveries_modal').load(href, function () {
            $(this).modal('show');

            $('form#delivery_edit_form').submit(function (e) {
                e.preventDefault();
                var form = $(this);
                // var data = form.serialize();
                let formData = new FormData(this); // Create a FormData object

                $.ajax({
                    method: 'POST',
                    url: form.attr('action'),
                    data: formData,
                    processData: false,  // Required for FormData
                    contentType: false,  // Required for FormData
                    beforeSend: function (xhr) {
                        __disable_submit_button(form.find('button[type="submit"]'));
                    },
                    success: function (result) {
                        if (result.success) {
                            $('div.deliveries_modal').modal('hide');
                            toastr.success(result.msg);
                            deliveries_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                    error: function (xhr) {
                        toastr.error("test");
                    }
                });
            });
        });
    });

    $(document).on('click', 'button.delete_delivery_button', function () {
        var href = $(this).data('href');

        swal({
            title: LANG.sure,
            text: LANG.confirm_delete_delivery,
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
                            deliveries_table.ajax.reload();
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

</script>
@endsection