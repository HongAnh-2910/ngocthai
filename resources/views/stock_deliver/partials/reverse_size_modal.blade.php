<div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">
                @lang('sale.reverse_size')
            </h4>
        </div>
        {!! Form::open(['url' => action('SellPosController@postReverseSize', [$plate_stock->id]), 'method' => 'post', 'id' => 'reverse_size_form' ]) !!}
        {!! Form::hidden('plate_stock_id', $plate_stock->id, ['id' => 'reverse_plate_stock_id']) !!}
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('reverse_quantity', __('sale.reverse_quantity') . ':') !!}
                        {!! Form::number('reverse_quantity', 1, ['class' => 'form-control', 'placeholder' => __('sale.reverse_quantity'), 'id' => 'reverse_quantity', 'required']); !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary" id="reverse_confirm">@lang( 'messages.update' )</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
        {!! Form::close() !!}
    </div>
</div>
