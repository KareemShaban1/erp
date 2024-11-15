<div class="modal fade" id="assignDeliveryModal" tabindex="-1" role="dialog" aria-labelledby="assignDeliveryLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="assignDeliveryLabel">@lang('lang_v1.assign_delivery')</h4>
                </div>
                <div class="modal-body">
                    <form id="assignDeliveryForm">
                        <input type="hidden" name="order_id" id="order_id">
                        <div class="form-group">
                            <label for="delivery_id">@lang('lang_v1.select_delivery')</label>
                            <select class="form-control" name="delivery_id" id="delivery_id"></select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary"
                        id="saveDeliveryAssignment">@lang('lang_v1.assign')</button>
                    <button type="button" class="btn btn-secondary"
                        data-dismiss="modal">@lang('lang_v1.cancel')</button>
                </div>
            </div>
        </div>
    </div>