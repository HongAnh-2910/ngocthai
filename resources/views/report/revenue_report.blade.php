@extends('layouts.app')
@section('title', __( 'report.revenue_report'))
<style>
</style>
@section('content')
    <section class="content-header no-print">
        <h1>@lang( 'report.revenue_report')
        </h1>
    </section>

    <!-- Main content -->
    {!! Form::open(['action' => 'ReportController@exportRevenueByDayReport', 'method' => 'post', 'id' => 'revenue_by_day_form']) !!}
    <section class="content no-print">
        @component('components.widget', ['class' => 'box-primary'])
            @slot('tool')
                <div class="box-tools-2 row">
                    <div class="box-tools-left col-md-6">
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('report_on_day_filter_date', __('report.date') . ':') !!}
                                {!! Form::text('report_on_day_filter_date', date('Y-m-d'), ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
                            </div>
                        </div>
                    </div>
                    <div class="box-tools-right col-md-6">
                        {{--<button type="submit" class="btn btn-primary">
                            <i class="fa fa-file-excel"></i> @lang('messages.export_to_excel')</button>--}}
                        <button type="button" class="btn btn-primary print_revenue_by_day">
                            <i class="fas fa-print"></i> @lang('messages.print')</button>
                    </div>
                </div>
            @endslot

            <div class="row">
                <div class="col-md-3">
                    <span><i class="fas fa-money-bill-alt fa-fw" style="font-size: 17px;margin-right: 10px"></i></span>
                    <span>@lang('report.total_revenue'):</span>
                    <span class="display_currency total_revenue" data-currency_symbol="true" style="font-size: 20px;padding-right: 10px">{{ $total['total_revenue'] }}</span>
                </div>
{{--                <div class="col-md-3">--}}
{{--                    <span><i class="fas fa-money-bill-alt fa-fw" style="font-size: 17px;margin-right: 10px"></i></span>--}}
{{--                    <span>@lang('report.total_debt'): </span>--}}
{{--                    <span class="display_currency total_debt" data-currency_symbol="true" style="font-size: 20px;padding-right: 10px">{{ $total['total_debt'] }}</span>--}}
{{--                </div>--}}
                <div class="col-md-3">
                    <span><i class="fas fa-money-bill-alt fa-fw" style="font-size: 17px;margin-right: 10px"></i></span>
                    <span>@lang('report.debit'): </span>
                    <span class="display_currency total_due_splice" data-currency_symbol="true" style="font-size: 20px;padding-right: 10px">{{ $total['due'] ?? 0 }}</span>
                </div>
                <div class="col-md-3">
                    <span><i class="fas fa-money-bill-alt fa-fw" style="font-size: 17px;margin-right: 10px"></i></span>
                    <span>@lang('report.credit'): </span>
                    <span class="display_currency total_payment" data-currency_symbol="true" style="font-size: 20px;padding-right: 10px">{{ $total['payment'] ?? 0 }}</span>
                </div>
                <div class="col-md-3">
                    <span><i class="fas fa-money-bill-alt fa-fw" style="font-size: 17px;margin-right: 10px"></i></span>
                    <span>@lang('report.total_cash'): </span>
                    <span class="display_currency total_money_payment_cash" data-currency_symbol="true" style="font-size: 20px;padding-right: 10px">{{ $total['total_money_payment_cash'] }}</span>
                </div>
                <div class="col-md-3">
                    <span><i class="fas fa-money-bill-alt fa-fw" style="font-size: 17px;margin-right: 10px"></i></span>
                    <span>@lang('lang_v1.total_bank_transform'): </span>
                    <span class="display_currency total_money_payment_bank" data-currency_symbol="true" style="font-size: 20px;padding-right: 10px">{{ $total['total_money_payment_bank'] }}</span>
                </div>
            </div>
        @endcomponent

        <div class="row">
            <div class="col-md-6">
                @component('components.filters', ['class' => 'box-primary', 'icon' => '<i class="fas fa-file-signature"></i>', 'title' => __( 'report.receipts_on_day'), 'idCashier' => 'list_receipt'])
                    <div class="row no-print" style="margin-bottom: 10px">
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('receipt_filter_customer_id',  __('contact.customer') . ':') !!}
                                {!! Form::select('receipt_filter_customer_id', $receipt_customers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                            </div>
                        </div>
{{--                        <div class="col-md-6">--}}
{{--                            <div class="form-group">--}}
{{--                                {!! Form::label('receipt_filter_date_range', __('report.date_range') . ':') !!}--}}
{{--                                {!! Form::text('receipt_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}--}}
{{--                            </div>--}}
{{--                        </div>--}}
                    </div>
                    <div class="clearfix"></div>
                    <div class="table-responsive" style="margin-top: 10px;">
                        <table class="table table-bordered table-striped ajax_view" id="receipt_table">
                            <thead>
                            <tr>
                                <th>@lang('expense.customer')</th>
                                <th>@lang('expense.content')</th>
                                <th>@lang('expense.total_money')</th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot>
                            <tr class="bg-gray font-17 footer-total text-center">
                                <td colspan="2"><strong>@lang('sale.total'):</strong></td>
                                <td><span class="display_currency" id="footer_receipt_total" data-currency_symbol ="true"></span></td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                @endcomponent
            </div>
            <div class="col-md-6">
                @component('components.filters', ['class' => 'box-primary', 'icon' => '<i class="fa-fw fas fa-file-invoice"></i>', 'title' => __( 'report.expenses_on_day'), 'idCashier' => 'list_expense'])
                    <div class="row no-print" style="margin-bottom: 10px">
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('expense_filter_customer_id',  __('contact.customer') . ':') !!}
                                {!! Form::select('expense_filter_customer_id', $receipt_customers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                            </div>
                        </div>
{{--                        <div class="col-md-6">--}}
{{--                            <div class="form-group">--}}
{{--                                {!! Form::label('expense_filter_date_range', __('report.date_range') . ':') !!}--}}
{{--                                {!! Form::text('expense_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}--}}
{{--                            </div>--}}
{{--                        </div>--}}
                    </div>
                    <div class="clearfix"></div>
                    <div class="table-responsive" style="margin-top: 10px;">
                        <table class="table table-bordered table-striped ajax_view" id="expense_cashier_table">
                            <thead>
                            <tr>
                                <th>@lang('expense.customer')</th>
                                <th>@lang('expense.content')</th>
                                <th>@lang('expense.total_money')</th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot>
                            <tr class="bg-gray font-17 footer-total text-center">
                                <td colspan="2"><strong>@lang('sale.total'):</strong></td>
                                <td><span class="display_currency" id="footer_expense_total" data-currency_symbol="true"></span></td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                @endcomponent
            </div>
            <div class="col-md-12">
                @component('components.filters', ['class' => 'box-primary', 'icon' => '<i class="fa-fw fas fa-file-invoice-dollar"></i>', 'title' => __( 'report.due_on_day'), 'idCashier' => 'list_expense'])
                    <div class="row no-print" style="margin-bottom: 10px">
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('debt_filter_customer_id',  __('contact.customer') . ':') !!}
                                {!! Form::select('debt_filter_customer_id', $receipt_customers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                            </div>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="table-responsive" style="margin-top: 10px;">
                        <table class="table table-bordered table-striped ajax_view" id="debt_table">
                            <thead>
                            <tr>
                                <th>@lang('expense.customer')</th>
                                <th>@lang('report.total_due_customer')</th>
                                <th>@lang('report.total_due_business')</th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot>
                                <tr class="bg-gray font-17 footer-total text-center">
                                    <td colspan="1"><strong>@lang('sale.total'):</strong></td>
                                    <td><span class="display_currency" id="footer_total_due_customer" data-currency_symbol="true"></span></td>
                                    <td><span class="display_currency" id="footer_total_due_business" data-currency_symbol="true"></span></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endcomponent
            </div>
        </div>
    </section>
    {!! Form::close() !!}
@stop

@section('javascript')
    <script type="text/javascript">
        $(document).ready( function() {
            $('#report_on_day_filter_date').datepicker({
                format: 'yyyy-mm-dd',
            });

            function getTotalRevenueByDay(date){
                $.ajax({
                    method: 'POST',
                    url: '/reports/get-total-revenue-by-day-report',
                    "data": {
                        date: date,
                    },
                    dataType: 'json',
                    success: function(result) {
                        if (result.success == 1) {
                            $('.total_revenue').text(new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'VND' }).format(result.data.total_revenue));
                            $('.total_debt').text(new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'VND' }).format(result.data.total_debt));
                            $('.total_due_splice').text(new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'VND' }).format(result.data.due));
                            $('.total_payment').text(new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'VND' }).format(result.data.payment));
                            $('.total_money_payment_cash').text(new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'VND' }).format(result.data.total_money_payment_cash));
                            $('.total_money_payment_bank').text(new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'VND' }).format(result.data.total_money_payment_bank));
                        }
                    },
                });
            }

            $('#report_on_day_filter_date').datepicker().on('changeDate', function (e) {
                var date = $('#report_on_day_filter_date').val();
                getTotalRevenueByDay(date);

                debt_table.ajax.reload();
                expense_cashier_table.ajax.reload();
                receipt_table.ajax.reload();
            });

            $(document).on('click', '.print_revenue_by_day', function(){
                var date = $('#report_on_day_filter_date').val();
                var receipt_customer_id = $('#receipt_filter_customer_id').val();
                var expense_customer_id = $('#expense_filter_customer_id').val();
                var debt_customer_id = $('#debt_filter_customer_id').val();

                $.ajax({
                    method: 'POST',
                    url: '/reports/print-revenue-by-day-report',
                    "data": {
                        date: date,
                        receipt_customer_id: receipt_customer_id,
                        expense_customer_id: expense_customer_id,
                        debt_customer_id: debt_customer_id,
                    },
                    dataType: 'json',
                    success: function(result) {
                        if (result.success == 1 && result.receipt.html_content != '') {
                            $('#receipt_section').html(result.receipt.html_content);
                            __currency_convert_recursively($('#receipt_section'));
                            __print_receipt('receipt_section');
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            });

            receipt_table = $('#receipt_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [],
                buttons: [],
                'ajax': {
                    'url': '/reports/revenue-by-date-report',
                    "data": function ( d ) {
                        d.date = $('#report_on_day_filter_date').val();
                        d.customer_id = $('#receipt_filter_customer_id').val();
                        d = __datatable_ajax_callback(d);
                    }
                },
                columns: [
                    { data: 'payment_for', name: 'payment_for'},
                    { data: 'note', name: 'note', 'searchable': false },
                    { data: 'amount', name: 'amount'},
                ],
                "fnDrawCallback": function (oSettings) {
                    $('#footer_receipt_total').text(sum_table_col($('#receipt_table'), 'total-money-receipt'));
                    __currency_convert_recursively($('#receipt_table'));
                },
            });

            $(document).on('change', '#receipt_filter_customer_id, #receipt_filter_note',  function() {
                receipt_table.ajax.reload();
            });

            // Expense
            expense_cashier_table = $('#expense_cashier_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [],
                buttons: [],
                'ajax': {
                    'url': '/reports/expense-revenue-report',
                    "data": function ( d ) {
                        d.date = $('#report_on_day_filter_date').val();
                        d.customer_id = $('#expense_filter_customer_id').val();

                        d = __datatable_ajax_callback(d);
                    }
                },
                columns: [
                    { data: 'payment_for', name: 'payment_for'},
                    { data: 'note', name: 'note', 'searchable': false},
                    { data: 'amount', name: 'amount'}
                ],
                "fnDrawCallback": function (oSettings) {
                    $('#footer_expense_total').text(sum_table_col($('#expense_cashier_table'), 'total-money-expense'));
                    __currency_convert_recursively($('#expense_cashier_table'));
                },
            });

            $(document).on('change', '#expense_filter_customer_id, #expense_filter_note',  function() {
                expense_cashier_table.ajax.reload();
            });

            // Debt
            debt_table = $('#debt_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [],
                buttons: [],
                'ajax': {
                    'url': '/reports/debt-revenue-report',
                    "data": function ( d ) {
                        d.date = $('#report_on_day_filter_date').val();
                        d.customer_id = $('#debt_filter_customer_id').val();

                        d = __datatable_ajax_callback(d);
                    }
                },
                columns: [
                    { data: 'contact_id', name: 'contact_id'},
                    { data: 'total_due_customer', name: 'total_due_customer'},
                    { data: 'total_due_business', name: 'total_due_business', orderable: false}
                ],
                "fnDrawCallback": function (oSettings) {
                    $('#footer_total_due_customer').text(sum_table_col($('#debt_table'), 'total_due_customer'));
                    $('#footer_total_due_business').text(sum_table_col($('#debt_table'), 'total_due_business'));
                    __currency_convert_recursively($('#debt_table'));
                },
            });

            $(document).on('change', '#debt_filter_customer_id',  function() {
                debt_table.ajax.reload();
            });
        });
    </script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
@endsection
