@extends('layouts.app')

@section('title', __('lang_v1.stock.to_deliver'))

@section('content')
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1>@lang('lang_v1.stock.to_deliver') <small>(@lang('sale.invoice_no'): <span class="text-success">#{{$transaction->invoice_no}})</span></small></h1>
	</section>
	<!-- Main content -->
	<section class="content">
		<input type="hidden" id="amount_rounding_method" value="{{$pos_settings['amount_rounding_method'] ?? ''}}">
		<input type="hidden" id="amount_rounding_method" value="{{$pos_settings['amount_rounding_method'] ?? 'none'}}">
		@if(!empty($pos_settings['allow_overselling']))
			<input type="hidden" id="is_overselling_allowed">
		@endif
		@if(session('business.enable_rp') == 1)
			<input type="hidden" id="reward_point_enabled">
		@endif
		<input type="hidden" id="item_addition_method" value="{{$business_details->item_addition_method}}">
		{!! Form::open(['url' => action('SellPosController@storeStockDeliver', ['id' => $transaction->id ]), 'method' => 'put', 'id' => 'add_deliver_form' ]) !!}
		{!! Form::hidden('location_id', $transaction->location_id, ['id' => 'location_id', 'data-receipt_printer_type' => !empty($location_printer_type) ? $location_printer_type : 'browser']); !!}
		{!! Form::hidden('default_unit_id', $default_unit_id, ['id' => 'default_unit_id']) !!}

		<div class="row">
			<div class="col-md-12 col-sm-12">
				@component('components.widget', ['class' => 'box-primary'])
					<div class="row">
						<div class="@if(!empty($commission_agent)) col-sm-3 @else col-sm-4 @endif">
							<div class="form-group">
								{!! Form::label('contact_id', __('contact.customer') . ':*') !!}
								<div class="input-group">
									<span class="input-group-addon">
										<i class="fa fa-user"></i>
									</span>
									{!! Form::text('contact_id', $transaction->contact->name, ['class' => 'form-control', 'disabled']) !!}

									{{--<input type="hidden" id="default_customer_id"
										   value="{{ $transaction->contact->id }}" >
									<input type="hidden" id="default_customer_name"
										   value="{{ $transaction->contact->name }}" >
									{!! Form::select('contact_id',
                                        [], null, ['class' => 'form-control mousetrap', 'id' => 'customer_id', 'placeholder' => 'Enter Customer name / phone', 'required', 'disabled']); !!}--}}
								</div>
							</div>
						</div>

						<div class="col-md-4">
							<div class="form-group">
								{!! Form::label('phone_contact', __( 'lang_v1.phone_contact' ) . ':') !!}
								{!! Form::text('phone_contact', $transaction->phone_contact, ['class' => 'form-control', 'placeholder' => __('lang_v1.phone_contact')]); !!}
							</div>
						</div>

						<div class="col-md-4">
							<div class="form-group">
								{!! Form::label('delivered_to', __('lang_v1.delivered_to') . ':' ) !!}
								{!! Form::text('delivered_to', $transaction->delivered_to, ['class' => 'form-control','placeholder' => __('lang_v1.delivered_to')]); !!}
							</div>
						</div>

						<div class="col-md-4">
							<div class="form-group">
								{!! Form::label('shipping_address', __('lang_v1.shipping_address')) !!}
								<div class="input-group">
						<span class="input-group-addon">
							<i class="fa fa-map-marker"></i>
						</span>
									{!! Form::textarea('shipping_address',$transaction->shipping_address, ['class' => 'form-control','placeholder' => __('lang_v1.shipping_address') ,'rows' => '1', 'cols'=>'30']); !!}
								</div>
							</div>
						</div>
						<div class="col-md-4">
							<div class="form-group">
								{!! Form::label('shipping_details', __('sale.shipping_details')) !!}
								<div class="input-group">
						<span class="input-group-addon">
							<i class="fa fa-info"></i>
						</span>
									{!! Form::textarea('shipping_details',$transaction->shipping_details, ['class' => 'form-control','placeholder' => __('sale.shipping_details') ,'rows' => '1', 'cols'=>'30']); !!}
								</div>
							</div>
						</div>

						<div class="form-group col-md-4">
							{!! Form::label('shipping_charges', __( 'purchase.additional_shipping_charges' ) . ':') !!}
							{!! Form::text('shipping_charges', number_format($transaction->shipping_charges), ['class' => 'form-control input_number', 'required', 'readonly']); !!}
						</div>

						<div class="form-group col-md-4">
							{!! Form::label('cod', __( 'sale.cod_amount' ) . ':') !!}
							{!! Form::text('cod', $transaction->cod ? number_format($transaction->cod) : 0, ['class' => 'form-control input_number', 'required', 'readonly']); !!}
						</div>

						<div class="@if(!empty($commission_agent)) col-sm-3 @else col-sm-4 @endif">
							<div class="form-group">
								{!! Form::label('transaction_date', __('sale.sale_date') . ':*') !!}
								<div class="input-group">
						<span class="input-group-addon">
							<i class="fa fa-calendar"></i>
						</span>
									{!! Form::text('transaction_date', $transaction->transaction_date, ['class' => 'form-control', 'readonly', 'required', 'readonly']); !!}
								</div>
							</div>
						</div>

						@if(in_array('types_of_service', $enabled_modules) && !empty($transaction->types_of_service))
							<div class="col-md-4 col-sm-6">
								<div class="form-group">
									<div class="input-group">
							<span class="input-group-addon">
								<i class="fas fa-external-link-square-alt text-primary service_modal_btn"></i>
							</span>
										{!! Form::text('types_of_service_text', $transaction->types_of_service->name, ['class' => 'form-control', 'readonly']); !!}

										{!! Form::hidden('types_of_service_id', $transaction->types_of_service_id, ['id' => 'types_of_service_id']) !!}

										<span class="input-group-addon">
								@show_tooltip(__('lang_v1.types_of_service_help'))
							</span>
									</div>
									<small><p class="help-block @if(empty($transaction->selling_price_group_id)) hide @endif" id="price_group_text">@lang('lang_v1.price_group'): <span>@if(!empty($transaction->selling_price_group_id)){{$transaction->price_group->name}}@endif</span></p></small>
								</div>
							</div>
							<div class="modal fade types_of_service_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
								@if(!empty($transaction->types_of_service))
									@include('types_of_service.pos_form_modal', ['types_of_service' => $transaction->types_of_service])
								@endif
							</div>
						@endif

						@if(in_array('subscription', $enabled_modules))
							<div class="col-md-4 pull-right col-sm-6">
								<div class="checkbox">
									<label>
										{!! Form::checkbox('is_recurring', 1, $transaction->is_recurring, ['class' => 'input-icheck', 'id' => 'is_recurring']); !!} @lang('lang_v1.subscribe')?
									</label><button type="button" data-toggle="modal" data-target="#recurringInvoiceModal" class="btn btn-link"><i class="fa fa-external-link"></i></button>@show_tooltip(__('lang_v1.recurring_invoice_help'))
								</div>
							</div>
						@endif

						<div class="clearfix"></div>
						<!-- Call restaurant module if defined -->
						@if(in_array('tables' ,$enabled_modules) || in_array('service_staff' ,$enabled_modules))
							<span id="restaurant_module_span"
								  data-transaction_id="{{$transaction->id}}">
					<div class="col-md-3"></div>
				</span>
						@endif

						@if(!empty($commission_agent))
							<div class="col-sm-3">
								<div class="form-group">
									{!! Form::label('commission_agent', __('lang_v1.commission_agent') . ':') !!}
									{!! Form::select('commission_agent',
                                                $commission_agent, $transaction->commission_agent, ['class' => 'form-control select2', 'disabled']); !!}
								</div>
							</div>
						@endif

						<div class="clearfix"></div>
					</div>
				@endcomponent

				@component('components.widget', ['class' => 'box-primary'])
					<div class="row">
						<div class="col-sm-12" style="min-height: 0">
							<input type="hidden" name="sell_price_tax" id="sell_price_tax" value="{{$business_details->sell_price_tax}}">
							<!-- Keeps count of product rows -->
							<input type="hidden" id="product_row_count"
								   value="{{count($sell_details)}}">
							@php
								$hide_tax = '';
                                if( session()->get('business.enable_inline_tax') == 0){
                                    $hide_tax = 'hide';
                                }
                                $area_allow_cut = !empty($pos_settings['area_allow_cut']) ? $pos_settings['area_allow_cut'] : 0;
							@endphp
							<div class="table-responsive">
								<table class="table table-condensed table-th-green text-center table-bordered table-striped table-responsive" id="select_plate_deliver_table">
									<thead>
									<tr>
										<th rowspan="2">#</th>
										<th colspan="4">@lang('sale.customer_order')</th>
										<th colspan="8">@lang('sale.deliver_order')</th>
										<th rowspan="2"><i class="fa fa-trash" aria-hidden="true"></i></th>
									</tr>
									<tr>
										<th>@lang( 'product.product_name' )</th>
										<th>@lang( 'product.height' )</th>
										<th>@lang( 'product.width' )</th>
										<th>@lang( 'purchase.purchase_quantity' )</th>
										<th>@lang( 'sale.warehouse' )</th>

										<th>@lang( 'product.product_name' )</th>
										<th>@lang( 'sale.height_before' )</th>
										<th>@lang( 'sale.width_before' )</th>
										<th>@lang( 'sale.plate_quantity_before' )</th>
										<th>@lang( 'sale.plate_quantity_after' )</th>
										<th>@lang( 'sale.area_remaining' )</th>
										<th>@lang( 'sale.cut_option' ) @show_tooltip(__('sale.tooltip_cut_option', ['area' => $area_allow_cut]))</th>
									</tr>
									</thead>
									<tbody>
									{!! Form::hidden('transaction_id', $transaction->id, ['class' => 'transaction_id']) !!}
									{!! Form::hidden('sell_details', json_encode($sell_details, JSON_UNESCAPED_UNICODE), ['class' => 'sell_details']) !!}
									@foreach($sell_details as $sell_line)
										@include('stock_deliver.partials.product_row', [
    																						'sell_line' => $sell_line,
    																						'row_count' => $loop->index,
    																						'tax_dropdown' => $taxes,
    																						'sub_units' => !empty($sell_line->sub_units) ? $sell_line->sub_units : [],
    																						'action' => 'edit'
																						])
									@endforeach
									</tbody>
								</table>
							</div>
							{{--<div class="table-responsive">
								<table class="table table-condensed table-bordered table-striped table-responsive">
									<tr>
										<td>
											<div class="pull-right">
												<b>@lang('purchase.purchase_total_quantity'):</b>
												<span class="total_quantity">0</span> m2
												&nbsp;&nbsp;&nbsp;&nbsp;
												<b>@lang('purchase.net_total_amount'): </b>
												<span class="price_total">0</span>
											</div>
										</td>
									</tr>
								</table>
							</div>--}}
						</div>
					</div>
				@endcomponent

				{{--@component('components.widget', ['class' => 'box-primary', 'title' => __('sale.deliver_order')])
						<div class="row">
							<div class="col-sm-12">
								<div class="form-group">
									<button type="button" id="plate_stock_button" class="btn btn-primary btn-flat" data-toggle="modal" data-target="#plate_stock_modal"><i class="fa fa-search"></i> @lang('sale.manually_select_product')</button>
								</div>
							</div>

							<div class="col-sm-12">
								@include('stock_deliver.partials.edit_entry_row')

								<hr/>
								<div class="pull-right col-md-5">
									<table class="pull-right col-md-12">
										<tr>
											<th class="col-md-7 text-right">@lang( 'lang_v1.total_area' ):</th>
											<td class="col-md-5 text-left">
												<span id="total_quantity"></span> m2
											</td>
										</tr>
										<tr>
											<th class="col-md-7 text-right">@lang( 'purchase.total_weight' ):</th>
											<td class="col-md-5 text-left">
												<span id="total_weight"></span> kg
											</td>
										</tr>
									</table>
								</div>
							</div>
						</div>
					@endcomponent--}}

				{{--@component('components.widget', ['class' => 'box-primary'])
					<div class="row">
						<div class="col-sm-12">
							<div class="row">
								<div class="form-group col-md-4">
									{!! Form::label('shipping_charges', __( 'purchase.additional_shipping_charges' ) . ':') !!}
									{!! Form::text('shipping_charges', intval($transaction->shipping_charges), ['class' => 'form-control input_number', 'required', 'readonly']); !!}
								</div>
								<div class="form-group col-md-4" style="margin-bottom: 0px">
									{!! Form::label('tax_rate_id', __('purchase.purchase_tax') . ':') !!}
									{!! Form::hidden('tax_amount', 0, ['id' => 'tax_amount']); !!}
									{!! Form::text('order_tax_text', intval($transaction->tax_amount), ['class' => 'form-control input_number', 'id' => 'order_tax', 'readonly']); !!}
								</div>
								<div class="form-group col-md-4">
									{!! Form::label('discount_type', __( 'lang_v1.discounts' ) . ':') !!}
									{!! Form::hidden('discount_type', 'fixed') !!}
									{!! Form::text('discount_amount', intval($transaction->discount_amount), ['class' => 'form-control input_number', 'required', 'data-default' => $business_details->default_sales_discount, 'id' => 'discount_amount', 'readonly']); !!}
								</div>

								<div class="col-md-4 col-md-offset-10"><b>@lang('sale.total_payable'): </b>
									<input type="hidden" name="final_total" id="final_total_input">
									<span id="total_payable">0</span>
								</div>

								<input type="hidden" name="is_direct_sale" value="1">
							</div>
						</div>
					</div>
				@endcomponent--}}

				{{--@can('sell.payments')
                    @component('components.widget', ['class' => 'box-primary', 'id' => "payment_rows_div", 'title' => __('sale.deposit')])
                        <div class="payment_row">
                            @include('sale_pos.partials.deposit_row_form', ['row_index' => 0])
                            <hr>
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="pull-right"><strong>@lang('lang_v1.balance'):</strong> <span class="balance_due">0.00</span></div>
                                </div>
                            </div>
                        </div>
                    @endcomponent

                    @component('components.widget', ['class' => 'box-primary', 'id' => "cod_rows_div", 'title' => __('sale.cod')])
                        <div class="payment_row">
                            @include('sale_pos.partials.cod_row_form', ['row_index' => 0])
                        </div>
                    @endcomponent
                @endcan--}}
			</div>
		</div>

		<div class="row">
			<div class="col-sm-12 text-right">
				{!! Form::hidden('is_save_and_print', 0, ['id' => 'is_save_and_print']); !!}
				<button type="button" class="btn btn-primary" id="save-and-print">@lang('lang_v1.stock.to_deliver_and_print')</button>
				{{--<button type="button" class="btn btn-primary" id="submit-deliver">@lang('lang_v1.stock.to_deliver')</button>--}}
				<a href="{{ url('/stock-to-deliver') }}" class="btn btn-default">@lang('messages.cancel')</a>
			</div>
		</div>
		{!! Form::close() !!}
	</section>
	<!-- /.content -->
	<div class="modal fade register_details_modal" tabindex="-1" role="dialog"
		 aria-labelledby="gridSystemModalLabel">
	</div>
	<div class="modal fade close_register_modal" tabindex="-1" role="dialog"
		 aria-labelledby="gridSystemModalLabel">
	</div>
	<!-- quick product modal -->
	<div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>

	<div class="modal fade choose_plate_manually" tabindex="-1" role="dialog"
		 aria-labelledby="gridSystemModalLabel">
	</div>

	<div class="modal fade reverse_size_modal" tabindex="-1" role="dialog"
		 aria-labelledby="gridSystemModalLabel">
	</div>

	@include('sale_pos.partials.configure_search_modal')
	@include('stock_deliver.partials.plate_stock')
@stop

@section('javascript')
	<script src="{{ asset('js/stock_deliver.js?v=' . $asset_v) }}"></script>
	<script src="https://cdn.jsdelivr.net/npm/lodash@4.17.20/lodash.min.js"></script>
@endsection
