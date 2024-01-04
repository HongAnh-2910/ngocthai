@extends('layouts.app')
@section('title', __('target.targets'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>@lang('target.targets')
        <small></small>
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content no-print">
    @component('components.filters', ['title' => __('report.filters')])
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('target_list_filter_date_range', __('report.date_range') . ':') !!}
                {!! Form::text('target_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
            </div>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __('target.all_targets')])
        @can('target.create')
            @slot('tool')
                <div class="box-tools">
                    <a class="btn btn-block btn-primary" href="{{action('TargetController@create')}}">
                    <i class="fa fa-plus"></i> @lang('messages.add')</a>
                </div>
            @endslot
        @endcan
        @can('target.view')
            <div class="table-responsive">
                <table class="table table-bordered table-striped ajax_view" id="target_table">
                    <thead>
                        <tr>
                            <th>@lang('messages.action')</th>
                            <th>@lang('target.target_total')</th>
                            <th>@lang('target.start_date')</th>
                            <th>@lang('target.end_date')</th>
                        </tr>
                    </thead>
                    {{--<tfoot>
                        <tr class="bg-gray font-17 text-center footer-total">
                            <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                            <td id="footer_status_count"></td>
                            <td id="footer_payment_status_count"></td>
                            <td><span class="display_currency" id="footer_target_total" data-currency_symbol ="true"></span></td>
                            <td class="text-left"><small>@lang('report.purchase_due') - <span class="display_currency" id="footer_total_due" data-currency_symbol ="true"></span><br>
                            @lang('lang_v1.purchase_return') - <span class="display_currency" id="footer_total_purchase_return_due" data-currency_symbol ="true"></span>
                            </small></td>
                            <td></td>
                        </tr>
                    </tfoot>--}}
                </table>
            </div>
        @endcan
    @endcomponent

    {{--<div class="modal fade product_modal" tabindex="-1" role="dialog"
    	aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade payment_modal" tabindex="-1" role="dialog"
        aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
    </div>--}}

</section>

<!-- /.content -->
@stop
@section('javascript')
<script src="{{ asset('js/target.js?v=' . $asset_v) }}"></script>
@endsection