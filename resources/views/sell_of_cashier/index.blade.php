@extends('layouts.app')
@section('title', __( 'sale.sell_of_cashier'))

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header no-print">
        <h1>@lang( 'sale.sell_of_cashier')
        </h1>
    </section>

    <!-- Main content -->
    <section class="content no-print">
        @component('components.widget', ['class' => 'box-primary', 'title' => __( 'lang_v1.info_day')])
            <div class="row">
                <div class="col-md-5">
                    <span><i class="fas fa-money-bill-alt fa-fw" style="font-size: 17px;margin-right: 10px"></i></span>
                    <span>@lang('lang_v1.total_cash'):</span>
                    <span class="display_currency total_cash" data-currency_symbol="true" style="font-size: 20px;padding-right: 10px">{{ $total_payment_on_day['total_money_payment_cash'] }}</span>
                </div>
                <div class="col-md-5">
                    <span><i class="fas fa-money-check-alt fa-fw" style="font-size: 17px;margin-right: 10px"></i></span>
                    <span>@lang('lang_v1.total_bank_transform'): </span>
                    <span class="display_currency total_bank" data-currency_symbol="true" style="font-size: 20px;padding-right: 10px">{{ $total_payment_on_day['total_money_payment_bank'] }}</span>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        {{--{!! Form::label('total_day_filter_date_range',  __('report.date_range') . ':') !!}--}}
                        {!! Form::text('total_day_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'id' => 'total_day_filter_date_range', 'class' => 'form-control', 'readonly']); !!}
                    </div>
                </div>
            </div>
        @endcomponent

        {!! Form::open(['action' => 'SellPosController@exportOnDayExcelCashier', 'method' => 'post']) !!}
        @component('components.filters', [
            'class' => 'box-primary',
            'icon' => '<i class="fa-fw fas fa-file-invoice-dollar"></i>',
            'title' => __( 'lang_v1.all_sales'),
            'idCashier' => 'list_sell',
        ])
            @component('components.cashier')
                @include('sell_of_cashier.partials.sell_list_filters')
                @if($is_woocommerce)
                    <div class="col-md-3">
                        <div class="form-group">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('only_woocommerce_sells', 1, false,
                                    [ 'class' => 'input-icheck', 'id' => 'synced_from_woocommerce']); !!} {{ __('lang_v1.synced_from_woocommerce') }}
                                </label>
                            </div>
                        </div>
                    </div>
                @endif
            @endcomponent
{{--            @can('sell.create')--}}
{{--                @slot('tool')--}}
{{--                    <div class="box-tools">--}}
{{--                        <button type="submit" class="btn btn-primary">--}}
{{--                            <i class="fa fa-file-excel"></i> @lang('messages.export_to_excel')</button>--}}
{{--                    </div>--}}
{{--                @endslot--}}
{{--            @endcan--}}
            @can('sell.receipt_expense')
                @slot('tool')
                        <br>
                        <br>
                    <div class="box-tools" style="float: left">
                        <button type="button" class="btn btn-success confirm_remaining_cash"><i class="fas fa-money-bill-alt"></i>&nbsp;&nbsp;@lang('lang_v1.confirm_remaining_by_cash')</button>
                        <button type="button" class="btn btn-warning confirm_remaining_bank"><i class="fas fa-money-check-alt"></i>&nbsp;&nbsp;@lang('lang_v1.confirm_remaining_by_bank')</button>
{{--                        <button type="button" class="btn btn-warning confirm_remaining_bank" href="btn-bank-account"><i class="fas fa-money-check-alt"></i>&nbsp;&nbsp;@lang('lang_v1.confirm_remaining_by_bank')</button>--}}
                        <button type="button" class="btn btn-info confirm_debit_paper"><i class="fas fa-clipboard-check"></i>&nbsp;&nbsp;@lang('lang_v1.confirm_paper_debit')</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-file-excel"></i>&nbsp;&nbsp;@lang('lang_v1.hand_over_end_day')</button>
                    </div>
                @endslot
            @endcan

            @if(auth()->user()->can('sell.receipt_expense'))
                <div class="table-responsive">
                    <table class="table table-bordered table-striped ajax_view" id="sell_table">
                        <thead>
                        <tr>
                            <th>@lang('messages.action')</th>
                            <th>@lang('messages.date')</th>
                            <th>@lang('sale.shipper')</th>
                            <th style="width: 85px;">@lang('sale.invoice_no')</th>
                            <th style="width: 200px;">@lang('sale.customer_name')</th>
                            <th>@lang('sale.address')</th>
                            <th>@lang('sale.total_amount')</th>
                            <th>@lang('sale.total_paid')</th>
                            <th>@lang('lang_v1.deposit')</th>
                            <th>@lang('lang_v1.cod')</th>
                            <th>@lang('lang_v1.return_money')</th>
                            <th>@lang('lang_v1.debit_paper')</th>
                            <th><input type="checkbox" id="select-all-row"></th>
                            {{--<th>@lang('sale.payment_status')</th>--}}
                        </tr>
                        </thead>
                        <tfoot>
                        <tr class="bg-gray font-17 footer-total text-center">
                            <td colspan="6"><strong>@lang('sale.total'):</strong></td>
                            <td><span class="display_currency" id="footer_sale_total" data-currency_symbol ="true"></span></td>
                            <td><span class="display_currency" id="footer_total_paid" data-currency_symbol ="true"></span></td>
                            <td><span class="display_currency" id="footer_deposit" data-currency_symbol ="true"></span></td>
                            <td><span class="display_currency" id="footer_cod" data-currency_symbol ="true"></span></td>
                            <td><span class="display_currency" id="footer_total_remaining" data-currency_symbol ="true"></span></td>
                            <td></td>
                            <td></td>
                            {{--<td id="footer_payment_status_count"></td>--}}
                        </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        @endcomponent
        {!! Form::close() !!}

        @can('sell.receipt_expense')
            @component('components.filters', ['class' => 'box-primary', 'icon' => '<i class="fas fa-file-signature"></i>', 'title' => __( 'lang_v1.receipts'), 'idCashier' => 'list_receipt'])
                <div class="row" style="margin-bottom: 10px">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('receipt_filter_type',  __('expense.type') . ':') !!}
                            {!! Form::select('receipt_filter_type', \App\TransactionReceipt::$TYPES, null, ['class' => 'form-control', 'placeholder' => __('lang_v1.all')]); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('receipt_filter_method',  __('expense.payment_method') . ':') !!}
                            {!! Form::select('receipt_filter_method', ['cash' => __('lang_v1.cash'), 'bank_transfer' => __('lang_v1.bank_transfer')], null, ['class' => 'form-control', 'placeholder' => __('lang_v1.all')]); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('receipt_filter_customer_id',  __('contact.customer') . ':') !!}
                            {!! Form::select('receipt_filter_customer_id', $receipt_customers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                        </div>
                    </div>
                    {{--<div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('receipt_filter_date_range', __('report.date_range') . ':') !!}
                            {!! Form::text('receipt_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
                        </div>
                    </div>--}}
                </div>
                <button style="margin-bottom: 20px;float: right" type="button" class="btn btn-primary btn-add-receipt" data-href="{{ action('ExpenseController@createReceiptRow') }}"
                        data-container=".receipt_modal">
                    <i class="fa fa-plus"></i> @lang( 'messages.add' )
                </button>
                <div class="clearfix"></div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped ajax_view" id="receipt_table">
                        <thead>
                        <tr>
                            <th>@lang('expense.receipt_no')</th>
                            <th>@lang('expense.type')</th>
                            <th>@lang('sale.customer_id')</th>
                            <th>@lang('sale.customer_name')</th>
                            <th>@lang('expense.content')</th>
                            <th>@lang('expense.total_money')</th>
                            <th>@lang('expense.payment_method')</th>
                            <th>@lang('expense.account_bank')</th>
                            <th>@lang('messages.actions')</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                        {{--<tfoot>
                            <tr class="font-17 footer-total text-center">
                                <td colspan="3"><strong>@lang('sale.total'):</strong></td>
                                <td><span class="display_currency" id="footer_receipt_total" data-currency_symbol ="true"></span></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>--}}
                    </table>
                    <input type="hidden" id="row_count_receipt" value="0">
                </div>
            @endcomponent

            @component('components.filters', ['class' => 'box-primary', 'icon' => '<i class="fa-fw fas fa-file-invoice"></i>', 'title' => __( 'lang_v1.expenditures'), 'idCashier' => 'list_expense'])
                <div class="row" style="margin-bottom: 10px">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('expense_filter_type',  __('expense.type') . ':') !!}
                            {!! Form::select('expense_filter_type', \App\TransactionExpense::$TYPES, null, ['class' => 'form-control', 'placeholder' => __('lang_v1.all')]); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('expense_filter_method',  __('expense.payment_method') . ':') !!}
                            {!! Form::select('expense_filter_method', ['cash' => __('lang_v1.cash'), 'bank_transfer' => __('lang_v1.bank_transfer')], null, ['class' => 'form-control', 'placeholder' => __('lang_v1.all')]); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('expense_filter_customer_id',  __('contact.customer') . ':') !!}
                            {!! Form::select('expense_filter_customer_id', $receipt_customers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                        </div>
                    </div>
                    {{--<div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('expense_filter_date_range', __('report.date_range') . ':') !!}
                            {!! Form::text('expense_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
                        </div>
                    </div>--}}
                </div>
                <button style="margin-bottom: 20px;float: right" type="button" class="btn btn-primary btn-add-expense" data-href="{{ action('ExpenseController@createExpenseRow') }}"
                        data-container=".expense_modal">
                    <i class="fa fa-plus"></i> @lang( 'messages.add' )
                </button>
                <div class="clearfix"></div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped ajax_view" id="expense_cashier_table">
                        <thead>
                        <tr>
                            <th>@lang('expense.expense_no')</th>
                            <th>@lang('expense.type')</th>
                            <th>@lang('sale.customer_id')</th>
                            <th>@lang('sale.customer_name')</th>
                            <th>@lang('expense.content')</th>
                            <th>@lang('expense.total_money')</th>
                            <th>@lang('expense.payment_method')</th>
                            <th>@lang('expense.account_bank')</th>
                            <th>@lang('messages.actions')</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                        {{--<tfoot>
                        <tr class="font-17 footer-total text-center">
                            <td colspan="3"><strong>@lang('sale.total'):</strong></td>
                            <td><span class="display_currency" id="footer_expense_total" data-currency_symbol ="true"></span></td>
                            <td colspan="2"></td>
                        </tr>
                        </tfoot>--}}
                    </table>
                    <input type="hidden" id="row_count_expense" value="0">
                </div>
                @endcomponent
                </div>
                @endcan
    </section>
    <!-- /.content -->
    <div class="modal fade payment_modal" tabindex="-1" role="dialog"
         aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog"
         aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade receipt_modal" tabindex="-1" role="dialog"
         aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade expense_modal" tabindex="-1" role="dialog"
         aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade debit_paper_modal" tabindex="-1" role="dialog"
         aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade" id="bank_account_modal">
        <div class="modal-dialog">
            <div class="modal-content">

                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title">@lang('lang_v1.select_bank_account')</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <!-- Modal body -->
                <div class="modal-body">
                    <div class="form-group">
                        <label for="bank_account">@lang('lang_v1.bank_account')</label>
                        <select name="bank_account" id="bank_account" class="form-control">
                            @foreach($accounts as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Modal footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-success btn-bank-account" id="btn-bank-account" data-dismiss="modal">@lang('messages.submit')</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('messages.close')</button>
                </div>

            </div>
        </div>
    </div>

    <div id="list-link-fast">
        <div>
            <a href="#list_sell" class="fixed-facebook"><i class="fa-fw fas fa-file-invoice-dollar"></i><span>@lang('lang_v1.list_sell')</span></a>
        </div>
        @can('sell.receipt_expense')
            <div>
                <a href="#list_receipt" class="fixed-gplus"><i class="fas fa-file-signature"></i><span>@lang('lang_v1.list_receipt')</span></a>
            </div>
            <div>
                <a href="#list_expense" class="fixed-tumblr"><i class="fa-fw fas fa-file-invoice"></i><span>@lang('lang_v1.list_expense')</span></a>
            </div>
        @endcan
    </div>
@stop

@section('javascript')
    <script type="text/javascript">
        $(document).ready( function(){
            var receipt_buttons = [
                {
                    extend: 'excel',
                    text: '<i class="fa fa-file-excel" aria-hidden="true"></i> ' + LANG.export_to_excel,
                    className: 'btn-sm',
                    exportOptions: {
                        columns: [0,1,2,3,4,5]
                    },
                    footer: true,
                    customize: function( xlsx, row ) {
                        var sheet = xlsx.xl.worksheets['sheet1.xml'];
                        $('row c[r^="F"]', sheet).attr( 's', 63);
                    },
                },
                {
                    extend: 'print',
                    text: '<i class="fa fa-print" aria-hidden="true"></i> ' + LANG.print,
                    className: 'btn-sm',
                    exportOptions: {
                        columns: [0,1,2,3,4,5],
                        stripHtml: true,
                    },
                    footer: true,
                    customize: function ( win ) {
                        if ($('.print_table_part').length > 0 ) {
                            $($('.print_table_part').html()).insertBefore($(win.document.body).find( 'table' ));
                        }
                        if ($(win.document.body).find( 'table.hide-footer').length) {
                            $(win.document.body).find( 'table.hide-footer tfoot' ).remove();
                        }
                        __currency_convert_recursively($(win.document.body).find( 'table' ));
                    }
                },
                {
                    extend: 'colvis',
                    text: '<i class="fa fa-columns" aria-hidden="true"></i> ' + LANG.col_vis,
                    className: 'btn-sm',
                },
            ];

            var sell_buttons = [
                {
                    extend: 'print',
                    text: '<i class="fa fa-print" aria-hidden="true"></i> ' + LANG.print,
                    className: 'btn-sm',
                    exportOptions: {
                        columns: ':gt(0)',
                        stripHtml: true,
                    },
                    footer: true,
                    customize: function ( win ) {
                        if ($('.print_table_part').length > 0 ) {
                            $($('.print_table_part').html()).insertBefore($(win.document.body).find( 'table' ));
                        }
                        if ($(win.document.body).find( 'table.hide-footer').length) {
                            $(win.document.body).find( 'table.hide-footer tfoot' ).remove();
                        }
                        __currency_convert_recursively($(win.document.body).find( 'table' ));
                    }
                },
                {
                    extend: 'colvis',
                    text: '<i class="fa fa-columns" aria-hidden="true"></i> ' + LANG.col_vis,
                    className: 'btn-sm',
                },
            ];

            //Date range total on day
            $('#total_day_filter_date_range').daterangepicker(
                dateRangeSettings,
                function (start, end) {
                    $('#total_day_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                    sell_table.ajax.reload();
                    receipt_table.ajax.reload();
                    expense_cashier_table.ajax.reload();
                }
            );
            $('#total_day_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#total_day_filter_date_range').val('');
                sell_table.ajax.reload();
                receipt_table.ajax.reload();
                expense_cashier_table.ajax.reload();
            });

            $('#total_day_filter_date_range').change(function () {
                var start = $(this).data('daterangepicker').startDate.format('YYYY-MM-DD');
                var end = $(this).data('daterangepicker').endDate.format('YYYY-MM-DD');
                $.ajax({
                    type: 'post',
                    url: '/sells-of-cashier/get_total_filter_by_day',
                    dataType: 'json',
                    data: {
                        start_date: start,
                        end_date: end
                    },
                    success: function(result) {
                        if (result.success) {
                            var currency_symbols = {
                                'VND': '₫'
                            };
                            var currency_name = 'VND';
                            var currency = currency_symbols[currency_name];

                            var total_cash = new Intl.NumberFormat().format(result.data.total_money_payment_cash);
                            var total_bank = new Intl.NumberFormat().format(result.data.total_money_payment_bank);
                            $('.total_cash').text(`${total_cash} ${currency}`);
                            $('.total_bank').text(`${total_bank} ${currency}`);
                        }
                    }
                });
            });

            /*//Date range as a button
            $('#sell_list_filter_date_range').daterangepicker(
                dateRangeSettings,
                function (start, end) {
                    $('#sell_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                    sell_table.ajax.reload();
                }
            );

            $('#sell_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#sell_list_filter_date_range').val('');
                sell_table.ajax.reload();
            });*/

            sell_table = $('#sell_table').DataTable({
                processing: true,
                serverSide: true,
                buttons: sell_buttons,
                aaSorting: [[1, 'desc']],
                "ajax": {
                    "url": "/sells-of-cashier",
                    "data": function ( d ) {
                        if($('#total_day_filter_date_range').val()) {
                            var start = $('#total_day_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                            var end = $('#total_day_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                            d.start_date = start;
                            d.end_date = end;
                            $('#sell_list_filter_start_date').val(start);
                            $('#sell_list_filter_end_date').val(end);
                        }
                        d.is_direct_sale = 1;

                        d.location_id = $('#sell_list_filter_location_id').val();
                        d.customer_id = $('#sell_list_filter_customer_id').val();
                        d.payment_status = $('#sell_list_filter_payment_status').val();
                        d.created_by = $('#created_by').val();
                        d.sales_cmsn_agnt = $('#sales_cmsn_agnt').val();
                        d.service_staffs = $('#service_staffs').val();
                        d.address = $('#sell_list_filter_address').val();
                        d.phone = $('#sell_list_filter_phone').val();
                        d.payment_method = $('#sell_list_filter_payment_method').val();

                        @if($is_woocommerce)
                        if($('#synced_from_woocommerce').is(':checked')) {
                            d.only_woocommerce_sells = 1;
                        }
                        @endif

                        if($('#only_subscriptions').is(':checked')) {
                            d.only_subscriptions = 1;
                        }

                        d = __datatable_ajax_callback(d);
                    }
                },
                columns: [
                    { data: 'action', name: 'action', orderable: false, "searchable": false},
                    { data: 'transaction_date', name: 'transaction_date'  },
                    { data: 'shipper', name: 'shipper', 'searchable': false},
                    { data: 'invoice_no', name: 'invoice_no'},
                    { data: 'name', name: 'contacts.name'},
                    { data: 'shipping_address', name: 'shipping_address'},
                    { data: 'final_total', name: 'final_total'},
                    { data: 'total_paid', name: 'total_paid'},
                    { data: 'deposit', name: 'deposit'},
                    { data: 'cod', name: 'cod'},
                    { data: 'total_remaining', name: 'total_remaining'},
                    { data: 'is_confirm_debit_paper' },
                    { data: 'mass_action', orderable: false, searchable: false },
                    // { data: 'payment_status', name: 'payment_status'},
                ],
                "fnDrawCallback": function (oSettings) {

                    $('#footer_sale_total').text(sum_table_col($('#sell_table'), 'final-total'));

                    $('#footer_total_paid').text(sum_table_col($('#sell_table'), 'total-paid'));

                    $('#footer_cod').text(sum_table_col($('#sell_table'), 'total_cod'));

                    $('#footer_deposit').text(sum_table_col($('#sell_table'), 'total_deposit'));

                    $('#footer_total_remaining').text(sum_table_col($('#sell_table'), 'payment_due'));

                    // $('#footer_total_sell_return_due').text(sum_table_col($('#sell_table'), 'sell_return_due'));

                    $('#footer_payment_status_count').html(__sum_status_html($('#sell_table'), 'payment-status-label'));

                    $('#service_type_count').html(__sum_status_html($('#sell_table'), 'service-type-label'));
                    $('#payment_method_count').html(__sum_status_html($('#sell_table'), 'payment-method'));

                    __currency_convert_recursively($('#sell_table'));
                },
                createdRow: function( row, data, dataIndex ) {
                    $( row ).find('td:eq(8), td:eq(9), td:eq(10), td:eq(11)').attr('class', 'clickable_td');
                    $( row ).find('td:eq(12)').attr('class', 'selectable_td');
                }
            });

            $(document).on('change', '#sell_list_filter_location_id, #sell_list_filter_customer_id, #sell_list_filter_payment_status, #created_by, #sales_cmsn_agnt, #service_staffs, #sell_list_filter_address, #sell_list_filter_phone, #sell_list_filter_payment_method',  function() {
                sell_table.ajax.reload();
            });
            @if($is_woocommerce)
            $('#synced_from_woocommerce').on('ifChanged', function(event){
                sell_table.ajax.reload();
            });
            @endif

            $('#only_subscriptions').on('ifChanged', function(event){
                sell_table.ajax.reload();
            });

            // Receipt
            receipt_table = $('#receipt_table').DataTable({
                processing: true,
                serverSide: true,
                buttons: receipt_buttons,
                aaSorting: [],
                'ajax': {
                    'url': '/receipt-of-cashier',
                    "data": function ( d ) {
                        if($('#total_day_filter_date_range').val()) {
                            var start = $('#total_day_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                            var end = $('#total_day_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                            d.start_date = start;
                            d.end_date = end;
                        }
                        // d.location_id = $('#sell_list_filter_location_id').val();
                        d.customer_id = $('#receipt_filter_customer_id').val();
                        d.note = $('#receipt_filter_note').val();
                        d.type = $('#receipt_filter_type').val();
                        d.payment_method = $('#receipt_filter_method').val();

                        d = __datatable_ajax_callback(d);
                    }
                },
                columns: [
                    { data: 'ref_no', name: 't.ref_no' },
                    { data: 'type', name: 'type' },
                    { data: 'contact_id', name: 'ct.contact_id'},
                    { data: 'name', name: 'ct.name'},
                    { data: 'note', name: 'note'},
                    { data: 'total_money', name: 'total_money'},
                    { data: 'method', name: 'tp.method'},
                    { data: 'bank_account_number', name: 'tp.bank_account_number'},
                    { data: 'action', name: 'action', orderable: false, "searchable": false},
                ],
                "fnDrawCallback": function (oSettings) {
                    // $('#footer_receipt_total').text(sum_table_col($('#sell_table'), 'final-total-receipt'));
                    __currency_convert_recursively($('#receipt_table'));
                },
            });

            $(document).on('change', '#receipt_filter_customer_id, #receipt_filter_note, #receipt_filter_type, #receipt_filter_method',  function() {
                receipt_table.ajax.reload();
            });

            /*$('#receipt_filter_date_range').daterangepicker(
                dateRangeSettings,
                function (start, end) {
                    $('#receipt_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                    receipt_table.ajax.reload();
                }
            );
            $('#receipt_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#receipt_filter_date_range').val('');
                receipt_table.ajax.reload();
            });*/

            // Add a new row receipt
            $(document).on('submit', 'form#receipt_add_form', function(e) {
                e.preventDefault();
                $(this)
                    .find('button[type="submit"]')
                    .attr('disabled', true);
                var data = $(this).serialize();

                $.ajax({
                    method: 'POST',
                    url: $(this).attr('action'),
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            $('.receipt_modal').modal('hide');
                            toastr.success(result.msg);
                            receipt_table.ajax.reload();
                            location.reload();
                            /*if (result.data.method == 'cash') {
                                $('.total_cash').text(__currency_trans_from_en(result.data.total_cash, true, false));
                            } else if (result.data.method == 'bank_transfer') {
                                $('.total_bank').text(__currency_trans_from_en(result.data.total_bank, true, false));
                            }*/
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            });

            // Update receipt
            $(document).on('click', 'button.edit_receipt_button', function() {
                $('.receipt_modal').load($(this).data('href'), function() {
                    $(this).modal('show');
                    getFormatNumber();

                    $('form#receipt_update_form').submit(function(e) {
                        e.preventDefault();
                        $(this)
                            .find('button[type="submit"]')
                            .attr('disabled', true);
                        var data = $(this).serialize();

                        $.ajax({
                            method: 'POST',
                            url: $(this).attr('action'),
                            dataType: 'json',
                            data: data,
                            success: function(result) {
                                if (result.success == true) {
                                    $('div.receipt_modal').modal('hide');
                                    toastr.success(result.msg);
                                    receipt_table.ajax.reload();
                                    location.reload();
                                    /*if (result.data.method == 'cash') {
                                        $('.total_cash').text(__currency_trans_from_en(result.data.total_cash, true, false));
                                    } else if (result.data.method == 'bank_transfer') {
                                        $('.total_bank').text(__currency_trans_from_en(result.data.total_bank, true, false));
                                    }*/
                                } else {
                                    toastr.error(result.msg);
                                }
                            },
                        });
                    });
                });
            });

            // Delete receipt
            $(document).on('click', 'button.delete_receipt_button', function() {
                swal({
                    title: LANG.sure,
                    text: LANG.confirm_delete_receipt,
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(willDelete => {
                    if (willDelete) {
                        var href = $(this).data('href');
                        var data = $(this).serialize();

                        $.ajax({
                            method: 'post',
                            url: href,
                            dataType: 'json',
                            data: data,
                            success: function(result) {
                                if (result.success == true) {
                                    toastr.success(result.msg);
                                    receipt_table.ajax.reload();
                                    location.reload();
                                    /*if (result.data.method == 'cash') {
                                        $('.total_cash').text(__currency_trans_from_en(result.data.total_cash, true, false));
                                    } else if (result.data.method == 'bank_transfer') {
                                        $('.total_bank').text(__currency_trans_from_en(result.data.total_bank, true, false));
                                    }*/
                                } else {
                                    toastr.error(result.msg);
                                }
                            },
                        });
                    }
                });
            });

            $(document).on('click', '.btn-add-receipt', function(e) {
                e.preventDefault();
                var container = $(this).data('container');

                $.ajax({
                    url: $(this).data('href'),
                    dataType: 'html',
                    success: function(result) {
                        $(container)
                            .html(result)
                            .modal('show');
                        getFormatNumber();
                    }
                });
            });

            // Expense
            expense_cashier_table = $('#expense_cashier_table').DataTable({
                processing: true,
                serverSide: true,
                buttons: receipt_buttons,
                aaSorting: [],
                'ajax': {
                    'url': '/expense-of-cashier',
                    "data": function ( d ) {
                        if($('#total_day_filter_date_range').val()) {
                            var start = $('#total_day_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                            var end = $('#total_day_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                            d.start_date = start;
                            d.end_date = end;
                        }
                        // d.location_id = $('#sell_list_filter_location_id').val();
                        d.customer_id = $('#expense_filter_customer_id').val();
                        d.note = $('#expense_filter_note').val();
                        d.type = $('#expense_filter_type').val();
                        d.payment_method = $('#expense_filter_method').val();

                        d = __datatable_ajax_callback(d);
                    }
                },
                columns: [
                    { data: 'ref_no', name: 't.ref_no'},
                    { data: 'type', name: 'type'  },
                    { data: 'contact_id', name: 'ct.contact_id'},
                    { data: 'name', name: 'ct.name'},
                    { data: 'note', name: 'note', 'searchable': false},
                    { data: 'total_money', name: 'total_money'},
                    { data: 'method', name: 'tp.method'},
                    { data: 'bank_account_number', name: 'tp.bank_account_number'},
                    { data: 'action', name: 'action', orderable: false, "searchable": false},
                ],
                "fnDrawCallback": function (oSettings) {
                    // $('#footer_expense_total').text(sum_table_col($('#expense_cashier_table'), 'final-total-expense'));
                    __currency_convert_recursively($('#expense_cashier_table'));
                },
            });

            $(document).on('change', '#expense_filter_customer_id, #expense_filter_note, #expense_filter_type, #expense_filter_method',  function() {
                expense_cashier_table.ajax.reload();
            });

            /*$('#expense_filter_date_range').daterangepicker(
                dateRangeSettings,
                function (start, end) {
                    $('#expense_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                    expense_cashier_table.ajax.reload();
                }
            );
            $('#expense_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#expense_filter_date_range').val('');
                expense_cashier_table.ajax.reload();
            });*/

            $(document).on('click', '.btn-add-expense', function(e) {
                e.preventDefault();
                var container = $(this).data('container');

                $.ajax({
                    url: $(this).data('href'),
                    dataType: 'html',
                    success: function(result) {
                        $(container)
                            .html(result)
                            .modal('show');
                        getFormatNumber();
                    },
                });
            });

            $(document).on('submit', 'form#expense_add_form', function(e) {
                e.preventDefault();
                $(this)
                    .find('button[type="submit"]')
                    .attr('disabled', true);
                var data = $(this).serialize();

                $.ajax({
                    method: 'POST',
                    url: $(this).attr('action'),
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            $('.expense_modal').modal('hide');
                            toastr.success(result.msg);
                            expense_cashier_table.ajax.reload();
                            location.reload();
                            /*if (result.data.method == 'cash') {
                                $('.total_cash').text(__currency_trans_from_en(result.data.total_cash, true, false));
                            } else if (result.data.method == 'bank_transfer') {
                                $('.total_bank').text(__currency_trans_from_en(result.data.total_bank, true, false));
                            }*/
                        }
                        else {
                            $('.expense_modal').modal('hide');
                            swal({
                                icon: 'error',
                                title: result.msg.title_expense,
                                text: result.msg.content_expense
                            });
                        }
                    },
                });
            });

            // Update expense
            $(document).on('click', 'button.edit_expense_button', function() {
                $('.expense_modal').load($(this).data('href'), function() {
                    $(this).modal('show');
                    getFormatNumber();

                    $('form#expense_update_form').submit(function(e) {
                        e.preventDefault();
                        $(this)
                            .find('button[type="submit"]')
                            .attr('disabled', true);
                        var data = $(this).serialize();
                        $.ajax({
                            method: 'POST',
                            url: $(this).attr('action'),
                            dataType: 'json',
                            data: data,
                            success: function(result) {
                                if (result.success == true) {
                                    $('div.expense_modal').modal('hide');
                                    toastr.success(result.msg);
                                    expense_cashier_table.ajax.reload();
                                    location.reload();
                                } else {
                                    console.log(result);
                                    $('.expense_modal').modal('hide');
                                    swal({
                                        icon: 'error',
                                        title: result.msg.title_expense,
                                        text: result.msg.content_expense
                                    });
                                }
                            },
                        });
                    });
                });
            });

            // Delete receipt
            $(document).on('click', 'button.delete_expense_button', function() {
                swal({
                    title: LANG.sure,
                    text: LANG.confirm_delete_expense,
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true
                }).then(willDelete => {
                    if (willDelete) {
                        var href = $(this).data('href');
                        var data = $(this).serialize();

                        $.ajax({
                            method: 'post',
                            url: href,
                            dataType: 'json',
                            data: data,
                            success: function(result) {
                                if (result.success == true) {
                                    toastr.success(result.msg);
                                    expense_cashier_table.ajax.reload();
                                    location.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            },
                        });
                    }
                });
            });

            function changeDisableReceipt() {
                if ($('.receipt_row').val() == 'recovery_dues' || $('.receipt_row').val() == 'deposit') {
                    $('.receipt_customer_row').prop('disabled', false);
                    $('.receipt_customer').css('display', 'block');
                    $('.receipt_total_row').prop('disabled', false);
                    $('.receipt_method_row').prop('disabled', false);
                    $('.btn-save-receipt').prop('disabled', false);
                    $('.receipt_customer_row').prop('required', true);
                } else if ($('.receipt_row').val() == 'receipt') {
                    $('.receipt_customer_row').prop('disabled', true);
                    $('.receipt_customer').css('display', 'none');
                    $('.receipt_total_row').prop('disabled', false);
                    $('.receipt_method_row').prop('disabled', false);
                    $('.btn-save-receipt').prop('disabled', false);
                    $('.receipt_customer_row').prop('required', false);
                } /*else {
                    $('.receipt_customer_row').prop('disabled', true);
                    $('.receipt_total_row').prop('disabled', true);
                    $('.receipt_method_row').prop('disabled', true);
                    $('.receipt_bank_account_row').prop('disabled', true);
                    $('.btn-save-receipt').prop('disabled', true);
                    $('.receipt_customer_row').prop('required', false);
                }*/

                if ($('.receipt_method_row').val() == 'cash') {
                    $('.receipt_bank_account').css('display', 'none');
                } else {
                    $('.receipt_bank_account').css('display', 'block');
                }
            }

            function changeDisableExpense() {
                if ($('.expense_row').val() == 'return_customer') {
                    $('.expense_customer_row').prop('disabled', false);
                    $('.expense_customer').css('display', 'block');
                    $('.expense_total_row').prop('disabled', false);
                    $('.expense_method_row').prop('disabled', false);
                    $('.btn-save-expense').prop('disabled', false);
                } else if ($('.expense_row').val() == 'expense') {
                    $('.expense_customer_row').prop('disabled', true);
                    $('.expense_customer_row').val('');
                    $('.expense_customer').css('display', 'none');
                    $('.expense_total_row').prop('disabled', false);
                    $('.expense_method_row').prop('disabled', false);
                    $('.btn-save-expense').prop('disabled', false);
                } else {
                    $('.expense_customer_row').prop('disabled', true);
                    $('.expense_total_row').prop('disabled', true);
                    $('.expense_method_row').prop('disabled', true);
                    $('.expense_bank_account_row').prop('disabled', true);
                    $('.btn-save-expense').prop('disabled', true);
                }

                if ($('.expense_method_row').val() == 'cash') {
                    $('.expense_bank_account').css('display', 'none');
                } else {
                    $('.expense_bank_account').css('display', 'block');
                }
            }

            $(document).on('change', '.receipt_row', function () {
                changeDisableReceipt();
            });

            $(document).on('change', '.expense_row', function () {
                changeDisableExpense();
            });

            $(document).on('change', '.receipt_payment_types_dropdown', function() {
                if ($('.receipt_payment_types_dropdown').val() == 'bank_transfer') {
                    $('.receipt_bank_account_row').prop('disabled', false);
                    $('.receipt_bank_account').css('display', 'block');
                } else {
                    $('.receipt_bank_account_row').prop('disabled', true);
                    $('.receipt_bank_account').css('display', 'none');
                }
            });

            $(document).on('click', '.btn-save-receipt, .btn-update-receipt', function() {
                $tr = $(this).closest('tr');
                let type = $tr.find('.receipt_row').val();
                let contact_id = $tr.find('.receipt_customer_row').val();
                let total_money = $tr.find('.receipt_total_row').val();
                let note = $tr.find('.receipt_description_row').val();
                let method = $tr.find('.receipt_method_row').val();
                let id = $tr.find('.receipt_id_row').val();
                let bank_account = $tr.find('.receipt_bank_account_row').val();
                let transaction_id = $tr.find('.receipt_transaction_id_row').val();

                if (type && total_money && method) {
                    $.ajax({
                        url: '/receipts/store-receipt-row',
                        type: 'post',
                        data: {
                            type: type,
                            contact_id: contact_id,
                            total_money: total_money,
                            note: note,
                            method: method,
                            bank_account: bank_account,
                            id: id,
                            transaction_id: transaction_id
                        },
                        dataType: 'json',
                        success: function(result) {
                            receipt_table.ajax.reload();
                        }
                    });
                }
            });

            $(document).on('click', '.btn-edit-receipt', function() {
                $tr = $(this).closest('tr');
            });

            // Expense
            $(document).on('change', '.expense_payment_types_dropdown', function() {
                if ($('.expense_payment_types_dropdown').val() == 'bank_transfer') {
                    $('.expense_bank_account_row').prop('disabled', false);
                    $('.expense_bank_account').css('display', 'block');
                } else {
                    $('.expense_bank_account_row').prop('disabled', true);
                    $('.expense_bank_account').css('display', 'none');
                }
            });

            $(document).on('click', '.btn-save-expense, .btn-update-expense', function() {
                $tr = $(this).closest('tr');
                let type = $tr.find('.expense_row').val();
                let contact_id = $tr.find('.expense_customer_row').val();
                let total_money = $tr.find('.expense_total_row').val();
                let note = $tr.find('.expense_description_row').val();
                let method = $tr.find('.expense_method_row').val();
                let id = $tr.find('.expense_id_row').val();
                let bank_account = $tr.find('.expense_bank_account_row').val();
                let transaction_id = $tr.find('.expense_transaction_id_row').val();

                if (type && total_money && method) {
                    $.ajax({
                        url: '/expenses/store-expense-row',
                        type: 'post',
                        data: {
                            type: type,
                            contact_id: contact_id,
                            total_money: total_money,
                            note: note,
                            method: method,
                            bank_account: bank_account,
                            id: id,
                            transaction_id: transaction_id
                        },
                        dataType: 'json',
                        success: function(result) {
                            if (result.success == true) {
                                location.reload();
                            }
                        }
                    });
                }
            });

            // getFormatNumber
            $(document).on('click', '.btn-edit-expense', function() {
                $tr = $(this).closest('tr');
                $tr.find('.expense_row').prop('disabled', true);
                changeDisableExpense($tr);
                if ($tr.find('.expense_payment_types_dropdown').val() == 'bank_transfer') {
                    $tr.find('.expense_bank_account_row').prop('disabled', false);
                }

                $tr.find('.btn-update-expense').css('display', 'block');
                $tr.find('.btn-edit-expense').css('display', 'none');
            });

            $(document).on( 'click', '.approve_payment', function(e){
                e.preventDefault();
                var container = $('.edit_payment_modal');

                $.ajax({
                    url: $(this).data('href'),
                    dataType: 'html',
                    success: function(result) {
                        container.html(result).modal('show');
                        __currency_convert_recursively(container);
                        container.find('form#transaction_payment_add_form').validate();
                    },
                });
            });

            $(document).on('click', '.add_remaining_payment', function(e) {
                e.preventDefault();
                var container = $('.payment_modal');
                if (e.target.tagName == 'I' || e.target.tagName == 'A' ) {
                    return false;
                }

                $.ajax({
                    url: $(this).attr('href'),
                    dataType: 'json',
                    success: function(result) {
                        if (result.status == 'due') {
                            container.html(result.view).modal('show');
                            __currency_convert_recursively(container);
                            /*$('#paid_on').datetimepicker({
                                format: moment_date_format + ' ' + moment_time_format,
                                ignoreReadonly: true,
                            });*/
                            container.find('form#transaction_payment_add_form').validate();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            });

            $(document).on('click', '.show-debit-paper', function() {
                var container = $('.debit_paper_modal');

                $.ajax({
                    url: $(this).attr('href'),
                    dataType: 'html',
                    success: function(result) {
                        container.html(result).modal('show');
                    }
                });
            });
        });

        function getSelectedRows() {
            var selected_rows = [];
            var i = 0;
            $('.row-select:checked').each(function () {
                selected_rows[i++] = $(this).val();
            });

            return selected_rows;
        }

        $(document).on('click', '.confirm_debit_paper', function(e){
            e.preventDefault();
            var selected_rows = getSelectedRows();

            if (selected_rows.length > 0) {
                swal({
                    title: LANG.sure,
                    text: LANG.is_confirm_debit_paper,
                    icon: 'warning',
                    buttons: ["Hủy", "Xác nhận"]
                    // cancelButtonColor: '#d33',
                    // confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result) {
                        $.ajax({
                            url: '/expense-of-cashier/confirm-debit-paper',
                            type: 'post',
                            data: { selected_rows: selected_rows },
                            dataType: 'json',
                            global: false,
                            cache: false,
                            beforeSend: function() {
                                $('body').find('.loading_wrap').show();
                            },
                            success: function (result) {
                                if (result.success) {
                                    swal(
                                        LANG.confirmed,
                                        result.message,
                                        'success'
                                    )
                                    location.reload();
                                } else {
                                    swal(
                                        LANG.failure,
                                        result.message,
                                        'error'
                                    );
                                }
                            },
                            complete: function(data) {
                                $('body').find('.loading_wrap').hide();
                            },
                        });
                    }
                })
            } else{
                swal('@lang("lang_v1.no_row_selected")');
            }
        });

        $(document).on('click', '.confirm_remaining_cash', function(e){
            e.preventDefault();
            var selected_rows = getSelectedRows();
            swal({
                title: LANG.sure,
                text: LANG.confirm_remaining_by_cash,
                icon: 'warning',
                buttons: [LANG.cancel, LANG.confirm]
            }).then((result) => {
                if (result) {
                    $.ajax({
                        url: '/expense-of-cashier/confirm-bulk-remaining',
                        type: 'post',
                        data: { selected_rows: selected_rows, method: 'cash' },
                        dataType: 'json',
                        global: false,
                        cache: false,
                        beforeSend: function() {
                            $('body').find('.loading_wrap').show();
                        },
                        success: function (result) {
                            if (result.success) {
                                swal({
                                    title: LANG.confirmed,
                                    text: result.message,
                                    icon: 'success',
                                    allowOutsideClick: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                swal(
                                    LANG.failure,
                                    result.message,
                                    'error'
                                );
                            }
                        },
                        complete: function(data) {
                            $('body').find('.loading_wrap').hide();
                        },
                    });
                }
            })
        });

        $(document).on('click', '.confirm_remaining_bank', function(e){
            e.preventDefault();
            $('#bank_account_modal').modal('show');
        });

        $(document).on('click', '#btn-bank-account', function(e){
            var selected_rows = getSelectedRows();
            console.log(selected_rows);

            if (selected_rows.length > 0) {
                $(this).attr('disabled', true);
                var bank_account = $('#bank_account').val();

                $.ajax({
                    url: '/expense-of-cashier/confirm-bulk-remaining',
                    type: 'post',
                    data: {selected_rows: selected_rows, method: 'bank_transfer', bank_account: bank_account},
                    dataType: 'json',
                    global: false,
                    cache: false,
                    beforeSend: function () {
                        $('body').find('.loading_wrap').show();
                    },
                    success: function (result) {
                        console.log(result);
                        if (result.success) {
                            swal({
                                title: LANG.confirmed,
                                text: result.message,
                                icon: 'success',
                                allowOutsideClick: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            swal(
                                LANG.failure,
                                result.message,
                                'error'
                            );
                            $('#bank_account').val('');
                        }
                    },
                    complete: function (data) {
                        $('.btn-bank-account').attr('disabled', false);
                        $('body').find('.loading_wrap').hide();
                    },
                });
            } else{
                swal('@lang("lang_v1.no_row_selected")');
            }
        });

        $(document).on('click', '.btn-cancel-remaining', function(e){
            e.preventDefault();
            let id = $(this).data('transaction_id');
            swal({
                title: LANG.sure,
                text: LANG.cancel_remaining,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then((result) => {
                if (result) {
                    $.ajax({
                        url: '/expense-of-cashier/cancel-remaining',
                        type: 'post',
                        data: { id: id },
                        dataType: 'json',
                        success: function (result) {
                            if (result.success) {
                                toastr.success(result.msg);
                                location.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });
    </script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
@endsection
