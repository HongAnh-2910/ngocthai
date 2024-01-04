<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('WarehouseController@update', [$warehouse->id]), 'method' => 'PUT', 'id' => 'warehouse_edit_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'warehouse.edit_warehouse' )</h4>
    </div>

    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('name', __( 'warehouse.warehouse_name' ) . ':*') !!}
          {!! Form::text('name', $warehouse->name, ['class' => 'form-control', 'required', 'placeholder' => __( 'warehouse.warehouse_name' )]); !!}
      </div>
      <div class="form-group">
        {!! Form::label('location_id', __('warehouse.business_location').':*') !!}
        {!! Form::select('location_id', $business_locations, $warehouse->location_id, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
      </div>
      <div class="form-group">
        {!! Form::label('description', __( 'warehouse.short_description' ) . ':') !!}
          {!! Form::text('description', $warehouse->description, ['class' => 'form-control','placeholder' => __( 'warehouse.short_description' )]); !!}
      </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.update' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
