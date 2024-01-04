{{-- Layout for A5 --}}
<table class="sell-order-print-a5" style="width: 135mm; margin-left: 4mm; margin-top: 5mm;">
	<tr style="height: 26.8mm;">
		<td colspan="6"></td>
	</tr>
	<tr style="height: 4.4mm;">
		<td colspan="6" style="text-align: right; line-height: 0; padding-right: 1mm;">{{ $receipt_details->ref_no }}</td>
	</tr>
	<tr style="height: 5mm;">
		<td></td>
		<td colspan="2" style="text-align: right; line-height: 0;">{{ $receipt_details->is_visitor ? 'KH' : '' }}{{ $receipt_details->is_visitor && !empty($receipt_details->customer_name) ? ': ' : '' }}{{ $receipt_details->customer_name }}</td>
		<td colspan="3" style="text-align: right; line-height: 0; padding-right: 1mm;">{{ !empty($receipt_details->shipping_note) ? $receipt_details->shipping_note : '' }}</td>
	</tr>
	<tr style="height: 5.76mm; vertical-align: bottom;">
		<td></td>
		<td colspan="5">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $receipt_details->address }}</td>
	</tr>

	<tr style="height: 10.51mm;">
		<td style="width: 10mm;"></td>
		<td style="width: 49mm;"></td>
		<td style="width: 15mm;"></td>
		<td style="width: 18mm;"></td>
		<td style="/*width: 20mm;*/"></td>
		<td style="/*width: 0;*/"></td>
	</tr>

	<tr class="sell-order-print-a5-total-row">
		<td colspan="2">@lang('sale.deposit_amount')</td>
		<td colspan="4">{{ @num_format($receipt_details->final_total) }}đ</td>
	</tr>

	@for($i = 1; $i <= 8; $i++)
		<tr class="sell-order-print-a5-product-row">
			<td colspan="6"></td>
		</tr>
	@endfor

	<tr class="sell-order-print-a5-total-row">
		<td colspan="6"></td>
	</tr>

	<tr style="height: 22mm;">
		<td colspan="6"></td>
	</tr>

	<tr style="height: 5mm; vertical-align: bottom;">
		<td colspan="6">
			<span style="text-align: right; float: right;">&nbsp;&nbsp;&nbsp;{{ $receipt_details->invoice_day }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $receipt_details->invoice_month }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $receipt_details->invoice_year }}&nbsp;</span>
		</td>
	</tr>

	<tr style="height: 8mm;">
		<td colspan="6"></td>
	</tr>

	<tr style="height: 5mm; vertical-align: bottom;">
		<td colspan="4"></td>
		<td colspan="2" style="text-align: center;">{{ $receipt_details->added_by }}</td>
	</tr>
</table>

{{--
<div class="sell_order_print">
	<div class="invoice_no">{{ $receipt_details->ref_no }}</div>
	<div class="contact">
		<span class="delivered_to">{{ $receipt_details->customer_name }}</span>
		<span style="float: right" class="shipping_note">{{ !empty($receipt_details->additional_notes) ? $receipt_details->additional_notes : '' }}</span>
	</div>

	<div class="shipping_address">
		<span>{{ !empty($receipt_details->address) ? $receipt_details->address : '' }}</span>
	</div>

	<div class="total_block">
		<table style="margin-top: 30px">
			<tr style="line-height: 1.5">
				<td style="width: 30%">
					<span style="font-size: 16px;">@lang('sale.deposit_amount')</span>
				</td>
				<td style="width: 30%"></td>
				<td style="width: 30%">
					<span class="price_right" style="font-size: 16px;">{{ @num_format($receipt_details->final_total) }}đ</span>
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
--}}
