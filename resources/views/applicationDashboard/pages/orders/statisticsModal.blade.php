<!-- Modal -->
<div class="modal fade" id="orderStatisticsModal" tabindex="-1" role="dialog"
    aria-labelledby="orderStatisticsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderStatisticsModalLabel">
                    @lang('lang_v1.orders_statistics')
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Date Filters -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="statistics_start_date">
                                @lang('lang_v1.start_date')
                            </label>
                            <input type="date" class="form-control" id="statistics_start_date" name="start_date">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="statistics_end_date">@lang('lang_v1.end_date')</label>
                            <input type="date" class="form-control" id="statistics_end_date" name="end_date">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button class="btn btn-primary form-control"
                                id="filter_statistics">@lang('lang_v1.filter')</button>
                        </div>
                    </div>
                </div>

                <!-- Statistics Display -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-aqua"><i class="fa fa-shopping-cart"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">@lang('lang_v1.total_orders')</span>
                                <span class="info-box-number" id="total_orders_count">0</span>
                                <span class="info-box-number" id="total_orders_amount">$0.00</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box">
                            <span class="info-box-icon bg-aqua"><i class="fa fa-shopping-cart"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">@lang('lang_v1.total_completed_paid_orders')</span>
                                <span class="info-box-number" id="total_completed_paid_orders_count">0</span>
                                <span class="info-box-number" id="total_completed_paid_orders_amount">$0.00</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box">
                            <span class="info-box-icon bg-aqua"><i class="fa fa-shopping-cart"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">@lang('lang_v1.total_completed_not_paid_orders')</span>
                                <span class="info-box-number" id="total_completed_not_paid_orders_count">0</span>
                                <span class="info-box-number" id="total_completed_not_paid_orders_amount">$0.00</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box">
                            <span class="info-box-icon bg-red"><i class="fa fa-undo"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">@lang('lang_v1.refund_orders')</span>
                                <span class="info-box-number" id="refund_orders_count">0</span>
                                <span class="info-box-number" id="refund_orders_amount">$0.00</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box">
                            <span class="info-box-icon bg-green"><i class="fa fa-exchange"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">@lang('lang_v1.transfer_orders')</span>
                                <span class="info-box-number" id="transfer_orders_count">0</span>
                                <span class="info-box-number" id="transfer_orders_amount">$0.00</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box">
                            <span class="info-box-icon bg-yellow"><i class="fa fa-ban"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">@lang('lang_v1.cancelled_orders')</span>
                                <span class="info-box-number" id="cancelled_orders_count">0</span>
                                <span class="info-box-number" id="cancelled_orders_amount">$0.00</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">

                        <div class="info-box">
                            <span class="info-box-icon bg-blue"><i class="fa fa-calculator"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">
                                    @lang('lang_v1.net_total')
                                </span>
                                <span class="info-box-number" id="net_total_amount">$0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>