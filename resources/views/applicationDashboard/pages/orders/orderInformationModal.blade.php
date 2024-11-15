<div class="modal fade" id="viewOrderInfoModal" tabindex="-1" role="dialog" aria-labelledby="viewOrderInfoLabel">
        <div class="modal-dialog  modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="viewOrderInfoLabel">@lang('lang_v1.order_details')</h4>
                </div>
                <div class="modal-body">
                    <form id="viewOrderInfoForm">
                        <input type="hidden" name="order_id" id="view_order_id">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('lang_v1.order_number'):</label>
                                    <p id="order_number"></p>
                                </div>

                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('lang_v1.business_location'):</label>
                                    <p id="business_location"></p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('lang_v1.client'):</label>
                                    <p id="client_name"></p>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('lang_v1.payment_method'):</label>
                                    <p id="payment_method"></p>
                                </div>

                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('lang_v1.shipping_cost'):</label>
                                    <p id="shipping_cost"></p>
                                </div>

                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('lang_v1.sub_total'):</label>
                                    <p id="sub_total"></p>
                                </div>

                            </div>
                        </div>


                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('lang_v1.total'):</label>
                                    <p id="total">
                                    </p>
                                </div>

                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('lang_v1.order_status'):</label>
                                    <p id="order_status">
                                    </p>
                                </div>

                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('lang_v1.payment_status'):</label>
                                    <p id="payment_status">
                                    </p>
                                </div>

                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>@lang('lang_v1.order_items'):</label>
                                    <table class="table table-bordered" id="order_items_table">
                                        <thead>
                                            <tr>
                                                <th>@lang('lang_v1.image')</th>
                                                <th>@lang('lang_v1.item_name')</th>
                                                <th>@lang('lang_v1.quantity')</th>
                                                <th>@lang('lang_v1.unit_price')</th>
                                                <th>@lang('lang_v1.total_price')</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Items will be dynamically inserted here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>





                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('lang_v1.close')</button>
                </div>
            </div>
        </div>
    </div>
