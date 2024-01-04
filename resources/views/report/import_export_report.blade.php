@extends('layouts.app')
@section('title', __('report.import_export_report'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>{{ __('report.import_export_report')}}</h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                @component('components.filters', ['title' => __('report.filters')])
                    {!! Form::open(['url' => action('ReportController@reportExportImport'), 'method' => 'get', 'id' => 'invention_stock_report_filter_form' ]) !!}
{{--                    <div class="col-md-3">--}}
{{--                        <div class="form-group">--}}
{{--                            {!! Form::label('invention_location_id',  __('purchase.business_location') . ':') !!}--}}
{{--                            {!! Form::select('invention_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}--}}
{{--                        </div>--}}
{{--                    </div>--}}
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('invention_category_id', __('category.category') . ':') !!}
                            {!! Form::select('invention_category', $categories, null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'invention_category_id']); !!}
                        </div>
                    </div>
{{--                    <div class="col-md-3">--}}
{{--                        <div class="form-group">--}}
{{--                            {!! Form::label('sub_category_id', __('product.sub_category') . ':') !!}--}}
{{--                            {!! Form::select('sub_category', array(), null, ['placeholder' => __('messages.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'invention_sub_category_id']); !!}--}}
{{--                        </div>--}}
{{--                    </div>--}}
                    <div class="col-md-3">
                        {!! Form::label('report_by_stock_filter_range', __('report.date_range') . ':') !!}
                        {!! Form::text('report_by_stock_filter_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
                    </div>
                @endcomponent
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['class' => 'box-primary'])
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-head-center" id="import_export_stock_table">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>@lang('product.product_name')</th>
                                    <th>@lang('sale.height')</th>
                                    <th>@lang('sale.width')</th>
                                    <th>@lang('report.begin_stock_quantity')</th>
                                    <th>@lang('report.import_in_period_stock_quantity')</th>
                                    <th>@lang('report.export_in_period_stock_quantity')</th>
                                    <th>@lang('report.end_stock_quantity')</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                @endcomponent
            </div>
        </div>

        <div class="modal fade add_alert_modal" tabindex="-1" role="dialog"
             aria-labelledby="gridSystemModalLabel">
        </div>
    </section>
    <!-- /.content -->

@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">
        //Date range as a button
        $('#report_by_stock_filter_range').daterangepicker(
            dateRangeSettings,
            function (start, end) {
                $('#report_by_stock_filter_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            }
        );
        $('#report_by_stock_filter_range').on('cancel.daterangepicker', function(ev, picker) {
            $('#report_by_stock_filter_range').val('');
        });
    </script>
@endsection
