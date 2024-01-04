@extends('layouts.app')
@section('title', __( 'sale.stock_to_deliver'))

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header no-print">
        <h1>@lang( 'sale.stock_to_deliver')
        </h1>
    </section>

    <!-- Main content -->
    {!! Form::open(['action' => 'SellPosController@exportExcelDeliver', 'method' => 'post']) !!}
    <section class="content no-print">
        @component('components.filters', ['title' => __('report.filters')])
            @include('stock_deliver.partials.sell_list_filters')
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
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-file-excel"></i> @lang('messages.export_to_excel')</button>
                </div>
            @endslot

            @if(auth()->user()->can('direct_sell.access') ||  auth()->user()->can('view_own_sell_only') || auth()->user()->can('stock.view_deliver_orders'))
                @php
                    $custom_labels = json_decode(session('business.custom_labels'), true);
                @endphp
                <div class="table-responsive">
                    <table class="table table-bordered table-striped ajax_view" id="sell_table">
                        <thead>
                        <tr>
                            <th>@lang('messages.action')</th>
                            <th>@lang('messages.date')</th>
                            <th>@lang('sale.invoice_no')</th>
                            <th>@lang('sale.products')</th>
                            <th>@lang('sale.customer_name')</th>
                            <th>@lang('sale.deliver_address')</th>
                            <th>@lang('sale.deliver_status')</th>
                            <th>@lang('lang_v1.shipping_status')</th>
                            <th>@lang('sale.shipper')</th>
                        </tr>
                        </thead>
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
                aaSorting: [[1, 'desc']],
                "ajax": {
                    "url": "/stock-to-deliver",
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
                        d.category_id = $('#sell_list_filter_category_id').val();
                        d.variation_id = $('#sell_list_filter_variation_id').val();
                        d.shipping_status = $('#shipping_status').val();
                        d.export_status = $('#export_status').val();

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
                    { data: 'invoice_no', name: 'invoice_no'},
                    { data: 'products', "searchable": false},
                    { data: 'name', name: 'contacts.name'},
                    { data: 'shipping_address'},
                    { data: 'export_status'},
                    { data: 'shipping_status', name: 'shipping_status'},
                    { data: 'shipper', name: 'shipper'},
                ],
                createdRow: function( row, data, dataIndex ) {
                    // $( row ).find('td:eq(6)').attr('class', 'clickable_td');
                }
            });

            $(document).on('change', '#sell_list_filter_location_id, #sell_list_filter_customer_id, #sell_list_filter_payment_status, #created_by, #sales_cmsn_agnt, #service_staffs, #sell_list_filter_variation_id, #shipping_status, #export_status',  function() {
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

            function getProductsByCategory(category_id, variation_id = '') {
                $.ajax({
                    method: 'get',
                    url: '/products/get-product-by-cate',
                    dataType: 'json',
                    data: {
                        category_id: category_id
                    },
                    success: function (result) {
                        let key = Object.keys(result);

                        $('#sell_list_filter_variation_id').empty();
                        $('#sell_list_filter_variation_id').append('<option value="">' + LANG.all + '</option>');

                        for (let i = 0; i < key.length; i++) {
                            let selected = '';
                            if(result[i].id == variation_id){
                                selected = 'selected';
                            }
                            $('#sell_list_filter_variation_id').append('<option value="' + result[i].id + '" '+ selected +'>' + result[i].product_name + '</option>');
                        }
                    }
                });

                if(variation_id != ''){
                    $('#sell_list_filter_variation_id').val(variation_id);
                }
            }

            $('#sell_list_filter_category_id').change(function() {
                let category_id = $(this).val();
                $('#sell_list_filter_variation_id').val('');
                getProductsByCategory(category_id);
                sell_table.ajax.reload();
            });
        });
    </script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
@endsection
