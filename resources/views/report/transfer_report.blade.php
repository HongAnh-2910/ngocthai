@extends('layouts.app')
@section('title', __( 'report.transfer_report'))

@section('content')
    <section class="content-header no-print">
        <h1>@lang( 'report.transfer_report')
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
            @php
                $custom_labels = json_decode(session('business.custom_labels'), true);
            @endphp
            <div class="table-responsive">
                <table class="table table-bordered table-striped ajax_view" id="transfer_table">
                    <thead>
                    <tr>
                        <th>@lang('report.staff_shipping')</th>
                        <th>@lang('sale.customer_id')</th>
                        <th>@lang('sale.customer_name')</th>
                        <th>@lang('report.deliver_to')</th>
                        <th>@lang('report.total_shipping_charge')</th>
                        <th>@lang('report.total_money_on_bill')</th>
                        <th>@lang('lang_v1.shipping_status')</th>
                    </tr>
                    </thead>
                    <tfoot>
                    <tr class="bg-gray font-17 footer-total text-center">
                        <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                        <td><span class="display_currency" id="total_shipping_charge" data-currency_symbol ="true"></span></td>
                        <td><span class="display_currency" id="total_money_on_bill" data-currency_symbol ="true"></span></td>
                        <td></td>
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
                    transfer_table.ajax.reload();
                }
            );
            $('#sell_list_filter_date_range').on('cancel.daterangepicker', function (ev, picker) {
                $('#sell_list_filter_date_range').val('');
                transfer_table.ajax.reload();
            });

            transfer_table = $('#transfer_table').DataTable({
                processing: true,
                serverSide: true,
                buttons: [
                    {
                        extend: 'excel',
                        text: '<i class="fa fa-file-excel" aria-hidden="true"></i> ' + LANG.export_to_excel,
                        className: 'btn-sm',
                        footer: true,
                        customize: function( xlsx, row ) {
                            var sheet = xlsx.xl.worksheets['sheet1.xml'];
                            $('row c[r^="E"], row c[r^="F"]', sheet).attr( 's', 63);
                        },
                    },
                    {
                        extend: 'print',
                        text: '<i class="fa fa-print" aria-hidden="true"></i> ' + LANG.print,
                        className: 'btn-sm',
                        exportOptions: {
                            stripHtml: true
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
                    }
                ],
                aaSorting: [],
                "ajax": {
                    "url": "/reports/transfer-report",
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
                        d.shipping_status = $('#shipping_status').val();

                        if ($('#only_subscriptions').is(':checked')) {
                            d.only_subscriptions = 1;
                        }

                        d = __datatable_ajax_callback(d);
                    }
                },
                columns: [
                    {data: 'shipper', name: 'shipper', 'searchable': false},
                    {data: 'contact_id', name: 'contacts.contact_id'},
                    {data: 'name', name: 'contacts.name'},
                    {data: 'shipping_address', name: 'shipping_address'},
                    {data: 'shipping_charges', name: 'shipping_charges'},
                    {data: 'final_total', name: 'final_total'},
                    {data: 'shipping_status', name: 'shipping_status'},
                ],
                "fnDrawCallback": function (oSettings) {

                    $('#total_shipping_charge').text(sum_table_col($('#transfer_table'), 'total-shipping-charge'));

                    $('#total_money_on_bill').text(sum_table_col($('#transfer_table'), 'final-total'));

                    __currency_convert_recursively($('#transfer_table'));
                },
                createdRow: function (row, data, dataIndex) {
                    $(row).find('td:eq(6)').attr('class', 'clickable_td');
                }
            });

            $(document).on('change', '#sell_list_filter_location_id, #sell_list_filter_customer_id, #sell_list_filter_payment_status, #created_by, #service_staffs, #shipping_status', function () {
                transfer_table.ajax.reload();
            });
        });
    </script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
@endsection
