@extends('layouts.app')
@section('title', __( 'sale.drafts'))
@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>@lang('sale.drafts')
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">
    @component('components.filters', ['title' => __('report.filters')])
        {{--<div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sell_list_filter_location_id',  __('purchase.business_location') . ':') !!}

                {!! Form::select('sell_list_filter_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
            </div>
        </div>--}}
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sell_list_filter_customer_id',  __('contact.customer') . ':') !!}
                {!! Form::select('sell_list_filter_customer_id', $customers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sell_list_filter_date_range', __('report.date_range') . ':') !!}
                {!! Form::text('sell_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
            </div>
        </div>
        {{--<div class="col-md-3">
            <div class="form-group">
                {!! Form::label('created_by',  __('report.user') . ':') !!}
                {!! Form::select('created_by', $sales_representative, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
            </div>
        </div>--}}
    @endcomponent
    @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
            <div class="box-tools">
                <a class="btn btn-block btn-primary" href="{{action('SellController@create')}}">
                <i class="fa fa-plus"></i> @lang('messages.add')</a>
            </div>
        @endslot
{{--        <div class="form-group">--}}
{{--            <div class="input-group">--}}
{{--              <button type="button" class="btn btn-primary" id="daterange-btn">--}}
{{--                <span>--}}
{{--                  <i class="fa fa-calendar"></i> @lang('messages.filter_by_date')--}}
{{--                </span>--}}
{{--                <i class="fa fa-caret-down"></i>--}}
{{--              </button>--}}
{{--            </div>--}}
{{--        </div>--}}
        <div class="table-responsive">
            <table class="table table-bordered table-striped ajax_view" id="sell_table">
                <thead>
                    <tr>
                        <th>@lang('messages.date')</th>
                        <th>@lang('purchase.ref_no')</th>
                        <th>@lang('sale.customer_id')</th>
                        <th>@lang('sale.customer_name')</th>
                        <th>@lang('sale.location')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>
<!-- /.content -->
@stop
@section('javascript')
<script type="text/javascript">
$(document).ready( function(){
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
            extend: 'excel',
            text: '<i class="fa fa-file-excel" aria-hidden="true"></i> ' + LANG.export_to_excel,
            className: 'btn-sm',
            exportOptions: {
                columns: ':lt(5)',
            },
            footer: true
        },
        {
            extend: 'print',
            text: '<i class="fa fa-print" aria-hidden="true"></i> ' + LANG.print,
            className: 'btn-sm',
            exportOptions: {
                columns: ':lt(4)',
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
        aaSorting: [[0, 'desc']],
        "ajax": {
            "url": '/sells/draft-dt?is_quotation=0',
            "data": function ( d ) {
                if($('#sell_list_filter_date_range').val()) {
                    var start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    var end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    d.start_date = start;
                    d.end_date = end;
                }

                if($('#sell_list_filter_location_id').length) {
                    d.location_id = $('#sell_list_filter_location_id').val();
                }
                d.customer_id = $('#sell_list_filter_customer_id').val();

                if($('#created_by').length) {
                    d.created_by = $('#created_by').val();
                }
            }
        },
        columnDefs: [ {
            "targets": 5,
            "orderable": false,
            "searchable": false
        } ],
        columns: [
            { data: 'transaction_date', name: 'transaction_date'  },
            { data: 'invoice_no', name: 'invoice_no'},
            { data: 'contact_id', name: 'contacts.contact_id'},
            { data: 'name', name: 'contacts.name'},
            { data: 'business_location', name: 'bl.name'},
            { data: 'action', name: 'action'}
        ],
        "fnDrawCallback": function (oSettings) {
            __currency_convert_recursively($('#purchase_table'));
        }
    });
    $(document).on('change', '#sell_list_filter_location_id, #sell_list_filter_customer_id, #created_by',  function() {
        sell_table.ajax.reload();
    });
});
</script>

@endsection