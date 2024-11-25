<!-- Refund Modal -->
<div class="modal fade" id="refundOrderModal" tabindex="-1" role="dialog" aria-labelledby="refundOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="refundOrderModalLabel">@lang('lang_v1.refund_order')</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="refundOrderForm">
                    <input type="hidden" name="order_id" id="refund_order_id">
                    
                    <div class="row">
                              <div class="col-md-12">
                              <div class="form-group">
                                        <label>@lang('lang_v1.order_items'):</label>
                                        <table class="table table-bordered" id="order_items_table">
                                        <thead>
                                                  <tr>
                                                  <th>@lang('lang_v1.image')</th>
                                                  <th>@lang('lang_v1.item_name')</th>
                                                  <!-- <th>@lang('lang_v1.quantity')</th>
                                                  <th>@lang('lang_v1.unit_price')</th>
                                                  <th>@lang('lang_v1.total_price')</th> -->
                                                  <th>@lang('lang_v1.reason')</th>
                                                  <th>@lang('lang_v1.refund_amount')</th>
                                                  <th>@lang('lang_v1.refund_status')</th>
                                                  <th>@lang('lang_v1.admin_response')</th>
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
                <button type="button" id="saveRefund" class="btn btn-primary">@lang('lang_v1.save')</button>
            </div>
        </div>
    </div>
</div>
