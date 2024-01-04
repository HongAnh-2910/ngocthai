@extends('layouts.app')
@section('title', __('report.stock_report'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>{{ __('report.stock_report')}}</h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12" id="plate_stock_filter_form">
                @component('components.filters', ['title' => __('report.filters')])
                    {{--{!! Form::open(['url' => action('ReportController@getStockReport'), 'method' => 'get', 'id' => 'plate_stock_filter_form' ]) !!}--}}
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('location_id', __('purchase.business_location').':') !!}
                            {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required', 'id' => 'location_id']); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('warehouse_id', __('purchase.warehouse') . ':') !!}
                            {!! Form::select('warehouse_id', $warehouses, null, ['placeholder' => __('messages.all'), 'class' => 'form-control', 'style' => 'width:100%']); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('category_id', __('category.category') . ':') !!}
                            {!! Form::select('category', $categories, null, ['placeholder' => __('messages.all'), 'class' => 'form-control', 'style' => 'width:100%', 'id' => 'category_id']); !!}
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('variation_id', __('sale.product') . ':') !!}
                            {!! Form::select('variation_id', $products, null, ['placeholder' => __('messages.all'), 'class' => 'form-control', 'style' => 'width:100%']); !!}
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('height',  __('sale.height') . ':') !!}
                            {!! Form::text('height', null, ['class' => 'form-control', 'placeholder' => __('sale.height'), 'id' => 'plate_height']); !!}
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('width',  __('sale.width') . ':') !!}
                            {!! Form::text('width', null, ['class' => 'form-control', 'placeholder' => __('sale.width'), 'id' => 'plate_width']); !!}
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['class' => 'box-primary'])
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="plate_stock_table">
                            <thead>
                            <tr>
                                <th>@lang('product.sku')</th>
                                <th>@lang('business.product')</th>
                                <th>@lang('sale.height')</th>
                                <th>@lang('sale.width')</th>
                                <th>@lang('report.current_stock')</th>
                                <th>@lang('report.expect_stock')</th>
                                <th>@lang('purchase.warehouse')</th>
                                <th>@lang('sale.is_origin')</th>
                                <th>@lang('messages.view_detail')</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                @endcomponent
            </div>
        </div>
    </section>
    <!-- /.content -->

    <div class="modal fade stock_history_modal" tabindex="-1" role="dialog"
         aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade reverse_size_modal" tabindex="-1" role="dialog"
         aria-labelledby="gridSystemModalLabel">
    </div>
@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
@endsection
