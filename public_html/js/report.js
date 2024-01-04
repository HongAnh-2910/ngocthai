$(document).ready(function() {
    //TODO: Stock report
    $(document).on('click', '.reverse_size_button', function(e) {
        e.preventDefault();
        var container = $('.reverse_size_modal');

        $.ajax({
            url: $(this).attr('href'),
            dataType: 'json',
            success: function(result) {
                $('#plate_stock_deliver_modal').modal('hide');
                container.html(result.view).modal('show');
                container.find('form#reverse_size_form').validate();
                getFormatNumber();
            },
        });
    });

    $(document).on('submit', 'form#reverse_size_form', function(e){
        e.preventDefault();
        var reverse_plate_stock_id = $('#reverse_plate_stock_id').val();
        var reverse_quantity = $('#reverse_quantity').val();

        $.ajax({
            method: 'POST',
            url: '/sells/get-reverse-quantity',
            dataType: 'json',
            data: {
                reverse_plate_stock_id: reverse_plate_stock_id,
            },
            success: function(result) {
                if(result.success){
                    if(reverse_quantity > 0 && reverse_quantity <= result.data){
                        $('form#reverse_size_form')
                            .find('button[type="submit"]')
                            .attr('disabled', true);
                        var data = $('form#reverse_size_form').serialize();

                        $.ajax({
                            method: 'POST',
                            url: $('form#reverse_size_form').attr('action'),
                            dataType: 'json',
                            data: data,
                            success: function(result) {
                                $('.reverse_size_modal').modal('hide');
                                if (result.success == true) {
                                    toastr.success(LANG.reverse_quantity_success);
                                    plate_stock_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            },
                        });
                    }else{
                        $('#reverse_quantity').css('border-color', 'red');
                        toastr.error(__translate('reverse_quantity_not_valid', {quantity: result.data}));
                    }
                }
            },
        });
    });

    $('#plate_stock_table').on('click', '.view_history', function(){
        let plate_stock_id = $(this).data('plate_stock_id');

        $.ajax({
            method: 'GET',
            url: '/reports/stock-report/history/'+ plate_stock_id,
            success: function(result) {
                if(result.success){
                    var container = $('.stock_history_modal');
                    container.html(result.data).modal('show');
                }else{
                    swal({
                        title: result.message,
                        icon: 'warning',
                    });
                }
            },
        });
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

                $('#variation_id').empty();
                $('#variation_id').append('<option value="">' + LANG.all + '</option>');

                for (let i = 0; i < key.length; i++) {
                    let selected = '';
                    if(result[i].id == variation_id){
                        selected = 'selected';
                    }
                    $('#variation_id').append('<option value="' + result[i].id + '" '+ selected +'>' + result[i].product_name + '</option>');
                }
            }
        });

        if(variation_id != ''){
            $('#plate_stock_deliver_filter_form #variation_id').val(variation_id);
        }
    }

    $('#plate_stock_filter_form #category_id, #plate_stock_deliver_filter_form #category_id').change(function() {
        let category_id = $(this).val();
        getProductsByCategory(category_id);
    });

    //Get product by filter
    $('#plate_stock_filter_form').on('change', '#variation_id, #plate_width, #plate_height', function(){
        let product_id = $('#plate_stock_filter_form #variation_id').val();
        let width = $('#plate_stock_filter_form #plate_width').val();
        let height = $('#plate_stock_filter_form #plate_height').val();

        if(product_id && width && height){
            $('#select_product_by_filter').removeAttr('disabled');
        }else{
            $('#select_product_by_filter').attr('disabled', true);
        }
    });

    //Plate Stock
    var buttons = [
        {
            extend: 'excel',
            text: '<i class="fa fa-file-excel" aria-hidden="true"></i> ' + LANG.export_to_excel,
            className: 'btn-sm',
            exportOptions: {
                columns: ':lt(7)',
            },
            footer: true
        },
        {
            extend: 'print',
            text: '<i class="fa fa-print" aria-hidden="true"></i> ' + LANG.print,
            className: 'btn-sm',
            exportOptions: {
                columns: ':lt(7)',
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

    var plate_stock_cols = [
        { data: 'sku', name: 'p.sku' },
        { data: 'product', name: 'p.name' },
        { data: 'height', name: 'height' },
        { data: 'width', name: 'width' },
        { data: 'stock', name: 'stock', searchable: false },
        { data: 'expect_stock', name: 'expect_stock', searchable: false},
        { data: 'warehouses', name: 'warehouses', orderable: false,  searchable: false },
        { data: 'is_origin', name: 'is_origin' },
        { data: 'action', name: 'action', searchable: false, orderable: false },
    ];

    plate_stock_table = $('#plate_stock_table').DataTable({
        processing: true,
        serverSide: true,
        buttons: buttons,
        ajax: {
            url: '/sells/plate-stock',
            data: function(d) {
                d.location_id = $('#location_id').val();
                d.warehouse_id = $('#warehouse_id').val();
                d.category_id = $('#category_id').val();
                d.variation_id = $('#variation_id').val();
                d.width = $('#plate_width').val();
                d.height = $('#plate_height').val();
                d.quantity = $('#plate_quantity').val();
                d.layout = 'stock_report';
            },
        },
        order: [[1, 'asc']],
        columns: plate_stock_cols,
    });

    $('#location_id, #plate_stock_filter_form #category_id, #plate_stock_filter_form #view_stock_filter, #plate_stock_filter_form #plate_width, #plate_stock_filter_form #plate_height, #plate_stock_filter_form #variation_id, #plate_stock_filter_form #plate_quantity, #plate_stock_filter_form #warehouse_id'
    ).change(function() {
        plate_stock_table.ajax.reload();
    });

    function get_stock_report_details(rowData, location_id, warehouse_id, category_id, width, height) {
        var div = $('<div/>')
            .addClass('loading')
            .text('Loading...');
        $.ajax({
            url: '/sells/plate-stock-detail/' + rowData.variation_id,
            dataType: 'html',
            data: {
                'location_id': location_id,
                'warehouse_id': warehouse_id,
                'category_id': category_id,
                'width': rowData.width,
                'height': rowData.height,
                'is_origin': rowData.is_origin,
                'layout': 'stock_report',
            },
            success: function(data) {
                div.html(data).removeClass('loading');
            },
        });

        return div;
    }

    let detailRows = [];

    $('#plate_stock_table tbody').on('click', '.view_detail', function() {
        var tr = $(this).closest('tr');
        var row = plate_stock_table.row(tr);
        var idx = $.inArray(tr.attr('id'), detailRows);
        let location_id = $('#location_id').val();
        let warehouse_id = $('#warehouse_id').val();
        let category_id = $('#category_id').val();
        let width = $('#plate_width').val();
        let height = $('#plate_height').val();

        if (row.child.isShown()) {
            $(this)
                .find('i')
                .removeClass('fa-eye-slash')
                .addClass('fa-eye');
            row.child.hide();

            // Remove from the 'open' array
            detailRows.splice(idx, 1);
        } else {
            $(this)
                .find('i')
                .removeClass('fa-eye')
                .addClass('fa-eye-slash');

            row.child(get_stock_report_details(row.data(), location_id, warehouse_id, category_id, width, height)).show();

            // Add to the 'open' array
            if (idx === -1) {
                detailRows.push(tr.attr('id'));
            }
        }
    });

    // On each draw, loop over the `detailRows` array and show any child rows
    plate_stock_table.on('draw', function() {
        $.each(detailRows, function(i, id) {
            $('#' + id + ' .view_detail').trigger('click');
        });
    });

    //TODO: Target report
    owner_target_report = $('#report_owner_target_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/reports/report-owner-target',
            data: function(d) {
                var start = '';
                var end = '';
                if ($('#owner_target_filter_date_range').val()) {
                    start = $('input#owner_target_filter_date_range')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    end = $('input#owner_target_filter_date_range')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                }

                if ($('#owner_target_type').val()) {
                    d.type = $('#owner_target_type').val()
                }

                var today = new Date();
                var dd = String(today.getDate()).padStart(2, '0');
                var mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
                var yyyy = today.getFullYear();
                today = yyyy + '-' + mm + '-' + dd;

                if(start == ''){
                    start = today;
                }
                if(end == ''){
                    end = today;
                }

                d.start_date = start;
                d.end_date = end;
            }
        },
        order: [[1, 'asc']],
        columns: [
            { data: 'target_total', name: 'target_total', orderable: false, searchable: false },
            { data: 'start_date', name: 'start_date' },
            { data: 'end_date', name: 'end_date' },
            { data: 'percent_complete', name: 'percent_complete', orderable: false, searchable: false },
        ]
    });

    $('#owner_target_type').change(function() {
        owner_target_report.ajax.reload();
    });

    $('#owner_target_filter_date_range').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#owner_target_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            owner_target_report.ajax.reload();
        }
    );

    $('#owner_target_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
        $('#owner_target_filter_date_range').val('');
        owner_target_report.ajax.reload();
    });

    //Purchase & Sell report
    //Date range as a button
    if ($('#purchase_sell_date_filter').length == 1) {
        $('#purchase_sell_date_filter').daterangepicker(dateRangeSettings, function(start, end) {
            $('#purchase_sell_date_filter span').html(
                start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
            );
            updatePurchaseSell();
        });
        $('#purchase_sell_date_filter').on('cancel.daterangepicker', function(ev, picker) {
            $('#purchase_sell_date_filter').html(
                '<i class="fa fa-calendar"></i> ' + LANG.filter_by_date
            );
        });
        updatePurchaseSell();
    }

    function  getTotalDue () {
        $.ajax({
            method: "GET",
            dataType: "json",
            url: '/reports/calculate-total-due',
            async: false,
            data: {
                customer_group_id: $('#cnt_customer_group_id').val(),
                contact_type: $('#contact_type').val()
            },
            success: function(result){
                $('.total_payment').text(result.credit);
                $('.total_due_splice').text(result.debit);
            }
        })
    }

    getTotalDue()
    //contact report
    supplier_report_tbl = $('#supplier_report_tbl').DataTable({
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
                    $('row c[r^="C"], row c[r^="D"], row c[r^="E"], row c[r^="F"], row c[r^="G"]', sheet).attr( 's', 63);
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
        ajax: {
            url: '/reports/customer-supplier',
            data: function(d) {
                d.customer_group_id = $('#cnt_customer_group_id').val();
                d.contact_type = $('#contact_type').val();
            }
        },
        columns: [
            { data: 'contact_id', name: 'contact_id' },
            { data: 'name', name: 'name'},
            // { data: 'total_purchase', name: 'total_purchase' },
            // { data: 'total_purchase_return', name: 'total_purchase_return' },
            { data: 'total_invoice', name: 'total_invoice', searchable: false },
            { data: 'total_sell_return', name: 'total_sell_return', searchable: false },
            { data: 'total_revenue', name: 'total_revenue', searchable: false },
            { data: 'opening_balance_due', name: 'opening_balance_due', searchable: false },
            { data: 'due', name: 'due' }
        ],
        fnDrawCallback: function(oSettings) {
            // var total_purchase = sum_table_col($('#supplier_report_tbl'), 'total_purchase');
            // $('#footer_total_purchase').text(total_purchase);

            // var total_purchase_return = sum_table_col(
            //     $('#supplier_report_tbl'),
            //     'total_purchase_return'
            // );
            // $('#footer_total_purchase_return').text(total_purchase_return);

            var total_sell = sum_table_col($('#supplier_report_tbl'), 'total_invoice');
            $('#footer_total_sell').text(total_sell);

            var total_sell_return = sum_table_col($('#supplier_report_tbl'), 'total_sell_return');
            $('#footer_total_sell_return').text(total_sell_return);

            var total_revenue = sum_table_col($('#supplier_report_tbl'), 'total_revenue');
            $('#footer_total_revenue').text(total_revenue);

            var total_opening_bal_due = sum_table_col(
                $('#supplier_report_tbl'),
                'opening_balance_due'
            );
            $('#footer_total_opening_bal_due').text(total_opening_bal_due);

            var total_due = sum_table_col($('#supplier_report_tbl'), 'total_due');
            $('#footer_total_due').text(total_due);

            __currency_convert_recursively($('#supplier_report_tbl'));
        },
    });
    if($('#supplier_report_tbl').length != 0){
        $('#customer_group_id, #cnt_customer_group_id, #contact_type').change(function() {
            supplier_report_tbl.ajax.reload();
            getTotalDue()
        });
    }

    var stock_report_cols = [
            { data: 'sku', name: 'variations.sub_sku' },
            { data: 'product', name: 'p.name' },
            { data: 'unit_price', name: 'variations.sell_price_inc_tax' },
            { data: 'stock', name: 'stock', searchable: false },
            { data: 'stock_price', name: 'stock_price', searchable: false },
            { data: 'total_sold', name: 'total_sold', searchable: false },
            { data: 'total_transfered', name: 'total_transfered', searchable: false },
            { data: 'total_adjusted', name: 'total_adjusted', searchable: false }
        ];
        if ($('th.current_stock_mfg').length) {
            stock_report_cols.push({ data: 'total_mfg_stock', name: 'total_mfg_stock', searchable: false });
        }
    //Stock report table
    stock_report_table = $('#stock_report_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/reports/stock-report',
            data: function(d) {
                d.location_id = $('#location_id').val();
                d.category_id = $('#category_id').val();
                d.sub_category_id = $('#sub_category_id').val();
                d.brand_id = $('#brand').val();
                d.unit_id = $('#unit').val();

                d.only_mfg_products = $('#only_mfg_products').length && $('#only_mfg_products').is(':checked') ? 1 : 0;
            },
        },
        columns: stock_report_cols,
        fnDrawCallback: function(oSettings) {
            $('#footer_total_stock').html(__sum_stock($('#stock_report_table'), 'current_stock'));
            $('#footer_total_sold').html(__sum_stock($('#stock_report_table'), 'total_sold'));
            $('#footer_total_transfered').html(
                __sum_stock($('#stock_report_table'), 'total_transfered')
            );
            $('#footer_total_adjusted').html(
                __sum_stock($('#stock_report_table'), 'total_adjusted')
            );
            var total_stock_price = sum_table_col($('#stock_report_table'), 'total_stock_price');
            $('#footer_total_stock_price').text(total_stock_price);

            __currency_convert_recursively($('#stock_report_table'));
            if ($('th.current_stock_mfg').length) {
                $('#footer_total_mfg_stock').html(
                    __sum_stock($('#stock_report_table'), 'total_mfg_stock')
                );
            }
        },
    });

    //Import export report table
    import_export_stock_table = $('#import_export_stock_table').DataTable({
        processing: true,
        serverSide: true,
        // responsive: true,
        /*buttons: [
            {
                extend: 'csv',
                text: '<i class="fa fa-file-csv" aria-hidden="true"></i> ' + LANG.export_to_csv,
                className: 'btn-sm',
                footer: false,
                charset: 'utf-8',
                bom: true
            },
            {
                extend: 'excel',
                text: '<i class="fa fa-file-excel" aria-hidden="true"></i> ' + LANG.export_to_excel,
                className: 'btn-sm',
                footer: false
            },
            {
                extend: 'print',
                text: '<i class="fa fa-print" aria-hidden="true"></i> ' + LANG.print,
                className: 'btn-sm',
                exportOptions: {
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
            }
        ],*/
        ajax: {
            url: '/reports/report-import-export',
            data: function(d) {
                d.location_id = $('#invention_location_id').val();
                d.category_id = $('#invention_category_id').val();
                d.sub_category_id = $('#invention_sub_category_id').val();

                let start = $('input#report_by_stock_filter_range')
                    .data('daterangepicker')
                    .startDate.format('YYYY-MM-DD');
                let end = $('input#report_by_stock_filter_range')
                    .data('daterangepicker')
                    .endDate.format('YYYY-MM-DD');
                d.start_date = start
                d.end_date = end
            },
        },
        columns: [
            { data: 'sku', name: 'sku' },
            { data: 'product', name: 'product' },
            { data: 'height', name: 'height' },
            { data: 'width', name: 'width' },
            { data: 'begin_quantity', name: 'begin_quantity', searchable: false },
            { data: 'import_quantity', name: 'import_quantity', searchable: false },
            { data: 'export_quantity', name: 'export_quantity', searchable: false },
            { data: 'end_stock_quantity', name: 'end_stock_quantity', searchable: false },
        ],
        fnDrawCallback: function(oSettings) {
            $('#footer_total_stock').html(__sum_stock($('#import_export_stock_table'), 'current_stock'));
            $('#footer_total_sold').html(__sum_stock($('#import_export_stock_table'), 'total_sold'));
            $('#footer_total_transfered').html(
                __sum_stock($('#import_export_stock_table'), 'total_transfered')
            );
            $('#footer_total_adjusted').html(
                __sum_stock($('#import_export_stock_table'), 'total_adjusted')
            );
            var total_stock_price = sum_table_col($('#import_export_stock_table'), 'total_stock_price');
            $('#footer_total_stock_price').text(total_stock_price);

            __currency_convert_recursively($('#import_export_stock_table'));
        }
    });

    $('input#report_by_stock_filter_range').change(function () {
        import_export_stock_table.ajax.reload()
    })

    $('#invention_stock_report_filter_form #invention_location_id, #invention_stock_report_filter_form #invention_category_id, #invention_stock_report_filter_form #invention_sub_category_id, #invention_stock_report_filter_form #brand, #invention_stock_report_filter_form #unit,#invention_stock_report_filter_form #view_stock_filter'
    ).change(function() {
        import_export_stock_table.ajax.reload();
    });

    //Import export report table
    total_sale = $('#total_sale_table').DataTable({
        processing: true,
        serverSide: true,
        buttons: [
            {
                extend: 'csv',
                text: '<i class="fa fa-file-csv" aria-hidden="true"></i> ' + LANG.export_to_csv,
                className: 'btn-sm',
                footer: false,
                charset: 'utf-8',
                bom: true
            },
            {
                extend: 'excel',
                text: '<i class="fa fa-file-excel" aria-hidden="true"></i> ' + LANG.export_to_excel,
                className: 'btn-sm',
                footer: false
            },
            {
                extend: 'print',
                text: '<i class="fa fa-print" aria-hidden="true"></i> ' + LANG.print,
                className: 'btn-sm',
                exportOptions: {
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
            }
        ],
        ajax: {
            url: '/reports/total-sales',
            data: function(d) {
                d.user_id = $('#filter_user_id').val();

                let start = $('input#report_by_sale_filter_range')
                    .data('daterangepicker')
                    .startDate.format('YYYY-MM-DD');
                let end = $('input#report_by_sale_filter_range')
                    .data('daterangepicker')
                    .endDate.format('YYYY-MM-DD');
                d.start_date = start
                d.end_date = end
            },
        },
        columns: [
            { data: 'full_name', name: 'full_name' },
            { data: 'total_sell', name: 'total_sell', searchable: false },
        ],
        fnDrawCallback: function(oSettings) {
            __currency_convert_recursively($('#total_sale_table'));
        }
    });

    $('input#report_by_sale_filter_range').change(function () {
        total_sale.ajax.reload()
    })

    $('#sale_report_filter_form #filter_user_id').change(function() {
        total_sale.ajax.reload();
    });

    if ($('#tax_report_date_filter').length == 1) {
        $('#tax_report_date_filter').daterangepicker(dateRangeSettings, function(start, end) {
            $('#tax_report_date_filter span').html(
                start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
            );
            updateTaxReport();
        });
        $('#tax_report_date_filter').on('cancel.daterangepicker', function(ev, picker) {
            $('#tax_report_date_filter').html(
                '<i class="fa fa-calendar"></i> ' + LANG.filter_by_date
            );
        });
        updateTaxReport();
    }

    if ($('#trending_product_date_range').length == 1) {
        get_sub_categories();
        $('#trending_product_date_range').daterangepicker({
            ranges: ranges,
            autoUpdateInput: false,
            locale: {
                format: moment_date_format,
                cancelLabel: LANG.clear,
                applyLabel: LANG.apply,
                customRangeLabel: LANG.custom_range,
            },
        });
        $('#trending_product_date_range').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(
                picker.startDate.format(moment_date_format) +
                    ' ~ ' +
                    picker.endDate.format(moment_date_format)
            );
        });

        $('#trending_product_date_range').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });
    }

    $('#stock_report_filter_form #location_id, #stock_report_filter_form #category_id, #stock_report_filter_form #sub_category_id, #stock_report_filter_form #brand, #stock_report_filter_form #unit,#stock_report_filter_form #view_stock_filter'
    ).change(function() {
        stock_report_table.ajax.reload();
        stock_expiry_report_table.ajax.reload();
    });

    $('#only_mfg_products').on('ifChanged', function(event){
        stock_report_table.ajax.reload();
        lot_report.ajax.reload();
        stock_expiry_report_table.ajax.reload();
        items_report_table.ajax.reload();
    });

    $('#purchase_sell_location_filter').change(function() {
        updatePurchaseSell();
    });
    $('#tax_report_location_filter').change(function() {
        updateTaxReport();
    });

    //Stock Adjustment Report
    if ($('#stock_adjustment_date_filter').length == 1) {
        $('#stock_adjustment_date_filter').daterangepicker(dateRangeSettings, function(start, end) {
            $('#stock_adjustment_date_filter span').html(
                start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
            );
            updateStockAdjustmentReport();
        });
        $('#purchase_sell_date_filter').on('cancel.daterangepicker', function(ev, picker) {
            $('#purchase_sell_date_filter').html(
                '<i class="fa fa-calendar"></i> ' + LANG.filter_by_date
            );
        });
        updateStockAdjustmentReport();
    }

    $('#stock_adjustment_location_filter').change(function() {
        updateStockAdjustmentReport();
    });

    //Register report
    register_report_table = $('#register_report_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/reports/register-report',
        columnDefs: [{ targets: [7], orderable: false, searchable: false }],
        columns: [
            { data: 'created_at', name: 'created_at' },
            { data: 'closed_at', name: 'closed_at' },
            { data: 'location_name', name: 'bl.name' },
            { data: 'user_name', name: 'user_name' },
            { data: 'total_card_slips', name: 'total_card_slips' },
            { data: 'total_cheques', name: 'total_cheques' },
            { data: 'closing_amount', name: 'closing_amount' },
            { data: 'action', name: 'action' },
        ],
        fnDrawCallback: function(oSettings) {
            __currency_convert_recursively($('#register_report_table'));
        },
    });
    $('.view_register').on('shown.bs.modal', function() {
        __currency_convert_recursively($(this));
    });
    $(document).on('submit', '#register_report_filter_form', function(e) {
        e.preventDefault();
        updateRegisterReport();
    });

    $('#register_user_id, #register_status').change(function() {
        updateRegisterReport();
    });

    //Sales representative report
    if ($('#sr_date_filter').length == 1) {
        //date range setting
        $('input#sr_date_filter').daterangepicker(dateRangeSettings, function(start, end) {
            $('input#sr_date_filter').val(
                start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
            );
            updateSalesRepresentativeReport();
        });
        $('input#sr_date_filter').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(
                picker.startDate.format(moment_date_format) +
                    ' ~ ' +
                    picker.endDate.format(moment_date_format)
            );
        });

        $('input#sr_date_filter').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });

        //Sales representative report -> Total expense
        if ($('span#sr_total_expenses').length > 0) {
            salesRepresentativeTotalExpense();
        }
        //Sales representative report -> Total sales
        if ($('span#sr_total_sales').length > 0) {
            salesRepresentativeTotalSales();
        }

        //Sales representative report -> Sales
        sr_sales_report = $('table#sr_sales_report').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[0, 'desc']],
            ajax: {
                url: '/sells',
                data: function(d) {
                    var start = $('input#sr_date_filter')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    var end = $('input#sr_date_filter')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');

                    (d.created_by = $('select#sr_id').val()),
                        (d.location_id = $('select#sr_business_id').val()),
                        (d.start_date = start),
                        (d.end_date = end);
                },
            },
            columns: [
                { data: 'transaction_date', name: 'transaction_date' },
                { data: 'invoice_no', name: 'invoice_no' },
                { data: 'name', name: 'contacts.name' },
                { data: 'business_location', name: 'bl.name' },
                { data: 'payment_status', name: 'payment_status' },
                { data: 'final_total', name: 'final_total' },
                { data: 'total_paid', name: 'total_paid' },
                { data: 'total_remaining', name: 'total_remaining' },
            ],
            columnDefs: [
                {
                    searchable: false,
                    targets: [6],
                },
            ],
            fnDrawCallback: function(oSettings) {
                $('#sr_footer_sale_total').text(
                    sum_table_col($('#sr_sales_report'), 'final-total')
                );

                $('#sr_footer_total_paid').text(sum_table_col($('#sr_sales_report'), 'total-paid'));

                $('#sr_footer_total_remaining').text(
                    sum_table_col($('#sr_sales_report'), 'payment_due')
                );
                $('#sr_footer_total_sell_return_due').text(
                    sum_table_col($('#sr_sales_report'), 'sell_return_due')
                );

                $('#sr_footer_payment_status_count ').html(
                    __sum_status_html($('#sr_sales_report'), 'payment-status-label')
                );
                __currency_convert_recursively($('#sr_sales_report'));
            },
        });

        //Sales representative report -> Expenses
        sr_expenses_report = $('table#sr_expenses_report').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[0, 'desc']],
            ajax: {
                url: '/expenses',
                data: function(d) {
                    var start = $('input#sr_date_filter')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    var end = $('input#sr_date_filter')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');

                    (d.expense_for = $('select#sr_id').val()),
                        (d.location_id = $('select#sr_business_id').val()),
                        (d.start_date = start),
                        (d.end_date = end);
                },
            },
            columnDefs: [
                {
                    targets: 7,
                    orderable: false,
                    searchable: false,
                },
            ],
            columns: [
                { data: 'transaction_date', name: 'transaction_date' },
                { data: 'ref_no', name: 'ref_no' },
                { data: 'category', name: 'ec.name' },
                { data: 'location_name', name: 'bl.name' },
                { data: 'payment_status', name: 'payment_status' },
                { data: 'final_total', name: 'final_total' },
                { data: 'expense_for', name: 'expense_for' },
                { data: 'additional_notes', name: 'additional_notes' },
            ],
            fnDrawCallback: function(oSettings) {
                var expense_total = sum_table_col($('#sr_expenses_report'), 'final-total');
                $('#footer_expense_total').text(expense_total);
                $('#er_footer_payment_status_count').html(
                    __sum_status_html($('#sr_expenses_report'), 'payment-status')
                );
                __currency_convert_recursively($('#sr_expenses_report'));
            },
        });

        //Sales representative report -> Sales with commission
        sr_sales_commission_report = $('table#sr_sales_with_commission_table').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[0, 'desc']],
            ajax: {
                url: '/sells',
                data: function(d) {
                    var start = $('input#sr_date_filter')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    var end = $('input#sr_date_filter')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');

                    (d.commission_agent = $('select#sr_id').val()),
                        (d.location_id = $('select#sr_business_id').val()),
                        (d.start_date = start),
                        (d.end_date = end);
                },
            },
            columns: [
                { data: 'transaction_date', name: 'transaction_date' },
                { data: 'invoice_no', name: 'invoice_no' },
                { data: 'name', name: 'contacts.name' },
                { data: 'business_location', name: 'bl.name' },
                { data: 'payment_status', name: 'payment_status' },
                { data: 'final_total', name: 'final_total' },
                { data: 'total_paid', name: 'total_paid' },
                { data: 'total_remaining', name: 'total_remaining' },
            ],
            columnDefs: [
                {
                    searchable: false,
                    targets: [6],
                },
            ],
            fnDrawCallback: function(oSettings) {
                $('#footer_sale_total').text(
                    sum_table_col($('#sr_sales_with_commission_table'), 'final-total')
                );

                $('#footer_total_paid').text(
                    sum_table_col($('#sr_sales_with_commission_table'), 'total-paid')
                );

                $('#footer_total_remaining').text(
                    sum_table_col($('#sr_sales_with_commission_table'), 'payment_due')
                );
                $('#footer_total_sell_return_due').text(
                    sum_table_col($('#sr_sales_with_commission_table'), 'sell_return_due')
                );

                $('#footer_payment_status_count ').html(
                    __sum_status_html($('#sr_sales_with_commission_table'), 'payment-status-label')
                );
                __currency_convert_recursively($('#sr_sales_with_commission_table'));
                __currency_convert_recursively($('#sr_sales_with_commission'));
            },
        });

        //Sales representive filter
        $('select#sr_id, select#sr_business_id').change(function() {
            updateSalesRepresentativeReport();
        });
    }

    //Stock expiry report table
    stock_expiry_report_table = $('table#stock_expiry_report_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/reports/stock-expiry',
            data: function(d) {
                d.location_id = $('#location_id').val();
                d.category_id = $('#category_id').val();
                d.sub_category_id = $('#sub_category_id').val();
                d.brand_id = $('#brand').val();
                d.unit_id = $('#unit').val();
                d.exp_date_filter = $('#view_stock_filter').val();
                d.only_mfg_products = $('#only_mfg_products').length && $('#only_mfg_products').is(':checked') ? 1 : 0;
            },
        },
        order: [[4, 'asc']],
        columns: [
            { data: 'product', name: 'p.name' },
            { data: 'sku', name: 'p.sku' },
            // { data: 'ref_no', name: 't.ref_no' },
            { data: 'location', name: 'l.name' },
            { data: 'stock_left', name: 'stock_left', searchable: false },
            { data: 'lot_number', name: 'lot_number' },
            { data: 'exp_date', name: 'exp_date' },
            { data: 'mfg_date', name: 'mfg_date' },
            // { data: 'edit', name: 'edit' },
        ],
        fnDrawCallback: function(oSettings) {
            __show_date_diff_for_human($('#stock_expiry_report_table'));
            $('button.stock_expiry_edit_btn').click(function() {
                var purchase_line_id = $(this).data('purchase_line_id');

                $.ajax({
                    method: 'GET',
                    url: '/reports/stock-expiry-edit-modal/' + purchase_line_id,
                    dataType: 'html',
                    success: function(data) {
                        $('div.exp_update_modal')
                            .html(data)
                            .modal('show');
                        $('input#exp_date_expiry_modal').datepicker({
                            autoclose: true,
                            format: datepicker_date_format,
                        });

                        $('form#stock_exp_modal_form').submit(function(e) {
                            e.preventDefault();

                            $.ajax({
                                method: 'POST',
                                url: $('form#stock_exp_modal_form').attr('action'),
                                dataType: 'json',
                                data: $('form#stock_exp_modal_form').serialize(),
                                success: function(data) {
                                    if (data.success == 1) {
                                        $('div.exp_update_modal').modal('hide');
                                        toastr.success(data.msg);
                                        stock_expiry_report_table.ajax.reload();
                                    } else {
                                        toastr.error(data.msg);
                                    }
                                },
                            });
                        });
                    },
                });
            });
            $('#footer_total_stock_left').html(
                __sum_stock($('#stock_expiry_report_table'), 'stock_left')
            );
            __currency_convert_recursively($('#stock_expiry_report_table'));
        },
    });

    //Profit / Loss
    if ($('#profit_loss_date_filter').length == 1) {
        $('#profit_loss_date_filter').daterangepicker(dateRangeSettings, function(start, end) {
            $('#profit_loss_date_filter span').html(
                start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
            );
            updateProfitLoss();
        });
        $('#profit_loss_date_filter').on('cancel.daterangepicker', function(ev, picker) {
            $('#profit_loss_date_filter').html(
                '<i class="fa fa-calendar"></i> ' + LANG.filter_by_date
            );
        });
        updateProfitLoss();
    }

    $('#profit_loss_location_filter').change(function() {
        updateProfitLoss();
    });

    //Revenue Report
    if ($('#revenue_date_filter').length == 1) {
        $('#revenue_date_filter').daterangepicker(dateRangeSettings, function(start, end) {
            $('#revenue_date_filter span').html(
                start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
            );
            updateRevenue();
        });
        $('#revenue_date_filter').on('cancel.daterangepicker', function(ev, picker) {
            $('#revenue_date_filter').html(
                '<i class="fa fa-calendar"></i> ' + LANG.filter_by_date
            );
        });
        updateRevenue();
    }

    $('#revenue_location_filter').change(function() {
        console.log(123);
        updateRevenue();
    });

    //Product Purchase Report
    if ($('#product_pr_date_filter').length == 1) {
        $('#product_pr_date_filter').daterangepicker(dateRangeSettings, function(start, end) {
            $('#product_pr_date_filter').val(
                start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
            );
            product_purchase_report.ajax.reload();
        });
        $('#product_pr_date_filter').on('cancel.daterangepicker', function(ev, picker) {
            $('#product_pr_date_filter').val('');
            product_purchase_report.ajax.reload();
        });
    }
    $(
        '#product_purchase_report_form #variation_id, #product_purchase_report_form #location_id, #product_purchase_report_form #supplier_id, #product_purchase_report_form #product_pr_date_filter'
    ).change(function() {
        product_purchase_report.ajax.reload();
    });
    product_purchase_report = $('table#product_purchase_report_table').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[3, 'desc']],
        ajax: {
            url: '/reports/product-purchase-report',
            data: function(d) {
                var start = '';
                var end = '';
                if ($('#product_pr_date_filter').val()) {
                    start = $('input#product_pr_date_filter')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    end = $('input#product_pr_date_filter')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                }
                d.start_date = start;
                d.end_date = end;
                d.variation_id = $('#variation_id').val();
                d.supplier_id = $('select#supplier_id').val();
                d.location_id = $('select#location_id').val();
            },
        },
        columns: [
            { data: 'product_name', name: 'p.name' },
            { data: 'supplier', name: 'c.name' },
            { data: 'ref_no', name: 't.ref_no' },
            { data: 'transaction_date', name: 't.transaction_date' },
            { data: 'purchase_qty', name: 'purchase_lines.quantity' },
            { data: 'quantity_adjusted', name: 'purchase_lines.quantity_adjusted' },
            { data: 'unit_purchase_price', name: 'purchase_lines.purchase_price_inc_tax' },
            { data: 'subtotal', name: 'subtotal', searchable: false },
        ],
        fnDrawCallback: function(oSettings) {
            $('#footer_subtotal').text(
                sum_table_col($('#product_purchase_report_table'), 'row_subtotal')
            );
            $('#footer_total_purchase').html(
                __sum_stock($('#product_purchase_report_table'), 'purchase_qty')
            );
            $('#footer_total_adjusted').html(
                __sum_stock($('#product_purchase_report_table'), 'quantity_adjusted')
            );
            __currency_convert_recursively($('#product_purchase_report_table'));
        },
    });

    if ($('#search_product').length > 0) {
        $('#search_product').autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: '/purchases/get_products?check_enable_stock=false',
                    dataType: 'json',
                    data: {
                        term: request.term,
                    },
                    success: function(data) {
                        response(
                            $.map(data, function(v, i) {
                                if (v.variation_id) {
                                    return { label: v.text, value: v.variation_id };
                                }
                                return false;
                            })
                        );
                    },
                });
            },
            minLength: 2,
            select: function(event, ui) {
                $('#variation_id')
                    .val(ui.item.value)
                    .change();
                event.preventDefault();
                $(this).val(ui.item.label);
            },
            focus: function(event, ui) {
                event.preventDefault();
                $(this).val(ui.item.label);
            },
        });
    }

    //Product Sell Report
    if ($('#product_sr_date_filter').length == 1) {
        $('#product_sr_date_filter').daterangepicker(
            dateRangeSettings,
            function(start, end) {
                $('#product_sr_date_filter').val(
                    start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                );
                product_sell_report.ajax.reload();
                product_sell_grouped_report.ajax.reload();
                product_sell_report_with_purchase_table.ajax.reload();
            }
        );
        $('#product_sr_date_filter').on('cancel.daterangepicker', function(ev, picker) {
            $('#product_sr_date_filter').val('');
            product_sell_report.ajax.reload();
            product_sell_grouped_report.ajax.reload();
            product_sell_report_with_purchase_table.ajax.reload();
        });
    }
    product_sell_report = $('table#product_sell_report_table').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[4, 'desc']],
        ajax: {
            url: '/reports/product-sell-report',
            data: function(d) {
                var start = '';
                var end = '';
                if ($('#product_sr_date_filter').val()) {
                    start = $('input#product_sr_date_filter')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    end = $('input#product_sr_date_filter')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                }
                d.start_date = start;
                d.end_date = end;

                d.variation_id = $('#variation_id').val();
                d.customer_id = $('select#customer_id').val();
                d.location_id = $('select#location_id').val();
            },
        },
        columns: [
            { data: 'product_name', name: 'p.name' },
            { data: 'sub_sku', name: 'v.sub_sku' },
            { data: 'customer', name: 'c.name' },
            { data: 'contact_id', name: 'c.contact_id' },
            { data: 'invoice_no', name: 't.invoice_no' },
            { data: 'transaction_date', name: 't.transaction_date' },
            { data: 'sell_qty', name: 'transaction_sell_lines.quantity' },
            { data: 'unit_price', name: 'transaction_sell_lines.unit_price_before_discount' },
            { data: 'discount_amount', name: 'transaction_sell_lines.line_discount_amount' },
            { data: 'tax', name: 'tax_rates.name' },
            { data: 'unit_sale_price', name: 'transaction_sell_lines.unit_price_inc_tax' },
            { data: 'subtotal', name: 'subtotal', searchable: false },
        ],
        fnDrawCallback: function(oSettings) {
            $('#footer_subtotal').text(
                sum_table_col($('#product_sell_report_table'), 'row_subtotal')
            );
            $('#footer_total_sold').html(__sum_stock($('#product_sell_report_table'), 'sell_qty'));
            $('#footer_tax').html(__sum_stock($('#product_sell_report_table'), 'tax', 'left'));
            __currency_convert_recursively($('#product_sell_report_table'));
        },
    });

    product_sell_report_with_purchase_table = $('table#product_sell_report_with_purchase_table').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[4, 'desc']],
        ajax: {
            url: '/reports/product-sell-report-with-purchase',
            data: function(d) {
                var start = '';
                var end = '';
                if ($('#product_sr_date_filter').val()) {
                    start = $('input#product_sr_date_filter')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    end = $('input#product_sr_date_filter')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                }
                d.start_date = start;
                d.end_date = end;

                d.variation_id = $('#variation_id').val();
                d.customer_id = $('select#customer_id').val();
                d.location_id = $('select#location_id').val();
            },
        },
        columns: [
            { data: 'product_name', name: 'p.name' },
            { data: 'sub_sku', name: 'v.sub_sku' },
            { data: 'customer', name: 'c.name' },
            { data: 'invoice_no', name: 't.invoice_no' },
            { data: 'transaction_date', name: 't.transaction_date' },
            { data: 'ref_no', name: 'purchase.ref_no' },
            { data: 'supplier_name', name: 'supplier.name' },
            { data: 'purchase_quantity', name: 'tspl.quantity' },
        ],
        fnDrawCallback: function(oSettings) {
            __currency_convert_recursively($('#product_sell_report_with_purchase_table'));
        },
    });

    product_sell_grouped_report = $('table#product_sell_grouped_report_table').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[1, 'desc']],
        ajax: {
            url: '/reports/product-sell-grouped-report',
            data: function(d) {
                var start = '';
                var end = '';
                if ($('#product_sr_date_filter').val()) {
                    start = $('input#product_sr_date_filter')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    end = $('input#product_sr_date_filter')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                }
                d.start_date = start;
                d.end_date = end;

                d.variation_id = $('#variation_id').val();
                d.customer_id = $('select#customer_id').val();
                d.location_id = $('select#location_id').val();
            },
        },
        columns: [
            { data: 'product_name', name: 'p.name' },
            { data: 'sub_sku', name: 'v.sub_sku' },
            { data: 'transaction_date', name: 't.transaction_date' },
            { data: 'current_stock', name: 'current_stock', searchable: false, orderable: false },
            { data: 'total_qty_sold', name: 'total_qty_sold', searchable: false },
            { data: 'subtotal', name: 'subtotal', searchable: false },
        ],
        fnDrawCallback: function(oSettings) {
            $('#footer_grouped_subtotal').text(
                sum_table_col($('#product_sell_grouped_report_table'), 'row_subtotal')
            );
            $('#footer_total_grouped_sold').html(
                __sum_stock($('#product_sell_grouped_report_table'), 'sell_qty')
            );
            __currency_convert_recursively($('#product_sell_grouped_report_table'));
        },
    });

    $(
        '#product_sell_report_form #variation_id, #product_sell_report_form #location_id, #product_sell_report_form #customer_id'
    ).change(function() {
        product_sell_report.ajax.reload();
        product_sell_grouped_report.ajax.reload();
        product_sell_report_with_purchase_table.ajax.reload();
    });

    $('#product_sell_report_form #search_product').keyup(function() {
        if (
            $(this)
                .val()
                .trim() == ''
        ) {
            $('#product_sell_report_form #variation_id')
                .val('')
                .change();
        }
    });

    $(document).on('click', '.remove_from_stock_btn', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                $.ajax({
                    method: 'GET',
                    url: $(this).data('href'),
                    dataType: 'json',
                    success: function(result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            stock_expiry_report_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });

    //Product lot Report
    lot_report = $('table#lot_report').DataTable({
        processing: true,
        serverSide: true,
        // aaSorting: [[3, 'desc']],

        ajax: {
            url: '/reports/lot-report',
            data: function(d) {
                d.location_id = $('#location_id').val();
                d.category_id = $('#category_id').val();
                d.sub_category_id = $('#sub_category_id').val();
                d.brand_id = $('#brand').val();
                d.unit_id = $('#unit').val();
                d.only_mfg_products = $('#only_mfg_products').length && $('#only_mfg_products').is(':checked') ? 1 : 0;
            },
        },
        columns: [
            { data: 'sub_sku', name: 'v.sub_sku' },
            { data: 'product', name: 'products.name' },
            { data: 'lot_number', name: 'pl.lot_number' },
            { data: 'exp_date', name: 'pl.exp_date' },
            { data: 'stock', name: 'stock', searchable: false },
            { data: 'total_sold', name: 'total_sold', searchable: false },
            { data: 'total_adjusted', name: 'total_adjusted', searchable: false },
        ],

        fnDrawCallback: function(oSettings) {
            $('#footer_total_stock').html(__sum_stock($('#lot_report'), 'total_stock'));
            $('#footer_total_sold').html(__sum_stock($('#lot_report'), 'total_sold'));
            $('#footer_total_adjusted').html(__sum_stock($('#lot_report'), 'total_adjusted'));

            __currency_convert_recursively($('#lot_report'));
            __show_date_diff_for_human($('#lot_report'));
        },
    });

    if ($('table#lot_report').length == 1) {
        $('#location_id, #category_id, #sub_category_id, #unit, #brand').change(function() {
            lot_report.ajax.reload();
        });
    }

    //Purchase Payment Report
    purchase_payment_report = $('table#purchase_payment_report_table').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[2, 'desc']],
        ajax: {
            url: '/reports/purchase-payment-report',
            data: function(d) {
                d.supplier_id = $('select#supplier_id').val();
                d.location_id = $('select#location_id').val();
                var start = '';
                var end = '';
                if ($('input#ppr_date_filter').val()) {
                    start = $('input#ppr_date_filter')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    end = $('input#ppr_date_filter')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                }
                d.start_date = start;
                d.end_date = end;
            },
        },
        columns: [
            {
                orderable: false,
                searchable: false,
                data: null,
                defaultContent: '',
            },
            { data: 'payment_ref_no', name: 'payment_ref_no' },
            { data: 'paid_on', name: 'paid_on' },
            { data: 'amount', name: 'transaction_payments.amount' },
            { data: 'supplier', orderable: false, searchable: false },
            { data: 'method', name: 'method' },
            { data: 'ref_no', name: 't.ref_no' },
            { data: 'action', orderable: false, searchable: false },
        ],
        fnDrawCallback: function(oSettings) {
            var total_amount = sum_table_col($('#purchase_payment_report_table'), 'paid-amount');
            $('#footer_total_amount').text(total_amount);
            __currency_convert_recursively($('#purchase_payment_report_table'));
        },
        createdRow: function(row, data, dataIndex) {
            if (!data.transaction_id) {
                $(row)
                    .find('td:eq(0)')
                    .addClass('details-control');
            }
        },
    });

    // Array to track the ids of the details displayed rows
    var ppr_detail_rows = [];

    $('#purchase_payment_report_table tbody').on('click', 'tr td.details-control', function() {
        var tr = $(this).closest('tr');
        var row = purchase_payment_report.row(tr);
        var idx = $.inArray(tr.attr('id'), ppr_detail_rows);

        if (row.child.isShown()) {
            tr.removeClass('details');
            row.child.hide();

            // Remove from the 'open' array
            ppr_detail_rows.splice(idx, 1);
        } else {
            tr.addClass('details');

            row.child(show_child_payments(row.data())).show();

            // Add to the 'open' array
            if (idx === -1) {
                ppr_detail_rows.push(tr.attr('id'));
            }
        }
    });

    // On each draw, loop over the `detailRows` array and show any child rows
    purchase_payment_report.on('draw', function() {
        $.each(ppr_detail_rows, function(i, id) {
            $('#' + id + ' td.details-control').trigger('click');
        });
    });

    if ($('#ppr_date_filter').length == 1) {
        $('#ppr_date_filter').daterangepicker(dateRangeSettings, function(start, end) {
            $('#ppr_date_filter span').val(
                start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
            );
            purchase_payment_report.ajax.reload();
        });
        $('#ppr_date_filter').on('cancel.daterangepicker', function(ev, picker) {
            $('#ppr_date_filter').val('');
            purchase_payment_report.ajax.reload();
        });
    }

    $(
        '#purchase_payment_report_form #location_id, #purchase_payment_report_form #supplier_id'
    ).change(function() {
        purchase_payment_report.ajax.reload();
    });

    //Sell Payment Report
    sell_payment_report = $('table#sell_payment_report_table').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[2, 'desc']],
        ajax: {
            url: '/reports/sell-payment-report',
            data: function(d) {
                d.supplier_id = $('select#customer_id').val();
                d.location_id = $('select#location_id').val();
                d.payment_types = $('select#payment_types').val();

                var start = '';
                var end = '';
                if ($('input#spr_date_filter').val()) {
                    start = $('input#spr_date_filter')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    end = $('input#spr_date_filter')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                }
                d.start_date = start;
                d.end_date = end;
            },
        },
        columns: [
            {
                orderable: false,
                searchable: false,
                data: null,
                defaultContent: '',
            },
            { data: 'payment_ref_no', name: 'payment_ref_no' },
            { data: 'paid_on', name: 'paid_on' },
            { data: 'amount', name: 'transaction_payments.amount' },
            { data: 'customer', orderable: false, searchable: false },
            { data: 'method', name: 'method' },
            { data: 'invoice_no', name: 't.invoice_no' },
            { data: 'action', orderable: false, searchable: false },
        ],
        fnDrawCallback: function(oSettings) {
            var total_amount = sum_table_col($('#sell_payment_report_table'), 'paid-amount');
            $('#footer_total_amount').text(total_amount);
            __currency_convert_recursively($('#sell_payment_report_table'));
        },
        createdRow: function(row, data, dataIndex) {
            if (!data.transaction_id) {
                $(row)
                    .find('td:eq(0)')
                    .addClass('details-control');
            }
        },
    });
    // Array to track the ids of the details displayed rows
    var spr_detail_rows = [];

    $('#sell_payment_report_table tbody').on('click', 'tr td.details-control', function() {
        var tr = $(this).closest('tr');
        var row = sell_payment_report.row(tr);
        var idx = $.inArray(tr.attr('id'), spr_detail_rows);

        if (row.child.isShown()) {
            tr.removeClass('details');
            row.child.hide();

            // Remove from the 'open' array
            spr_detail_rows.splice(idx, 1);
        } else {
            tr.addClass('details');

            row.child(show_child_payments(row.data())).show();

            // Add to the 'open' array
            if (idx === -1) {
                spr_detail_rows.push(tr.attr('id'));
            }
        }
    });

    // On each draw, loop over the `detailRows` array and show any child rows
    sell_payment_report.on('draw', function() {
        $.each(spr_detail_rows, function(i, id) {
            $('#' + id + ' td.details-control').trigger('click');
        });
    });

    if ($('#spr_date_filter').length == 1) {
        $('#spr_date_filter').daterangepicker(dateRangeSettings, function(start, end) {
            $('#spr_date_filter span').val(
                start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
            );
            sell_payment_report.ajax.reload();
        });
        $('#spr_date_filter').on('cancel.daterangepicker', function(ev, picker) {
            $('#spr_date_filter').val('');
            sell_payment_report.ajax.reload();
        });
    }

    $('#sell_payment_report_form #location_id, #sell_payment_report_form #customer_id, #sell_payment_report_form #payment_types').change(
        function() {
            sell_payment_report.ajax.reload();
        }
    );

    //Items report
    if ($('#ir_purchase_date_filter').length == 1) {
        $('#ir_purchase_date_filter').daterangepicker(dateRangeSettings, function(start, end) {
            $('#ir_purchase_date_filter').val(
                start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
            );
            items_report_table.ajax.reload();
        });
        $('#ir_purchase_date_filter').on('cancel.daterangepicker', function(ev, picker) {
            $('#ir_purchase_date_filter').val('');
            items_report_table.ajax.reload();
        });
    }
    if ($('#ir_sale_date_filter').length == 1) {
        $('#ir_sale_date_filter').daterangepicker(dateRangeSettings, function(start, end) {
            $('#ir_sale_date_filter').val(
                start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
            );
            items_report_table.ajax.reload();
        });
        $('#ir_sale_date_filter').on('cancel.daterangepicker', function(ev, picker) {
            $('#ir_sale_date_filter').val('');
            items_report_table.ajax.reload();
        });
    }
    items_report_table = $('#items_report_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/reports/items-report',
            data: function(d) {
                var purchase_start = '';
                var purchase_end = '';
                if ($('#ir_purchase_date_filter').val()) {
                    purchase_start = $('input#ir_purchase_date_filter')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    purchase_end = $('input#ir_purchase_date_filter')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                }
                console.log(purchase_end);

                var sale_start = '';
                var sale_end = '';
                if ($('#ir_sale_date_filter').val()) {
                    sale_start = $('input#ir_sale_date_filter')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    sale_end = $('input#ir_sale_date_filter')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                }

                d.purchase_start = purchase_start;
                d.purchase_end = purchase_end;

                d.sale_start = sale_start;
                d.sale_end = sale_end;

                d.supplier_id = $('select#ir_supplier_id').val();
                d.customer_id = $('select#ir_customer_id').val();
                d.location_id = $('select#ir_location_id').val();
                d.only_mfg_products = $('#only_mfg_products').length && $('#only_mfg_products').is(':checked') ? 1 : 0;
            },
        },
        columns: [
            { data: 'product_name', name: 'p.name' },
            { data: 'sku', name: 'v.sub_sku' },
            { data: 'purchase_date', name: 'purchase.transaction_date' },
            { data: 'purchase_ref_no', name: 'purchase.ref_no' },
            { data: 'supplier', name: 'suppliers.name' },
            { data: 'purchase_price', name: 'PL.purchase_price_inc_tax' },
            { data: 'sell_date', searchable: false },
            { data: 'sale_invoice_no', name: 'sale_invoice_no' },
            { data: 'customer', searchable: false },
            { data: 'location', name: 'bl.name' },
            { data: 'quantity', searchable: false },
            { data: 'selling_price', searchable: false },
            { data: 'subtotal', searchable: false }
        ],
        fnDrawCallback: function(oSettings) {
            $('#footer_total_pp').html(sum_table_col($('#items_report_table'), 'purchase_price'));
            $('#footer_total_sp').html(sum_table_col($('#items_report_table'), 'row_selling_price'));
            $('#footer_total_subtotal').html(
                sum_table_col($('#items_report_table'), 'row_subtotal')
            );
            $('#footer_total_qty').html(
                __sum_stock($('#items_report_table'), 'quantity')
            );

            __currency_convert_recursively($('#items_report_table'));
        },
    });
    $(document).on('change', '#ir_supplier_id, #ir_customer_id, #ir_location_id', function(){
        items_report_table.ajax.reload();
    });

    expense_report_table = $('#expense_report_table').DataTable();
});

