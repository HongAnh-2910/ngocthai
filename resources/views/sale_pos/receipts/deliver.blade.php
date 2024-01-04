<div class="deliver_print">
	<div class="row">
		<!-- business information here -->
		<div class="col-xs-12 text-center">
			<!-- Title of receipt -->
			<h2 class="text-center">Đơn xuất kho - Ngọc Thái</h2>

			<!-- Invoice  number, Date  -->
			<p style="width: 100% !important" class="word-wrap">
				<span class="pull-left text-left word-wrap">
					<b>Số hóa đơn:</b> {{$receipt_details->invoice_no}}
					<!-- customer info -->
					@if(!empty($receipt_details->customer_name))
						<br/>
						<b>Tên khách hàng:</b> {{ $receipt_details->customer_name }}
					@endif
					@if(!empty($receipt_details->customer_address))
						<br/>
						<b>Địa chỉ:</b> {{ $receipt_details->customer_address }}
					@endif
					@if(!empty($receipt_details->customer_mobile))
						<br/>
						<b>Điện thoại:</b> {{ $receipt_details->customer_mobile }}
					@endif
				</span>

				<span class="pull-right text-left">
					<b>Ngày tạo:</b> {{$receipt_details->invoice_date}}
				</span>
			</p>
		</div>
	</div>

	<div class="row">
		@includeIf('sale_pos.receipts.partial.common_repair_invoice')
	</div>

	<div class="row">
		<div class="col-xs-12">
			<br/>
			@if($receipt_details->print_new_template && $receipt_details->is_new_template)
				<table class="print_table">
					<thead>
						<tr>
							<th class="border-right"></th>
							<th class="border-right"></th>
							<th class="border-bottom">@lang( 'sale.real_cut' )</th>
							<th class="border-right border-bottom"></th>
							<th class="border-bottom">@lang( 'sale.select_plate_cut' )</th>
							<th class="border-bottom"></th>
							<th class="border-right border-bottom"></th>
						</tr>
						<tr>
							<th class="border-right border-bottom">@lang( 'warehouse.warehouses' )</th>
							<th class="border-right border-bottom">@lang( 'product.product_name' )</th>
							<th class="border-right border-bottom">@lang( 'sale.width' )</th>
							<th class="border-right border-bottom">@lang( 'sale.qty' )</th>
							<th class="border-right border-bottom">@lang( 'sale.width' )</th>
							<th class="border-right border-bottom">@lang( 'sale.qty' )</th>
							<th class="border-right border-bottom">@lang( 'stock_adjustment.remaining_width' )</th>
						</tr>

						{{--<tr>
							<th rowspan="2">@lang( 'warehouse.warehouses' )</th>
							<th rowspan="2">@lang( 'product.product_name' )</th>
							<th colspan="2">@lang( 'sale.real_cut' )</th>
							<th colspan="4">@lang( 'sale.select_plate_cut' )</th>
						</tr>
						<tr>
							<th>@lang( 'sale.width' )</th>
							<th>@lang( 'sale.qty' )</th>
							<th>@lang( 'sale.height' )</th>
							<th>@lang( 'sale.width' )</th>
							<th>@lang( 'sale.qty' )</th>
							<th>@lang( 'stock_adjustment.remaining_width' )</th>
						</tr>--}}
					</thead>
					<tbody>
					@forelse($receipt_details->new_lines as $line)
						@php
							$order_line = $line['order'];
							$plate_line_first = null;
							$plate_line_remaining = [];

							if(count($line['deliver']) > 1){
								$rowspan = 'rowspan='. count($line['deliver']);
							}else{
								$rowspan = '';
							}

							if(!empty($line['deliver'])){
								foreach($line['deliver'] as $key => $deliver_line){
									if($key == 0){
										$plate_line_first =  $deliver_line;
									}else{
										$plate_line_remaining[] = $deliver_line;
									}
								}
							}
						@endphp
						<tr>
							@if($plate_line_first)
								@include('sale_pos.receipts.partial.new_plate_line_details',  ['plate_line' => $plate_line_first, 'type' => 'first'])
							@endif
						</tr>

						@if(!empty($plate_line_remaining))
							@foreach($plate_line_remaining as $plate_line)
								<tr>
									@include('sale_pos.receipts.partial.new_plate_line_details',  ['plate_line' => $plate_line, 'type' => 'remaining'])
								</tr>
							@endforeach
						@endif

						@if (!$loop->last)
							<tr>
								<td colspan="8"></td>
							</tr>
						@endif
					@empty
						<tr>
							<td colspan="8"></td>
						</tr>
					@endforelse
					</tbody>
				</table>
			@else
				<table class="print_table print_deliver_old_template">
				<thead>
				<tr>
					<th>@lang( 'warehouse.warehouses' )</th>
					<th>@lang( 'product.product_name' )</th>
					<th>@lang( 'sale.height_before' )</th>
					<th>@lang( 'sale.width_before' )</th>
					<th>@lang( 'sale.plate_quantity_before' )</th>
					<th>@lang( 'sale.width_after' )</th>
					<th>@lang( 'sale.plate_quantity_after' )</th>
					<th>@lang( 'sale.area_remaining' )</th>
					<th>@lang( 'sale.cut_order' )</th>
				</tr>
				</thead>
				<tbody>
				@forelse($receipt_details->lines as $line)
					@php
						$order_line = $line['order'];
						$plate_line_first = null;
						$plate_line_remaining = [];

						if(count($line['deliver']) > 1){
							$rowspan = 'rowspan='. count($line['deliver']);
						}else{
							$rowspan = '';
						}

						if(!empty($line['deliver'])){
							foreach($line['deliver'] as $key => $deliver_line){
								if($key == 0){
									$plate_line_first =  $deliver_line;
								}else{
									$plate_line_remaining[] = $deliver_line;
								}
							}
						}
					@endphp
					<tr>
						@if($plate_line_first)
							@include('sale_pos.receipts.partial.plate_line_details',  ['plate_line' => $plate_line_first])
						@endif
					</tr>

					@if(!empty($plate_line_remaining))
						@foreach($plate_line_remaining as $plate_line)
							<tr>
								@include('sale_pos.receipts.partial.plate_line_details',  ['plate_line' => $plate_line])
							</tr>
						@endforeach
					@endif
				@empty
					<tr>
						<td colspan="8"></td>
					</tr>
				@endforelse
				</tbody>
			</table>
			@endif
		</div>
	</div>

	@if($receipt_details->show_barcode)
		<div class="row">
			<div class="col-xs-12">
				<!-- Barcode -->
				<img class="center-block" src="data:image/png;base64,{{DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2,30,array(39, 48, 54), true)}}">
			</div>
		</div>
	@endif

	@if(!empty($receipt_details->footer_text))
		<div class="row">
			<div class="col-xs-12">
				{!! $receipt_details->footer_text !!}
			</div>
		</div>
	@endif
</div>