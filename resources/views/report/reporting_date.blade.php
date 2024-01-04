@extends('layouts.app')
@section('title', __( 'report.reporting_date_name'))

@section('content')
    <section class="content-header no-print">
        <h1>@lang( 'report.reporting_date_name')
        </h1>
    </section>

    <!-- Main content -->
    <section class="content no-print">
        @component('components.filters', [
                                            'class' => 'box-primary',
                                            'title' => __( 'report.filters'),
                                            'idCashier' => 'list_sell',
                                        ])
            <div class="row">
                @include('sell.partials.sell_list_filters')
            </div>
        @endcomponent

        @component('components.widget', ['class' => 'box-primary'])
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

            @php
                $custom_labels = json_decode(session('business.custom_labels'), true);
            @endphp
            <div class="table-responsive">
                <table class="table table-bordered table-striped ajax_view" id="reporting_date_table">
                    <thead>
                    <tr>
                        <th>@lang('sale.shipper')</th>
                        <th>@lang('sale.invoice_no')</th>
                        <th>@lang('sale.customer_name')</th>
                        <th>@lang('sale.address')</th>
                        <th>@lang('sale.total_amount')</th>
                        <th>@lang('lang_v1.deposit')</th>
                        <th>@lang('lang_v1.cod')</th>
                        <th>@lang('lang_v1.return_money')</th>
                        <th style="width: 150px;">@lang('sale.payment_status')</th>
                    </tr>
                    </thead>
                    <tfoot>
                    <tr class="bg-gray font-17 footer-total text-center">
                        <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                        <td><span class="display_currency" id="footer_sale_total" data-currency_symbol ="true"></span></td>
                        <td><span class="display_currency" id="footer_deposit" data-currency_symbol ="true"></span></td>
                        <td><span class="display_currency" id="footer_cod" data-currency_symbol ="true"></span></td>
                        <td><span class="display_currency" id="footer_total_remaining" data-currency_symbol ="true"></span></td>
                        <td id="footer_payment_status_count"></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        @endcomponent
    </section>
@stop

@section('javascript')
    <script type="text/javascript">
        $(document).ready( function() {
            //Date range as a button
            $('#sell_list_filter_date_range').daterangepicker(
                dateRangeRevenueSettings,
                function (start, end) {
                    $('#sell_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                    reporting_date_table.ajax.reload();
                }
            );
            $('#sell_list_filter_date_range').on('cancel.daterangepicker', function (ev, picker) {
                $('#sell_list_filter_date_range').val('');
                reporting_date_table.ajax.reload();
            });

            reporting_date_table = $('#reporting_date_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [[1, 'desc']],
                "ajax": {
                    "url": "/reports/reporting-date",
                    "data": function (d) {
                        if ($('#sell_list_filter_date_range').val()) {
                            var start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                            var end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                            d.start_date = start;
                            d.end_date = end;
                        }
                        d.is_direct_sale = 1;

                        d.location_id = $('#sell_list_filter_location_id').val();
                        d.customer_id = $('#sell_list_filter_customer_id').val();
                        d.payment_status = $('#sell_list_filter_payment_status').val();
                        d.created_by = $('#created_by').val();
                        d.sales_cmsn_agnt = $('#sales_cmsn_agnt').val();
                        d.service_staffs = $('#service_staffs').val();

                        @if($is_woocommerce)
                        if ($('#synced_from_woocommerce').is(':checked')) {
                            d.only_woocommerce_sells = 1;
                        }
                        @endif

                        if ($('#only_subscriptions').is(':checked')) {
                            d.only_subscriptions = 1;
                        }

                        d = __datatable_ajax_callback(d);
                    }
                },
                columns: [
                    // { data: 'transaction_date', name: 'transaction_date'  },
                    {data: 'shipper', name: 'shipper'},
                    {data: 'invoice_no', name: 'invoice_no'},
                    {data: 'name', name: 'contacts.name'},
                    {data: 'shipping_address', name: 'shipping_address'},
                    {data: 'final_total', name: 'final_total'},
                    // { data: 'total_paid', name: 'total_paid', "searchable": false},
                    {data: 'deposit', name: 'deposit', "searchable": false},
                    {data: 'cod', name: 'cod', "searchable": false},
                    {data: 'total_remaining', name: 'total_remaining'},
                    {data: 'payment_status', name: 'payment_status'},
                ],
                "fnDrawCallback": function (oSettings) {

                    $('#footer_sale_total').text(sum_table_col($('#reporting_date_table'), 'final-total'));

                    $('#footer_total_paid').text(sum_table_col($('#reporting_date_table'), 'total-paid'));

                    $('#footer_cod').text(sum_table_col($('#reporting_date_table'), 'total_cod'));

                    $('#footer_deposit').text(sum_table_col($('#reporting_date_table'), 'total_deposit'));

                    $('#footer_total_remaining').text(sum_table_col($('#reporting_date_table'), 'total_remaining'));

                    // $('#footer_total_sell_return_due').text(sum_table_col($('#reporting_date_table'), 'sell_return_due'));

                    $('#footer_payment_status_count').html(__sum_status_html($('#reporting_date_table'), 'payment-status-label'));

                    $('#service_type_count').html(__sum_status_html($('#reporting_date_table'), 'service-type-label'));
                    $('#payment_method_count').html(__sum_status_html($('#reporting_date_table'), 'payment-method'));

                    __currency_convert_recursively($('#reporting_date_table'));
                },
                createdRow: function (row, data, dataIndex) {
                    $(row).find('td:eq(6)').attr('class', 'clickable_td');
                }
            });

            $(document).on('change', '#sell_list_filter_location_id, #sell_list_filter_customer_id, #sell_list_filter_payment_status, #created_by, #sales_cmsn_agnt, #service_staffs', function () {
                reporting_date_table.ajax.reload();
            });
            @if($is_woocommerce)
            $('#synced_from_woocommerce').on('ifChanged', function (event) {
                reporting_date_table.ajax.reload();
            });
            @endif

            $('#only_subscriptions').on('ifChanged', function (event) {
                reporting_date_table.ajax.reload();
            });
        });
    </script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
@endsection