function updatePurchaseSell() {
    var start = $('#purchase_sell_date_filter')
        .data('daterangepicker')
        .startDate.format('YYYY-MM-DD');
    var end = $('#purchase_sell_date_filter')
        .data('daterangepicker')
        .endDate.format('YYYY-MM-DD');
    var location_id = $('#purchase_sell_location_filter').val();

    var data = { start_date: start, end_date: end, location_id: location_id };

    var loader = __fa_awesome();
    $('.total_purchase').html(loader);
    $('.purchase_due').html(loader);
    $('.total_sell').html(loader);
    $('.invoice_due').html(loader);
    $('.purchase_return_inc_tax').html(loader);
    $('.total_sell_return').html(loader);

    $.ajax({
        method: 'GET',
        url: '/reports/purchase-sell',
        dataType: 'json',
        data: data,
        success: function(data) {
            $('.total_purchase').html(
                __currency_trans_from_en(data.purchase.total_purchase_exc_tax, true)
            );
            $('.purchase_inc_tax').html(
                __currency_trans_from_en(data.purchase.total_purchase_inc_tax, true)
            );
            $('.purchase_due').html(__currency_trans_from_en(data.purchase.purchase_due, true));

            $('.total_sell').html(__currency_trans_from_en(data.sell.total_sell_exc_tax, true));
            $('.sell_inc_tax').html(__currency_trans_from_en(data.sell.total_sell_inc_tax, true));
            $('.sell_due').html(__currency_trans_from_en(data.sell.invoice_due, true));
            $('.purchase_return_inc_tax').html(
                __currency_trans_from_en(data.total_purchase_return, true)
            );
            $('.total_sell_return').html(__currency_trans_from_en(data.total_sell_return, true));

            $('.sell_minus_purchase').html(__currency_trans_from_en(data.difference.total, true));
            __highlight(data.difference.total, $('.sell_minus_purchase'));

            $('.difference_due').html(__currency_trans_from_en(data.difference.due, true));
            __highlight(data.difference.due, $('.difference_due'));

            // $('.purchase_due').html( __currency_trans_from_en(data.purchase_due, true));
        },
    });
}

