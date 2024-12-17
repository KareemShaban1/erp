<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('ApplicationDashboard\BannerController@update', [$banner->id]),'method' => 'PUT', 'id' => 'banner_edit_form']) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'lang_v1.add_banner' )</h4>
    </div>

    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('name', __( 'lang_v1.banner_name' ) . ':*') !!}
          {!! Form::text('name',  $banner->name, ['class' => 'form-control', 'required', 'placeholder' => __( 'lang_v1.banner_name' ) ]); !!}
      </div>

      <div class="form-group">
      <label>
          {!! Form::checkbox('active', 1, $banner->active, ['class' => 'input-icheck']) !!} 
          <strong>@lang('lang_v1.is_active')</strong>
        </label>
          </div>

      <div class="form-group">
            {!! Form::label('image', __('lang_v1.product_image') . ':') !!}
            {!! Form::file('image', ['id' => 'upload_image', 'accept' => 'image/*']); !!}
            <small><p class="help-block">@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)]) <br> @lang('lang_v1.aspect_ratio_should_be_1_1')</p></small>
          </div>

           <!-- Module Type -->
      <div class="form-group">
        {!! Form::label('module_type', __('lang_v1.module_type') . ':*') !!}
        {!! Form::select('module_type', [''=>'select type','product' => 'Product', 'category' => 'Category'], $banner->module_type, ['class' => 'form-control', 'id' => 'module_type', 'required']); !!}
      </div>

      <!-- Module ID (Dynamic Select) -->
      <div class="form-group" id="module_id_container">
    {!! Form::label('module_id', __('lang_v1.module_id') . ':*') !!}
    {!! Form::select('module_id', [], null, [
        'class' => 'form-control select2',
        'id' => 'module_id',
        'data-selected-id' => $banner->module_id ?? null,
        'required',
    ]) !!}
</div>




    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->