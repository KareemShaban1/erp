@extends('layouts.app')
@section('title', 'Order')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.orders')
        <small>@lang('lang_v1.manage_your_orders')</small>
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.all_your_orders')])
    @can('lang_v1.create')
        @slot('tool')
        <div class="box-tools">
            <!-- <button type="button" class="btn btn-block btn-primary btn-modal" 
                            data-href="{{action('ApplicationDashboard\OrderController@create')}}" 
                            data-container=".orders_modal">
                            <i class="fa fa-plus"></i> @lang( 'messages.add' )</button> -->
        </div>
        @endslot
    @endcan
    @can('lang_v1.view')
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="orders_table">
                <thead>
                    <tr>
                        <th>@lang('lang_v1.id')</th>
                        <th>@lang('lang_v1.number')</th>
                        <th>@lang('lang_v1.client')</th>
                        <th>@lang('lang_v1.payment_method')</th>
                        <th>@lang('lang_v1.order_status')</th>
                        <th>@lang('lang_v1.payment_status')</th>
                        <th>@lang('lang_v1.shipping_cost')</th>
                        <th>@lang('lang_v1.sub_total')</th>
                        <th>@lang('lang_v1.total')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcan
    @endcomponent

    <div class="modal fade orders_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->

@stop
@section('javascript')
<script>
    //Brand table
    var orders_table = $('#orders_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/orders',
        columnDefs: [
            {
                targets: 2,
                orderable: false,
                searchable: false,
            },
        ],
        columns: [
            { data: 'id', name: 'id' },
            { data: 'number', name: 'number' },
            { data: 'client_contact_name', name: 'client_contact_name' }, // Ensure this matches the added column name
            { data: 'payment_method', name: 'payment_method' },
            {
                data: 'order_status', name: 'order_status', render: function (data, type, row) {
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
                    
            <select class="form-control change-order-status" data-order-id="${row.id}">
                <option value="pending" ${data === 'pending' ? 'selected' : ''}>Pending</option>
                <option value="processing" ${data === 'processing' ? 'selected' : ''}>Processing</option>
                <option value="shipped" ${data === 'shipped' ? 'selected' : ''}>Shipped</option>
                <option value="completed" ${data === 'completed' ? 'selected' : ''}>Completed</option>
                <option value="canceled" ${data === 'canceled' ? 'selected' : ''}>Canceled</option>
            </select>`;
                }
            },
            // 'pending','paid','failed'
            {
                data: 'payment_status', name: 'payment_status', render: function (data, type, row) {
                    return `
            <select class="form-control change-payment-status" data-order-id="${row.id}">
                <option value="pending" ${data === 'pending' ? 'selected' : ''}>Pending</option>
                <option value="paid" ${data === 'paid' ? 'selected' : ''}>Paid</option>
                <option value="failed" ${data === 'failed' ? 'selected' : ''}>Failed</option>
            </select>`;
                }
            },
            { data: 'shipping_cost', name: 'shipping_cost' },
            { data: 'sub_total', name: 'sub_total' },
            { data: 'total', name: 'total' },
            // other columns as needed
        ]
    });

    $(document).on('change', '.change-order-status', function () {
        var orderId = $(this).data('order-id');
        var status = $(this).val();

        $.ajax({
            url: `/orders/${orderId}/change-order-status`, // Update this URL to match your route
            type: 'POST',
            data: {
                order_status: status,
                _token: '{{ csrf_token() }}' // CSRF token for security
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    // alert(response.message);
                    orders_table.ajax.reload(); // Reload DataTable to reflect the updated status
                } else {
                    alert('Failed to update order status.');
                }
            },
            error: function (xhr) {
                alert('An error occurred: ' + xhr.responseText);
            }
        });
    });


    $(document).on('change', '.change-payment-status', function () {
        var orderId = $(this).data('order-id');
        var status = $(this).val();

        $.ajax({
            url: `/orders/${orderId}/change-payment-status`, // Update this URL to match your route
            type: 'POST',
            data: {
                payment_status: status,
                _token: '{{ csrf_token() }}' // CSRF token for security
            },
            success: function (response) {
                if (response.success) {
                    alert(response.message);
                    orders_table.ajax.reload(); // Reload DataTable to reflect the updated status
                } else {
                    alert('Failed to update order status.');
                }
            },
            error: function (xhr) {
                alert('An error occurred: ' + xhr.responseText);
            }
        });
    });


</script>
@endsection