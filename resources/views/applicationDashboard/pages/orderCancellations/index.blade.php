@extends('layouts.app')
@section('title', 'Order Cancellation')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.order_cancellations')
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
    @can('orders_refund.create')
        @slot('tool')
        <div class="box-tools">
            <!-- Button to add new order_cancellations if needed -->
        </div>
        @component('components.filters', ['title' => __('report.filters')])
        <div class="row">
            <div class="col-md-3">
                <input type="date" id="start_date" class="form-control" placeholder="Start Date">
            </div>
            <div class="col-md-3">
                <input type="date" id="end_date" class="form-control" placeholder="End Date">
            </div>

            <div class="col-md-3">
            <div class="form-group">
                    <!-- {!! Form::label('type', __('contact.status') . ':*' ) !!} -->
                    <div class="input-group">
                        <!-- <span class="input-group-addon">
                            <i class="fa fa-user"></i>
                        </span> -->
                        <!-- 'requested', 'approved', 'rejected' -->
                        {!! Form::select('status', [
                                'all' => __('All'), 
                                'requested' => __('Requested'), 
                                'approved' => __('Approved'),
                                'rejected' => __('Rejected'),
                            ], 'all', [
                                'class' => 'form-control', 
                                'id' => 'status', 
                                'placeholder' => __('messages.please_select'), 
                                'required'
                            ]) !!}

                    </div>
                </div>

            </div>
            <div class="col-md-3">
                <button class="btn btn-primary" id="filter_date">Filter</button>
                <button class="btn btn-primary" id="clear_date">Clear</button>
            </div>
        </div>
        @endcomponent
        @endslot
    @endcan
    
    @can('orders_refund.view')
    <!-- <input type="hidden" value="{{$status}}" id="status"> -->

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="order_cancellations_table">
                <thead>
                    <tr>
                        <th>@lang('lang_v1.id')</th>
                        <th>@lang('lang_v1.number')</th>
                        <th>@lang('lang_v1.client')</th>
                        <th>@lang('lang_v1.order_cancellation_status')</th>
                        <th>@lang('lang_v1.order_status')</th>
                        <th>@lang('lang_v1.order_date_time')</th>
                        <th>@lang('lang_v1.actions')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcan
    @endcomponent

    <!-- Edit Order Cancellation Modal -->
    <div class="modal fade" id="editOrderCancellationModal" tabindex="-1" role="dialog" aria-labelledby="editOrderCancellationLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="editOrderCancellationForm">
                    <div class="modal-header">
                        <h4 class="modal-title" id="editOrderCancellationLabel">@lang('lang_v1.edit_order_cancellation')</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Add your form fields here, pre-filled with order cancellation data -->
                        <div class="form-group">
                            <label>@lang('lang_v1.status')</label>
                            <select name="status" id="orderCancellationStatus" class="form-control">
                                <option value="requested">@lang('lang_v1.requested')</option>
                                <option value="approved">@lang('lang_v1.approved')</option>
                                <option value="rejected">@lang('lang_v1.rejected')</option>
                            </select>
                        </div>
                        <div class="form-group">
                            {!! Form::label('reason', __( 'lang_v1.reason' ) . ':') !!}
                            {!! Form::text('reason', null, ['id'=>'orderCancellationReason', 'placeholder' => __( 'lang_v1.reason' ),'class' => "form-control", 'readonly' ]); !!}
                        </div>

                        <div class="form-group">
                            {!! Form::label('admin_response', __( 'lang_v1.admin_response' ) . ':') !!}
                            {!! Form::text('admin_response', null, ['class' => 'form-control','id'=>'orderCancellationResponse', 'placeholder' => __( 'lang_v1.admin_response' ) ]); !!}
                        </div>
                        <input type="hidden" id="orderCancellationId" name="id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">@lang('lang_v1.close')</button>
                        <button type="submit" class="btn btn-primary">@lang('lang_v1.save_changes')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('applicationDashboard.pages.orderCancellations.orderInformationModal')

</section>

@stop
@section('javascript')
<script>

