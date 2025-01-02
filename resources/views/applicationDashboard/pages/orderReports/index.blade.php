@extends('layouts.app')
@section('title', 'Order Reports')

@section('content')

<section class="content-header">
    <h1>{{__('lang_v1.client_orders_reports')}}</h1>
</section>

<section class="content">
    @component('components.filters', ['title' => __('report.filters')])

    <div class="row mb-3" style="display: flex;align-items: end;">
        <div class="col-md-3">
            <label for="start_date">{{__('lang_v1.start_date')}}</label>
            <input type="date" id="start_date" class="form-control">
        </div>
        <div class="col-md-3">
            <label for="end_date">{{__('lang_v1.end_date')}}</label>
            <input type="date" id="end_date" class="form-control">
        </div>
        <div class="col-md-3">
            <label for="search">{{__('lang_v1.search')}}</label>
            <input type="text" id="search" class="form-control" placeholder="Search by client name">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button id="filter" class="btn btn-primary mr-2">{{__('lang_v1.filter')}}</button>
            <button id="clear_filters" class="btn btn-danger">{{__('lang_v1.clear')}}</button>
        </div>
    </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary'])

    <table class="table table-bordered" id="order_report_table">
        <thead>
            <tr>
                <th>{{__('lang_v1.Client_Name')}}</th>
                <th>{{__('lang_v1.Client_Location')}}</th>
                <th>{{__('lang_v1.Total_Order_Amount')}}</th>
                <th>{{__('lang_v1.Total_Cancelled_Amount')}}</th>
            </tr>
        </thead>
    </table>
    @endcomponent
</section>


@stop
@section('javascript')

<script>
    $(document).ready(function () {
        const table = $('#order_report_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ action("ApplicationDashboard\OrderReportsController@index") }}',
                data: function (d) {
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                },
                dataSrc: function (json) {
                    // Update grand totals dynamically
                    $('#grand_total_amount').text(json.grand_total_amount);
                    $('#grand_canceled_amount').text(json.grand_canceled_amount);
                    return json.data;
                }
            },
            columns: [
                { data: 'client_name', name: 'client_name' },
                { data: 'client_location', name: 'client_location' },
                { data: 'total_amount', name: 'total_amount' },
                { data: 'canceled_amount', name: 'canceled_amount' },
            ]
        });

        // Clear button click
        $('#clear_filters').on('click', function () {
            $('#start_date').val('');
            $('#end_date').val('');
            $('#search').val('');
            table.search('').columns().search('').draw(); // Clear all filters
        });

        // Search on typing in search input
        $('#search').on('keyup', function () {
            table.search(this.value).draw();
        });

        $('#filter, #clear_filters').on('click', function () {
            table.ajax.reload();
        });
    });

</script>

@endsection