<div class="modal-dialog modal-lg" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('VariationTemplateController@update', [$variation->id]), 'method' => 'PUT', 'id' => 'variation_edit_form', 'class' => 'form-horizontal' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang('lang_v1.edit_variation')</h4>
    </div>

    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('name',__('lang_v1.variation_name') . ':*', ['class' => 'col-sm-3 control-label']) !!}

        <div class="col-sm-9">
          {!! Form::text('name', $variation->name, ['class' => 'form-control', 'required', 'placeholder' => __('lang_v1.variation_name')]); !!}
        </div>
      </div>
      <div class="row variation_row">
      @foreach( $variation->values as $attr)
  @if( $loop->first )
    <div class="col-md-4">
      <div class="row form-group">
        <label class="col-sm-3 col-md-4 control-label">@lang('lang_v1.add_variation_values'):*</label>
        <div class="col-sm-7 col-md-8">
          {!! Form::text('edit_variation_values[' . $attr->id . '][name]', $attr->name, ['class' => 'form-control', 'required']); !!}
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <!-- Color Picker -->
      <div class="row form-group">
        {!! Form::label('color', __('lang_v1.select_color') . ':', ['class' => 'col-sm-3 col-md-4 control-label']) !!}
        <div class="col-sm-9 col-md-8">
          {!! Form::color('edit_variation_values[' . $attr->id . '][color]', $attr->code ?? '#ffffff', ['class' => 'form-control color_picker']); !!}
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="row form-group">
        <label class="col-sm-3 col-md-4 control-label">@lang('lang_v1.code'):*</label>
        <div class="col-sm-7 col-md-8">
          {!! Form::text('edit_variation_values[' . $attr->id . '][code]', $attr->code ?? '#ffffff', ['class' => 'form-control code', 'required']); !!}
        </div>
      </div>
    </div>
  @endif
@endforeach


        <div class="col-sm-1 col-md-1">
          <button type="button" class="btn btn-primary" id="add_variation_values">+</button>
        </div>
      </div>
      <div id="variation_values" class="variation_row">
        @foreach( $variation->values as $attr)
          @if( !$loop->first )
          <div class="col-md-4">
      <div class="row form-group">
        <label class="col-sm-3 col-md-4 control-label">@lang('lang_v1.add_variation_values'):*</label>
        <div class="col-sm-7 col-md-8">
          {!! Form::text('edit_variation_values[' . $attr->id . '][name]', $attr->name, ['class' => 'form-control', 'required']); !!}
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <!-- Color Picker -->
      <div class="row form-group">
        {!! Form::label('color', __('lang_v1.select_color') . ':', ['class' => 'col-sm-3 col-md-4 control-label']) !!}
        <div class="col-sm-9 col-md-8">
          {!! Form::color('edit_variation_values[' . $attr->id . '][color]', $attr->code ?? '#ffffff', ['class' => 'form-control color_picker']); !!}
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="row form-group">
        <label class="col-sm-3 col-md-4 control-label">@lang('lang_v1.code'):*</label>
        <div class="col-sm-7 col-md-8">
          {!! Form::text('edit_variation_values[' . $attr->id . '][code]', $attr->code ?? '#ffffff', ['class' => 'form-control code', 'required']); !!}
        </div>
      </div>
    </div>
          @endif
        @endforeach
      </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script>
 document.getElementById('add_variation_values').addEventListener('click', function() {
    var index = document.querySelectorAll('.variation_row').length;
    var newRow = `
      <div class="row variation_row" data-index="${index}">
        <div class="col-md-4">
          <div class="form-group">
            <label class="col-sm-3 col-md-4 control-label">@lang('lang_v1.add_variation_values'):*</label>
            <div class="col-sm-7 col-md-8">
              <input type="text" name="variation_values[${index}][value]" class="form-control" required placeholder="Enter value">
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <!-- Color Picker -->
          <div class="form-group">
            <label class="col-sm-3 col-md-4 control-label">@lang('lang_v1.select_color'):</label>
            <div class="col-sm-9 col-md-8">
              <input type="color" name="variation_values[${index}][color]" class="form-control color_picker" value="#ffffff">
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <!-- Hex Code -->
          <div class="form-group">
            <label class="col-sm-3 col-md-4 control-label">@lang('lang_v1.code'):</label>
            <div class="col-sm-9 col-md-8">
              <input type="text" name="variation_values[${index}][code]" class="form-control code" value="#ffffff">
            </div>
          </div>
        </div>
        <div class="col-sm-1">
          <button type="button" class="btn btn-danger remove_variation_values">-</button>
        </div>
      </div>
    `;

    document.getElementById('variation_values').insertAdjacentHTML('beforeend', newRow);

    // Add event listener to the new color picker
    attachColorPickerEvents();
    attachRemoveVariationEvent();
    attachCodeInputEvents();
});

// Function to synchronize color picker and hex code inputs
function attachColorPickerEvents() {
    document.querySelectorAll('.color_picker').forEach(function(colorPicker) {
        colorPicker.addEventListener('input', function() {
            var selectedColor = this.value;
            console.log(selectedColor)
            var parentRow = this.closest('.variation_row');
            if (parentRow) {
              console.log(parentRow)
                var codeInput = parentRow.querySelector('.code');
                console.log(codeInput)
                if (codeInput) {
                    codeInput.value = selectedColor;
                }
            }
        });
    });
}

// Function to handle removing variation values dynamically
function attachRemoveVariationEvent() {
    document.querySelectorAll('.remove_variation_values').forEach(function(removeButton) {
        removeButton.addEventListener('click', function() {
            var parentRow = this.closest('.variation_row');
            if (parentRow) {
                parentRow.remove();
            }
        });
    });
}

// Function to update color picker based on hex code input
function attachCodeInputEvents() {
    document.querySelectorAll('.code').forEach(function(codeInput) {
        codeInput.addEventListener('input', function() {
            var code = this.value;
            var parentRow = this.closest('.variation_row');
            if (parentRow) {
                var colorPicker = parentRow.querySelector('.color_picker');
                if (colorPicker) {
                    // Validate the hex code and update the color picker
                    if(/^#([0-9A-F]{3}){1,2}$/i.test(code)) {
                        colorPicker.value = code;
                    }
                }
            }
        });
    });
}

// Initialize events for existing elements
attachColorPickerEvents();
attachRemoveVariationEvent();
attachCodeInputEvents();


</script>
