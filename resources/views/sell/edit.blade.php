@extends('layouts.app')

@section('title', __('sale.edit_sale'))

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('sale.edit_sale') <small>(@lang('sale.invoice_no'): <span class="text-success">#{{$transaction->invoice_no}})</span></small></h1>
</section>
<!-- Main content -->
<section class="content">
<input type="hidden" id="amount_rounding_method" value="{{$pos_settings['amount_rounding_method'] ?? 'none'}}">
@if(!empty($pos_settings['allow_overselling']))
	<input type="hidden" id="is_overselling_allowed">
@endif
@if(session('business.enable_rp') == 1)
    <input type="hidden" id="reward_point_enabled">
@endif
<input type="hidden" id="item_addition_method" value="{{$business_details->item_addition_method}}">
	{!! Form::open(['file' => true, 'url' => action('SellPosController@update', ['id' => $transaction->id ]), 'method' => 'put', 'id' => 'edit_sell_form', 'enctype' => "multipart/form-data" ]) !!}
	{!! Form::hidden('is_service_order', $transaction->is_service_order, ['id' => 'is_service_order']) !!}
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
						{!! Form::hidden('prev_customer_id', $transaction->contact->id, ['id' => 'prev_customer_id']) !!}
						<input type="hidden" id="default_customer_id"
							   value="{{ $transaction->contact->id }}" >
						<input type="hidden" id="default_customer_name"
							   value="{{ $transaction->contact->name }}" >
						{!! Form::select('contact_id',
							[], null, ['class' => 'form-control mousetrap', 'id' => 'customer_id', 'placeholder' => 'Enter Customer name / phone', 'required']); !!}
						<span class="input-group-btn">
							<button type="button" class="btn btn-default bg-white btn-flat add_new_customer" data-name=""><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
						</span>
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

			<div class="@if(!empty($commission_agent)) col-sm-3 @else col-sm-4 @endif">
				<div class="form-group">
					{!! Form::label('transaction_date', __('sale.sale_date') . ':*') !!}
					<div class="input-group">
						<span class="input-group-addon">
							<i class="fa fa-calendar"></i>
						</span>
						{!! Form::text('transaction_date', $transaction->status == 'draft' ? @format_datetime('now') : $transaction->transaction_date, ['class' => 'form-control', 'readonly', 'required']); !!}
					</div>
				</div>
			</div>

			<div class="col-md-4 col-sm-6">
				<div class="form-group">
					{!! Form::label('location', __('business.sell_price_tax') . ':') !!}
					<div class="input-group">
					<span class="input-group-addon">
						<i class="fas fa-money-bill-alt"></i>
					</span>
						{!! Form::hidden('price_group', !empty($transaction->selling_price_group_id) ? $transaction->selling_price_group_id : 0, ['id' => 'price_group']) !!}
						{!! Form::text('price_group_text', !empty($transaction->selling_price_group_id) && $transaction->price_group ? $transaction->price_group->name : __('lang_v1.default_selling_price'), ['class' => 'form-control', 'readonly']); !!}
						{{--{!! Form::text('price_group_text', !empty($transaction->selling_price_group_id) ? $transaction->price_group->name : __('lang_v1.default_selling_price'), ['class' => 'form-control', 'readonly']); !!}--}}
						<span class="input-group-addon">
						@show_tooltip(__('lang_v1.price_group_help_text'))
					</span>
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

			<div class="col-md-4">
			  <div class="form-group">
				<div class="multi-input">
				  {!! Form::label('pay_term_number', __('contact.pay_term') . ':') !!} @show_tooltip(__('tooltip.pay_term'))
				  <br/>
				  {!! Form::number('pay_term_number', $transaction->pay_term_number, ['class' => 'form-control width-40 pull-left', 'placeholder' => __('contact.pay_term')]); !!}

				  {!! Form::select('pay_term_type',
					['months' => __('lang_v1.months'),
						'days' => __('lang_v1.days')],
						$transaction->pay_term_type,
					['class' => 'form-control width-60 pull-left','placeholder' => __('messages.please_select')]); !!}
				</div>
			  </div>
			</div>
			@php
				if($transaction->status == 'draft' && $transaction->is_quotation == 1){
					$status = 'quotation';
				} else {
					$status = $transaction->status;
				}
			@endphp
			<div class="@if(!empty($commission_agent)) col-sm-3 @else col-sm-4 @endif">
				<div class="form-group">
					{!! Form::label('status', __('sale.status') . ':*') !!}
					@if($transaction->status == 'final')
						{!! Form::text('status_text', __('sale.final'), ['class' => 'form-control', 'readonly']); !!}
						{!! Form::hidden('status', $status) !!}
					@else
						{!! Form::select('status', ['final' => __('sale.final'), 'draft' => __('sale.draft')], $status, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
					@endif
				</div>
			</div>
			@if($transaction->status == 'draft')
				<div class="col-sm-3">
					<div class="form-group">
						{!! Form::label('invoice_scheme_id', __('invoice.invoice_scheme') . ':') !!}
						{!! Form::select('invoice_scheme_id', $invoice_schemes, $default_invoice_schemes->id, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]); !!}
					</div>
				</div>
			@endif
				@foreach($transaction->media as $media)
					<div class="img-thumbnail">
						<span class="badge bg-red delete-media" data-href="{{ action('SellController@deleteMedia', ['media_id' => $media->id])}}">x</span>
						{!! $media->thumbnail([100, 100], 'view_image') !!}
					</div>
				@endforeach
				<div class="col-sm-3">
					<div class="form-group">
						{!! Form::label('documents', __('lang_v1.image') . ':') !!}
						{!! Form::file('documents[]', ['class' => 'documents', 'accept' => 'image/*', 'multiple']); !!}
						<small><p class="help-block">@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])</p></small>
					</div>
				</div>
				<div id="imgModal" class="modal" style="display: none; /* Hidden by default */
														  position: fixed; /* Stay in place */
														  /*z-index: 1; !* Sit on top *!*/
														  padding-top: 100px; /* Location of the box */
														  left: 0;
														  top: 0;
														  width: 100%; /* Full width */
														  height: 100%; /* Full height */
														  overflow: auto; /* Enable scroll if needed */
														  background-color: rgb(0,0,0); /* Fallback color */
														  background-color: rgba(0,0,0,0.9);">

					<!-- The Close Button -->
					<span class="close" style="position: absolute;
						  top: 15px;
						  right: 35px;
						  color: #f1f1f1;
						  font-size: 40px;
						  font-weight: bold;
						  transition: 0.3s;">&times;</span>

					<!-- Modal Content (The Image) -->
					<img class="modal-content" id="img_popup" style="margin: auto;
																	  display: block;
																	  width: 80%;
																	  max-width: 700px; animation-name: zoom;
																	  animation-duration: 0.6s;">

				</div>
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
							$commission_agent, $transaction->commission_agent, ['class' => 'form-control select2']); !!}
				</div>
			</div>
			@endif

			<div class="clearfix"></div>
			</div>
		@endcomponent

		@component('components.widget', ['class' => 'box-primary'])
			<div class="col-sm-3 col-sm-offset-1">
				<div class="form-group">
					<button type="button" id="plate_stock_button" class="btn btn-primary btn-flat" data-toggle="modal" data-target="#plate_stock_modal"><i class="fa fa-search"></i> @lang('sale.product_search')</button>
				</div>
			</div>
			<div class="col-sm-10 col-sm-offset-1">
				<div class="form-group">
					<div class="input-group">
						<div class="input-group-btn">
							<button type="button" class="btn btn-default bg-white btn-flat" data-toggle="modal" data-target="#configure_search_modal" title="{{__('lang_v1.configure_product_search')}}"><i class="fa fa-barcode"></i></button>
						</div>
						{!! Form::text('search_product', null, ['class' => 'form-control mousetrap', 'id' => 'search_product', 'placeholder' => __('lang_v1.search_product_placeholder'),
						'autofocus' => true,
						]); !!}
						{{--<span class="input-group-btn">
							<button type="button" class="btn btn-default bg-white btn-flat pos_add_quick_product" data-href="{{action('ProductController@quickAdd')}}" data-container=".quick_add_product_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
						</span>--}}
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-sm-12 pos_product_div" style="min-height: 0">

				<input type="hidden" name="sell_price_tax" id="sell_price_tax" value="{{$business_details->sell_price_tax}}">

				<!-- Keeps count of product rows -->
				<input type="hidden" id="product_row_count"
					value="{{count($sell_details)}}">
				@php
					$hide_tax = '';
					if( session()->get('business.enable_inline_tax') == 0){
						$hide_tax = 'hide';
					}
				@endphp
				<div class="table-responsive">

				<table class="table table-condensed table-th-green text-center table-bordered table-striped table-responsive" id="pos_table">
					<thead>
					<tr>
						<th>#</th>
						@if(session('business.enable_lot_number'))
							<th>
								@lang('lang_v1.lot_number')
							</th>
						@endif
						<th>@lang( 'product.product_name' )</th>
						<th>@lang( 'product.base_unit' )</th>
						<th>@lang( 'product.height' )</th>
						<th>@lang( 'product.width' )</th>
						<th>@lang( 'purchase.purchase_quantity' )</th>
						<th>@lang( 'purchase.purchase_total_quantity' )</th>
						<th>@lang( 'product.weight' )</th>
						<th>@lang( 'lang_v1.unit_cost' )</th>
						<th style="width: 270px">@lang( 'sale.discount' )</th>
						<th>@lang( 'purchase.real_unit_cost' )</th>
						<th>@lang( 'sale.subtotal' )</th>
						<th><i class="fa fa-trash" aria-hidden="true"></i></th>
					</tr>
					</thead>
					<tbody>
						@foreach($sell_details as $sell_line)
							@include('sale_pos.product_row', ['product' => $sell_line, 'row_count' => $loop->index, 'tax_dropdown' => $taxes, 'sub_units' => !empty($sell_line->sub_units) ? $sell_line->sub_units : [], 'action' => 'edit' ])
						@endforeach
					</tbody>
				</table>

				</div>
				<div class="table-responsive">
				<table class="table table-condensed table-bordered table-striped table-responsive">
					<tr>
						<td>
							<div class="pull-right">
								<b>@lang('purchase.purchase_total_quantity'):</b>
								<span class="total_quantity">0</span> m<sup>2</sup>
								&nbsp;&nbsp;&nbsp;&nbsp;
								<b>@lang('purchase.net_total_amount'): </b>
								<span class="price_total">0</span>
							</div>
						</td>
					</tr>
				</table>
				</div>
			</div>
			</div>
		@endcomponent

		@component('components.widget', ['class' => 'box-primary'])
			<div class="row">
				<div class="col-sm-12">
					<div class="row">
						<div class="form-group col-md-4">
							{!! Form::label('shipping_charges', __( 'purchase.additional_shipping_charges' ) . ':') !!}
							{!! Form::text('shipping_charges', number_format($transaction->shipping_charges), ['class' => 'form-control input_number', 'required']); !!}
						</div>
						<div class="form-group col-md-4" style="margin-bottom: 0px">
							{!! Form::label('tax_rate_id', __('purchase.purchase_tax') . ':') !!}
							{!! Form::hidden('tax_amount', 0, ['id' => 'tax_amount']); !!}
							{!! Form::text('order_tax_text', number_format($transaction->tax_amount), ['class' => 'form-control input_number', 'id' => 'order_tax']); !!}
						</div>
						<div class="form-group col-md-4">
							{!! Form::label('discount_type', __( 'lang_v1.discounts' ) . ':') !!}
							{!! Form::hidden('discount_type', 'fixed') !!}
							{!! Form::text('discount_amount', number_format($transaction->discount_amount), ['class' => 'form-control input_number', 'required', 'data-default' => $business_details->default_sales_discount, 'id' => 'discount_amount']); !!}
						</div>

						<div class="col-md-4 pull-right"><b>@lang('sale.total_payable'): </b>
							<input type="hidden" name="final_total" id="final_total_input">
							<span id="total_payable">0</span>
						</div>

						<input type="hidden" name="is_direct_sale" value="1">
					</div>
				</div>
			</div>
		@endcomponent

		@can('sell.update')
			@component('components.widget', ['class' => 'box-primary', 'id' => "payment_rows_div", 'title' => __('sale.deposit')])
				<div class="payment_row">
					@include('sale_pos.partials.deposit_row_form', ['row_index' => 0, 'payment_line' => $deposit])
				</div>
			@endcomponent

			@component('components.widget', ['class' => 'box-primary', 'id' => "cod_rows_div", 'title' => __('sale.cod')])
				<div class="payment_row">
					@include('sale_pos.partials.cod_row_form', ['row_index' => 0, 'payment_line' => $cod])
				</div>
			@endcomponent
		@endcan
		</div>
	</div>

	<div class="row">
		<div class="col-sm-12 text-right">
			{!! Form::hidden('is_save_and_print', 0, ['id' => 'is_save_and_print']); !!}
			<button type="button" class="btn btn-primary" id="submit-sell">@lang('messages.update')</button>
			<button type="button" id="save-and-print" class="btn btn-primary btn-flat">@lang('messages.save_and_print')</button>
		</div>
	</div>

	@if(in_array('subscription', $enabled_modules))
		@include('sale_pos.partials.recurring_invoice_modal')
	@endif
	{!! Form::close() !!}
