<table class="sell-return-print">
	<tr>
		<td>
			<table class="header" style="width: 100%; font-size: 11px; margin: 0; padding: 0;">
				<thead>
				<tr style="font-weight: bold;">
					<td style="width: 50%; font-family: 'Roboto Condensed', sans-serif;">CÔNG TY THƯƠNG MẠI & DỊCH VỤ NGỌC THÁI</td>
					<td style="font-size: 12px;">ĐƠN TRẢ HÀNG</td>
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
					<td style="font-size: 12px;">
						{{--<span style="float: left; font-weight: bold;">Liên 1: Lưu</span>--}}
						<span style="float: right; font-style: italic;">Số: {{ $receipt_details->invoice_no }}</span>
					</td>
				</tr>
				<tr>
					<td colspan="2"></td>
				</tr>
				</tbody>
			</table>
		</td>
	</tr>
	<tr>
		<td>
			<span><b>Tên khách hàng:</b> {{ $receipt_details->is_visitor ? 'KH' : '' }}{{ $receipt_details->is_visitor && !empty($receipt_details->delivered_to) ? ': ' : '' }}{{ $receipt_details->delivered_to }}</span>
			<span style="float: right;">{{ !empty($receipt_details->shipping_note) ? $receipt_details->shipping_note : '' }}</span>
		</td>
	</tr>
	<tr>
		<td><b>Địa chỉ:</b> {{ $receipt_details->shipping_address }}</td>
	</tr>
	<tr>
		<td></td>
	</tr>
	<tr>
		<td>
			<table class="products" style="width: 100%;">
				<thead>
				<tr>
					<th style="text-align: center;">Tên hàng</th>
					<th style="width: 18%; text-align: center;">Tấm khách trả</th>
					<th style="width: 10%; text-align: center;">Số lượng</th>
					<th style="width: 10%; text-align: center;">Diện tích</th>
					<th style="width: 15%; text-align: center;">Đơn giá</th>
					<th style="width: 20%; text-align: center;">Thành tiền</th>
				</tr>
				</thead>
				<tbody>
				@foreach($receipt_details->lines as $line)
					@if(!empty($line['plate_line_return']))
						@php
							$plate_line_first = null;
							$plate_line_remaining = [];

							if(count($line['plate_line_return']) > 1){
								$rowspan = 'rowspan='. count($line['plate_line_return']);
							}else{
								$rowspan = '';
							}

							if(!empty($line['plate_line_return'])){
								foreach($line['plate_line_return'] as $key => $plate_line){
									if($key == 0){
										$plate_line_first =  $plate_line;
									}else{
										$plate_line_remaining[] = $plate_line;
									}
								}
							}
						@endphp
						<tr style="vertical-align: top;" class="product-row">
							<td {{ $rowspan }}>
								{{$line['name']}} {{$line['variation']}}
								@if(!empty($line['sub_sku'])) ({{$line['sub_sku']}}) @endif @if(!empty($line['brand'])), {{$line['brand']}} @endif
								@if(!empty($line['sell_line_note']))({{$line['sell_line_note']}}) @endif
								@if($line['unit_type'] == 'pcs')
									<span></span>
								@else
									<span>({{ @size_format($line['height']) }}x{{ @size_format($line['width']) }})</span>
								@endif
							</td>
							@if($plate_line_first)
								@include('sell_return.partials.plate_line_return_row',  ['line' => $line, 'plate_line' => $plate_line_first])
							@endif
						</tr>
						@if(!empty($plate_line_remaining))
							@foreach($plate_line_remaining as $plate_line)
								<tr>
									@include('sell_return.partials.plate_line_return_row',  ['line' => $line, 'plate_line' => $plate_line])
								</tr>
							@endforeach
						@endif
					@endif
				@endforeach

				@if($receipt_details->discount || $receipt_details->shop_return_amount)
					<tr class="total-row">
						<td colspan="2">{{ $receipt_details->table_net_total_amount_return }}</td>
						<td colspan="4">{{ @number_format($receipt_details->total_before_tax) }}đ</td>
					</tr>
				@endif

				@if($receipt_details->discount)
					<tr class="total-row">
						<td colspan="2">{{ $receipt_details->table_return_discount }} (-)</td>
						<td colspan="4">
							@if($receipt_details->discount_type == 'percentage')
								<span style="padding-right: 30px;">{{ @size_format($receipt_details->discount_amount) }}%</span>
							@endif
							<span>{{ $receipt_details->discount }} đ</span>
						</td>
					</tr>
				@endif

				@if($receipt_details->shop_return_amount)
					<tr class="total-row">
						<td colspan="2">@lang('lang_v1.shop_return_amount') (+)</td>
						<td colspan="4">{{ @number_format($receipt_details->shop_return_amount) }}đ</td>
					</tr>
				@endif

				<tr class="total-row">
					<td colspan="2">{{ $receipt_details->table_return_subtotal }}</td>
					<td colspan="4">{{ @number_format($receipt_details->total_money_return) }}đ</td>
				</tr>
				</tbody>
			</table>
		</td>
	</tr>
	<tr style="height: 30px;"></tr>
	<tr>
		<td>
			<table class="footer" style="width: 100%;">
				<tr>
					<td colspan="3">
						<span style="float: right; font-style: italic;">Hà Nội, ngày {{ $receipt_details->invoice_day }} tháng {{ $receipt_details->invoice_month }} năm 202{{ $receipt_details->invoice_year }}</span>
					</td>
				</tr>
				<tr style="font-weight: bold;">
					<td style="text-align: center;">Khách hàng</td>
					<td style="width: 33.3%;"></td>
					{{--<td style="text-align: center; width: 33.3%;">Vận chuyển</td>--}}
					<td style="text-align: center; ">Nhân viên bán hàng</td>
				</tr>
				{{--<tr style="font-weight: bold;">
					<td style="text-align: center;">(Đã kiểm tra - Đã nhận đủ)</td>
					<td colspan="2"></td>
				</tr>--}}
				<tr style="height: 40px">
					<td colspan="3"></td>
				</tr>
				<tr>
					<td colspan="2"></td>
					<td style="text-align: center; font-weight: bold;">{{ $receipt_details->added_by }}</td>
				</tr>
			</table>
		</td>
	</tr>
</table>