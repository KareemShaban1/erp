@extends('layouts.app')
@section('title', __('lang_v1.orders'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.orders')
        <small>@lang('lang_v1.manage_your_orders')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
    @can('create', App\Models\Order::class)
        @slot('tool')
        <div class="box-tools"></div>
        @component('components.filters', ['title' => __('report.filters')])
        <div class="row">
            <div class="col-md-3">
                <input type="date" id="start_date" class="form-control" placeholder="@lang('lang_v1.start_date')">
            </div>
            <div class="col-md-3">
                <input type="date" id="end_date" class="form-control" placeholder="@lang('lang_v1.end_date')">
            </div>

            <div class="col-md-2">
                <label class="form-label">@lang('lang_v1.order_type')</label>
                <select id="order_type" class="form-control">
                    <option value="">@lang('lang_v1.all')</option>
                    <option value="order">@lang('lang_v1.order')</option>
                    <option value="order_refund">@lang('lang_v1.order_refund')</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">@lang('lang_v1.order_status')</label>
                <select id="order_status" class="form-control">
                    <option value="">@lang('lang_v1.all')</option>
                    <option value="pending">@lang('lang_v1.pending')</option>
                    <option value="processing">@lang('lang_v1.processed')</option>
                    <option value="shipped">@lang('lang_v1.shipped')</option>
                    <option value="completed">@lang('lang_v1.completed')</option>
                    <option value="cancelled">@lang('lang_v1.cancelled')</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary" id="filter_date">@lang('lang_v1.filter')</button>
                <button class="btn btn-danger" id="clear_date">@lang('lang_v1.clear')</button>
            </div>

        </div>
        @endcomponent
        @endslot
    @endcan

    @can('view', App\Models\Order::class)
        <input type="hidden" name="client_id" id="client_id" value="{{ $clientId }}">

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="orders_table">
                <thead>
                    <tr>
                        <th>@lang('lang_v1.id')</th>
                        <th>@lang('lang_v1.number')</th>
                        <th>@lang('lang_v1.order_type')</th>
                        <th>@lang('lang_v1.client')</th>
                        <th>@lang('lang_v1.payment_method')</th>
                        <th>@lang('lang_v1.order_status')</th>
                        <th>@lang('lang_v1.payment_status')</th>
                        <th>@lang('lang_v1.shipping_cost')</th>
                        <th>@lang('lang_v1.sub_total')</th>
                        <th>@lang('lang_v1.total')</th>
                        <th>@lang('lang_v1.order_date_time')</th>
                        <th>@lang('lang_v1.assign_delivery')</th>
                        <th>@lang('lang_v1.actions')</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr>
                        <th colspan="8" class="text-right">Total:</th>
                        <th id="total_sum">0.00</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endcan
    @endcomponent

    <!-- Modals -->
    <div class="modal fade orders_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

    @include('applicationDashboard.pages.orders.assignDeliveryModal')
    @include('applicationDashboard.pages.orders.orderInformationModal')

</section>
@stop

@section('javascript')
    <script>
        $(document).ready(function () {
            var clientId = $('#client_id').val();
            var orders_table = $('#orders_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ action("ApplicationDashboard\OrderReportsController@clientOrders", ["id" => ":client_id"]) }}'.replace(':client_id', clientId),
                    data: function (d) {
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                        d.order_type = $('#order_type').val();
                        d.order_status = $('#order_status').val();

                    }
                },
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'number', name: 'number' },
                    { data: 'order_type', name: 'order_type' },
                    { data: 'client_contact_name', name: 'client_contact_name' },
                    { data: 'payment_method', name: 'payment_method' },
                    { data: 'order_status', name: 'order_status', render: formatOrderStatus },
                    { data: 'payment_status', name: 'payment_status', render: formatPaymentStatus },
                    { data: 'shipping_cost', name: 'shipping_cost' },
                    { data: 'sub_total', name: 'sub_total' },
                    { data: 'total', name: 'total' },
                    { data: 'created_at', name: 'created_at', render: formatDate },
                    { data: 'order_status', name: 'order_status', render: renderAssignDeliveryButton },
                    { data: 'id', name: 'id', render: renderViewOrderButton }
                ],
                footerCallback: function (row, data, start, end, display) {
                    var api = this.api();

                    // Get total sum from the server response
                    $.ajax({
                        url: "{{ route('client.orders', ['id' => ':client_id']) }}".replace(':client_id', clientId),
                        data: {
                            start_date: $('#start_date').val(),
                            end_date: $('#end_date').val(),
                            order_type: $('#order_type').val(),
                            order_status: $('#order_status').val()
                        },
                        success: function (response) {
                            console.log(response.totalSum)
                            $(api.column(8).footer()).html(
                                 response.totalSum
                            );
                        }
                    });
                },
                fnDrawCallback: function () {
                    __currency_convert_recursively($('#orders_table'));
                }
            });

            // Date Filter Button Click
            $('#filter_date').click(function () {
                orders_table.ajax.reload();
            });

            $('#clear_date').click(function () {
                $('#start_date, #end_date').val('');
                orders_table.ajax.reload();
            });

            // Functions for rendering status fields
            function formatOrderStatus(data, type, row) {
                let badgeClass = {
                    'pending': 'badge btn-warning',
                    'processing': 'badge btn-info',
                    'shipped': 'badge btn-primary',
                    'completed': 'badge btn-success',
                    'cancelled': 'badge btn-danger'
                }[data] || 'badge badge-secondary';

                return `<span class="${badgeClass}">${capitalize(data)}</span>
                    <select class="form-control change-order-status" data-order-id="${row.id}">
                        ${generateOptions(['pending', 'processing', 'shipped', 'completed', 'cancelled'], data)}
                    </select>`;
            }

            function formatPaymentStatus(data, type, row) {
                return `<select class="form-control change-payment-status" data-order-id="${row.id}">
                    ${generateOptions(['pending', 'paid', 'failed'], data)}
                </select>`;
            }

            function formatDate(data) {
                return data ? new Date(data).toLocaleString() : '';
            }

            function renderAssignDeliveryButton(data, type, row) {
                if (data === 'processing' && !row.has_delivery) {
                    return `<button class="btn btn-primary assign-delivery-btn" 
                        data-order-id="${row.id}" 
                        data-contact-name="${row.client_contact_name}">
                        @lang('lang_v1.assign_delivery')
                    </button>`;
                }
                if (row.has_delivery) {
                    return `<span class="badge badge-success">@lang('lang_v1.delivery_assigned')</span>`;
                }
                return '';
            }

            function renderViewOrderButton(data) {
                return `<button class="btn btn-info view-order-info-btn" data-order-id="${data}">
                    @lang('lang_v1.view_order_info')
                </button>`;
            }

            function generateOptions(options, selected) {
                return options.map(option =>
                    `<option value="${option}" ${option === selected ? 'selected' : ''}>${capitalize(option)}</option>`
                ).join('');
            }

            function capitalize(str) {
                return str.charAt(0).toUpperCase() + str.slice(1);
            }
        });
    </script>
@endsection