@php
	$num_total_row = 1;
	$max_row = 9;

    if(!empty($receipt_details->discount_amount)){
        $num_total_row += 1;
    }
    if(!empty($receipt_details->tax)){
        $num_total_row += 1;
    }
    if(!empty($receipt_details->shipping_charges)){
        $num_total_row += 1;
    }
    if(!empty($receipt_details->deposit)){
        $num_total_row += 1;
    }
    if(!empty($receipt_details->cod)){
        $num_total_row += 1;
    }
    if(!empty($receipt_details->deposit) || !empty($receipt_details->cod)){
        $num_total_row += 1;
    }

    $x2_total_row = intdiv($num_total_row, 2) + ($num_total_row % 2);
	$max_product_row = $max_row - $x2_total_row;
    $num_empty_row = count($receipt_details->lines) <= $max_product_row ? $max_product_row - count($receipt_details->lines) : 0;
    $is_receiver_different = $receipt_details->customer_name != $receipt_details->delivered_to && !$receipt_details->is_visitor;
@endphp

{{-- Layout for A5 --}}
<table class="sell-order-print-a5" style="width: 135mm; margin-left: 4mm; margin-top: 5mm;">
	<tr style="height: 26.8mm;">
		<td colspan="6"></td>
	</tr>
	<tr style="height: 4.4mm;">
		@if($is_receiver_different)
			<td></td>
			<td colspan="4" style="text-align: left; line-height: 0; padding-left: 20mm;">
				{{ $receipt_details->customer_name }}
			</td>
			<td style="text-align: right; line-height: 0; padding-right: 1mm;">{{ $receipt_details->invoice_no }}</td>
		@else
			<td colspan="6" style="text-align: right; line-height: 0; padding-right: 1mm;">{{ $receipt_details->invoice_no }}</td>
		@endif
	</tr>
	{{--<tr style="height: 4.4mm;">
		<td></td>
		<td colspan="2" style="text-align: right; line-height: 0;">
			@if($is_receiver_different)
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $receipt_details->customer_name }}
			@endif
		</td>
		<td colspan="3" style="text-align: right; line-height: 0; padding-right: 1mm;">{{ $receipt_details->invoice_no }}</td>
	</tr>--}}
	<tr style="height: 5mm;">
		<td></td>

		@if($is_receiver_different)
			<td colspan="3" style="text-align: left; line-height: 0; padding-left: 20mm; white-space: nowrap; text-overflow: ellipsis;">( {{ $receipt_details->delivered_to }} )</td>
			<td colspan="2" style="text-align: right; line-height: 0; padding-right: 1mm; white-space: nowrap; text-overflow: ellipsis;">{{ !empty($receipt_details->shipping_note) ? $receipt_details->shipping_note : '' }}</td>
		@else
			<td colspan="2" style="text-align: left; line-height: 0; padding-left: 20mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $receipt_details->is_visitor ? 'KH' : '' }}{{ $receipt_details->is_visitor && !empty($receipt_details->delivered_to) ? ': ' : '' }}{{ $receipt_details->delivered_to }} @if($receipt_details->customer_name != $receipt_details->delivered_to && !$receipt_details->is_visitor)(KH: {{ $receipt_details->customer_name }})@endif</td>
			<td colspan="3" style="text-align: right; line-height: 0; padding-right: 1mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ !empty($receipt_details->shipping_note) ? $receipt_details->shipping_note : '' }}</td>
		@endif

		{{--<td colspan="2" style="text-align: right; line-height: 0;">{{ $receipt_details->is_visitor ? 'KH' : '' }}{{ $receipt_details->is_visitor && !empty($receipt_details->delivered_to) ? ': ' : '' }}{{ $receipt_details->delivered_to }} @if($receipt_details->customer_name != $receipt_details->delivered_to && !$receipt_details->is_visitor)(KH: {{ $receipt_details->customer_name }})@endif</td>
		<td colspan="3" style="text-align: right; line-height: 0; padding-right: 1mm;">{{ !empty($receipt_details->shipping_note) ? $receipt_details->shipping_note : '' }}</td>--}}
	</tr>
	<tr style="height: 5.76mm; vertical-align: bottom;">
		<td></td>
		<td colspan="5">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $receipt_details->shipping_address }}</td>
	</tr>

	<tr style="height: 10.51mm;">
		<td style="width: 10mm;"></td>
		<td style="width: 49mm;"></td>
		<td style="width: 15mm;"></td>
		<td style="width: 18mm;"></td>
		<td style="/*width: 20mm;*/"></td>
		<td style="/*width: 0;*/"></td>
	</tr>

	@foreach($receipt_details->lines as $line)
		<tr style="vertical-align: top;" class="sell-order-print-a5-product-row">
			<td style="text-align: center;">{{ $loop->iteration }}</td>
			<td>
				{{$line['name']}}{{ !empty($line['variation']) ? ' - '.$line['variation'] : '' }}
				@if($line['unit_type'] == 'area')
					({{ @size_format($line['height']) }} x {{ @size_format($line['width']) }}m)
				@elseif($line['unit_type'] == 'meter')
					({{ @size_format($line['width']) }}m)
				@endif
			</td>
			<td style="text-align: center;">
				@if($line['unit_type'] != 'service')
					<span>{{@number_format($line['quantity_line'])}}{{ $line['unit_type'] == 'pcs' ? 'C' : 'T' }}</span>
				@endif
			</td>
			<td style="font-size: 14px; text-align: center;">
				@if($line['unit_type'] != 'service')
					<span>{{ $line['unit_type'] == 'pcs' ? @size_format($line['quantity_line']) : ($line['is_default_unit'] == 0 ? @size_format($line['quantity_line']) : @size_format($line['quantity'])) }}</span>
				@endif
			</td>
			<td style="text-align: center ;">
				@if($line['unit_type'] != 'service')
					<span>{{$line['unit_price_inc_tax']}}</span>
				@endif
			</td>
			<td style="text-align: right;">{{ $line['line_total'] }}</td>
		</tr>
	@endforeach

	@if(!empty($receipt_details->discount_amount))
		<tr class="sell-order-print-a5-total-row">
			<td colspan="2">{{ $receipt_details->discount_label }}</td>
			<td colspan="4">{{ $receipt_details->discount_amount }}</td>
		</tr>
	@endif

	@if(!empty($receipt_details->tax))
		<tr class="sell-order-print-a5-total-row">
			<td colspan="2">{{ $receipt_details->tax_label }}</td>
			<td colspan="4">{{ $receipt_details->tax }}</td>
		</tr>
	@endif

	@if(!empty($receipt_details->shipping_charges))
		<tr class="sell-order-print-a5-total-row">
			<td colspan="2">{{ $receipt_details->shipping_charges_label }}</td>
			<td colspan="4">{{ $receipt_details->shipping_charges }}</td>
		</tr>
	@endif

	<tr class="sell-order-print-a5-total-row">
		<td colspan="2">{{ $receipt_details->total_label }}</td>
		<td colspan="4">{{ $receipt_details->total }}</td>
	</tr>

	@if(!empty($receipt_details->deposit))
		<tr class="sell-order-print-a5-total-row">
			<td colspan="2">{{ $receipt_details->deposit_label }}</td>
			<td colspan="4">{{ $receipt_details->deposit }}</td>
		</tr>
	@endif

	@if(!empty($receipt_details->cod))
		<tr class="sell-order-print-a5-total-row">
			<td colspan="2">{{ $receipt_details->cod_label }}</td>
			<td colspan="4">{{ $receipt_details->cod }}</td>
		</tr>
	@endif

	@if(!empty($receipt_details->deposit) || !empty($receipt_details->cod))
		<tr class="sell-order-print-a5-total-row">
			<td colspan="2">{{ $receipt_details->remaining_label }}</td>
			<td colspan="4">{{ $receipt_details->remaining }}</td>
		</tr>
	@endif

	@for($i = 1; $i <= $num_empty_row; $i++)
		<tr class="sell-order-print-a5-product-row">
			<td colspan="6"></td>
		</tr>
	@endfor

	@if($num_total_row % 2 != 0)
		<tr class="sell-order-print-a5-total-row">
			<td colspan="6"></td>
		</tr>
	@endif

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

