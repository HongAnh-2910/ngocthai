<div class="sell_order_print">
	<div class="invoice_no">{{ $receipt_details->invoice_no }}</div>
	<div class="contact">
		@if(!empty($receipt_details->delivered_to))
			<span class="delivered_to">{{ $receipt_details->delivered_to }}</span>
		@else
			<span>&nbsp;</span>
		@endif
		@if(!empty($receipt_details->shipping_note))
			<span style="float: right" class="shipping_note">{{ $receipt_details->shipping_note }}</span>
		@else
			<span>&nbsp;</span>
		@endif
	</div>

	<div class="shipping_address">
		@if(!empty($receipt_details->shipping_address))
			<span>{{ $receipt_details->shipping_address }}</span>
		@else
			<span>&nbsp;</span>
		@endif
	</div>

	<div class="total_block">
		<table style="margin-top: 30px">
			@if(!empty($receipt_details->shipping_charges))
				<tr style="line-height: 1.5">
					<td style="width: 30%">
						<span>{{ $receipt_details->shipping_charges_label }}</span>
					</td>
					<td style="width: 30%">

					</td>
					<td style="width: 30%">
						<span class="price_right">{{ $receipt_details->shipping_charges }}</span>
					</td>
				</tr>
			@endif
				<tr style="line-height: 1.5">
					<td style="width: 30%">
						<span>{{ $receipt_details->vat_label }}</span>
					</td>
					<td style="width: 30%">

					</td>
					<td style="width: 30%">
						<span class="price_right">{{ $receipt_details->vat_money }}</span>
					</td>
				</tr>
			<tr style="line-height: 1.5">
				<td style="width: 30%">
					<span style="font-size: 16px;">{{ $receipt_details->total_label }}</span>
				</td>
				<td style="width: 30%"></td>
				<td style="width: 30%">
					<span class="price_right" style="font-size: 16px;">{{ $receipt_details->total }}</span>
				</td>
			</tr>
		</table>
	</div>
	<div class="signature_block">
		<div class="invoice_date" >
			<div class="invoice_date_inner">
				<span class="invoice_day">{{ $receipt_details->invoice_day }}</span>
				<span class="invoice_month">{{ $receipt_details->invoice_month }}</span>
				<span class="invoice_year">{{ $receipt_details->invoice_year }}</span>
			</div>
		</div>

		<div class="signature">
			<div class="sale_staff_signature">
				<span>{{ $receipt_details->added_by }}</span>
			</div>
		</div>
	</div>
</div>