function get_stock_details(rowData) {
    var div = $('<div/>')
        .addClass('loading')
        .text('Loading...');
    var location_id = $('#location_id').val();
    $.ajax({
        url: '/reports/stock-details?location_id=' + location_id,
        data: {
            product_id: rowData.DT_RowId,
        },
        dataType: 'html',
        success: function(data) {
            div.html(data).removeClass('loading');
            __currency_convert_recursively(div);
        },
    });

    return div;
}

function updateTaxReport() {
    var start = $('#tax_report_date_filter')
        .data('daterangepicker')
        .startDate.format('YYYY-MM-DD');
    var end = $('#tax_report_date_filter')
        .data('daterangepicker')
        .endDate.format('YYYY-MM-DD');
    var location_id = $('#tax_report_location_filter').val();
    var data = { start_date: start, end_date: end, location_id: location_id };

    var loader = '<i class="fas fa-sync fa-spin fa-fw margin-bottom"></i>';
    $('.input_tax').html(loader);
    $('.output_tax').html(loader);
    $('.expense_tax').html(loader);
    $('.tax_diff').html(loader);
    $.ajax({
        method: 'GET',
        url: '/reports/tax-report',
        dataType: 'json',
        data: data,
        success: function(data) {
            $('.input_tax').html(data.input_tax);
            __currency_convert_recursively($('.input_tax'));
            $('.output_tax').html(data.output_tax);
            __currency_convert_recursively($('.output_tax'));
            $('.expense_tax').html(data.expense_tax);
            __currency_convert_recursively($('.expense_tax'));
            $('.tax_diff').html(__currency_trans_from_en(data.tax_diff, true));
            __highlight(data.tax_diff, $('.tax_diff'));
        },
    });
}