{{-- Layout for A4 --}}
<table class="sell-order-print-a4" style="width: 90%; font-size: 14px; margin-top: 4.05%;">
	<tr>
		<td>
			<table class="header" style="width: 100%;">
				<thead>
				<tr>
					<th style="width: 60%;">CÔNG TY THƯƠNG MẠI VÀ DỊCH VỤ NGỌC THÁI</th>
					<th style="font-size: 16px;">ĐƠN ĐẶT HÀNG</th>
				</tr>
				</thead>
				<tbody>
				<tr>
					<td>ĐC : 135 - 137 Trường Chinh - HN</td>
					<td>+ Tấm nhựa lấy sáng Superlete - Polycarbonate</td>
				</tr>
				<tr>
					<td>ĐT : 024.3869 4160 - 024.3868 9078</td>
					<td>+ Tấm nhựa lấy sáng composite - Tấm Mica</td>
				</tr>
				<tr>
					<td>024.3629 0269 - DĐ : 0938 135 137</td>
					<td></td>
				</tr>
				<tr>
					<td>Website: tamlopthongminh.com</td>
					<td></td>
				</tr>
				<tr>
					<td></td>
					<td style="float: right;">Mã đơn hàng: {{ $receipt_details->invoice_no }}</td>
				</tr>
				</tbody>
			</table>
		</td>
	</tr>
	<tr>
		<td>
			<span>
				Tên khách hàng :
				@if($is_receiver_different)
					{{ $receipt_details->delivered_to }} ( {{ $receipt_details->customer_name  }} )
				@else
					{{ $receipt_details->is_visitor ? 'KH' : '' }}{{ $receipt_details->is_visitor && !empty($receipt_details->delivered_to) ? ': ' : '' }}{{ $receipt_details->delivered_to }}
					@if($receipt_details->customer_name != $receipt_details->delivered_to && !$receipt_details->is_visitor)
						(KH: {{ $receipt_details->customer_name }})
					@endif
				@endif
			</span>
			<span style="float: right;">{{ $receipt_details->shipping_note }}</span>
		</td>
	</tr>
	<tr>
		<td>Địa chỉ : {{ $receipt_details->shipping_address }}</td>
	</tr>
	<tr>
		<td></td>
	</tr>
	<tr>
		<td>
			<table class="products" style="width: 100%;">
				<thead>
				<tr>
					<th style="width: 7.34%; text-align: center;">TT</th>
					<th style="text-align: center;">Tên hàng</th>
					<th style="width: 10.93%; text-align: center;">ĐVT</th>
					<th style="width: 13.20%; text-align: center;">Số lượng</th>
					<th style="width: 15.26%; text-align: center;">Đơn giá</th>
					<th style="width: 16.95%; text-align: center;">Thành tiền</th>
				</tr>
				</thead>
				<tbody>
				@foreach($receipt_details->lines as $line)
					<tr style="vertical-align: top;" class="product-row">
						<td style="text-align: center;">{{ $loop->iteration }}</td>
						<td>
							{{$line['name']}}{{ !empty($line['variation']) ? ' - '.$line['variation'] : '' }}
							@if($line['unit_type'] != 'pcs')
								({{ @size_format($line['height']) }} x {{ @size_format($line['width']) }}m)
							@endif
						</td>
						<td style="text-align: center; text-align: center;">{{@number_format($line['quantity_line'])}}{{ $line['unit_type'] == 'pcs' ? 'C' : 'T' }}</td>
						<td style="text-align: right;">{{ ($line['unit_type'] == 'pcs' || $line['unit_type'] == 'weight') ? @size_format($line['quantity_line']) : ($line['is_default_unit'] == 0 ? @size_format($line['quantity_line']) : @size_format($line['quantity'])) }}</td>
						<td style="text-align: right;">{{ $line['unit_price_inc_tax'] }}</td>
						<td style="text-align: right;">{{ $line['line_total'] }}</td>
					</tr>
				@endforeach

				@if(!empty($receipt_details->discount_amount))
					<tr>
						<td colspan="2" style="text-align: right;">{{ $receipt_details->discount_label }}</td>
						<td colspan="4" style="text-align: right;">{{ $receipt_details->discount_amount }}</td>
					</tr>
				@endif

				@if(!empty($receipt_details->tax))
					<td colspan="2" style="text-align: right;">{{ $receipt_details->tax_label }}</td>
					<td colspan="4" style="text-align: right;">{{ $receipt_details->tax }}</td>
				@endif

				@if(!empty($receipt_details->shipping_charges))
					<tr>
						<td colspan="2" style="text-align: right;">{{ $receipt_details->shipping_charges_label }}</td>
						<td colspan="4" style="text-align: right;">{{ $receipt_details->shipping_charges }}</td>
					</tr>
				@endif

				<tr>
					<td colspan="2" style="text-align: right;">{{ $receipt_details->total_label }}</td>
					<td colspan="4" style="text-align: right;">{{ $receipt_details->total }}</td>
				</tr>

				@if(!empty($receipt_details->deposit))
					<tr>
						<td colspan="2" style="text-align: right;">{{ $receipt_details->deposit_label }}</td>
						<td colspan="4" style="text-align: right;">{{ $receipt_details->deposit }}</td>
					</tr>
				@endif

				@if(!empty($receipt_details->cod))
					<tr>
						<td colspan="2" style="text-align: right;">{{ $receipt_details->cod_label }}</td>
						<td colspan="4" style="text-align: right;">{{ $receipt_details->cod }}</td>
					</tr>
				@endif

				@if(!empty($receipt_details->deposit) || !empty($receipt_details->cod))
					<tr>
						<td colspan="2" style="text-align: right;">{{ $receipt_details->remaining_label }}</td>
						<td colspan="4" style="text-align: right;">{{ $receipt_details->remaining }}</td>
					</tr>
				@endif
				</tbody>
			</table>
		</td>
	</tr>
	<tr>
		<td style="text-align: center; height: 100px;">Quý khách cần phải khoan mồi tấm nhựa polycarbonate đặc rộng hơn 1.5 lần đường kính thân vít + ke tròn + bắn vít vừa chạm tấm (không quá chặt)</td>
	</tr>
	<tr>
		<td>
			<table class="footer" style="width: 100%;">
				<tr>
					<td colspan="3">
						<span style="float: right;">Hà Nội, ngày {{ $receipt_details->invoice_day }} tháng {{ $receipt_details->invoice_month }} năm 202{{ $receipt_details->invoice_year }}</span>
					</td>
				</tr>
				<tr>
					<td style="text-align: center;">Khách hàng<br>(Đã kiểm tra - Đã nhận đủ)</td>
					<td style="text-align: center; width: 33.3%;">Vận chuyển</td>
					<td style="text-align: center; ">Nhân viên bán hàng</td>
				</tr>
				<tr>
					<td colspan="3"></td>
				</tr>
				<tr>
					<td colspan="3"></td>
				</tr>
				<tr>
					<td colspan="2"></td>
					<td style="text-align: center;">{{ $receipt_details->added_by }}</td>
				</tr>
			</table>
		</td>
	</tr>
</table>