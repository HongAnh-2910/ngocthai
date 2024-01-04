<!-- business information here -->
<div class="print-shipping">
	<div class="row">
		<!-- business information here -->
		<div class="col-xs-12 text-center">
			<!-- Invoice  number  -->
			<p style="width: 100% !important" class="word-wrap">
				<span class="pull-left text-left word-wrap">
					@if(!empty($receipt_details->invoice_no_prefix))
						<b>@lang('sale.invoice_no'): </b>
					@endif
					<b>{{ $receipt_details->invoice_no }}</b>

					<!-- customer info -->
					<br>
					<b>Bên nhận: </b>
					@if(!empty($receipt_details->delivered_to))
						<b class="delivered_to">{{ $receipt_details->delivered_to }}</b>
					@else
						<span>&nbsp;</span>
					@endif
					@if(!empty($receipt_details->shipping_note))
						<b>{{ $receipt_details->shipping_note }}</b>
					@else
						<span>&nbsp;</span>
					@endif
					<br><b>@lang('lang_v1.shipping_address'): </b>
					@if(!empty($receipt_details->shipping_address))
						<b>{{ $receipt_details->shipping_address }}</b>
					@else
						<span>&nbsp;</span>
					@endif
				</span>
			</p>
		</div>
	</div>

	<div class="row">
		<div class="col-xs-12">
			<br/>
			<table class="table table-bordered">
				<thead>
					<tr>
						<th>STT</th>
						<th>Loại hàng</th>
						<th>ĐVT</th>
						<th>@lang( 'product.height' ) (m)</th>
						<th>@lang( 'product.width' ) (m)</th>
						<th>@lang( 'purchase.purchase_quantity' )</th>
					</tr>
				</thead>
				<tbody>
					@foreach($receipt_details->lines as $line)
						<tr>
							<td>{{ $loop->iteration }}</td>
							<td style="word-break: break-all;">
								{{$line['name']}}
							</td>
							<td>
								{{ $line['unit_type'] == 'pcs' ? $line['units'] : ($line['is_default_unit'] == 0 ? __('unit.plate') : 'm2') }}
							</td>
							<td>
								{{ $line['unit_type'] == 'pcs' ? '' : @size_format($line['height']) }}
							</td>
							<td>
								{{ $line['unit_type'] == 'pcs' ? '' : @size_format($line['width']) }}
							</td>
							<td>
								{{ number_format($line['quantity_line']) }}
							</td>
						</tr>
					@endforeach
				</tbody>
				<tfoot>
					<tr>
						<td colspan="6">
							<div class="text-cod" style="text-transform: uppercase;text-align: center;">Vận chuyển thu: {{ $receipt_details->cod }}</div>
						</td>
					</tr>
				</tfoot>
			</table>
		</div>

		<div class="row note-shipping">
			<div class="col-md-12">
				<div class="note-text">* Ghi chú:</div>
				<ul>
					<li>
						Bên bán và bên mua xác nhận đã giao và nhận đầy đủ số lượng hàng hóa trên.
					</li>
					<li>
						Hàng hóa còn nguyên đai nguyên kiện, không bị trầy xước hay nứt vỡ.
					</li>
				</ul>
			</div>
		</div>
	</div>

	<div class="row date-shipping" style="float: right">
		<div class="col-md-12">
			<div style="font-style: italic;">Hà Nội, ngày {{ $receipt_details->invoice_day }} tháng {{ $receipt_details->invoice_month }} năm {{ $receipt_details->invoice_year }}</div>
			<h4>
				<div class="signature-ship">Xác nhận bên nhận hàng</div>
			</h4>
		</div>
	</div>
</div>
