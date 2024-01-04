@extends('layouts.app')
@section('title', __('report.report_owner_target'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>{{ __('report.report_owner_target')}}</h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                @component('components.filters', ['title' => __('report.filters'), 'id' => 'filter_target_owner_report'])
                    {{--{!! Form::hidden('start_date', null, ['id' => 'start_date']) !!}
                    {!! Form::hidden('end_date', null, ['id' => 'end_date']) !!}--}}
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('owner_target_type',  __('target.type') . ':') !!}
                            {!! Form::select('owner_target_type', $types, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('owner_target_filter_date_range', __('report.date_range') . ':') !!}
                            {!! Form::text('owner_target_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']) !!}
                        </div>
                    </div>
                @endcomponent

                @component('components.widget', ['class' => 'box-primary'])
                        @can('target.view')
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="report_owner_target_table">
                                    <thead>
                                    <tr>
                                        <th>@lang('target.target_total')</th>
                                        <th>@lang('target.start_date')</th>
                                        <th>@lang('target.end_date')</th>
                                        <th>@lang('target.percent_complete')</th>
                                    </tr>
                                    </thead>
                                </table>
                            </div>
                        @endcan
                    @endcomponent
            </div>
        </div>
    </section>
    <!-- /.content -->

@endsection

@section('javascript')
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>
@endsection
