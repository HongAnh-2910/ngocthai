@extends('layouts.app')
@section('title', __('report.report_total_sale'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>{{ __('report.report_total_sale')}}</h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                @component('components.filters', ['title' => __('report.filters')])
                    {!! Form::open(['url' => action('ReportController@reportExportImport'), 'method' => 'get', 'id' => 'sale_report_filter_form' ]) !!}
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('user_id',  __('user.staff') . ':') !!}
                            {!! Form::select('user_id', $users, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'filter_user_id']); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        {!! Form::label('report_by_sale_filter_range', __('report.date_range') . ':') !!}
                        {!! Form::text('report_by_sale_filter_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
                    </div>
                @endcomponent
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['class' => 'box-primary'])
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-head-center" id="total_sale_table">
                            <thead>
                            <tr>
                                <th>@lang('user.name')</th>
                                <th>@lang('report.total_sale')</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                @endcomponent
            </div>
        </div>
    </section>
    <!-- /.content -->

@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">
        //Date range as a button
        $('#report_by_sale_filter_range').daterangepicker(
            dateRangeSettings,
            function (start, end) {
                $('#report_by_sale_filter_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            }
        );
        $('#report_by_sale_filter_range').on('cancel.daterangepicker', function(ev, picker) {
            $('#report_by_sale_filter_range').val('');
        });
    </script>
@endsection
