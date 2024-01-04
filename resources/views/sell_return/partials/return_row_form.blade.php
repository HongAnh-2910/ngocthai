<div class="row">
	<input type="hidden" class="payment_row_index" value="{{ $row_index}}">
	{!! Form::hidden("payment[$row_index][type]", 'return') !!}
	@if(isset($payment_line['id'])) {!! Form::hidden("payment[$row_index][payment_id]", $payment_line['id']) !!} @endif

	<div class="col-md-6">
		<div class="form-group">
			{!! Form::label("amount_$row_index" ,__('sale.deposit_amount') . ':') !!}
			<div class="input-group">
				<span class="input-group-addon">
					<i class="fas fa-money-bill-alt"></i>
				</span>
				{!! Form::text("payment[$row_index][amount]", @num_format($payment_line['amount']), ['class' => 'form-control payment-amount input_number', 'id' => "amount_$row_index", 'placeholder' => __('sale.amount')]); !!}
			</div>
		</div>
	</div>
	<div class="col-md-6">
		<div class="form-group">
			{!! Form::label("note_$row_index", __('sale.deposit_note') . ':') !!}
			{!! Form::textarea("payment[$row_index][note]", $payment_line['note'], ['class' => 'form-control', 'rows' => 1, 'id' => "note_$row_index"]); !!}
		</div>
	</div>
</div>
