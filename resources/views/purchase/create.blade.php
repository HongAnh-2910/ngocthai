@extends('layouts.app')
@section('title', __('purchase.add_purchase'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('purchase.add_purchase') <i class="fa fa-keyboard-o hover-q text-muted" aria-hidden="true" data-container="body" data-toggle="popover" data-placement="bottom" data-content="@include('purchase.partials.keyboard_shortcuts_details')" data-html="true" data-trigger="hover" data-original-title="" title=""></i></h1>
</section>

<!-- Main content -->
<section class="content">

	<!-- Page level currency setting -->
	<input type="hidden" id="p_code" value="{{$currency_details->code}}">
	<input type="hidden" id="p_symbol" value="{{$currency_details->symbol}}">
	<input type="hidden" id="p_thousand" value="{{$currency_details->thousand_separator}}">
	<input type="hidden" id="p_decimal" value="{{$currency_details->decimal_separator}}">
	{!! Form::hidden('default_unit_id', $default_unit_id, ['id' => 'default_unit_id']) !!}

	@include('layouts.partials.error')

	{!! Form::open(['url' => action('PurchaseController@store'), 'method' => 'post', 'id' => 'add_purchase_form', 'files' => true ]) !!}
	@component('components.widget', ['class' => 'box-primary'])
		<div class="row">
			@if(count($suppliers) == 1)
				@php
					$default_supplier = current(array_keys($suppliers->toArray()));
				@endphp
			@else
				@php
					$default_supplier = null;
				@endphp
			@endif
			<div class="col-sm-3">
				<div class="form-group">
					{!! Form::label('contact_id', __('purchase.supplier').':*') !!}
					{!! Form::select('contact_id', $suppliers, $default_supplier, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
				</div>
			</div>

			@if(count($business_locations) == 1)
				@php
					$default_location = current(array_keys($business_locations->toArray()));
					$search_disable = false;
				@endphp
			@else
				@php $default_location = null;
		$search_disable = true;
				@endphp
			@endif
			<div class="col-sm-3">
				<div class="form-group">
					{!! Form::label('location_id', __('purchase.business_location').':*') !!}
					@show_tooltip(__('tooltip.purchase_location'))
					{!! Form::select('location_id', $business_locations, $default_location, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
				</div>
			</div>

			<div class="col-sm-3">
				<div class="form-group">
					{!! Form::label('ref_no', __('purchase.ref_no').':') !!}
					{!! Form::text('ref_no', null, ['class' => 'form-control']); !!}
				</div>
			</div>
			<div class="col-sm-3">
				<div class="form-group">
					{!! Form::label('transaction_date', __('purchase.purchase_date') . ':*') !!}
					<div class="input-group">
						<span class="input-group-addon">
							<i class="fa fa-calendar"></i>
						</span>
						{!! Form::text('transaction_date', @format_datetime($transaction_date) , ['class' => 'form-control', 'readonly', 'required']); !!}
					</div>
				</div>
			</div>
			<div class="col-sm-3 @if(!empty($default_purchase_status)) hide @endif">
				<div class="form-group">
					{!! Form::label('status', __('purchase.purchase_status') . ':*') !!} @show_tooltip(__('tooltip.order_status'))
					{!! Form::select('status', $orderStatuses, $default_purchase_status, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
				</div>
			</div>
			<div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('document', __('purchase.attach_document') . ':') !!}
                    {!! Form::file('document', ['id' => 'upload_document', 'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types')))]); !!}
                    <p class="help-block">
                    	@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
                    	@includeIf('components.document_help_text')
                    </p>
                </div>
            </div>
		</div>
	@endcomponent

	@component('components.widget', ['class' => 'box-primary'])
		<div class="row">
			<div class="col-sm-8 col-sm-offset-2">
				<div class="form-group">
					<div class="input-group">
						<span class="input-group-addon">
							<i class="fa fa-search"></i>
						</span>
						{!! Form::text('search_product', null, ['class' => 'form-control mousetrap', 'id' => 'search_product', 'placeholder' => __('lang_v1.search_product_placeholder'), 'disabled' => $search_disable]); !!}
					</div>
				</div>
			</div>
			{{--<div class="col-sm-2">
				<div class="form-group">
					<button tabindex="-1" type="button" class="btn btn-link btn-modal"data-href="{{action('ProductController@quickAdd')}}"
            	data-container=".quick_add_product_modal"><i class="fa fa-plus"></i> @lang( 'product.add_new_product' ) </button>
				</div>
			</div>--}}
		</div>
		@php
			$hide_tax = '';
			if( session()->get('business.enable_inline_tax') == 0){
				$hide_tax = 'hide';
			}
		@endphp

		<div class="row">
			<div class="col-sm-12">
				<div class="table-responsive">
					<table class="table table-condensed table-bordered table-th-green text-center table-striped" id="purchase_entry_table">
						<thead>
							<tr>
								<th>#</th>
								@if(session('business.enable_lot_number'))
									<th>@lang('lang_v1.lot_number')</th>
								@endif
                                <th>@lang( 'product.product_name' )</th>
                                <th>@lang( 'product.base_unit' )</th>
                                <th>@lang( 'product.height' )</th>
                                <th>@lang( 'product.width' )</th>
								<th>@lang( 'purchase.purchase_price' )</th>
								<th>@lang( 'purchase.purchase_quantity' )</th>
								<th>@lang( 'purchase.purchase_total_quantity' )</th>
								<th>@lang( 'product.weight' )</th>
								<th>@lang( 'purchase.total_purchase_price' )</th>
								<th>@lang( 'purchase.warehouse' )</th>
								<th>@lang( 'purchase.is_origin' )</th>
								<th><i class="fa fa-trash" aria-hidden="true"></i></th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>
				<hr/>
				<div class="pull-right col-md-5">
					<table class="pull-right col-md-12">
						<tr>
							<th class="col-md-7 text-right">@lang( 'lang_v1.total_area' ):</th>
							<td class="col-md-5 text-left">
								<span id="total_quantity">0</span> m<sup>2</sup>
							</td>
						</tr>
						<tr>
							<th class="col-md-7 text-right">@lang( 'purchase.total_weight' ):</th>
							<td class="col-md-5 text-left">
								<span id="total_weight">0</span> kg
							</td>
						</tr>
						<tr>
							<th class="col-md-7 text-right">@lang( 'purchase.total_purchase_price' ):</th>
							<td class="col-md-5 text-left">
								{!! Form::hidden('final_total', 0, ['id' => 'total_price_hidden']) !!}
								<span id="total_price">0</span> đ
							</td>
						</tr>
					</table>
				</div>

				<input type="hidden" id="row_count" value="0">
			</div>
		</div>
	@endcomponent

	@component('components.widget', ['class' => 'box-primary'])
		<div class="box-body">
			<div class="row">
				<div class="form-group">
					{!! Form::label('additional_notes',__('purchase.additional_notes')) !!}
					{!! Form::textarea('additional_notes', null, ['class' => 'form-control', 'rows' => 3]); !!}
				</div>
			</div>
			<br>
			<div class="row">
				<button type="button" id="submit_purchase_form" class="btn btn-primary pull-right btn-flat">@lang('messages.save')</button>
			</div>
		</div>
	@endcomponent
{!! Form::close() !!}
</section>
<!-- quick product modal -->
<div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>
<div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
	@include('contact.create', ['quick_add' => true])
</div>
<!-- /.content -->
@endsection

@section('javascript')
	<script src="{{ asset('js/purchase.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
	@include('purchase.partials.keyboard_shortcuts')
@endsection
