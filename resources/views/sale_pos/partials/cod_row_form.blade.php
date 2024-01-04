<div class="row">
	<input type="hidden" class="payment_row_index" value="{{ $row_index}}">
	{!! Form::hidden("cod[$row_index][type]", 'cod') !!}
	@if(isset($payment_line['id'])) {!! Form::hidden("cod[$row_index][payment_id]", $payment_line['id']) !!} @endif

	<div class="col-md-6">
		<div class="form-group">
			{!! Form::label("amount_$row_index" ,__('sale.cod_amount') . ':') !!}
			<div class="input-group">
				<span class="input-group-addon">
					<i class="fas fa-money-bill-alt"></i>
				</span>
				{!! Form::text("cod[$row_index][amount]", @num_format($payment_line['amount']), ['class' => 'form-control payment-amount input_number', 'id' => "cod_amount", 'placeholder' => __('sale.amount'), $payment_line['approval_status'] == 'approved' ? 'readonly' : '']); !!}
			</div>
		</div>
	</div>
	<div class="col-md-6">
		<div class="form-group">
			{!! Form::label("note_$row_index", __('sale.cod_note') . ':') !!}
			{!! Form::textarea("cod[$row_index][note]", $payment_line['note'], ['class' => 'form-control', 'rows' => 1, 'id' => "note_$row_index", $payment_line['approval_status'] == 'approved' ? 'readonly' : '']); !!}
		</div>
	</div>
	<div class="col-md-6 pull-right">
		<p class="align-right">
			<b>@lang('sale.total_payable_include_deposit_cod'): </b>
			<span id="total_payable_include_deposit_cod">0</span>
		</p>
	</div>
</div>
