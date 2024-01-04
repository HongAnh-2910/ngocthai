@extends('layouts.app')

@section('title', __('sale.add_sale'))

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('sale.add_sale')</h1>
</section>
<!-- Main content -->
<section class="content no-print">
<input type="hidden" id="amount_rounding_method" value="{{$pos_settings['amount_rounding_method'] ?? ''}}">
@if(!empty($pos_settings['allow_overselling']))
	<input type="hidden" id="is_overselling_allowed">
@endif
@if(session('business.enable_rp') == 1)
    <input type="hidden" id="reward_point_enabled">
@endif
{!! Form::hidden('default_unit_id', $default_unit_id, ['id' => 'default_unit_id']) !!}
	@if(is_null($default_location))
		<div class="col-sm-4">
			<div class="form-group">
				{!! Form::label('location', __('messages.location') . ':*') !!}
				<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-map-marker"></i>
							</span>
					{!! Form::select('select_location_id', $business_locations, null, ['class' => 'form-control',
                    'placeholder' => __('lang_v1.select_location'),
                    'id' => 'select_location_id',
                    'required', 'autofocus'], $bl_attributes); !!}
					<span class="input-group-addon">
						@show_tooltip(__('tooltip.sale_location'))
					</span>
				</div>
			</div>
		</div>
	@endif
