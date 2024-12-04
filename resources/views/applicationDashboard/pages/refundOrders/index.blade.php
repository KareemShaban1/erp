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
    @can('lang_v1.create')
        @slot('tool')
        <div class="box-tools">
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
                    <div class="input-group">
                        <!-- <span class="input-group-addon">
                                            <i class="fa fa-user"></i>
                                        </span> -->
                        {!! Form::select('status', [
            'all' => __('All'),
            'pending' => __('Pending'),
            'processing' => __('Processing'),
            'shipped' => __('Shipped'),
            'completed' => __('Completed'),
            'cancelled' => __('Cancelled')
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
            <table class="table table-bordered table-striped" id="orders_table">
                <thead>
                    <tr>
                        <th>@lang('lang_v1.id')</th>
                        <th>@lang('lang_v1.order_type')</th>
                        <th>@lang('lang_v1.number')</th>
                        <th>@lang('lang_v1.client')</th>
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
    @include('applicationDashboard.pages.refundOrders.orderInformationModal')


</section>
<!-- /.content -->

@stop
@section('javascript')
@include('applicationDashboard.pages.refundOrders.js_code')
@endsection