@extends('layouts.app')
@section('title', __('sale.payment_confirm'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>@lang('sale.payment_confirm')</h1>
</section>

<!-- Main content -->
<section class="content no-print">
    @component('components.filters', ['title' => __('report.filters')])
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('transaction_payments_filter_approval_status',  __('sale.confirm_status') . ':') !!}
                {!! Form::select('transaction_payments_filter_approval_status', $approval_statuses, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('transaction_payments_filter_payment_method',  __('lang_v1.payment_method') . ':') !!}
                {!! Form::select('transaction_payments_filter_payment_method', $payment_methods, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('transaction_payments_filter_date_range', __('report.date_range') . ':') !!}
                {!! Form::text('transaction_payments_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
            </div>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'lang_v1.all_payments')])
        @can('sell.view')
            @include('sell_of_cashier.partials.transaction_payments_table')
        @endcan
    @endcomponent
</section>

<div class="modal fade payment_modal" tabindex="-1" role="dialog"
     aria-labelledby="gridSystemModalLabel">
</div>

<div class="modal fade edit_payment_modal" tabindex="-1" role="dialog"
     aria-labelledby="gridSystemModalLabel">
</div>
@stop

@section('javascript')
@include('sell_of_cashier.partials.transaction_payments_table_javascript')
<script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
@endsection
