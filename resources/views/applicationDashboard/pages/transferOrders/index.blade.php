@extends('layouts.app')
@section('title', 'Order')

@section('content')

@php
    $statuses = ['all', 'pending', 'processing'];
@endphp

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.order_transfer')
        <small>@lang('lang_v1.manage_your_orders')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
    @can('orders_refund.create')
        @slot('tool')
        <div class="box-tools">
        </div>
        @component('components.filters', ['title' => __('report.filters')])
        @include('applicationDashboard.pages.transferOrders.filters')

        @endcomponent

        @endslot
    @endcan
    @can('orders_refund.view')
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="order_transfer_table">
                <thead>
                    <tr>
                        <th>@lang('lang_v1.id')</th>
                        <!-- <th>@lang('lang_v1.business_location')</th> -->
                        <th>@lang('lang_v1.number')</th>
                        <th>@lang('lang_v1.client')</th>
                        <th>@lang('lang_v1.client_number')</th>
                        <!-- <th>@lang('lang_v1.payment_method')</th> -->
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
            </table>
        </div>
    @endcan
    @endcomponent

    <div class="modal fade orders_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    <!-- Delivery Assignment Modal -->
    @include('applicationDashboard.pages.transferOrders.assignDeliveryModal')

    <!-- Order Information Modal -->
    @include('applicationDashboard.pages.transferOrders.orderInformationModal')


</section>
<!-- /.content -->

@stop
@section('javascript')
@include('applicationDashboard.pages.transferOrders.js_code')
@endsection