function updateStockAdjustmentReport() {
    var location_id = $('#stock_adjustment_location_filter').val();
    var start = $('#stock_adjustment_date_filter')
        .data('daterangepicker')
        .startDate.format('YYYY-MM-DD');
    var end = $('#stock_adjustment_date_filter')
        .data('daterangepicker')
        .endDate.format('YYYY-MM-DD');

    var data = { start_date: start, end_date: end, location_id: location_id };

    var loader = __fa_awesome();
    $('.total_amount').html(loader);
    $('.total_recovered').html(loader);
    $('.total_normal').html(loader);
    $('.total_abnormal').html(loader);

    $.ajax({
        method: 'GET',
        url: '/reports/stock-adjustment-report',
        dataType: 'json',
        data: data,
        success: function(data) {
            $('.total_amount').html(__currency_trans_from_en(data.total_amount, true));
            $('.total_recovered').html(__currency_trans_from_en(data.total_recovered, true));
            $('.total_normal').html(__currency_trans_from_en(data.total_normal, true));
            $('.total_abnormal').html(__currency_trans_from_en(data.total_abnormal, true));
        },
    });

    stock_adjustment_table.ajax
        .url(
            '/stock-adjustments?location_id=' +
                location_id +
                '&start_date=' +
                start +
                '&end_date=' +
                end
        )
        .load();
}

