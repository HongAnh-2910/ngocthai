<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('SellController@storeVAT'), 'method' => 'post', 'id' => 'add_vat' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'lang_v1.add_vat' )</h4>
    </div>

    <div class="modal-body">
      <div class="row">
        {!! Form::hidden('transaction_id', $id, ['id' => 'transaction_id']) !!}
        <div class="form-group col-sm-12">
          {!! Form::label('vat', __( 'lang_v1.vat_money' ) . ':') !!}
            {!! Form::text('vat', number_format($transaction->vat_money), ['class' => 'form-control input_number', 'id' => 'vat']); !!}
        </div>

        <div class="form-group col-sm-12">
          {!! Form::label('transfer_fee', __( 'lang_v1.transfer_fee' ) . ':') !!}
          {!! Form::text('transfer_fee', number_format($transaction->shipping_charges), ['class' => 'form-control input_number']); !!}
        </div>
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
