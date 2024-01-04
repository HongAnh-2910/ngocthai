@extends('layouts.app')
@section('title', __( 'sale.sells'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>@lang( 'sale.sells')
    </h1>
</section>

<!-- Main content -->
{!! Form::open(['action' => 'SellPosController@exportExcel', 'method' => 'post']) !!}
<section class="content no-print">
    @component('components.filters', ['title' => __('report.filters')])
        @include('sell.partials.sell_list_filters')
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
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'lang_v1.all_sales')])
        @slot('tool')
            <div class="box-tools">
                @can('sell.create')
                <a class="btn btn-primary" href="{{action('SellController@create')}}">
                    <i class="fa fa-plus"></i> @lang('messages.add')</a>
                @endcan
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-file-excel"></i> @lang('messages.export_to_excel')</button>
            </div>
        @endslot

        @if(auth()->user()->can('direct_sell.access') ||  auth()->user()->can('view_own_sell_only'))
            @php
                $custom_labels = json_decode(session('business.custom_labels'), true);
            @endphp
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
                        </tr>
                    </thead>
                    <tfoot>
                    {{--<tr>
                        <td data-filter="0"></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>--}}
                    <tr class="bg-gray font-17 footer-total text-center">
                        <td colspan="6"><strong>@lang('sale.total'):</strong></td>
                        <td><span class="display_currency" id="footer_sale_total" data-currency_symbol ="true"></span></td>
                        <td><span class="display_currency" id="footer_total_paid" data-currency_symbol ="true"></span></td>
                        <td><span class="display_currency" id="footer_deposit" data-currency_symbol ="true"></span></td>
                        <td><span class="display_currency" id="footer_cod" data-currency_symbol ="true"></span></td>
                        <td><span class="display_currency" id="footer_total_remaining" data-currency_symbol ="true"></span></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    @endcomponent
</section>
{!! Form::close() !!}
<!-- /.content -->

<div class="modal fade payment_modal" tabindex="-1" role="dialog"
    aria-labelledby="gridSystemModalLabel">
</div>

<div class="modal fade edit_payment_modal" tabindex="-1" role="dialog"
    aria-labelledby="gridSystemModalLabel">
</div>
@stop

@section('javascript')
<script type="text/javascript">
$(document).ready( function(){
    //Date range as a button
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
    });

    var buttons = [
        {
            extend: 'copy',
            text: '<i class="fas fa-copy" aria-hidden="true"></i> ' + LANG.copy,
            className: 'btn-sm',
            exportOptions: {
                columns: ':visible',
            },
            footer: true,
        },
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

    sell_table = $('#sell_table').DataTable({
        processing: true,
        serverSide: true,
        buttons: buttons,
        // orderCellsTop: true,
        aaSorting: [[1, 'desc']],
        "ajax": {
            "url": "/sells",
            "data": function ( d ) {
                if($('#sell_list_filter_date_range').val()) {
                    var start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    var end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
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
            { data: 'action', name: 'action', orderable: false, 'searchable': false},
            { data: 'transaction_date', name: 'transaction_date'},
            { data: 'shipper', name: 'shipper', 'searchable': false},
            { data: 'invoice_no', name: 'invoice_no'},
            { data: 'name', name: 'contacts.name'},
            { data: 'shipping_address', name: 'shipping_address'},
            { data: 'final_total', name: 'transactions.final_total'},
            { data: 'total_paid', name: 'total_paid'},
            { data: 'deposit', name: 'deposit'},
            { data: 'cod', name: 'cod'},
            { data: 'total_remaining'},
        ],

        /*initComplete: function () {
            this.api().columns().every(function () {
                var column = this;
                var input = '<input type="text" class="form-control input-sm multiple_search_box" value="">';
                var filter = $(column.footer()).attr('data-filter');
                if(filter == undefined){
                    filter = 1;
                }

                if(filter == 1){
                    $(input).appendTo($(column.footer()).empty())
                        .on('keyup', function () {
                            var val = $.fn.dataTable.util.escapeRegex($(this).val());
                            column.search(val ? val : '', true, false).draw();
                        });
                }
            });
        },*/

        "fnDrawCallback": function (oSettings) {

            $('#footer_sale_total').text(sum_table_col($('#sell_table'), 'final-total'));

            $('#footer_total_paid').text(sum_table_col($('#sell_table'), 'total-paid'));

            $('#footer_cod').text(sum_table_col($('#sell_table'), 'total_cod'));

            $('#footer_deposit').text(sum_table_col($('#sell_table'), 'total_deposit'));

            $('#footer_total_remaining').text(sum_table_col($('#sell_table'), 'payment_due'));

            // $('#footer_total_sell_return_due').text(sum_table_col($('#sell_table'), 'sell_return_due'));

            // $('#footer_payment_status_count').html(__sum_status_html($('#sell_table'), 'payment-status-label'));

            $('#service_type_count').html(__sum_status_html($('#sell_table'), 'service-type-label'));
            $('#payment_method_count').html(__sum_status_html($('#sell_table'), 'payment-method'));

            __currency_convert_recursively($('#sell_table'));
        },
    });

    $(document).on('change', '#sell_list_filter_location_id, #sell_list_filter_customer_id, #sell_list_filter_payment_status, #created_by, #sales_cmsn_agnt, #service_staffs, #sell_list_filter_address, #sell_list_filter_phone',  function() {
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
});
</script>
<script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
@endsection
