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
    @can('orders_refund.create')
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
    @can('orders_refund.view')
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
                        <!-- <th>@lang('lang_v1.refund_status')</th> -->
                        <!-- <th>@lang('lang_v1.order_status')</th> -->
                         <!-- <th>@lang('lang_v1.delivery_assigned')</th> -->
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
                            <select name="status" id="orderRefundStatus" class="form-control" readonly>
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

    @include('applicationDashboard.pages.orderRefunds.orderInformationModal')

</section>

@stop

@include('applicationDashboard.pages.orderRefunds.js_code')

