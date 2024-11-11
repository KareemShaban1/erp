@extends('layouts.app')
@section('title', 'orderDelivery')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.orderDeliveries')
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])

    @can('lang_v1.view')
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="orderDeliveries_table">
                <thead>
                    <tr>
                        <th>@lang('lang_v1.id')</th>
                        <th>@lang('lang_v1.delivery_name')</th>
                        <th>@lang('lang_v1.order_number')</th>
                        <th>@lang('lang_v1.client_name')</th>
                        <th>@lang('lang_v1.order_status')</th>
                        <th>@lang('lang_v1.order_total_price')</th>
                        <th>@lang('lang_v1.order_date_time')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcan
    @endcomponent

    <div class="modal fade orderDeliveries_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->

@stop
@section('javascript')
<script>
 var delivery_id = {{ $delivery_id ?? 'null' }}; // Ensure this value is passed from the backend

var orderDeliveries_table = $('#orderDeliveries_table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: '{{ action("ApplicationDashboard\DeliveryController@orderDeliveries") }}', // Base URL
        data: function(d) {
            // Add delivery_id to the query parameters if it exists
            if (delivery_id && delivery_id !== null) {
                d.delivery_id = delivery_id;
            }
        }
    },
    columnDefs: [
        {
            targets: 2,
            orderable: false,
            searchable: false,
        },
    ],
    columns: [
        { data: 'id', name: 'id' },
        { data: 'delivery_name', name: 'delivery.name' },
        { data: 'order.number', name: 'order.number' },
        { data: 'client_name', name: 'order.client.contact.name' },
        { data: 'order.order_status', name: 'order.order_status' },
        { data: 'order.total', name: 'order.total' },
        { 
            data: 'order.created_at', 
            name: 'order.created_at',
            render: function(data) {
                // Format the date using JavaScript
                if (data) {
                    const date = new Date(data);
                    return date.toLocaleString(); // Adjust format as needed
                }
                return '';
            }
        },
    ]
});


    $(document).on('submit', 'form#orderDelivery_add_form', function (e) {
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
                    $('div.orderDeliveries_modal').modal('hide');
                    toastr.success(result.msg);
                    orderDeliveries_table.ajax.reload();
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


    $(document).on('click', 'button.edit_orderDelivery_button', function () {
        var href = $(this).data('href');
        $('div.orderDeliveries_modal').load(href, function () {
            $(this).modal('show');

            $('form#orderDelivery_edit_form').submit(function (e) {
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
                            $('div.orderDeliveries_modal').modal('hide');
                            toastr.success(result.msg);
                            orderDeliveries_table.ajax.reload();
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

    $(document).on('click', 'button.delete_orderDelivery_button', function () {
        var href = $(this).data('href');

        swal({
            title: LANG.sure,
            text: LANG.confirm_delete_orderDelivery,
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
                            orderDeliveries_table.ajax.reload();
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