function updateRegisterReport() {
    var data = {
        user_id: $('#register_user_id').val(),
        status: $('#register_status').val(),
    };
    var out = [];

    for (var key in data) {
        out.push(key + '=' + encodeURIComponent(data[key]));
    }
    url_data = out.join('&');
    register_report_table.ajax.url('/reports/register-report?' + url_data).load();
}

function updateSalesRepresentativeReport() {
    //Update total expenses and total sales
    salesRepresentativeTotalExpense();
    salesRepresentativeTotalSales();
    salesRepresentativeTotalCommission();

    //Expense and expense table refresh
    sr_expenses_report.ajax.reload();
    sr_sales_report.ajax.reload();
    sr_sales_commission_report.ajax.reload();
}

function salesRepresentativeTotalExpense() {
    var start = $('input#sr_date_filter')
        .data('daterangepicker')
        .startDate.format('YYYY-MM-DD');
    var end = $('input#sr_date_filter')
        .data('daterangepicker')
        .endDate.format('YYYY-MM-DD');

    var data_expense = {
        expense_for: $('select#sr_id').val(),
        location_id: $('select#sr_business_id').val(),
        start_date: start,
        end_date: end,
    };

    $('span#sr_total_expenses').html(__fa_awesome());

    $.ajax({
        method: 'GET',
        url: '/reports/sales-representative-total-expense',
        dataType: 'json',
        data: data_expense,
        success: function(data) {
            $('span#sr_total_expenses').html(__currency_trans_from_en(data.total_expense, true));
        },
    });
}

