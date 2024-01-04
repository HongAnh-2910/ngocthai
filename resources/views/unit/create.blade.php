<div class="modal-dialog" role="document">
  <div class="modal-content">
    {!! Form::open(['url' => action('UnitController@store'), 'method' => 'post', 'id' => $quick_add ? 'quick_add_unit_form' : 'unit_add_form' ]) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'unit.add_unit' )</h4>
    </div>

    <div class="modal-body">
      <div class="row">
        {!! Form::hidden('unit_id', null, ['id' => 'unit_id']) !!}
        {!! Form::hidden('type_validate', 'create', ['id' => 'type_validate']) !!}
        <div class="form-group col-sm-12">
          {!! Form::label('actual_name', __( 'unit.name' ) . ':*') !!}
            {!! Form::text('actual_name', null, ['class' => 'form-control', 'id' => 'actual_name', 'placeholder' => __( 'unit.name' )]); !!}
        </div>

        {{--<div class="form-group col-sm-12">
          {!! Form::label('short_name', __( 'unit.short_name' ) . ':') !!}
            {!! Form::text('short_name', null, ['class' => 'form-control', 'placeholder' => __( 'unit.short_name' )]); !!}
        </div>--}}

        <div class="form-group col-sm-12">
          {!! Form::label('type', __( 'unit.type' ) . ':*') !!}
          {!! Form::select('type', $unit_types, null, ['class' => 'form-control', 'required']); !!}
        </div>

        <div class="form-group col-sm-12">
          {!! Form::label('allow_decimal', __( 'unit.allow_decimal' ) . ':*') !!}
            {!! Form::select('allow_decimal', ['1' => __('messages.yes'), '0' => __('messages.no')], 0, ['placeholder' => __( 'messages.please_select' ), 'required', 'class' => 'form-control']); !!}
        </div>
        @if(!$quick_add)
{{--          {!! Form::hidden('type_validate', 'create', ['id' => 'type_validate']) !!}--}}
          <div class="form-group col-sm-12">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                     {!! Form::checkbox('define_base_unit', 1, true,['id' => 'define_base_unit', 'class' => 'toggler', 'data-toggle_id' => 'base_unit_div' ]); !!} @lang( 'lang_v1.add_as_multiple_of_base_unit' )
                  </label> @show_tooltip(__('lang_v1.multi_unit_help'))
                </div>
            </div>
          </div>
          <div id="base_unit_div">
            <div id="area_box">
              <div class="form-group col-md-6" id="height_box">
                {!! Form::label('height', __( 'unit.height' ) . ':*') !!}
                {!! Form::number('height', null, ['class' => 'form-control input_decimal', 'id' => 'height', 'placeholder' => __( 'unit.height' )]); !!}
              </div>
              <div class="form-group col-md-6" id="width_box">
                {!! Form::label('width', __( 'unit.width' ) . ':*') !!}
                {!! Form::number('width', null, ['class' => 'form-control input_decimal', 'id' => 'width', 'placeholder' => __( 'unit.width' )]); !!}
              </div>
            </div>
            <div class="form-group col-md-12">
              {!! Form::label('base_unit_multiplier', '1 '.__( 'product.unit' ).' bằng' . ':') !!}
              <div class="base_unit">
                {!! Form::number('base_unit_multiplier', null, ['class' => 'form-control input_decimal', 'id' => 'base_unit_multiplier', 'placeholder' => __( 'lang_v1.times_base_unit' ), 'readonly']); !!}
{{--                <span id="default_unit_name" class="form-control default_unit_name">&nbsp;{{ $default_unit->name }}</span>--}}
                {!! Form::select('base_unit_id', $units, $default_unit->id, ['id' => 'base_unit_id', 'placeholder' => __( 'lang_v1.select_base_unit' ), 'class' => 'form-control']); !!}
              </div>
            </div>
          </div>
        @endif
      </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script src="{{ asset('js/unit.js') }}"></script>
