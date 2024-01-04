<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('WarehouseController@store'), 'method' => 'post', 'id' => $quick_add ? 'quick_add_warehouse_form' : 'warehouse_add_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'warehouse.add_warehouse' )</h4>
    </div>

    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('name', __( 'warehouse.warehouse_name' ) . ':*') !!}
          {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'warehouse.warehouse_name' ) ]); !!}
      </div>

      @if(count($business_locations) == 1)
        @php
          $default_location = current(array_keys($business_locations->toArray()));
        @endphp
      @else
        @php
          $default_location = null;
        @endphp
      @endif
      <div class="form-group">
        {!! Form::label('location_id', __('warehouse.business_location').':*') !!}
        {!! Form::select('location_id', $business_locations, $default_location, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
      </div>

      <div class="form-group">
        {!! Form::label('description', __( 'warehouse.short_description' ) . ':') !!}
          {!! Form::text('description', null, ['class' => 'form-control','placeholder' => __( 'warehouse.short_description' )]); !!}
      </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
