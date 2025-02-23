<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('ApplicationDashboard\ApplicationNotificationsController@store'), 'method' => 'post', 'id' => 'notifications_add_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'lang_v1.add_notification' )</h4>
    </div>

    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('title', __( 'lang_v1.title' ) . ':*') !!}
          {!! Form::text('title', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'lang_v1.title' ) ]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('message', __( 'lang_v1.message' ) . ':*') !!}
        {!! Form::textarea('body', null, ['class' => 'form-control','placeholder' => __( 'lang_v1.message' ), 'rows' => 3 ]); !!}
        </div>

      <div class="form-group">
        {!! Form::label('type', __('lang_v1.type') . ':*') !!}
        {!! Form::select('type', [''=>'select type','general' => 'General', 'client' => 'Client'], null, ['class' => 'form-control', 'id' => 'type']); !!}
      </div>

      <div class="form-group" id="client_id_container" style="display: none;">
        {!! Form::label('client_id', __('lang_v1.client_id') . ':*') !!}
        {!! Form::select('client_id', [], null, ['class' => 'form-control', 'id' => 'client_id']); !!}
      </div>


    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->