$(document).ready(function(){

    
        $('#filter_date').click(function() {
        order_cancellations_table.ajax.reload(); // Reload DataTable with the new date filters
    });

    $('#clear_date').click(function () {
        $('#start_date').val('');
        $('#end_date').val('');
        order_cancellations_table.ajax.reload();
    });
    //Orders table
    var order_cancellations_table = $('#order_cancellations_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ action("ApplicationDashboard\OrderCancellationController@index") }}',
            data: function (d) {
                d.status = $('#status').val();
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
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
            { data: 'order_number', name: 'order.number' },
            { data: 'client_contact_name', name: 'client_contact_name' }, // Ensure this matches the added column name
            {
                data: 'status', name: 'status', render: function (data, type, row) {
                    let badgeClass;
        switch(data) {
            case 'requested': badgeClass = 'badge btn-warning'; break;
            case 'approved': badgeClass = 'badge btn-primary'; break;
            case 'rejected': badgeClass = 'badge btn-danger'; break;
            default: badgeClass = 'badge badge-secondary'; // For any other statuses
        }
                    
                    return `
                    <span class="${badgeClass}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>
                    
            <select class="form-control change-order-status" data-order-cancellation-id="${row.id}">
                <option value="requested" ${data === 'requested' ? 'selected' : ''}>Requested</option>
                <option value="approved" ${data === 'approved' ? 'selected' : ''}>Approved</option>
                <option value="rejected" ${data === 'rejected' ? 'selected' : ''}>Rejected</option>
            </select>`;
                }
            },
            {
                data: 'order.order_status', name: 'order.order_status', render: function (data, type, row) {
                    let badgeClass;
        switch(data) {
            case 'pending': badgeClass = 'badge btn-warning'; break;
            case 'processing': badgeClass = 'badge btn-info'; break;
            case 'shipped': badgeClass = 'badge btn-primary'; break;
            case 'completed': badgeClass = 'badge btn-success'; break;
            case 'canceled': badgeClass = 'badge btn-danger'; break;
            default: badgeClass = 'badge badge-secondary'; // For any other statuses
        }
                    
                    return `
                    <span class="${badgeClass}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>
                    `;
                }
            },
            {
                data: 'created_at',
                name: 'created_at',
                render: function (data) {
                    // Format the date using JavaScript
                    if (data) {
                        const date = new Date(data);
                        return date.toLocaleString(); // Adjust format as needed
                    }
                    return '';
                }
            },
            { 
        data: 'id', 
        name: 'actions',
        orderable: false,
        searchable: false,
        render: function (data, type, row) {
            // Initialize an empty string to accumulate buttons
            let buttons = '';

            // Add the "Edit Order Refund" button
            buttons += `<button class="btn btn-sm btn-primary edit-order-cancellation" 
                        data-id="${row.id}">
                        @lang('lang_v1.edit')
                        </button>`;

            // Add the "View Order Info" button
            buttons += `<button class="btn btn-info view-order-cancellation-info-btn" 
                        data-order-id="${row.id}">
                        @lang('lang_v1.view_order_info')
                        </button>`;

            // Return all buttons as a single string
            return buttons;
        }
    }
            // other columns as needed
        ]
    });

    $(document).on('change', '.change-order-status', function () {
        var orderCancellationId = $(this).data('order-cancellation-id');
        var status = $(this).val();

        $.ajax({
            // url: `/order-cancellations/${orderCancellationId}/change-status`, // Update this URL to match your route
            url: `{{ action("ApplicationDashboard\OrderCancellationController@changeOrderCancellationStatus", ['orderCancellationId' => ':orderCancellationId']) }}`.replace(':orderCancellationId', orderCancellationId), // Replacing the placeholder with the actual orderId
            type: 'POST',
            data: {
                status: status,
                _token: '{{ csrf_token() }}' // CSRF token for security
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    // alert(response.message);
                    order_cancellations_table.ajax.reload(); // Reload DataTable to reflect the updated status
                } else {
                    alert('Failed to update order status.');
                }
            },
            error: function (xhr) {
                alert('An error occurred: ' + xhr.responseText);
            }
        });
    });



    $(document).on('click', '.edit-order-cancellation', function () {
    var orderCancellationId = $(this).data('id');
    var url = $(this).data('url');

    // Fetch current order cancellation data using AJAX
    $.ajax({
        // url: `applicationDashboard/order-cancellations/${orderCancellationId}`,
        url: `{{ action("ApplicationDashboard\OrderCancellationController@show", ['order_cancellation' => ':order_cancellation']) }}`.replace(':order_cancellation', orderCancellationId), // Replacing the placeholder with the actual orderId
        type: 'GET',
        success: function (data) {
            // Populate form fields with existing data
            $('#orderCancellationId').val(data.id);
            $('#orderCancellationStatus').val(data.status);
            $('#orderCancellationReason').val(data.reason);
            $('#orderCancellationResponse').val(data.admin_response);

            // Open modal
            $('#editOrderCancellationModal').modal('show');
        },
        error: function () {
            alert('Failed to fetch order cancellation data.');
        }
    });
});

