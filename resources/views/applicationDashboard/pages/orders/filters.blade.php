<div class="row">

    <div class="col-md-3">
        <div class="form-group">
            <label for="business_location">@lang('lang_v1.start_date')</label>
            <input type="date" id="start_date" class="form-control" placeholder="Start Date">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="business_location">@lang('lang_v1.end_date')</label>
            <input type="date" id="end_date" class="form-control" placeholder="End Date">
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="business_location">@lang('lang_v1.order_status')</label>
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
    <div class="col-md-3">
        <button class="btn btn-primary" id="filter_date">@lang('lang_v1.filter') </button>
        <button class="btn btn-primary" id="clear_date">@lang('lang_v1.clear') </button>

    </div>
</div>

<div class="row">
    <!-- Business Location Filter -->
    <div class="col-md-3">
        <div class="form-group">
            <label for="business_location">@lang('lang_v1.business_location')</label>
            {!! Form::select('business_location', $business_locations->pluck('name', 'id'), null, [
    'class' => 'form-control',
    'id' => 'business_location',
    'placeholder' => __('messages.please_select')
]) !!}
        </div>
    </div>

    <!-- Delivery Name Filter -->
    <div class="col-md-3">
        <div class="form-group">
            <label for="delivery_name">@lang('lang_v1.delivery_name')</label>
            <input type="text" id="delivery_name" class="form-control" placeholder="@lang('lang_v1.delivery_name')">
        </div>
    </div>

    <!-- Payment Status Filter -->
    <div class="col-md-3">
        <div class="form-group">
            <label for="payment_status">@lang('lang_v1.payment_status')</label>
            {!! Form::select('payment_status', [
    'all' => __('All'),
    'pending' => __('Pending'),
    'paid' => __('Paid'),
    'failed' => __('Failed'),
], 'all', [
    'class' => 'form-control',
    'id' => 'payment_status',
    'required'
]) !!}
        </div>
    </div>
    <div class="col-md-3">
    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#orderStatisticsModal">
             @lang('lang_v1.view_orders_statistics') 
        </button>
    </div>
</div>