@extends('layouts.app')

@section('title', __('lang_v1.stock.edit_deliver'))

@section('content')
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1>@lang('lang_v1.stock.edit_deliver') <small>(@lang('sale.invoice_no'): <span class="text-success">#{{$transaction->invoice_no}})</span></small></h1>
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
		{!! Form::open(['url' => action('SellPosController@updateStockDeliver', ['id' => $transaction->id ]), 'method' => 'put', 'id' => 'edit_deliver_form' ]) !!}
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
										@foreach($sell_line->plate_lines as $key => $plate_line)
											@include('stock_deliver.partials.edit_entry_row', [
												'sell_line' => $sell_line,
												'plate_line' => $plate_line,
												'plate_stock' => $plate_line->selected_plate_stock,
											])
										@endforeach
									@endforeach
									</tbody>
								</table>
							</div>
						</div>
					</div>
				@endcomponent
			</div>
		</div>

		<div class="row">
			<div class="col-sm-12 text-right">
				{!! Form::hidden('is_save_and_print', 0, ['id' => 'is_save_and_print']); !!}
				<button type="button" id="save-and-print" class="btn btn-primary">@lang('messages.save_and_print')</button>
				<button type="button" class="btn btn-success" id="submit-deliver">@lang('messages.save')</button>
				<a href="{{ url('/stock-to-deliver') }}" class="btn btn-default">@lang('messages.cancel')</a>
			</div>
		</div>

		@foreach($selected_remaining_widths as $selected_remaining_width)
			<input type="hidden" name="selected_remaining_widths[{{ $selected_remaining_width['plate_stock_id'] }}]"
				   id="selected_remaining_widths_{{ $selected_remaining_width['plate_stock_id'] }}"
				   class="selected_remaining_widths"
				   data-plate_stock_id="{{ $selected_remaining_width['plate_stock_id'] }}"
				   value="{{ $selected_remaining_width['value'] }}"
				   data-index="{{ $selected_remaining_width['index'] }}"
				   @foreach($selected_remaining_width['cut_plates'] as $cut_plate)
				   		data-value_{{ $cut_plate['index'] }}="{{ $cut_plate['value'] }}" data-row_id_{{ $cut_plate['index'] }}="{{ $cut_plate['row_id'] }}"
				   @endforeach
			>
		@endforeach
		<input type="hidden" name="plates_sort_order" id="plates_sort_order" value="{{ $transaction->plates_sort_order }}">
		{!! Form::close() !!}
	</section>
	<!-- /.content -->
	<div class="modal fade choose_plate_manually" tabindex="-1" role="dialog"
		 aria-labelledby="gridSystemModalLabel">
	</div>

	<div class="modal fade reverse_size_modal" tabindex="-1" role="dialog"
		 aria-labelledby="gridSystemModalLabel">
	</div>

	@include('stock_deliver.partials.plate_stock')
@stop

@section('javascript')
	<script src="{{ asset('js/stock_deliver.js?v=' . $asset_v) }}"></script>
	<script src="https://cdn.jsdelivr.net/npm/lodash@4.17.20/lodash.min.js"></script>
@endsection
