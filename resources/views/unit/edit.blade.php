<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('UnitController@update', [$unit->id]), 'method' => 'PUT', 'id' => 'unit_edit_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'unit.edit_unit' )</h4>
    </div>

    <div class="modal-body">
      <div class="row">
        <input type="hidden" name="unit_id" id="unit_id" value="{{ $unit->id }}">
        {!! Form::hidden('type_validate', 'edit', ['id' => 'type_validate']) !!}
        <div class="form-group col-sm-12">
          {!! Form::label('actual_name', __( 'unit.name' ) . ':*') !!}
            {!! Form::text('actual_name', $unit->actual_name, ['class' => 'form-control', 'id' => 'actual_name', 'required', 'placeholder' => __( 'unit.name' )]); !!}
        </div>

        {{--<div class="form-group col-sm-12">
          {!! Form::label('short_name', __( 'unit.short_name' ) . ':') !!}
            {!! Form::text('short_name', $unit->short_name, ['class' => 'form-control', 'placeholder' => __( 'unit.short_name' )]); !!}
        </div>--}}

        <div class="form-group col-sm-12">
          {!! Form::label('type', __( 'unit.type' ) . ':*') !!}
          {!! Form::select('type', $unit_types, $unit->type, ['class' => 'form-control', 'required', $unit->is_default ? 'disabled' : '']); !!}
        </div>

        <div class="form-group col-sm-12">
          {!! Form::label('allow_decimal', __( 'unit.allow_decimal' ) . ':*') !!}
            {!! Form::select('allow_decimal', ['1' => __('messages.yes'), '0' => __('messages.no')], $unit->allow_decimal, ['placeholder' => __( 'messages.please_select' ), 'required', 'class' => 'form-control', $unit->is_default ? 'disabled' : '']); !!}
        </div>
        <div class="form-group col-sm-12">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                     {!! Form::checkbox('define_base_unit', 1, !empty($unit->base_unit_id),['id' => 'define_base_unit', 'class' => 'toggler', 'data-toggle_id' => 'base_unit_div', $unit->is_default ? 'disabled' : '']); !!} @lang( 'lang_v1.add_as_multiple_of_base_unit' )
                  </label> @show_tooltip(__('lang_v1.multi_unit_help'))
                </div>
            </div>
          </div>
        <div class="@if(empty($unit->base_unit_id)) hide @endif" id="base_unit_div">
          <div id="area_box">
            <div class="form-group col-md-6" id="height_box" {{ $unit->type != 'area' ? 'style=display:none' : '' }}>
              {!! Form::label('height', __( 'unit.height' ) . ':*') !!}
              {!! Form::number('height', $unit->height, ['class' => 'form-control input_decimal', 'id' => 'height', 'placeholder' => __( 'unit.height' )]); !!}
            </div>
            <div class="form-group col-md-6" id="width_box" {{ !in_array($unit->type, ['area', 'meter']) ? 'style=display:none' : '' }}>
              {!! Form::label('width', __( 'unit.width' ) . ':*') !!}
              {!! Form::number('width', $unit->width, ['class' => 'form-control input_decimal', 'id' => 'width', 'placeholder' => __( 'unit.width' )]); !!}
            </div>
          </div>
          <div class="form-group col-md-12">
            {!! Form::label('base_unit_multiplier', '1 '.$unit->actual_name.' bằng' .':') !!}
            <div class="base_unit">
              {!! Form::number('base_unit_multiplier', !empty($unit->base_unit_multiplier) ? number_format($unit->base_unit_multiplier, 3) : null, ['class' => 'form-control input_decimal', 'id' => 'base_unit_multiplier', 'placeholder' => __( 'lang_v1.times_base_unit' ), (isset($unit->base_unit) && in_array($unit->base_unit->type, ['area', 'meter'])) ? 'readonly' : '']); !!}
              {!! Form::select('base_unit_id', $units, $unit->base_unit_id, ['id' => 'base_unit_id', 'placeholder' => __( 'lang_v1.select_base_unit' ), 'class' => 'form-control ']); !!}
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.update' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>
    {!! Form::close() !!}
  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script src="{{ asset('js/unit.js') }}"></script>