</section>

<div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
	@include('contact.create', ['quick_add' => true])
</div>
<!-- /.content -->
<div class="modal fade register_details_modal" tabindex="-1" role="dialog"
	aria-labelledby="gridSystemModalLabel">
</div>
<div class="modal fade close_register_modal" tabindex="-1" role="dialog"
	aria-labelledby="gridSystemModalLabel">
</div>
<!-- quick product modal -->
<div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>

@include('sale_pos.partials.configure_search_modal')
@include('sale_pos.partials.plate_stock')

@stop

@section('javascript')
	<script src="{{ asset('js/pos.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>
	<!-- Call restaurant module if defined -->
    @if(in_array('tables' ,$enabled_modules) || in_array('modifiers' ,$enabled_modules) || in_array('service_staff' ,$enabled_modules))
    	<script src="{{ asset('js/restaurant.js?v=' . $asset_v) }}"></script>
    @endif
	<script type="text/javascript">
		$(document).ready(function() {
			// Get the modal
			var modal = document.getElementById("imgModal");

			var modalImg = document.getElementById("img_popup");
			var captionText = document.getElementById("caption");

			$('.view_image').each(function () {
				$(this).click(function () {
					modal.style.display = "block";
					modalImg.src = $(this).attr('src');
					captionText.innerHTML = this.alt;
				})
			})

			var span = document.getElementsByClassName("close")[0];
			span.onclick = function() {
				modal.style.display = "none";
			}
		})
	</script>
@endsection
