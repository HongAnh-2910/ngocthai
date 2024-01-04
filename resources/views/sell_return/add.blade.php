@extends('layouts.app')
@section('title', __('lang_v1.sell_return'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>@lang('lang_v1.sell_return')</h1>
</section>

<!-- Main content -->
<section class="content no-print">

{!! Form::hidden('location_id', $sell->location->id, ['id' => 'location_id', 'data-receipt_printer_type' => $sell->location->receipt_printer_type ]); !!}

	{!! Form::open(['url' => action('SellReturnController@store'), 'method' => 'post', 'id' => 'sell_return_form' ]) !!}
	{!! Form::hidden('transaction_id', $sell->id); !!}
	{!! Form::hidden('action', isset($edit) ? 'edit' : 'add'); !!}
	<div class="box box-solid">
		<div class="box-header">
			<h3 class="box-title">@lang('lang_v1.parent_sale')</h3>
		</div>
		<div class="box-body">
			<div class="row">
				<div class="col-sm-4">
					<strong>@lang('sale.invoice_no'):</strong> {{ $sell->invoice_no }} <br>
					<strong>@lang('messages.date'):</strong> {{@format_date($sell->transaction_date)}} <br>
					<strong>@lang('purchase.business_location'):</strong> {{ $sell->location->name }}
				</div>
				<div class="col-sm-4">
					<strong>@lang('purchase.additional_shipping_charges'):</strong> {{ @num_format($sell->shipping_charges) }} đ <br>
					<strong>@lang('purchase.purchase_tax'):</strong> {{ @num_format($sell->tax_amount) }} đ <br>
					<strong>@lang('lang_v1.discounts'):</strong> {{ @num_format($sell->discount_amount) }} đ <br>
				</div>
				<div class="col-sm-4">
					<strong>@lang('contact.customer'):</strong> {{ $sell->contact->name }} <br>
					<strong>@lang('lang_v1.selling_price_group'):</strong> {{ !empty($sell->selling_price_group_id) ? $sell->price_group->name : __('lang_v1.default_selling_price') }}
				</div>
			</div>
		</div>
	</div>
	<div class="box box-solid">
		<div class="box-body">
			<div class="row">
				<div class="col-sm-4">
					<div class="form-group">
						{!! Form::label('invoice_no', __('sale.invoice_no').':') !!}
						{!! Form::text('invoice_no', !empty($sell->return_parent->invoice_no) ? $sell->return_parent->invoice_no : null, ['class' => 'form-control']); !!}
					</div>
				</div>
				<div class="col-sm-3">
					<div class="form-group">
						{!! Form::label('transaction_date', __('messages.date') . ':*') !!}
						<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-calendar"></i>
							</span>
							{!! Form::text('transaction_date', @format_datetime($transaction_date), ['class' => 'form-control', 'readonly', 'required']); !!}
							{{--@php
								$transaction_date = !empty($sell->return_parent->transaction_date) ? $sell->return_parent->transaction_date : 'now';
							@endphp
							{!! Form::text('transaction_date', @format_datetime($transaction_date), ['class' => 'form-control', 'readonly', 'required']); !!}--}}
						</div>
					</div>
				</div>
				<div class="col-sm-12">
					<table class="table table-condensed table-th-green text-center table-bordered table-striped table-responsive" id="sell_return_table">
			          	<thead>
							<tr>
								<th rowspan="2" style="width: 50px">#</th>
								<th rowspan="2" style="width: 250px">@lang( 'product.product_name' )</th>
								<th colspan="6">@lang( 'lang_v1.sell_quantity' )</th>
								<th colspan="9">@lang( 'lang_v1.return_quantity' )</th>
							</tr>
				            <tr>
								<th style="width: 100px">@lang( 'product.height' )</th>
								<th style="width: 100px">@lang( 'product.width' )</th>
								<th style="width: 100px">@lang( 'purchase.purchase_quantity' )</th>
								<th style="width: 100px">@lang( 'purchase.purchase_total_quantity' )</th>
								<th style="width: 130px">@lang( 'lang_v1.old_sell_price_type' )</th>
								<th style="width: 130px">@lang( 'lang_v1.old_price' )</th>

								<th style="width: 100px">@lang( 'product.height' )</th>
								<th style="width: 100px">@lang( 'product.width' )</th>
								<th style="width: 100px">@lang( 'purchase.purchase_quantity' )</th>
								<th style="width: 100px">@lang( 'purchase.purchase_total_quantity' )</th>
								<th style="width: 150px">@lang( 'purchase.sell_price_type' )</th>
								<th style="width: 100px">@lang( 'purchase.return_price' )</th>
								<th style="width: 100px">@lang( 'lang_v1.return_subtotal' )</th>
								<th style="width: 150px">@lang( 'purchase.warehouse' )</th>
								<th style="width: 50px"><i class="fa fa-trash" aria-hidden="true"></i></th>
				            </tr>
				        </thead>
				        <tbody>
							@php
								$row_index = 0;
							@endphp
							@foreach($sell->sell_lines as $sell_line)
								@foreach($sell_line->plate_lines as $plate_line)
									@php
									@endphp
									<tr data-row_index="{{ $row_index }}" id="row_{{ $row_index }}">
										@php
											$new_unit_price	= $plate_line->selected_plate_stock->variation->default_sell_price;
											$new_unit_price_by_plate = $plate_line->selected_plate_stock->variation->default_sell_price_by_plate;

											if($sell->price_group){
												$price_group_id = $sell->price_group->id;
												$group_prices = $plate_line->selected_plate_stock->variation->group_prices;
												foreach ($group_prices as $group_price){
													if($group_price->price_group_id == $price_group_id){
														$new_unit_price	= $group_price->price_inc_tax;
														$new_unit_price_by_plate = $group_price->price_by_plate;
														break;
													}
												}
											}
										@endphp
										{!! Form::hidden('products['. $row_index .'][sell_line_id]', $sell_line->id, ['class' => 'sell_line_id']) !!}
										{!! Form::hidden('products['. $row_index .'][plate_stock_id]', $plate_line->selected_plate_stock->id, ['class' => 'plate_stock_id']) !!}
										{!! Form::hidden('products['. $row_index .'][plate_line_id]', $plate_line->id, ['class' => 'plate_line_id']) !!}
										{!! Form::hidden('products['. $row_index .'][variation_id]', $plate_line->variation_id, ['class' => 'variation_id']) !!}
										{!! Form::hidden('products['. $row_index .'][product_id]', $plate_line->product_id, ['class' => 'product_id']) !!}
										{!! Form::hidden('products['. $row_index .'][unit_price]', $sell_line->unit_price, ['class' => 'old_unit_price']) !!}
										{!! Form::hidden('products['. $row_index .'][new_unit_price]', $new_unit_price, ['class' => 'new_unit_price']) !!}
										{!! Form::hidden('products['. $row_index .'][new_unit_price_by_plate]', $new_unit_price_by_plate, ['class' => 'new_unit_price_by_plate']) !!}

										<td>{{ $row_index + 1 }}</td>
										<td class="name_product">
											@php
												$product_name = $plate_line->selected_plate_stock->product->name ;
                                                if( $plate_line->selected_plate_stock->product->type == 'variable'){
                                                    $product_name .= ' ('. $plate_line->selected_plate_stock->variation->name .')';
                                                }
                                                $product_name .= ' - '.$plate_line->selected_plate_stock->variation->sub_sku;
											@endphp
											{!! $product_name !!}
										</td>
										<td>
											@if($sell_line->sub_unit->type == 'area')
												<span class="old_height">{{ @size_format($plate_line->height) }}</span>
											@else
												<span>
													<input type="hidden" class="old_height" value="1">
												</span>
											@endif
										</td>
										<td>
											@if(in_array($sell_line->sub_unit->type, ['area', 'meter']))
												<span class="old_width">{{ @size_format($plate_line->width) }}</span>
											@else
												<span>
													<input type="hidden" class="old_width" value="1">
												</span>
											@endif
										</td>
										<td>
											@if(in_array($sell_line->sub_unit->type, ['area', 'meter']))
												<span class="old_quantity">{{ @num_format($plate_line->quantity) }}</span> @lang('unit.plate')
											@else
												<span class="old_quantity">{{ @num_format($plate_line->quantity) }}</span> {{ $sell_line->sub_unit->actual_name }}
											@endif
										</td>
										<td>
											@if($sell_line->sub_unit->type == 'area')
												<span class="old_area">{{ @size_format($plate_line->width * $plate_line->height * $plate_line->quantity) }}</span> m<sup>2</sup>
											@else
												<span class="old_area"></span>
											@endif
										</td>
										<td>
											@if(in_array($sell_line->sub_unit->type, ['area', 'meter']))
												{{ $sell_line->sub_unit->base_unit_id ? __('lang_v1.price_by_origin_plate') : __('lang_v1.price_by_square_meter') }}
											@endif
										</td>
										<td>
											<span class="display_currency old_price" data-currency_symbol="true" data-orig-value="{{ $sell_line->unit_price }}">{{ @num_format($sell_line->unit_price) }}</span>
										</td>
										<td colspan="9" style="text-align: left;">
											<a href="#" class="btn btn-xs btn-primary add_plate_return" data-toggle="modal" data-target="#plate_stock_deliver_modal">
												<i class="fas fa-plus"></i> @lang('messages.add_plate_return')
											</a>
										</td>
									</tr>
									@if(isset($edit))
										@if(count($plate_line_return) > 0)
											@foreach($plate_line_return as $key => $value)
												@if($plate_line->id == $value->transaction_plate_line_id)
													<tr class="sub_row_{{ $row_index }} sub_row" data-row_index="{{ $row_index }}" data-sub_row_index="{{ $key }}">
														{!! Form::hidden('products['. $row_index .'][return_plates]['. $key .'][type]', $value->plate_stock->product->unit->type, ['class' => 'type']) !!}
														{!! Form::hidden('products['. $row_index .'][return_plates]['. $key .'][old_width]', $plate_line->width, ['class' => 'old_width']) !!}
														{!! Form::hidden('products['. $row_index .'][return_plates]['. $key .'][sell_return_id]', $value->id, ['class' => 'sell_return_id']) !!}
														{!! Form::hidden('products['. $row_index .'][return_plates]['. $key .'][old_base_unit_id]', $plate_line->sell_line->sub_unit->base_unit_id ? $plate_line->sell_line->sub_unit->base_unit_id : '', ['class' => 'old_base_unit_id']) !!}

														<td colspan="8"></td>
														<td>
															@if($value->plate_stock->product->unit->type == 'area')
																{!! Form::number('products['. $row_index .'][return_plates]['. $key .'][height]', @size_format($value->height), ['class' => 'form-control input-sm input_decimal text-center new_height', 'readonly']) !!}
															@else
																{!! Form::hidden('products['. $row_index .'][return_plates]['. $key .'][height]', 1, ['class' => 'new_height']) !!}
															@endif
														</td>
														<td>
															@if(in_array($value->plate_stock->product->unit->type, ['area', 'meter']))
																{!! Form::number('products['. $row_index .'][return_plates]['. $key .'][width]', @size_format($value->width), ['class' => 'form-control input-sm input_decimal text-center new_width', ($value->sell_price_type == 'new_by_plate' || ($value->sell_price_type == 'old' && $plate_line->sell_line->sub_unit->base_unit_id)) ? 'readonly' : '']) !!}
															@else
																{!! Form::hidden('products['. $row_index .'][return_plates]['. $key .'][width]', 1, ['class' => 'new_width']) !!}
															@endif
														</td>
														<td>
															{!! Form::text('products['. $row_index .'][return_plates]['. $key .'][quantity]', @number_format($value->quantity), ['class' => 'form-control input-sm input_number text-center new_quantity']) !!}
														</td>
														<td>
															@if($sell_line->sub_unit->type == 'area')
																<span class="new_area">{{ @size_format($value->height * $value->width * $value->quantity) }}</span> m<sup>2</sup>
															@endif
														</td>
														<td>
														{!! Form::select('products['. $row_index .'][return_plates]['. $key .'][sell_price_type]', $return_price_types, $value->sell_price_type, ['class' => 'form-control input-sm sell_price_type']) !!}
														<td>
															<span class="new_price">{{ @number_format($value->unit_price) }}</span> đ
															{!! Form::hidden('products['. $row_index .'][return_plates]['. $key .'][unit_price_hidden]', $value->unit_price, ['class' => 'unit_price_hidden']) !!}
														</td>
														<td>
															<span class="new_total_price">{{ ($value->sell_price_type == 'new_by_plate' || ($value->sell_price_type == 'old' && $plate_line->sell_line->sub_unit->base_unit_id)) ? @number_format($value->unit_price * $value->quantity) : @number_format($value->unit_price * $value->height * $value->width * $value->quantity) }}</span> đ
														</td>
														<td>
															{!! Form::select('products['. $row_index .'][return_plates]['. $key .'][warehouse_id]', $warehouses, $value->warehouse_id, ['class' => 'form-control input-sm warehouse_id', 'required', 'placeholder' => __('messages.please_select')]); !!}
														</td>
														<td>
															<i class="fa fa-times remove_sell_return_entry_row text-danger" title="@lang('messages.delete')" style="cursor:pointer;"></i>
														</td>
													</tr>
												@endif
											@endforeach
										@endif
									@endif
									@php
										$row_index += 1;
									@endphp
								@endforeach
							@endforeach
			          	</tbody>
			        </table>
				</div>
			</div>
			<div class="row">
				@php
					$discount_type = !empty($sell->return_parent->discount_type) ? $sell->return_parent->discount_type : $sell->discount_type;
					$discount_amount = !empty($sell->return_parent->discount_amount) ? $sell->return_parent->discount_amount : $sell->discount_amount;

					$discount = 0;
					if (isset($edit)) {
					 	if($discount_type == 'fixed') {
					 		$discount = $discount_amount;
						} else {
							$discount = $sell->return_parent->total_before_tax * ($discount_amount / 100);
						}
					}

					if (isset($edit)) {
					    $total_before_tax = $sell->return_parent->total_before_tax;
					}else{
					    $total_before_tax = 0;
					}
				@endphp
				<div class="col-sm-4">
					{!! Form::label('discount_type', __( 'lang_v1.expense_customer_return' ) . ':') !!}
					<div class="input-group" style="display: flex">
						<div class="input-group-prepend">
							{!! Form::select('discount_type', ['fixed' => __( 'lang_v1.fixed' ), 'percentage' => __( 'lang_v1.percentage' )], $discount_type, ['class' => 'form-control', 'style' => 'width: 150px']); !!}
						</div>
						{!! Form::text('discount_amount', isset($edit) ? @num_format($discount_amount) : 0, ['class' => 'form-control input_number discount_amount']); !!}
					</div>
				</div>
				<div class="col-sm-4">
					{!! Form::label('shop_return_amount', __( 'lang_v1.shop_return_amount' ) . ':') !!}
					{!! Form::text('shop_return_amount', isset($edit) ? @num_format($sell->return_parent->shop_return_amount) : 0, ['class' => 'form-control input_number shop_return_amount']); !!}
				</div>
				<div class="col-sm-4">
					{!! Form::label('return_note', __( 'sale.return_note' ) . ':*') !!}
					{!! Form::textarea('return_note', isset($edit) ? $sell->return_parent->return_note : '', ['class' => 'form-control', 'rows' => 1, 'required']); !!}
				</div>
			</div>
			<br>
			@php
				$tax_percent = 0;
				if(!empty($sell->tax)){
					$tax_percent = $sell->tax->amount;
				}
			@endphp
			{!! Form::hidden('tax_id', $sell->tax_id); !!}
			{!! Form::hidden('tax_amount', 0, ['id' => 'tax_amount']); !!}
			{!! Form::hidden('tax_percent', $tax_percent, ['id' => 'tax_percent']); !!}
			{!! Form::hidden('invoice_discount', 0, ['class' => 'invoice_discount']) !!}
			<div class="row">
				<div class="col-sm-12 text-right">
					<strong>@lang('lang_v1.net_total_amount_return'):</strong>
					&nbsp;<span id="total_before_tax" class="display_currency" data-currency_symbol="true">{{ @num_format($total_before_tax) }}</span>
					{!! Form::hidden('total_before_tax', $total_before_tax, ['class' => 'total_before_tax_hidden']) !!}
				</div>
				<div class="col-sm-12 text-right">
					<strong>@lang('lang_v1.expense_customer_return') (-):</strong>
					&nbsp;<span id="total_return_discount" class="display_currency" data-currency_symbol="true">{{ @num_format($discount) }}</span>
					{!! Form::hidden('total_return_discount_hidden', $discount, ['class' => 'total_return_discount_hidden']) !!}
				</div>
				<div class="col-sm-12 text-right">
					<strong>@lang('lang_v1.shop_return_amount') (+):</strong>
					&nbsp;<span id="total_shop_return_amount" class="display_currency" data-currency_symbol="true">{{ isset($edit) ? @num_format($sell->return_parent->shop_return_amount) : 0 }}</span>
				</div>
				<div class="col-sm-12 text-right">
					____________________________
				</div>
				<div class="col-sm-12 text-right">
					<strong>@lang('lang_v1.return_total'):</strong>&nbsp;
					<span id="net_return" class="display_currency" data-currency_symbol="true">
						{{ isset($edit) ? @num_format($sell->return_parent->final_total) : 0 }}
					</span>
				</div>
				{!! Form::hidden('total_sell_return', isset($edit) ? $sell->return_parent->final_total : 0, ['class' => 'total_sell_return']) !!}
			</div>
			<br>
			<div class="row">
				<div class="col-sm-12">
					<button type="submit" class="btn btn-primary pull-right" id="sell_return_submit">@lang('messages.save')</button>
					{{--<button type="button" class="btn btn-primary pull-right" id="sell_return_submit">@lang('messages.save')</button>--}}
				</div>
			</div>
		</div>
	</div>
	{!! Form::close() !!}

</section>
@stop
@section('javascript')
<script src="{{ asset('js/printer.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/sell_return.js?v=' . $asset_v) }}"></script>
@endsection
