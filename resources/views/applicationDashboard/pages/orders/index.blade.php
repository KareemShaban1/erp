@extends('layouts.app')
@section('title', 'Order')

@section('content')

@php
    $statuses = ['all', 'pending', 'processing'];
@endphp

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.orders')
        <small>@lang('lang_v1.manage_your_orders')</small>
    </h1>
   
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
    @can('orders.create')
        @slot('tool')
        <div class="box-tools">
            
        </div>
        @component('components.filters', ['title' => __('report.filters')])
        
            @include('applicationDashboard.pages.orders.filters')

        @endcomponent

        @endslot
    @endcan
    @can('orders.view')
    <input type="hidden" value="{{$order_status}}" id="order_status">

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="orders_table">
                <thead>
                    <tr>
                        <th></th>
                        <th>@lang('lang_v1.id')</th>
                        <th>@lang('lang_v1.business_location')</th>
                        <th>@lang('lang_v1.number')</th>
                        <th>@lang('lang_v1.invoice_no')</th>
                        <th>@lang('lang_v1.client')</th>
                        <th>@lang('lang_v1.location')</th>
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
    @include('applicationDashboard.pages.orders.assignDeliveryModal')

    <!-- Order Information Modal -->
    @include('applicationDashboard.pages.orders.orderInformationModal')

    @include('applicationDashboard.pages.orders.refundOrderModal')

    @include('applicationDashboard.pages.orders.statisticsModal')

</section>
<!-- /.content -->

@stop
@section('javascript')
@include('applicationDashboard.pages.orders.js_code')
@endsection