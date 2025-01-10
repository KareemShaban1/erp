<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('ApplicationDashboard\TagController@store'), 'method' => 'post', 'id' => 'tag_add_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'lang_v1.add_tag' )</h4>
    </div>

    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('name', __( 'lang_v1.tag_name' ) . ':*') !!}
          {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'lang_v1.tag_name' ) ]); !!}
      </div>

      <div class="form-group">
            <label>
              {!! Form::checkbox('active', 1, true, ['class' => 'input-icheck']); !!} <strong>@lang('lang_v1.is_active')</strong>
            </label>
          </div>

    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->