$('#editOrderCancellationForm').submit(function (e) {
    e.preventDefault();
    var orderCancellationId = $('#orderCancellationId').val();
    var status = $('#orderCancellationStatus').val();
    var reason = $('#orderCancellationReason').val();
    var admin_response = $('#orderCancellationResponse').val();

    // Send updated data to the server
    $.ajax({
        // url: `/order-cancellations/${orderCancellationId}`,
        url: `{{ action("ApplicationDashboard\OrderCancellationController@update", ['order_cancellation' => ':order_cancellation']) }}`.replace(':order_cancellation', orderCancellationId), // Replacing the placeholder with the actual orderId
        type: 'PUT',
        data: {
            status: status,
            reason: reason,
            admin_response: admin_response,
            _token: '{{ csrf_token() }}' // CSRF token for security
        },
        success: function (response) {
            if (response.success) {
                toastr.success(response.message);
                $('#editOrderCancellationModal').modal('hide');
                order_cancellations_table.ajax.reload(); // Refresh the DataTable
            } else {
                alert('Failed to update order cancellation data.');
            }
        },
        error: function (xhr) {
            alert('An error occurred: ' + xhr.responseText);
        }
    });
});

$(document).on('click', '.view-order-cancellation-info-btn', function () {
        var orderCancellationId = $(this).data('order-id'); // Get the order ID

        // Fetch the order details
        $.ajax({
            url: `{{ action("ApplicationDashboard\OrderCancellationController@getCancellationDetails", 
            ['orderCancellationId' => ':orderCancellationId']) }}`.replace(':orderCancellationId', orderCancellationId),
            type: 'GET',
            success: function (response) {
                if (response.success) {
                    const date = new Date(response.order_cancellation.order.created_at);
                    const orderCancellationDate = date.toLocaleString();
                    // Populate the modal with the order details
                    $('#order_cancellation_status').text(response.order_cancellation.status);
                    $('#view_order_id').val(response.order_cancellation.order.id);
                    $('#order_number').text(response.order_cancellation.order.number);
                    $('#business_location').text(response.order_cancellation.order.business_location.name);
                    $('#client_name').text(response.order_cancellation.order.client.contact.name);
                    $('#payment_method').text(response.order_cancellation.order.payment_method);
                    $('#shipping_cost').text(response.order_cancellation.order.shipping_cost);
                    $('#sub_total').text(response.order_cancellation.order.sub_total);
                    $('#total').text(response.order_cancellation.order.total);
                    $('#order_status').text(response.order_cancellation.order.order_status);
                    $('#payment_status').text(response.order_cancellation.order.payment_status);
                    // $('#delivery_name').text(response.order_cancellation.order.delivery?.contact.name);
                    $('#order_type').text(response.order_cancellation.order.order_type);
                    $('#order_cancellation_date').text(orderCancellationDate);


                    // Populate the order items
                    const itemsTable = $('#order_items_table tbody');
                    itemsTable.empty(); // Clear existing rows

                    response.order_cancellation.order.order_items.forEach(item => {
                        const row = `
                        <tr>
                            <td><img src="${item.product.image_url}" alt="${item.product.name}" style="width: 50px; height: 50px; object-fit: cover;"></td>
                            <td>${item.product.name}</td>
                            <td>${item.quantity}</td>
                            <td>${item.price}</td>
                            <td>${item.sub_total}</td>
                        </tr>
                    `;
                        itemsTable.append(row);
                    });


                      // Populate the order items
                      const activityLogsTable = $('#activity_logs_table tbody');
                      activityLogsTable.empty(); // Clear existing rows

                    response.activityLogs.forEach(item => {
                        const date = new Date(item.created_at);
                        const formattedDate = date.toLocaleString();

                        const row = `
                        <tr>
                            <td>${item.properties?.order_number || item.properties?.number} </td>
                            <td>
                            ${item.description}
                            </td>

                            <td>${item.properties.status}</td>
                            <td>${item.created_by}</td>
                            <td>${formattedDate}
                            </td>
                        </tr>
                    `;
                    activityLogsTable.append(row);
                    });


                    // Show the modal
                    $('#viewOrderCancellationInfoModal').modal('show');
                } else {
                    alert('Failed to fetch order details.');
                }
            },
            error: function () {
                alert('An error occurred while fetching the order details.');
            }
        });
    });

});
  
</script>
@endsection