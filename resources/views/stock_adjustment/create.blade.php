@extends('layouts.app')
@section('title', __('stock_adjustment.add'))

@section('content')

	<!-- Content Header (Page header) -->
	<section class="content-header">
		<br>
		<h1>@lang('stock_adjustment.add')</h1>
		<!-- <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
            <li class="active">Here</li>
        </ol> -->
	</section>

	<!-- Main content -->
	<section class="content no-print">
		{!! Form::open(['url' => action('StockAdjustmentController@store'), 'method' => 'post', 'id' => 'stock_adjustment_form' ]) !!}
		{!! Form::hidden('max_weight_dont_need_confirm', $max_weight_dont_need_confirm, ['id' => 'max_weight_dont_need_confirm']) !!}
		{!! Form::hidden('max_pcs_dont_need_confirm', $max_pcs_dont_need_confirm, ['id' => 'max_pcs_dont_need_confirm']) !!}

		<div class="box box-solid">
			<div class="box-body">
				<div class="row">
						<div class="col-sm-3">
							<div class="form-group">
								{!! Form::label('location_id', __('purchase.business_location').':*') !!}
								{!! Form::select('location_id', $business_locations, empty($default_location) ? null : $default_location, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required', 'id' => 'adjust_location_id']); !!}
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
							{!! Form::label('transaction_date', __('messages.date') . ':*') !!}
							<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-calendar"></i>
							</span>
								{!! Form::text('transaction_date', @format_datetime($transaction_date), ['class' => 'form-control', 'readonly', 'required']); !!}
							</div>
						</div>
					</div>
					<div class="col-sm-3">
						<div class="form-group">
							{!! Form::label('adjustment_type', __('stock_adjustment.adjustment_type') . ':*') !!} @show_tooltip(__('tooltip.adjustment_type'))
							{!! Form::select('adjustment_type', [ 'normal' =>  __('stock_adjustment.normal'), 'abnormal' =>  __('stock_adjustment.abnormal')], 'normal', ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
						</div>
					</div>
				</div>
			</div>
		</div> <!--box end-->
		<div class="box box-solid">
			<div class="box-body">
				<div class="row">
					<div class="col-sm-12">
						<div class="form-group">
							<div class="input-group">
								<button type="button" id="plate_stock_button" class="btn btn-primary btn-flat" data-toggle="modal" data-target="#plate_stock_deliver_modal"><i class="fa fa-search"></i> @lang('stock_adjustment.select_plate_to_adjustment')</button>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-12">
						{{--<input type="hidden" id="product_row_index" value="0">
						<input type="hidden" id="total_amount" name="final_total" value="0">--}}
						<div class="table-responsive">
							<table class="table table-condensed table-th-green text-center table-bordered table-striped table-responsive" id="stock_adjustment_product_table">
								<thead>
									<tr>
										<th>@lang( 'product.product_name' )</th>
										<th>@lang( 'stock_adjustment.current_height' )</th>
										<th>@lang( 'stock_adjustment.current_width' )</th>
										<th>@lang( 'stock_adjustment.current_stock' )</th>
										<th>@lang( 'purchase.warehouse' )</th>
										<th>@lang( 'stock_adjustment.adjustment_height' )</th>
										<th>@lang( 'stock_adjustment.adjustment_width' )</th>
										<th>@lang( 'stock_adjustment.adjustment_plate' )</th>
										<th>@lang( 'stock_adjustment.weight_or_quantity_adjustment' ) @show_tooltip( __('tooltip.max_quantity_dont_need_confirm', [ 'weight' => $max_weight_dont_need_confirm, 'quantity' => $max_pcs_dont_need_confirm ]))</th>
										<th><i class="fa fa-trash" aria-hidden="true"></i></th>
									</tr>
								</thead>
								<tbody></tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div> <!--box end-->
		<div class="box box-solid">
			<div class="box-body">
				<div class="row">
					<div class="col-sm-4">
						<div class="form-group">
							{!! Form::label('total_amount_recovered', __('stock_adjustment.total_amount_recovered') . ':') !!} @show_tooltip(__('tooltip.total_amount_recovered'))
							{!! Form::text('total_amount_recovered', 0, ['class' => 'form-control input_number', 'placeholder' => __('stock_adjustment.total_amount_recovered')]); !!}
						</div>
					</div>
					<div class="col-sm-4">
						<div class="form-group">
							{!! Form::label('additional_notes', __('stock_adjustment.reason_for_stock_adjustment') . ':') !!}
							{!! Form::textarea('additional_notes', null, ['class' => 'form-control', 'placeholder' => __('stock_adjustment.reason_for_stock_adjustment'), 'rows' => 3]); !!}
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-12">
						<button type="button" class="btn btn-primary pull-right" id="save_stock_adjustment">@lang('messages.save')</button>
					</div>
				</div>

			</div>
		</div> <!--box end-->
		{!! Form::close() !!}
	</section>
	@include('stock_adjustment.partials.plate_stock')
@stop
@section('javascript')
	<script src="{{ asset('js/stock_adjustment.js?v=' . $asset_v) }}"></script>
@endsection
