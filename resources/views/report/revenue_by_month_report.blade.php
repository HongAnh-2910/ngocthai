@extends('layouts.app')
@section('title', __( 'report.revenue_by_month_report'))

@section('content')
    <section class="content-header no-print">
        <h1>@lang( 'report.revenue_by_month_report')
        </h1>
    </section>

    <!-- Main content -->
    <section class="content no-print">
        @component('components.filters', ['class' => 'box-primary', 'title' => __( 'report.revenue_by_month_report')])
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('customer_id',  __('contact.customer') . ':') !!}
                    {!! Form::select('customer_id', $customers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('customer_group_id',  __('lang_v1.customer_groups') . ':') !!}
                    {!! Form::select('customer_group_id', $customer_groups, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('revenue_filter_date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('revenue_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
                </div>
            </div>
        @endcomponent

        @component('components.widget', ['class' => 'box-primary'])
            <div class="table-responsive">
                <table class="table table-bordered table-striped ajax_view" id="revenue_by_month_table">
                    <thead>
                    <tr>
                        <th>@lang('lang_v1.contact_id')</th>
                        <th>@lang('report.contact')</th>
                        <th>@lang('lang_v1.customer_groups')</th>
                        <th>@lang('home.total_sell')</th>
                    </tr>
                    </thead>
                    <tfoot>
                    <tr class="bg-gray font-17 footer-total text-center">
                        <td colspan="3"><strong>@lang('sale.total'):</strong></td>
                        <td><span class="display_currency" id="total_money_on_bill" data-currency_symbol ="true"></span></td>
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
            revenue_by_month_table = $('#revenue_by_month_table').DataTable({
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
                            $('row c[r^="D"]', sheet).attr( 's', 63);
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
                    "url": "/reports/revenue-by-month-report",
                    "data": function (d) {
                        if ($('#revenue_filter_date_range').val()) {
                            var start = $('#revenue_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                            var end = $('#revenue_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                            d.start_date = start;
                            d.end_date = end;
                        }

                        d.customer_id = $('#customer_id').val();
                        d.customer_group_id = $('#customer_group_id').val();

                        d = __datatable_ajax_callback(d);
                    }
                },
                columns: [
                    {data: 'contact_id', name: 'contact_id'},
                    {data: 'name', name: 'name'},
                    {data: 'customer_group_name', name: 'customer_group_name'},
                    {data: 'total_sell', name: 'total_sell', searchable: false},
                ],
                "fnDrawCallback": function (oSettings) {
                    $('#total_money_on_bill').text(sum_table_col($('#revenue_by_month_table'), 'total_sell'));

                    __currency_convert_recursively($('#revenue_by_month_table'));
                }
            });

            $('#revenue_filter_date_range').daterangepicker(
                monthRangeRevenueSettings,
                function (start, end) {
                    $('#revenue_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                    revenue_by_month_table.ajax.reload();
                }
            );
            $('#revenue_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#revenue_filter_date_range').val('');
                revenue_by_month_table.ajax.reload();
            });

            $(document).on('change', '#revenue_filter_date_range, #customer_id, #customer_group_id', function () {
                revenue_by_month_table.ajax.reload();
            });
        });
    </script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
@endsection
