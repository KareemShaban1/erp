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

    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#deliveryStatisticsModal">
        @lang('lang_v1.view_delivery_statistics')
    </button>

    <!-- Add Date Filters -->
    <div class="row">
        <div class="col-md-3">
            <label for="start_date">@lang('lang_v1.start_date')</label>
            <input type="date" id="start_date" class="form-control">
        </div>
        <div class="col-md-3">
            <label for="end_date">@lang('lang_v1.end_date')</label>
            <input type="date" id="end_date" class="form-control">
        </div>
        <div class="col-md-3 align-self-end">
            <button id="filter_orders" class="btn btn-primary">@lang('lang_v1.filter')</button>
        </div>
    </div>
    <br>



    @can('deliveries.orders')
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="orderDeliveries_table">
                <thead>
                    <tr>
                        <th>@lang('lang_v1.id')</th>
                        <th>@lang('lang_v1.delivery_name')</th>
                        <th>@lang('lang_v1.order_number')</th>
                        <th>@lang('lang_v1.client_name')</th>
                        <th>@lang('lang_v1.order_status')</th>
                        <th>@lang('lang_v1.payment_status')</th>
                        <th>@lang('lang_v1.order_total_price')</th>
                        <th>@lang('lang_v1.order_date_time')</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th colspan="7" style="text-align:right">Total:</th>
                        <th id="total_order_sum">0.00</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endcan
    @endcomponent

    <div class="modal fade orderDeliveries_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->

@include('applicationDashboard.pages.orderDeliveries.statisticsModal')
@stop
@section('javascript')
    <script>

        $(document).ready(function () {

            var delivery_id = {{ $delivery_id ?? 'null' }}; // Ensure this value is passed from the backend

            var orderDeliveries_table = $('#orderDeliveries_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ action("ApplicationDashboard\DeliveryController@orderDeliveries") }}', // Base URL
                    data: function (d) {
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                        d.delivery_id = {{ $delivery_id ?? 'null' }};
                    }
                },
                columnDefs: [
                    {
                        orderable: true,
                        searchable: true,
                    },
                ],
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'delivery_name', name: 'delivery_name' },
                    { data: 'order.number', name: 'order.number' },
                    { data: 'client_name', name: 'order.client.contact.name' },
                    // { data: 'order.order_status', name: 'order.order_status' },
                    {
                        data: 'order.order_status', name: 'order.order_status', render: function (data, type, row) {
                            let badgeClass;
                            switch (data) {
                                case 'pending': badgeClass = 'badge btn-warning'; break;
                                case 'processing': badgeClass = 'badge btn-info'; break;
                                case 'shipped': badgeClass = 'badge btn-primary'; break;
                                case 'completed': badgeClass = 'badge btn-success'; break;
                                case 'cancelled': badgeClass = 'badge btn-danger'; break;
                                default: badgeClass = 'badge badge-secondary'; // For any other statuses
                            }

                            return `
                                        <span class="${badgeClass}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>
                                 `
                        }
                    },
                    // { data: 'payment_status', name: 'payment_status' },
                    {
                        data: 'order.payment_status',
                        name: 'order.payment_status',
                        render: function (data, type, row) {
                            let badgeClass;
                            switch (data) {
                                case 'paid': badgeClass = 'badge btn-success'; break;
                                case 'pending': badgeClass = 'badge btn-waring'; break;
                                case 'failed': badgeClass = 'badge btn-danger'; break;
                                default: badgeClass = 'badge badge-secondary'; // For any other statuses
                            }

                            // Check if order_status is 'completed'
                            // if (row.order_status !== 'completed') {
                            //     // Only display the badge
                            //     return `
                            //         <span class="${badgeClass}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>
                            //     `;
                            // }

                            // Display the badge and dropdown when order_status is 'completed'
                            return `
                                        <span class="${badgeClass}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>

                                    `;
                            // <select class="form-control change-payment-status" data-order-id="${row.id}">
                            //         <option value="paid" ${data === 'paid' ? 'selected' : ''}>Paid</option>
                            //         <option value="not_paid" ${data === 'failed' ? 'selected' : ''}>Not Paid</option>
                            //     </select>
                        }
                    },

                    { data: 'order.total', name: 'order.total' },
                    {
                        data: 'order.created_at',
                        name: 'order.created_at',
                        render: function (data) {
                            // Format the date using JavaScript
                            if (data) {
                                const date = new Date(data);
                                return date.toLocaleString(); // Adjust format as needed
                            }
                            return '';
                        }
                    },
                ],
                drawCallback: function (settings) {
                    var api = this.api();
                    var total_order_sum = settings.json.total_order_sum || '0.00';
                    $(api.column(7).footer()).html('<strong>Total: ' + total_order_sum + '</strong>');
                }
            });



            $('#filter_orders').click(function () {
                orderDeliveries_table.ajax.reload();
            });



            // Set default dates to today
            const today = new Date().toISOString().split('T')[0];
            $('#start_date').val(today);
            $('#end_date').val(today);

            // Fetch statistics when the modal is shown
            $('#deliveryStatisticsModal').on('show.bs.modal', function () {
                fetchDeliveryStatistics();
            });

            // Fetch statistics on filter button click
            $('#filter_statistics').click(function () {
                fetchDeliveryStatistics();
            });

            function fetchDeliveryStatistics() {
                const delivery_id = {{ $delivery_id ?? 'null' }}; // Ensure this value is passed from the backend
                const start_date = $('#start_date').val();
                const end_date = $('#end_date').val();

                $.ajax({
                    url: '{{ route("deliveries.statistics") }}',
                    type: 'GET',
                    data: {
                        delivery_id: delivery_id,
                        start_date: start_date,
                        end_date: end_date,
                    },
                    success: function (response) {
                        if (response.success) {
                            const data = response.data;

                            // Update the UI with the fetched statistics
                            $('#total_orders_count').text(data.total_orders_count || 0);
                            $('#total_orders_amount').text('$' + (data.total_orders_amount || 0).toFixed(2));
                            $('#refund_orders_count').text(data.refund_orders_count || 0);
                            $('#refund_orders_amount').text('$' + (data.refund_orders_amount || 0).toFixed(2));
                            $('#transfer_orders_count').text(data.transfer_orders_count || 0);
                            $('#transfer_orders_amount').text('$' + (data.transfer_orders_amount || 0).toFixed(2));
                            $('#cancelled_orders_count').text(data.cancelled_orders_count || 0);
                            $('#cancelled_orders_amount').text('$' + (data.cancelled_orders_amount || 0).toFixed(2));
                            $('#paid_orders_count').text(data.paid_orders_count || 0);
                            $('#paid_orders_amount').text('$' + (data.paid_orders_amount || 0).toFixed(2));
                            $('#failed_paid_orders_count').text(data.failed_paid_orders_count || 0);
                            $('#failed_paid_orders_amount').text('$' + (data.failed_paid_orders_amount || 0).toFixed(2));
                            $('#pending_paid_orders_count').text(data.pending_paid_orders_count || 0);
                            $('#pending_paid_orders_amount').text('$' + (data.pending_paid_orders_amount || 0).toFixed(2));
                            $('#net_total_amount').text('$' + (data.net_total_amount || 0).toFixed(2));
                        } else {
                            alert('Failed to fetch statistics.');
                        }
                    },
                    error: function () {
                        alert('An error occurred while fetching statistics.');
                    }
                });
            }

        });
    </script>
@endsection