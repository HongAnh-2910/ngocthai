<script type="text/javascript">
    $(document).ready( function(){
        //Date range as a button
        $('#transaction_payments_filter_date_range').daterangepicker(
            dateRangeSettings,
            function (start, end) {
                $('#ledger_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                transaction_payments_table.ajax.reload();
            }
        );

        transaction_payments_table = $('#transaction_payments_table').DataTable({
            processing: true,
            serverSide: true,
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="fa fa-file-excel" aria-hidden="true"></i> ' + LANG.export_to_excel,
                    className: 'btn-sm',
                    exportOptions: {
                        columns: ':lt(8)',
                    },
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
            ],
            aaSorting: [[0, 'desc']],
            "ajax": {
                "url": "/sells-of-cashier/transaction-payments",
                "data": function ( d ) {
                    var start = $('#transaction_payments_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    var end = $('#transaction_payments_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    d.start_date = start;
                    d.end_date = end;

                    d.payment_method = $('#transaction_payments_filter_payment_method').val();
                    d.approval_status = $('#transaction_payments_filter_approval_status').val();
                }
            },
            columnDefs: [ {
                "targets": 8,
                "orderable": false,
                "searchable": false
            } ],
            columns: [
                { data: 'paid_on', name: 'paid_on'},
                { data: 'invoice_no', name: 'transactions.invoice_no'},
                { data: 'name', name: 'contacts.name'},
                { data: 'amount', name: 'amount'},
                { data: 'method', name: 'method'},
                { data: 'type', name: 'type'},
                { data: 'approval_status', name: 'approval_status'},
                { data: 'user_confirm', name: 'user_confirm'},
                { data: 'action', name: 'action'}
            ],
            "fnDrawCallback": function (oSettings) {
                __currency_convert_recursively($('#transaction_payments_table'));
            }
        });

        $(document).on('change', '#transaction_payments_filter_payment_method, #transaction_payments_filter_approval_status',  function() {
            transaction_payments_table.ajax.reload();
        });
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
</script>
