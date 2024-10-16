<div class="modal-dialog modal-lg" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('VariationTemplateController@store'), 'method' => 'post', 'id' => 'variation_add_form', 'class' => 'form-horizontal' ]) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang('lang_v1.add_variation')</h4>
    </div>

    <div class="modal-body">
      <!-- Variation Name -->
      <div class="form-group">
        {!! Form::label('name',__('lang_v1.variation_name') . ':*', ['class' => 'col-sm-3 control-label']) !!}
        <div class="col-sm-9">
          {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __('lang_v1.variation_name')]); !!}
        </div>
      </div>
      
     

      

      <!-- Color and Hex Code for First Variation -->
      <div class="row variation_row" data-index="0">
    
        <div class="col-md-4">
          <!-- Variation Values -->
      <div class="row form-group">
        <label class="col-sm-3 col-md-4 control-label">@lang('lang_v1.add_variation_values'):*</label>
        <div class="col-sm-7 col-md-8">
           {!! Form::text('variation_values[0][value]', null, ['class' => 'form-control', 'required', 'placeholder' => 'Enter value']); !!}
        </div>
        
      </div>
        </div>
        <div class="col-md-4">
          <!-- Color Picker -->
          <div class="row form-group">
            {!! Form::label('color', __('lang_v1.select_color') . ':', ['class' => 'col-sm-3 col-md-4 control-label']) !!}
            <div class="col-sm-9 col-md-8">
              {!! Form::color('variation_values[0][color]', '#ffffff', ['class' => 'form-control color_picker']); !!}
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <!-- Hex Code -->
          <div class="form-group">
            {!! Form::label('code', __('lang_v1.code') . ':', ['class' => 'col-sm-3 col-md-4 control-label']) !!}
            <div class="col-sm-9 col-md-8">
              {!! Form::text('variation_values[0][code]', null, ['class' => 'form-control code', 'readonly']); !!}
            </div>
          </div>
        </div>
        <div class="col-md-1">
          <button type="button" class="btn btn-primary" id="add_variation_values">+</button>
        </div>
      </div>

      <div id="variation_values"></div> <!-- This will hold additional variation rows -->

    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script>
  // Handle color picker input to set hex code value
  document.querySelectorAll('.color_picker').forEach(function(colorPicker) {
    colorPicker.addEventListener('input', function() {
      var selectedColor = this.value;
      var parentRow = this.closest('.variation_row');
      parentRow.querySelector('.code').value = selectedColor;
    });
  });

  // Add more variation values, color pickers, and hex code fields dynamically
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
              <input type="text" name="variation_values[${index}][code]" class="form-control code" readonly>
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
    document.querySelectorAll('.color_picker').forEach(function(colorPicker) {
      colorPicker.addEventListener('input', function() {
        var selectedColor = this.value;
        var parentRow = this.closest('.variation_row');
        parentRow.querySelector('.code').value = selectedColor;
      });
    });

    // Remove variation values dynamically
    document.querySelectorAll('.remove_variation_values').forEach(function(removeButton) {
      removeButton.addEventListener('click', function() {
        var parentRow = this.closest('.variation_row');
        parentRow.remove();
      });
    });
  });
</script>
