@extends('layouts.app')
@section('title', 'Order')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.order_refunds')
    </h1>
</section>

<!-- Main content -->
<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
    @can('lang_v1.create')
        @slot('tool')
        <div class="box-tools">
            <!-- Button to add new order_refunds if needed -->
        </div>
        @component('components.filters', ['title' => __('report.filters')])
        <div class="row">
            <div class="col-md-3">
                <input type="date" id="start_date" class="form-control" placeholder="Start Date">
            </div>
            <div class="col-md-3">
                <input type="date" id="end_date" class="form-control" placeholder="End Date">
            </div>
            <!-- 'all', 'requested','processed', 'approved', 'rejected' -->
            <div class="col-md-3">
            <div class="form-group">
                    <!-- {!! Form::label('type', __('contact.status') . ':*' ) !!} -->
                    <div class="input-group">
                        <!-- <span class="input-group-addon">
                            <i class="fa fa-user"></i>
                        </span> -->
                        {!! Form::select('status', [
                                'all' => __('All'), 
                                'requested' => __('Requested'), 
                                'processed' => __('Processed'),
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
    @can('lang_v1.view')
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="order_refunds_table">
                <thead>
                    <tr>
                        <th>@lang('lang_v1.id')</th>
                        <th>@lang('lang_v1.number')</th>
                        <th>@lang('lang_v1.amount')</th>
                        <th>@lang('lang_v1.order_item')</th>
                        <th>@lang('lang_v1.client')</th>
                        <th>@lang('lang_v1.order_refund_status')</th>
                        <th>@lang('lang_v1.refund_status')</th>
                        <!-- <th>@lang('lang_v1.order_status')</th> -->
                         <th>@lang('lang_v1.delivery_assign')</th>
                        <th>@lang('lang_v1.order_date_time')</th>
                        <th>@lang('lang_v1.actions')</th> <!-- New Actions column -->
                    </tr>
                </thead>
            </table>
        </div>
    @endcan
    @endcomponent

    <!-- Edit Order Refund Modal -->
    <div class="modal fade" id="editOrderRefundModal" tabindex="-1" role="dialog" aria-labelledby="editOrderRefundLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="editOrderRefundForm">
                    <div class="modal-header">
                        <h4 class="modal-title" id="editOrderRefundLabel">@lang('lang_v1.edit_order_refund')</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Add your form fields here, pre-filled with order refund data -->
                        <div class="form-group">
                            <label>@lang('lang_v1.status')</label>
                            <select name="status" id="orderRefundStatus" class="form-control">
                                <option value="requested">@lang('lang_v1.requested')</option>
                                <option value="processed">@lang('lang_v1.processed')</option>
                                <option value="approved">@lang('lang_v1.approved')</option>
                                <option value="rejected">@lang('lang_v1.rejected')</option>
                            </select>
                        </div>
                        <div class="form-group">
                            {!! Form::label('reason', __( 'lang_v1.reason' ) . ':') !!}
                            {!! Form::text('reason', null, ['id'=>'orderRefundReason', 'placeholder' => __( 'lang_v1.reason' ),'class' => "form-control", 'readonly' ]); !!}
                        </div>

                        <div class="form-group">
                            {!! Form::label('admin_response', __( 'lang_v1.admin_response' ) . ':') !!}
                            {!! Form::text('admin_response', null, ['class' => 'form-control','id'=>'orderRefundResponse', 'placeholder' => __( 'lang_v1.admin_response' ) ]); !!}
                        </div>
                        <input type="hidden" id="orderRefundId" name="id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">@lang('lang_v1.close')</button>
                        <button type="submit" class="btn btn-primary">@lang('lang_v1.save_changes')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

@stop
@section('javascript')
<script>
    
        $('#filter_date').click(function() {
        order_refunds_table.ajax.reload(); // Reload DataTable with the new date filters
    });
    $('#clear_date').click(function () {
        $('#start_date').val('');
        $('#end_date').val('');
        order_refunds_table.ajax.reload();
    });
    //Orders table
    var order_refunds_table = $('#order_refunds_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ action("ApplicationDashboard\OrderRefundController@index") }}',
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
            { data: 'amount', name: 'amount' },
            {
    data: 'order_item', name: 'order_item',
    render: function (data) {
        try {
            // Parse the order_item JSON string
            let orderItem = JSON.parse(data);
            
            // Check if the parsed data and product properties exist
            if (orderItem && orderItem.product && orderItem.product.name && orderItem.quantity) {
                return `${orderItem.product.name} - ${orderItem.quantity}`;
            }
        } catch (e) {
            console.error('Error parsing order_item:', e);
        }
        
        return 'N/A'; // If parsing fails or the required data is missing, return 'N/A'
    }
},


            { data: 'client_contact_name', name: 'client_contact_name' }, // Ensure this matches the added column name
            {
                data: 'status', name: 'status', render: function (data, type, row) {
                    let badgeClass;
        switch(data) {
            case 'requested': badgeClass = 'badge btn-warning'; break;
            case 'requested': badgeClass = 'badge btn-info'; break;
            case 'approved': badgeClass = 'badge btn-primary'; break;
            case 'rejected': badgeClass = 'badge btn-danger'; break;
            default: badgeClass = 'badge badge-secondary'; // For any other statuses
        }
                    
                    return `
                    <span class="${badgeClass}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>
                    
            <select class="form-control change-order-status" data-order-refund-id="${row.id}">
                <option value="requested" ${data === 'requested' ? 'selected' : ''}>Requested</option>
                 <option value="processed" ${data === 'processed' ? 'selected' : ''}>Processed</option>
                <option value="approved" ${data === 'approved' ? 'selected' : ''}>Approved</option>
                <option value="rejected" ${data === 'rejected' ? 'selected' : ''}>Rejected</option>
            </select>`;
                }
            },
            {
                data: 'refund_status', name: 'refund_status', render: function (data, type, row) {
                    let badgeClass;
        switch(data) {
            case 'pending': badgeClass = 'badge btn-warning'; break;
            case 'processed': badgeClass = 'badge btn-info'; break;
            case 'delivering': badgeClass = 'badge btn-primary'; break;
            case 'completed': badgeClass = 'badge btn-success'; break;
            default: badgeClass = 'badge badge-secondary'; // For any other statuses
        }
                    
                    return `
                    <span class="${badgeClass}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>
                    
            <select class="form-control change-refund-status" data-order-refund-id="${row.id}">
                <option value="pending" ${data === 'pending' ? 'selected' : ''}>Pending</option>
                 <option value="processed" ${data === 'processed' ? 'selected' : ''}>Processed</option>
                <option value="delivering" ${data === 'delivering' ? 'selected' : ''}>Delivering</option>
                <option value="completed" ${data === 'completed' ? 'selected' : ''}>Completed</option>
            </select>`;
                }
            },
            {
                data: 'order_status',
                name: 'order.order_status',
                render: function (data, type, row) {
                    // Case 1: If the order status is 'processing' and has no delivery assigned
                    if (data === 'processing' && row.has_delivery === false) {
                        return `<button class="btn btn-primary assign-delivery-btn" 
                    data-order-id="${row.id}" 
                    data-contact-name="${row.client_contact_name
                            } ">
                    @lang('lang_v1.assign_delivery')
                </button > `;
                    }
                    if (row.has_delivery === true) {
                        return `<span class="badge badge-success">
                        @lang('lang_v1.delivery_assigned')
                    </span>`;
                    }

                    return '';
                },
                orderable: false,
                searchable: false
            },
            // {
            //     data: 'order.order_status', name: 'order.order_status', render: function (data, type, row) {
            //         let badgeClass;
            //         switch(data) {
            //             case 'pending': badgeClass = 'badge btn-warning'; break;
            //             case 'processing': badgeClass = 'badge btn-info'; break;
            //             case 'shipped': badgeClass = 'badge btn-primary'; break;
            //             case 'completed': badgeClass = 'badge btn-success'; break;
            //             case 'canceled': badgeClass = 'badge btn-danger'; break;
            //             default: badgeClass = 'badge badge-secondary'; // For any other statuses
            //         }
                    
            //         return `
            //         <span class="${badgeClass}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>
            //         `;
            //     }
            // },
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
            return `<button class="btn btn-sm btn-primary edit-order-refund" 
            data-id="${row.id}">@lang('lang_v1.edit')</button>`;
        }
    }
            // other columns as needed
        ]
    });

    $(document).on('change', '.change-order-status', function () {
        var orderRefundId = $(this).data('order-refund-id');
        var status = $(this).val();

        $.ajax({
            // url: `/order-refunds/${orderRefundId}/change-status`, // Update this URL to match your route
            url: `{{ action("ApplicationDashboard\OrderRefundController@changeOrderRefundStatus", ['orderRefundId' => ':orderRefundId']) }}`.replace(':orderRefundId', orderRefundId), // Replacing the placeholder with the actual orderId
            type: 'POST',
            data: {
                status: status,
                _token: '{{ csrf_token() }}' // CSRF token for security
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    // alert(response.message);
                    order_refunds_table.ajax.reload(); // Reload DataTable to reflect the updated status
                } else {
                    alert('Failed to update order status.');
                }
            },
            error: function (xhr) {
                alert('An error occurred: ' + xhr.responseText);
            }
        });
    });


    $(document).on('change', '.change-refund-status', function () {
        var orderRefundId = $(this).data('order-refund-id');
        var status = $(this).val();

        $.ajax({
            url: `{{ action("ApplicationDashboard\OrderRefundController@changeRefundStatus", ['orderRefundId' => ':orderRefundId']) }}`.replace(':orderRefundId', orderRefundId), // Replacing the placeholder with the actual orderId
            type: 'POST',
            data: {
                status: status,
                _token: '{{ csrf_token() }}' // CSRF token for security
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    // alert(response.message);
                    order_refunds_table.ajax.reload(); // Reload DataTable to reflect the updated status
                } else {
                    alert('Failed to update order status.');
                }
            },
            error: function (xhr) {
                alert('An error occurred: ' + xhr.responseText);
            }
        });
    });


    $(document).on('click', '.edit-order-refund', function () {
    var orderRefundId = $(this).data('id');
    var url = $(this).data('url');

    // Fetch current order refund data using AJAX
    $.ajax({
        // url: `applicationDashboard/order-refunds/${orderRefundId}`,
        url: `{{ action("ApplicationDashboard\OrderRefundController@show", ['order_refund' => ':order_refund']) }}`.replace(':order_refund', orderRefundId), // Replacing the placeholder with the actual orderId
        type: 'GET',
        success: function (data) {
            // Populate form fields with existing data
            $('#orderRefundId').val(data.id);
            $('#orderRefundStatus').val(data.status);
            $('#orderRefundReason').val(data.reason);
            $('#orderRefundResponse').val(data.admin_response);

            // Open modal
            $('#editOrderRefundModal').modal('show');
        },
        error: function () {
            alert('Failed to fetch order refund data.');
        }
    });
});

$('#editOrderRefundForm').submit(function (e) {
    e.preventDefault();
    var orderRefundId = $('#orderRefundId').val();
    var status = $('#orderRefundStatus').val();
    var reason = $('#orderRefundReason').val();
    var admin_response = $('#orderRefundResponse').val();

    // Send updated data to the server
    $.ajax({
        // url: `/order-refunds/${orderRefundId}`,
        url: `{{ action("ApplicationDashboard\OrderRefundController@update", ['order_refund' => ':order_refund']) }}`.replace(':order_refund', orderRefundId), // Replacing the placeholder with the actual orderId
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
                $('#editOrderRefundModal').modal('hide');
                order_refunds_table.ajax.reload(); // Refresh the DataTable
            } else {
                alert('Failed to update order refund data.');
            }
        },
        error: function (xhr) {
            alert('An error occurred: ' + xhr.responseText);
        }
    });
});



  
</script>
@endsection