function salesRepresentativeTotalSales() {
    var start = $('input#sr_date_filter')
        .data('daterangepicker')
        .startDate.format('YYYY-MM-DD');
    var end = $('input#sr_date_filter')
        .data('daterangepicker')
        .endDate.format('YYYY-MM-DD');

    var data_expense = {
        created_by: $('select#sr_id').val(),
        location_id: $('select#sr_business_id').val(),
        start_date: start,
        end_date: end,
    };

    $('span#sr_total_sales').html(__fa_awesome());
    $('span#sr_total_sales_return').html(__fa_awesome());
    $('span#sr_total_sales_final').html(__fa_awesome());

    $.ajax({
        method: 'GET',
        url: '/reports/sales-representative-total-sell',
        dataType: 'json',
        data: data_expense,
        success: function(data) {
            $('span#sr_total_sales').html(__currency_trans_from_en(data.total_sell_exc_tax, true));
            $('span#sr_total_sales_return').html(
                __currency_trans_from_en(data.total_sell_return_exc_tax, true)
            );
            $('span#sr_total_sales_final').html(__currency_trans_from_en(data.total_sell, true));
        },
    });
}

function salesRepresentativeTotalCommission() {
    var start = $('input#sr_date_filter')
        .data('daterangepicker')
        .startDate.format('YYYY-MM-DD');
    var end = $('input#sr_date_filter')
        .data('daterangepicker')
        .endDate.format('YYYY-MM-DD');

    var data_sell = {
        commission_agent: $('select#sr_id').val(),
        location_id: $('select#sr_business_id').val(),
        start_date: start,
        end_date: end,
    };

    $('span#sr_total_commission').html(__fa_awesome());
    if (data_sell.commission_agent) {
        $('div#total_commission_div').removeClass('hide');
        $.ajax({
            method: 'GET',
            url: '/reports/sales-representative-total-commission',
            dataType: 'json',
            data: data_sell,
            success: function(data) {
                var str =
                    '<div style="padding-right:15px; display: inline-block">' +
                    __currency_trans_from_en(data.total_commission, true) +
                    '</div>';
                if (data.commission_percentage != 0) {
                    str +=
                        ' <small>(' +
                        data.commission_percentage +
                        '% of ' +
                        __currency_trans_from_en(data.total_sales_with_commission) +
                        ')</small>';
                }

                $('span#sr_total_commission').html(str);
            },
        });
    } else {
        $('div#total_commission_div').addClass('hide');
    }
}

function show_child_payments(rowData) {
    var div = $('<div/>')
        .addClass('loading')
        .text('Loading...');
    $.ajax({
        url: '/payments/show-child-payments/' + rowData.DT_RowId,
        dataType: 'html',
        success: function(data) {
            div.html(data).removeClass('loading');
            __currency_convert_recursively(div);
        },
    });

    return div;
}