<input type="hidden" id="item_addition_method" value="{{$business_details->item_addition_method}}">
	{!! Form::open(['url' => action('SellPosController@store'), 'method' => 'post', 'id' => 'add_sell_form', 'files' => true ]) !!}
	{!! Form::hidden('is_service_order', 0, ['id' => 'is_service_order']) !!}
	<div class="row">
		<div class="col-md-12 col-sm-12">
		@component('components.widget', ['class' => 'box-primary'])
			<div class="row">

			{!! Form::hidden('location_id', !empty($default_location) ? $default_location->id : null , ['id' => 'location_id', 'data-receipt_printer_type' => !empty($default_location->receipt_printer_type) ? $default_location->receipt_printer_type : 'browser', 'data-default_accounts' => !empty($default_location) ? $default_location->default_payment_accounts : '']); !!}
				<div class="@if(!empty($commission_agent)) col-sm-3 @else col-sm-4 @endif">
					<div class="form-group">
						{!! Form::label('contact_id', __('contact.customer') . ':*') !!}
						<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-user"></i>
							</span>
							{!! Form::hidden('prev_customer_id', $walk_in_customer['id'], ['id' => 'prev_customer_id']) !!}
							<input type="hidden" id="default_customer_id"
								   value="{{ $walk_in_customer['id']}}" >
							<input type="hidden" id="default_customer_name"
								   value="{{ $walk_in_customer['name']}}" >
							{!! Form::select('contact_id',
								[], null, ['class' => 'form-control mousetrap', 'id' => 'customer_id', 'placeholder' => 'Enter Customer name / phone', 'required']); !!}
							<span class="input-group-btn">
								<button type="button" class="btn btn-default bg-white btn-flat add_new_customer" data-name=""><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
							</span>
						</div>
					</div>
				</div>

				<div class="col-md-4">
					{!! Form::label('phone_contact', __( 'lang_v1.phone_contact' ) . ':') !!}
					{!! Form::text('phone_contact', null, ['class' => 'form-control', 'id' => 'phone_contact', 'placeholder' => __('lang_v1.phone_contact')]); !!}
				</div>

				<div class="col-md-4">
					<div class="form-group">
						{!! Form::label('delivered_to', __('lang_v1.delivered_to') . ':' ) !!}
						{!! Form::text('delivered_to', null, ['class' => 'form-control','placeholder' => __('lang_v1.delivered_to')]); !!}
					</div>
				</div>

				<div class="col-md-4">
					<div class="form-group">
						{!! Form::label('shipping_address', __('lang_v1.shipping_address')) !!}
						<div class="input-group">
							<span class="input-group-addon">
			                    <i class="fa fa-map-marker"></i>
			                </span>
							{!! Form::textarea('shipping_address',null, ['class' => 'form-control','placeholder' => __('lang_v1.shipping_address') ,'rows' => '1', 'cols'=>'30']); !!}
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
							{!! Form::textarea('shipping_details',null, ['class' => 'form-control','placeholder' => __('sale.shipping_details') ,'rows' => '1', 'cols'=>'30']); !!}
						</div>
					</div>
				</div>
				{{--<div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('shipping_status', __('lang_v1.shipping_status')) !!}
                        {!! Form::select('shipping_status',$shipping_statuses, null, ['class' => 'form-control','placeholder' => __('messages.please_select')]); !!}
                    </div>
                </div>--}}

				<div class="@if(!empty($commission_agent)) col-sm-3 @else col-sm-4 @endif">
					<div class="form-group">
						{!! Form::label('transaction_date', __('sale.sale_date') . ':*') !!}
						<div class="input-group">
						<span class="input-group-addon">
							<i class="fa fa-calendar"></i>
						</span>
							{!! Form::text('transaction_date', @format_datetime($transaction_date), ['class' => 'form-control', 'readonly', 'required']); !!}
						</div>
					</div>
				</div>

				{!! Form::hidden('default_price_group', null, ['id' => 'default_price_group']) !!}

				@if(in_array('types_of_service', $enabled_modules) && !empty($types_of_service))
					<div class="col-md-4 col-sm-6">
						<div class="form-group">
							<div class="input-group">
								<span class="input-group-addon">
									<i class="fa fa-external-link-square-alt text-primary service_modal_btn"></i>
								</span>
								{!! Form::select('types_of_service_id', $types_of_service, null, ['class' => 'form-control', 'id' => 'types_of_service_id', 'style' => 'width: 100%;', 'placeholder' => __('lang_v1.select_types_of_service')]); !!}

								{!! Form::hidden('types_of_service_price_group', null, ['id' => 'types_of_service_price_group']) !!}

								<span class="input-group-addon">
									@show_tooltip(__('lang_v1.types_of_service_help'))
								</span>
							</div>
							<small><p class="help-block hide" id="price_group_text">@lang('lang_v1.price_group'): <span></span></p></small>
						</div>
					</div>
					<div class="modal fade types_of_service_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
				@endif

				@if(in_array('subscription', $enabled_modules))
					<div class="col-md-4 pull-right col-sm-6">
						<div class="checkbox">
							<label>
							  {!! Form::checkbox('is_recurring', 1, false, ['class' => 'input-icheck', 'id' => 'is_recurring']); !!} @lang('lang_v1.subscribe')?
							</label><button type="button" data-toggle="modal" data-target="#recurringInvoiceModal" class="btn btn-link"><i class="fa fa-external-link"></i></button>@show_tooltip(__('lang_v1.recurring_invoice_help'))
						</div>
					</div>
				@endif

				@if(!empty($price_groups))
					@if(count($price_groups) > 1)
						<div class="col-sm-4">
							<div class="form-group">
								{!! Form::label('location', __('business.sell_price_tax') . ':') !!}
								<div class="input-group">
									<span class="input-group-addon">
										<i class="fas fa-money-bill-alt"></i>
									</span>
									@php
										reset($price_groups);
									@endphp
									{!! Form::hidden('hidden_price_group', key($price_groups), ['id' => 'hidden_price_group']) !!}
									{!! Form::select('price_group', $price_groups, null, ['class' => 'form-control select2', 'id' => 'price_group']); !!}
									<span class="input-group-addon">
										@show_tooltip(__('lang_v1.price_group_help_text'))
									</span>
								</div>
							</div>
						</div>
					@else
						@php
							reset($price_groups);
						@endphp
						{!! Form::hidden('price_group', key($price_groups), ['id' => 'price_group']) !!}
					@endif
				@endif

				<div class="col-md-4">
				  <div class="form-group">
					<div class="multi-input">
					  {!! Form::label('pay_term_number', __('contact.pay_term') . ':') !!} @show_tooltip(__('tooltip.pay_term'))
					  <br/>
					  {!! Form::number('pay_term_number', $walk_in_customer['pay_term_number'], ['class' => 'form-control width-40 pull-left', 'placeholder' => __('contact.pay_term')]); !!}

					  {!! Form::select('pay_term_type',
						['months' => __('lang_v1.months'),
							'days' => __('lang_v1.days')],
							$walk_in_customer['pay_term_type'],
						['class' => 'form-control width-60 pull-left', 'id' => 'pay_term_type', 'placeholder' => __('messages.please_select')]); !!}
					</div>
				  </div>
				</div>

				<div class="@if(!empty($commission_agent)) col-sm-3 @else col-sm-4 @endif">
					<div class="form-group">
						{!! Form::label('status', __('sale.status') . ':*') !!}
						{!! Form::select('status', ['final' => __('sale.final'), 'draft' => __('sale.draft')], 'final', ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
					</div>
				</div>
                <div class="col-sm-3">
					<div class="form-group">
						{!! Form::label('documents', __('lang_v1.image') . ':') !!}
						{!! Form::file('documents[]', ['class' => 'documents', 'accept' => 'image/*', 'multiple']); !!}
						<small><p class="help-block">@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])</p></small>
					</div>
                </div>

				@if(!empty($commission_agent))
				<div class="col-sm-3">
					<div class="form-group">
					{!! Form::label('commission_agent', __('lang_v1.commission_agent') . ':') !!}
					{!! Form::select('commission_agent',
								$commission_agent, null, ['class' => 'form-control select2']); !!}
					</div>
				</div>
				@endif
{{--				<div class="col-sm-3">--}}
{{--					<div class="form-group">--}}
{{--						{!! Form::label('invoice_scheme_id', __('invoice.invoice_scheme') . ':') !!}--}}
{{--						{!! Form::select('invoice_scheme_id', $invoice_schemes, $default_invoice_schemes->id, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]); !!}--}}
{{--					</div>--}}
{{--				</div>--}}
				<div class="clearfix"></div>
				<!-- Call restaurant module if defined -->
				@if(in_array('tables' ,$enabled_modules) || in_array('service_staff' ,$enabled_modules))
					<span id="restaurant_module_span">
						<div class="col-md-3"></div>
					</span>
				@endif
			</div>
		@endcomponent

		@component('components.widget', ['class' => 'box-primary'])
			<div class="row">
				<div class="col-sm-3 col-sm-offset-1">
					<div class="form-group">
						<button type="button" id="plate_stock_button" class="btn btn-primary btn-flat" data-toggle="modal" data-target="#plate_stock_modal" {{ is_null($default_location) ? 'disabled' : '' }}><i class="fa fa-search"></i> @lang('sale.product_search')</button>
					</div>
				</div>
				<div class="col-sm-10 col-sm-offset-1">
					<div class="form-group">
						<div class="input-group">
							<div class="input-group-btn">
								<button type="button" class="btn btn-default bg-white btn-flat" data-toggle="modal" data-target="#configure_search_modal" title="{{__('lang_v1.configure_product_search')}}"><i class="fa fa-barcode"></i></button>
							</div>
							{!! Form::text('search_product', null, ['class' => 'form-control mousetrap', 'id' => 'search_product', 'placeholder' => __('lang_v1.search_product_placeholder'),
							'disabled' => is_null($default_location)? true : false,
							'autofocus' => is_null($default_location)? false : true,
							]); !!}
							{{--<span class="input-group-btn">
							<button type="button" class="btn btn-default bg-white btn-flat pos_add_quick_product" data-href="{{action('ProductController@quickAdd')}}" data-container=".quick_add_product_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
						</span>--}}
						</div>
					</div>
				</div>

				<div class="col-sm-12 pos_product_div" style="min-height: 0">

					<input type="hidden" name="sell_price_tax" id="sell_price_tax" value="{{$business_details->sell_price_tax}}">

					<!-- Keeps count of product rows -->
					<input type="hidden" id="product_row_count"
						   value="0">
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
								<th>@lang( 'sale.discount' )</th>
								<th>@lang( 'purchase.real_unit_cost' )</th>
								<th>@lang( 'sale.subtotal' )</th>
								<th>@lang( 'messages.action' )</th>
							</tr>
							</thead>
							<tbody></tbody>
						</table>
					</div>
					<div class="table-responsive">
						<table class="table table-condensed table-bordered table-striped">
							<tr>
								<td>
									<div class="pull-right">
										<b>@lang('lang_v1.total_items'):</b>
										<span class="total_quantity">0</span> m<sup>2</sup>
										&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
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
						<div class="col-md-4">
							<div class="form-group">
								{!!Form::label('shipping_charges', __('sale.shipping_charges'))!!}
								{!!Form::text('shipping_charges',@num_format(0.00),['class'=>'form-control input_number','placeholder'=> __('sale.shipping_charges')]);!!}
							</div>
						</div>
						<div class="form-group col-md-4" style="margin-bottom: 0px">
							{!! Form::label('tax_rate_id', __('purchase.purchase_tax') . ':') !!}
							{!! Form::hidden('tax_amount', 0, ['id' => 'tax_amount']); !!}
							{!! Form::text('order_tax_text', 0, ['class' => 'form-control input_number', 'id' => 'order_tax']); !!}
						</div>
						<div class="form-group col-md-4">
							{!! Form::label('discount_type', __( 'lang_v1.discounts' ) . ':') !!}
							{!! Form::hidden('discount_type', 'fixed') !!}
							{!! Form::text('discount_amount', @num_format($business_details->default_sales_discount), ['class' => 'form-control input_number', 'required', 'data-default' => $business_details->default_sales_discount, 'id' => 'discount_amount']); !!}
						</div>
						<div class="col-md-6 pull-right">
							<input type="hidden" name="final_total" id="final_total_input">
							<p class="align-right">
								<b>@lang('sale.total_payable'): </b>
								<span id="total_payable">0</span>
							</p>
						</div>
						<input type="hidden" name="is_direct_sale" value="1">
					</div>
				</div>
			</div>
		@endcomponent

		@can('sell.create')
			@component('components.widget', ['class' => 'box-primary', 'id' => "payment_rows_div", 'title' => __('sale.deposit')])
				<div class="payment_row">
					@include('sale_pos.partials.deposit_row_form', ['row_index' => 0])
				</div>
			@endcomponent

			@component('components.widget', ['class' => 'box-primary', 'id' => "cod_rows_div", 'title' => __('sale.cod')])
				<div class="payment_row">
					@include('sale_pos.partials.cod_row_form', ['row_index' => 0])
				</div>
			@endcomponent
		@endcan
		</div>
	</div>

	<div class="row">
		{!! Form::hidden('is_save_and_print', 0, ['id' => 'is_save_and_print']); !!}
		<div class="col-sm-12 text-right">
			<button type="button" id="submit-sell" class="btn btn-primary btn-flat">@lang('messages.save')</button>
			<button type="button" id="save-and-print" class="btn btn-primary btn-flat">@lang('lang_v1.save_and_print')</button>
		</div>
	</div>

	@if(empty($pos_settings['disable_recurring_invoice']))
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
@include('sale_pos.partials.modalSuggestProduct')
@include('sale_pos.partials.plate_stock')

@stop

@section('javascript')
	<script src="{{ asset('js/pos.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>
@endsection
