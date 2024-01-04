@extends('layouts.app')
@section('title', __( 'contact.confirm_reduce_debt'))

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header no-print">
        <h1>@lang('contact.confirm_reduce_debt')
        </h1>
    </section>

    <!-- Main content -->
    <section class="content no-print">
        @component('components.filters', ['title' => __('report.filters')])
            @include('reduce_debt.partials.sell_list_filters')
        @endcomponent
        @component('components.widget', ['class' => 'box-primary', 'title' => __( 'contact.list_reduce_debt')])
            @slot('tool')
                <div class="box-tools">
                    @can('sell.confirm_reduce_debt')
                        <button type="button" class="btn btn-primary confirm_reduce_debt"><i class="fas fa-check"></i> @lang('contact.confirm_reduce_debt')</button>
                    @endcan

                    {{--<button type="button" class="btn btn-success confirm_remaining_cash"><i class="fas fa-money-bill-alt"></i>&nbsp;&nbsp;@lang('lang_v1.confirm_remaining_by_cash')</button>
                    <button type="button" class="btn btn-warning confirm_remaining_bank" href="btn-bank-account"><i class="fas fa-money-check-alt"></i>&nbsp;&nbsp;@lang('lang_v1.confirm_remaining_by_bank')</button>
                    <button type="button" class="btn btn-info confirm_debit_paper"><i class="fas fa-clipboard-check"></i>&nbsp;&nbsp;@lang('lang_v1.confirm_paper_debit')</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-file-excel"></i>&nbsp;&nbsp;@lang('lang_v1.hand_over_end_day')</button>--}}
                </div>
            @endslot

            <div class="table-responsive">
                <table class="table table-bordered table-striped ajax_view" id="sell_table">
                    <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all-row"></th>
                        <th>@lang('messages.date')</th>
                        <th>@lang('sale.customer_name')</th>
                        <th>@lang('sale.total_amount')</th>
                        <th>@lang('contact.reduce_debt_note')</th>
                        <th>@lang('contact.created_by')</th>
                        <th>@lang('sale.status')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                    </thead>
                    <tfoot>
                    <tr class="bg-gray font-17 footer-total text-center">
                        <td colspan="3"><strong>@lang('sale.total'):</strong></td>
                        <td><span class="display_currency" id="footer_sale_total" data-currency_symbol ="true"></span></td>
                        <td colspan="4"></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        @endcomponent
    </section>

    <div class="modal fade reduce_debt_modal" tabindex="-1" role="dialog"
         aria-labelledby="gridSystemModalLabel">
    </div>
    <!-- /.content -->
@stop

@section('javascript')
    <script type="text/javascript">
        $(document).ready( function(){
            //Date range filter
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

            sell_table = $('#sell_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [[1, 'desc']],
                "ajax": {
                    "url": "/contacts/reduce-debts",
                    "data": function ( d ) {
                        if($('#sell_list_filter_date_range').val()) {
                            var start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                            var end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                            d.start_date = start;
                            d.end_date = end;
                            $('#sell_list_filter_start_date').val(start);
                            $('#sell_list_filter_end_date').val(end);
                        }

                        d.customer_id = $('#sell_list_filter_customer_id').val();
                        d.created_by = $('#created_by').val();
                        d.status = $('#status').val();

                        d = __datatable_ajax_callback(d);
                    }
                },
                columns: [
                    { data: 'mass_action', orderable: false, searchable: false },
                    { data: 'transaction_date', name: 'transaction_date'  },
                    { data: 'name', name: 'contacts.name'},
                    { data: 'final_total', name: 'final_total'},
                    { data: 'additional_notes', name: 'additional_notes'},
                    { data: 'added_by', name: 'added_by'},
                    { data: 'status', name: 'status'},
                    { data: 'action', name: 'action', orderable: false, "searchable": false},
                ],
                "fnDrawCallback": function (oSettings) {
                    $('#footer_sale_total').text(sum_table_col($('#sell_table'), 'final-total'));

                    __currency_convert_recursively($('#sell_table'));
                },
                createdRow: function( row, data, dataIndex ) {
                    $( row ).find('td:eq(6)').attr('class', 'selectable_td');
                    // $( row ).find('td:eq(2)').attr('class', 'clickable_td');
                }
            });

            $(document).on('change', '#sell_list_filter_customer_id, #created_by, #status',  function() {
                sell_table.ajax.reload();
            });

            $('#only_subscriptions').on('ifChanged', function(event){
                sell_table.ajax.reload();
            });

            $(document).on('click', '.confirm_reduce_debt', function(e){
                var selected_rows = getSelectedRows();
                swal({
                    title: LANG.sure,
                    text: LANG.confirm_reduce_debt,
                    icon: 'warning',
                    buttons: [LANG.cancel, LANG.confirm]
                }).then((result) => {
                    if (result) {
                        $.ajax({
                            url: '/contacts/reduce-debts/confirm-bulk',
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
                                    toastr.success(LANG.confirm_reduce_debt_success);
                                    sell_table.ajax.reload();
                                } else {
                                    toastr.error(LANG.something_went_wrong);
                                }
                            },
                            complete: function(data) {
                                $('body').find('.loading_wrap').hide();
                            },
                        });
                    }
                })
            });

            function getSelectedRows() {
                var selected_rows = [];
                var i = 0;
                $('.row-select:checked').each(function () {
                    selected_rows[i++] = $(this).val();
                });

                return selected_rows;
            }


            $(document).on('click', 'button.edit_reduce_debt_button', function() {
                $('div.reduce_debt_modal').load($(this).data('href'), function() {
                    __currency_convert_recursively($(this));
                    // container.find('form#reduce_debt_edit_form').validate();
                    getFormatNumber();
                    __select2($('.select2'));
                    $(this).modal('show');

                    $('form#reduce_debt_edit_form').submit(function(e) {
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
                                    $('div.reduce_debt_modal').modal('hide');
                                    toastr.success(result.msg);
                                    sell_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            },
                        });
                    });
                });
            });

            $(document).on('click', 'button.delete_reduce_debt_button', function() {
                swal({
                    title: LANG.sure,
                    text: LANG.delete_reduce_debt,
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(willDelete => {
                    if (willDelete) {
                        var href = $(this).data('href');
                        var data = $(this).serialize();

                        $.ajax({
                            method: 'DELETE',
                            url: href,
                            dataType: 'json',
                            data: data,
                            success: function(result) {
                                if (result.success == true) {
                                    toastr.success(result.msg);
                                    sell_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            },
                        });
                    }
                });
            });
        });
    </script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
@endsection
