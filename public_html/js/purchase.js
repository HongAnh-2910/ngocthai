$(document).ready(function() {
    if ($('input#iraqi_selling_price_adjustment').length > 0) {
        iraqi_selling_price_adjustment = true;
    } else {
        iraqi_selling_price_adjustment = false;
    }

    /*//Date picker
    $('#transaction_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });*/

    //get suppliers
    /*$('#supplier_id').select2({
        ajax: {
            url: '/purchases/get_suppliers',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term, // search term
                    page: params.page,
                };
            },
            processResults: function(data) {
                return {
                    results: data,
                };
            },
        },
        minimumInputLength: 1,
        escapeMarkup: function(m) {
            return m;
        },
        templateResult: function(data) {
            if (!data.id) {
                return data.text;
            }
            var html = data.text + ' - ' + data.business_name + ' (' + data.contact_id + ')';
            return html;
        },
        language: {
            noResults: function() {
                var name = $('#supplier_id')
                    .data('select2')
                    .dropdown.$search.val();
                return (
                    '<button type="button" data-name="' +
                    name +
                    '" class="btn btn-link add_new_supplier"><i class="fa fa-plus-circle fa-lg" aria-hidden="true"></i>&nbsp; ' +
                    __translate('add_name_as_new_supplier', { name: name }) +
                    '</button>'
                );
            },
        },
    }).on('select2:select', function (e) {
        var data = e.params.data;
        $('#pay_term_number').val(data.pay_term_number);
        $('#pay_term_type').val(data.pay_term_type);
    });*/

    //Quick add supplier
    $(document).on('click', '.add_new_supplier', function() {
        $('#supplier_id').select2('close');
        var name = $(this).data('name');
        $('.contact_modal')
            .find('input#name')
            .val(name);
        $('.contact_modal')
            .find('select#contact_type')
            .val('supplier')
            .closest('div.contact_type_div')
            .addClass('hide');
        $('.contact_modal').modal('show');
    });

    $('form#quick_add_contact')
        .submit(function(e) {
            e.preventDefault();
        })
        .validate({
            rules: {
                contact_id: {
                    remote: {
                        url: '/contacts/check-contact-id',
                        type: 'post',
                        data: {
                            contact_id: function() {
                                return $('#contact_id').val();
                            },
                            hidden_id: function() {
                                if ($('#hidden_id').length) {
                                    return $('#hidden_id').val();
                                } else {
                                    return '';
                                }
                            },
                        },
                    },
                },
            },
            messages: {
                contact_id: {
                    remote: LANG.contact_id_already_exists,
                },
            },
            submitHandler: function(form) {
                $(form)
                    .find('button[type="submit"]')
                    .attr('disabled', true);
                var data = $(form).serialize();
                $.ajax({
                    method: 'POST',
                    url: $(form).attr('action'),
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            $('select#supplier_id').append(
                                $('<option>', { value: result.data.id, text: result.data.name })
                            );
                            $('select#supplier_id')
                                .val(result.data.id)
                                .trigger('change');
                            $('div.contact_modal').modal('hide');
                            toastr.success(result.msg);
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            },
        });
    $('.contact_modal').on('hidden.bs.modal', function() {
        $('form#quick_add_contact')
            .find('button[type="submit"]')
            .removeAttr('disabled');
        $('form#quick_add_contact')[0].reset();
    });

    //Add products
    if ($('#search_product').length > 0) {
        $('#search_product')
            .autocomplete({
                source: function(request, response) {
                    $.getJSON(
                        '/purchases/get_products',
                        { location_id: $('#location_id').val(), term: request.term },
                        response
                    );
                },
                minLength: 2,
                response: function(event, ui) {
                    if (ui.content.length == 1) {
                        ui.item = ui.content[0];
                        $(this)
                            .data('ui-autocomplete')
                            ._trigger('select', 'autocompleteselect', ui);
                        $(this).autocomplete('close');
                    } else if (ui.content.length == 0) {
                        var term = $(this).data('ui-autocomplete').term;
                        swal({
                            title: LANG.no_products_found,
                            text: __translate('add_name_as_new_product', { term: term }),
                            buttons: [LANG.cancel, LANG.ok],
                        }).then(value => {
                            if (value) {
                                var container = $('.quick_add_product_modal');
                                $.ajax({
                                    url: '/products/quick_add?product_name=' + term,
                                    dataType: 'html',
                                    success: function(result) {
                                        $(container)
                                            .html(result)
                                            .modal('show');
                                    },
                                });
                            }
                        });
                    }
                },
                select: function(event, ui) {
                    $(this).val(null);
                    get_purchase_entry_row(ui.item.product_id, ui.item.variation_id, ui.item.location_id);
                },
            })
            .autocomplete('instance')._renderItem = function(ul, item) {
            return $('<li>')
                .append('<div>' + item.text + '</div>')
                .appendTo(ul);
        };
    }

    $(document).on('click', '.remove_purchase_entry_row', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(value => {
            if (value) {
                $(this)
                    .closest('tr')
                    .remove();
                update_table_total();
                update_grand_total();
                update_table_sr_number();
            }
        });
    });

    //On Change of quantity
    function updateTotal(tr) {
        var area = tr.find('.area_hidden').val();
        var purchase_before_tax = __read_number(tr.find('input.purchase_unit_cost'), true);
        var purchase_after_tax = __read_number(
            tr.find('input.purchase_unit_cost_after_tax'),
            true
        );

        //Calculate sub totals
        let sub_total_before_tax = area * purchase_before_tax;
        let sub_total_after_tax = area * purchase_after_tax;

        tr.find('.row_subtotal_before_tax').text(
            __currency_trans_from_en(sub_total_before_tax, false, true)
        );
        __write_number(
            tr.find('input.row_subtotal_before_tax_hidden'),
            sub_total_before_tax,
            true
        );

        tr.find('.row_subtotal_after_tax').text(
            __currency_trans_from_en(sub_total_after_tax, false, true)
        );
        __write_number(tr.find('input.row_subtotal_after_tax_hidden'), sub_total_after_tax, true);

        update_table_total();
        update_grand_total();
    }

    $(document).on('change', '.purchase_unit_cost_without_discount', function() {
        var purchase_before_discount = __read_number($(this), true);

        var row = $(this).closest('tr');
        var discount_percent = __read_number(row.find('input.inline_discounts'), true);
        var quantity = __read_number(row.find('input.purchase_quantity'), true);

        //Calculations.
        var purchase_before_tax =
            parseFloat(purchase_before_discount) -
            __calculate_amount('percentage', discount_percent, purchase_before_discount);

        __write_number(row.find('input.purchase_unit_cost'), purchase_before_tax, true);

        var sub_total_before_tax = quantity * purchase_before_tax;

        //Tax
        var tax_rate = parseFloat(
            row
                .find('select.purchase_line_tax_id')
                .find(':selected')
                .data('tax_amount')
        );
        var tax = __calculate_amount('percentage', tax_rate, purchase_before_tax);

        var purchase_after_tax = purchase_before_tax + tax;
        var sub_total_after_tax = quantity * purchase_after_tax;

        row.find('.row_subtotal_before_tax').text(
            __currency_trans_from_en(sub_total_before_tax, false, true)
        );
        __write_number(
            row.find('input.row_subtotal_before_tax_hidden'),
            sub_total_before_tax,
            true
        );

        __write_number(row.find('input.purchase_unit_cost_after_tax'), purchase_after_tax, true);
        row.find('.row_subtotal_after_tax').text(
            __currency_trans_from_en(sub_total_after_tax, false, true)
        );
        __write_number(row.find('input.row_subtotal_after_tax_hidden'), sub_total_after_tax, true);

        row.find('.purchase_product_unit_tax_text').text(
            __currency_trans_from_en(tax, false, true)
        );
        __write_number(row.find('input.purchase_product_unit_tax'), tax, true);

        update_inline_profit_percentage(row);
        update_table_total();
        update_grand_total();
    });

    $(document).on('change', '.inline_discounts', function() {
        var row = $(this).closest('tr');

        var discount_percent = __read_number($(this), true);

        var discount_type = row.find('.discount_type_row').val();

        var quantity = __read_number(row.find('.area_hidden'), true);

        var purchase_before_discount = __read_number(
            row.find('input.purchase_unit_cost_without_discount'),
            true
        );

        //Calculations.
        var purchase_before_tax = parseFloat(purchase_before_discount) -
            __calculate_amount(discount_type, discount_percent, purchase_before_discount);

        __write_number(row.find('input.purchase_unit_cost'), purchase_before_tax, true);
        row.find('span.purchase_unit_cost').text(
            __currency_trans_from_en(purchase_before_tax, false, true)
        );

        row.find('input.purchase_unit_cost_hidden').val(purchase_before_tax);

        var sub_total_before_tax = quantity * purchase_before_tax;

        //Tax
        var tax_rate = parseFloat(
            row
                .find('select.purchase_line_tax_id')
                .find(':selected')
                .data('tax_amount')
        );
        var tax = __calculate_amount('percentage', tax_rate, purchase_before_tax);

        var purchase_after_tax = purchase_before_tax + tax;
        var sub_total_after_tax = quantity * purchase_after_tax;

        row.find('.row_subtotal_before_tax').text(
            __currency_trans_from_en(sub_total_before_tax, false, true)
        );
        __write_number(
            row.find('input.row_subtotal_before_tax_hidden'),
            sub_total_before_tax,
            true
        );

        __write_number(row.find('input.purchase_unit_cost_after_tax'), purchase_after_tax, true);
        row.find('.row_subtotal_after_tax').text(
            __currency_trans_from_en(sub_total_after_tax, false, true)
        );
        __write_number(row.find('input.row_subtotal_after_tax_hidden'), sub_total_after_tax, true);
        row.find('.purchase_product_unit_tax_text').text(
            __currency_trans_from_en(tax, false, true)
        );
        __write_number(row.find('input.purchase_product_unit_tax'), tax, true);

        update_inline_profit_percentage(row);
        update_table_total();
        update_grand_total();
    });

    $(document).on('change', '.purchase_unit_cost', function() {
        var row = $(this).closest('tr');
        var quantity = __read_number(row.find('input.purchase_quantity'), true);
        var purchase_before_tax = __read_number($(this), true);

        var sub_total_before_tax = quantity * purchase_before_tax;

        //Update unit cost price before discount
        var discount_percent = __read_number(row.find('input.inline_discounts'), true);
        var purchase_before_discount = __get_principle(purchase_before_tax, discount_percent, true);
        __write_number(
            row.find('input.purchase_unit_cost_without_discount'),
            purchase_before_discount,
            true
        );

        //Tax
        var tax_rate = parseFloat(
            row
                .find('select.purchase_line_tax_id')
                .find(':selected')
                .data('tax_amount')
        );
        var tax = __calculate_amount('percentage', tax_rate, purchase_before_tax);

        var purchase_after_tax = purchase_before_tax + tax;
        var sub_total_after_tax = quantity * purchase_after_tax;

        row.find('.row_subtotal_before_tax').text(
            __currency_trans_from_en(sub_total_before_tax, false, true)
        );
        __write_number(
            row.find('input.row_subtotal_before_tax_hidden'),
            sub_total_before_tax,
            true
        );

        row.find('.purchase_product_unit_tax_text').text(
            __currency_trans_from_en(tax, false, true)
        );
        __write_number(row.find('input.purchase_product_unit_tax'), tax, true);

        //row.find('.purchase_product_unit_tax_text').text( tax );
        __write_number(row.find('input.purchase_unit_cost_after_tax'), purchase_after_tax, true);
        row.find('.row_subtotal_after_tax').text(
            __currency_trans_from_en(sub_total_after_tax, false, true)
        );
        __write_number(row.find('input.row_subtotal_after_tax_hidden'), sub_total_after_tax, true);

        update_inline_profit_percentage(row);
        update_table_total();
        update_grand_total();
    });

    $(document).on('change', 'select.purchase_line_tax_id', function() {
        var row = $(this).closest('tr');
        var purchase_before_tax = __read_number(row.find('.purchase_unit_cost'), true);
        var quantity = __read_number(row.find('input.purchase_quantity'), true);

        //Tax
        var tax_rate = parseFloat(
            $(this)
                .find(':selected')
                .data('tax_amount')
        );
        var tax = __calculate_amount('percentage', tax_rate, purchase_before_tax);

        //Purchase price
        var purchase_after_tax = purchase_before_tax + tax;
        var sub_total_after_tax = quantity * purchase_after_tax;

        row.find('.purchase_product_unit_tax_text').text(
            __currency_trans_from_en(tax, false, true)
        );
        __write_number(row.find('input.purchase_product_unit_tax'), tax, true);

        __write_number(row.find('input.purchase_unit_cost_after_tax'), purchase_after_tax, true);

        row.find('.row_subtotal_after_tax').text(
            __currency_trans_from_en(sub_total_after_tax, false, true)
        );
        __write_number(row.find('input.row_subtotal_after_tax_hidden'), sub_total_after_tax, true);

        update_table_total();
        update_grand_total();
    });

    $(document).on('change', '.purchase_unit_cost_after_tax', function() {
        var row = $(this).closest('tr');
        var purchase_after_tax = __read_number($(this), true);
        var quantity = __read_number(row.find('input.purchase_quantity'), true);

        var sub_total_after_tax = purchase_after_tax * quantity;

        //Tax
        var tax_rate = parseFloat(
            row
                .find('select.purchase_line_tax_id')
                .find(':selected')
                .data('tax_amount')
        );
        var purchase_before_tax = __get_principle(purchase_after_tax, tax_rate);
        var sub_total_before_tax = quantity * purchase_before_tax;
        var tax = __calculate_amount('percentage', tax_rate, purchase_before_tax);

        //Update unit cost price before discount
        var discount_percent = __read_number(row.find('input.inline_discounts'), true);
        var purchase_before_discount = __get_principle(purchase_before_tax, discount_percent, true);
        __write_number(
            row.find('input.purchase_unit_cost_without_discount'),
            purchase_before_discount,
            true
        );

        row.find('.row_subtotal_after_tax').text(
            __currency_trans_from_en(sub_total_after_tax, false, true)
        );
        __write_number(row.find('input.row_subtotal_after_tax_hidden'), sub_total_after_tax, true);

        __write_number(row.find('.purchase_unit_cost'), purchase_before_tax, true);

        row.find('.row_subtotal_before_tax').text(
            __currency_trans_from_en(sub_total_before_tax, false, true)
        );
        __write_number(
            row.find('input.row_subtotal_before_tax_hidden'),
            sub_total_before_tax,
            true
        );

        row.find('.purchase_product_unit_tax_text').text(__currency_trans_from_en(tax, true, true));
        __write_number(row.find('input.purchase_product_unit_tax'), tax);

        update_table_total();
        update_grand_total();
    });

    $('#tax_id, #discount_type, #discount_amount, input#shipping_charges').change(function() {
        update_grand_total();
    });

    //Purchase table
    purchase_table = $('#purchase_table').DataTable({
        processing: true,
        serverSide: true,
        buttons: [
            {
                extend: 'excel',
                text: '<i class="fa fa-file-excel" aria-hidden="true"></i> ' + LANG.export_to_excel,
                className: 'btn-sm',
                exportOptions: {
                    columns: ':gt(0)',
                },
                footer: true,
                customize: function( xlsx, row ) {
                    let sheet = xlsx.xl.worksheets['sheet1.xml'];
                    $('row c[r^="F"]', sheet).attr( 's', 63);
                },
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
        ],
        ajax: {
            url: '/purchases',
            data: function(d) {
                if ($('#purchase_list_filter_location_id').length) {
                    d.location_id = $('#purchase_list_filter_location_id').val();
                }
                if ($('#purchase_list_filter_supplier_id').length) {
                    d.supplier_id = $('#purchase_list_filter_supplier_id').val();
                }
                if ($('#purchase_list_filter_payment_status').length) {
                    d.payment_status = $('#purchase_list_filter_payment_status').val();
                }
                if ($('#purchase_list_filter_status').length) {
                    d.status = $('#purchase_list_filter_status').val();
                }

                var start = '';
                var end = '';
                if ($('#purchase_list_filter_date_range').val()) {
                    start = $('input#purchase_list_filter_date_range')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    end = $('input#purchase_list_filter_date_range')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                }
                d.start_date = start;
                d.end_date = end;

                d = __datatable_ajax_callback(d);
            },
        },
        aaSorting: [[1, 'desc']],
        columns: [
            { data: 'action', name: 'action', orderable: false, searchable: false },
            { data: 'transaction_date', name: 'transaction_date' },
            { data: 'ref_no', name: 'ref_no' },
            { data: 'location_name', name: 'BS.name' },
            { data: 'contact_id', name: 'contacts.contact_id' },
            { data: 'name', name: 'contacts.name' },
            // { data: 'status', name: 'status' },
            // { data: 'payment_status', name: 'payment_status' },
            { data: 'final_total', name: 'final_total' },
            // { data: 'payment_due', name: 'payment_due', orderable: false, searchable: false },
            { data: 'added_by', name: 'u.first_name' },
        ],
        fnDrawCallback: function(oSettings) {
            var total_purchase = sum_table_col($('#purchase_table'), 'final_total');
            $('#footer_purchase_total').text(total_purchase);

            /*var total_due = sum_table_col($('#purchase_table'), 'payment_due');
            $('#footer_total_due').text(total_due);

            var total_purchase_return_due = sum_table_col($('#purchase_table'), 'purchase_return');
            $('#footer_total_purchase_return_due').text(total_purchase_return_due);

            $('#footer_status_count').html(__sum_status_html($('#purchase_table'), 'status-label'));

            $('#footer_payment_status_count').html(
                __sum_status_html($('#purchase_table'), 'payment-status-label')
            );*/

            __currency_convert_recursively($('#purchase_table'));
        },
        /*createdRow: function(row, data, dataIndex) {
            $(row)
                .find('td:eq(5)')
                .attr('class', 'clickable_td');
        },*/
    });

    $(document).on(
        'change',
        '#purchase_list_filter_location_id, \
                    #purchase_list_filter_supplier_id, #purchase_list_filter_payment_status,\
                     #purchase_list_filter_status',
        function() {
            purchase_table.ajax.reload();
        }
    );

    update_table_sr_number();

    $(document).on('change', '.mfg_date', function() {
        var this_date = $(this).val();
        var this_moment = moment(this_date, moment_date_format);
        var expiry_period = parseFloat(
            $(this)
                .closest('td')
                .find('.row_product_expiry')
                .val()
        );
        var expiry_period_type = $(this)
            .closest('td')
            .find('.row_product_expiry_type')
            .val();
        if (this_date) {
            if (expiry_period && expiry_period_type) {
                exp_date = this_moment
                    .add(expiry_period, expiry_period_type)
                    .format(moment_date_format);
                $(this)
                    .closest('td')
                    .find('.exp_date')
                    .datepicker('update', exp_date);
            } else {
                $(this)
                    .closest('td')
                    .find('.exp_date')
                    .datepicker('update', '');
            }
        } else {
            $(this)
                .closest('td')
                .find('.exp_date')
                .datepicker('update', '');
        }
    });

    $('#purchase_entry_table tbody')
        .find('.expiry_datepicker')
        .each(function() {
            $(this).datepicker({
                autoclose: true,
                format: datepicker_date_format,
            });
        });

    $(document).on('change', '.profit_percent', function() {
        var row = $(this).closest('tr');
        var profit_percent = __read_number($(this), true);

        var purchase_unit_cost = __read_number(row.find('input.purchase_unit_cost_after_tax'), true);
        var default_sell_price =
            parseFloat(purchase_unit_cost) +
            __calculate_amount('percentage', profit_percent, purchase_unit_cost);
        var exchange_rate = $('input#exchange_rate').val();
        __write_number(
            row.find('input.default_sell_price'),
            default_sell_price * exchange_rate,
            true
        );
    });

    $(document).on('change', '.default_sell_price', function() {
        var row = $(this).closest('tr');
        update_inline_profit_percentage(row);
    });

    $('table#purchase_table tbody').on('click', 'a.delete-purchase', function(e) {
        e.preventDefault();
        swal({
            title: LANG.sure,
            text: LANG.confirm_delete_purchase,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var href = $(this).attr('href');
                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            purchase_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });

    //Calculate area
    function calculateArea(tr){
        var sub_unit_id = tr.find('.sub_unit').val();
        var width = tr.find('.width').val();
        var height = tr.find('.height').val();
        var quantity = __read_number(tr.find('.purchase_quantity'));
        var product_id = tr.find('.product_id').val();

        $.ajax({
            method: 'POST',
            url: '/products/calculate-area',
            dataType: 'json',
            data: {
                sub_unit_id : sub_unit_id,
                width : width,
                height : height,
                quantity : quantity,
                product_id: product_id,
            },
            success: function(result) {
                if (result.success == true) {
                    var data = result.data;

                    var area = data.area;
                    if (data.type != 'area' && data.type != 'meter') {
                        area = 0;
                    }
                    tr.find('.area_hidden').val(area);
                    __write_size(tr.find('.area'), data.area);

                    tr.find('.weight_hidden').val(data.weight);
                    __write_weight(tr.find('.weight'), data.weight);

                    updateTotal(tr);
                }
            },
        });
    }

    function calculatePurchasePrice(tr){
        var height = __read_number(tr.find('.height'));
        var width = __read_number(tr.find('.width'));
        var purchase_quantity = __read_number(tr.find('.purchase_quantity'));
        var purchase_price = __read_number(tr.find('.purchase_price'));
        var total_purchase_price = height * width * purchase_quantity * purchase_price;

        tr.find('.total_purchase_price_hidden').val(total_purchase_price);
        __write_number(tr.find('.total_purchase_price'), total_purchase_price);
    }

    $('table#purchase_entry_table').on('change', '.width, .height, .purchase_quantity, .purchase_price', function() {
        var tr = $(this).closest('tr');
        calculateArea(tr);
        calculatePurchasePrice(tr);
        update_table_total();
    });

    let productProperties = [];
    function changeUnit(tr){
        var sub_unit_id = tr.find('.sub_unit').val();
        var product_unit_id = tr.find('.product_unit_id').val();
        let productId = tr.find('.hidden_variation_id').val();

        if (sub_unit_id == product_unit_id) {
            if(typeof(productProperties[productId]) != "undefined") {
                tr.find('.width').val((productProperties[productId].width));
                tr.find('.height').val((productProperties[productId].height));
            }
            tr.find('.width').prop('readonly', false);
            tr.find('.height').prop('readonly', false);
        } else {
            let properties = {
                height: tr.find('.height').val(),
                width: tr.find('.width').val()
            };

            productProperties[productId] = properties;

            var old_width = tr.find('.sub_unit').find(':selected').data('width');
            __write_size(tr.find('.width'), old_width);

            var old_height = tr.find('.sub_unit').find(':selected').data('height');
            __write_size(tr.find('.height'), old_height);

            tr.find('.width').prop('readonly', true);
            tr.find('.height').prop('readonly', true);
        }
    }

    function initChangeUnit(tr){
        var sub_unit_id = tr.find('.sub_unit').val();
        var product_unit_id = tr.find('.product_unit_id').val();

        if (sub_unit_id == product_unit_id) {
            tr.find('.width').prop('readonly', false);
            tr.find('.height').prop('readonly', false);
        } else {
            tr.find('.width').prop('readonly', true);
            tr.find('.height').prop('readonly', true);
        }
    }

    $('#purchase_entry_table .sub_unit').each(function(){
        var tr = $(this).closest('tr');
        initChangeUnit(tr);
    });

    $('table#purchase_entry_table').on('change', 'select.sub_unit', function() {
        var tr = $(this).closest('tr');
        // Giá gốc
        var purchase_unit_cost = tr.find('input.purchase_unit_cost_hidden ').val();

        calculateArea(tr);
        calculatePurchasePrice(tr);

        var multiplier = parseFloat(
            $(this)
                .find(':selected')
                .data('multiplier')
        );

        changeUnit(tr);

        // var unit_sp = base_unit_selling_price * multiplier;
        var unit_cost = purchase_unit_cost * multiplier;

        // var sp_element = tr.find('input.default_sell_price');
        // __write_number(sp_element, unit_sp);

        var cp_element = tr.find('.row_subtotal_after_tax');
        __write_number(cp_element, unit_cost);
        cp_element.change();
    });
    toggle_search();
});

function calculateAmount(tr){
    var sub_unit_id = tr.find('.sub_unit').val();
    var product_unit_id = tr.find('.product_unit_id').val();
    var width = tr.find('.width').val();
    var height = tr.find('.height').val();
    var quantity = tr.find('.pos_quantity').val();
    var quantity_element = tr.find('.pos_quantity');
    var discount_type = tr.find('.discount_type_row').val();
    var discount_amount = tr.find('.discount_amount_row').val();

    //Update line total and check for quantity not greater than max quantity
    if (sell_form_validator) {
        sell_form_validator.element(quantity_element);
    }
    if (pos_form_validator) {
        pos_form_validator.element(quantity_element);
    }
    // var max_qty = parseFloat(quantity_element.data('rule-max'));
    var entered_qty = __read_number(quantity_element);

    var unit_price_inc_tax = __read_number(tr.find('input.pos_unit_price_inc_tax'));
    var line_total;

    if(sub_unit_id == product_unit_id){
        line_total = width * height * entered_qty * unit_price_inc_tax;
        if (discount_type == 'fixed') {
            line_total -= discount_amount;
        } else if (discount_type == 'percentage') {
            let discount_amount_value = line_total * (discount_amount / 100);
            line_total -= discount_amount_value;
        }
    }else{
        line_total = entered_qty * unit_price_inc_tax;
    }

    __write_number(tr.find('input.pos_line_total'), line_total, false, 2);
    tr.find('span.pos_line_total_text').text(__currency_trans_from_en(line_total, true));

    pos_total_row();

    adjustComboQty(tr);
}

function get_purchase_entry_row(product_id, variation_id, location_id) {
    if (product_id) {
        var row_count = $('#row_count').val();
        $.ajax({
            method: 'POST',
            url: '/purchases/get_purchase_entry_row',
            dataType: 'html',
            data: { product_id: product_id, row_count: row_count, variation_id: variation_id, location_id: location_id },
            success: function(result) {
                $(result)
                    .find('.purchase_quantity')
                    .each(function() {
                        row = $(this).closest('tr');

                        $('#purchase_entry_table tbody').append(
                            update_purchase_entry_row_values(row)
                        );

                        update_row_price_for_exchange_rate(row);

                        update_inline_profit_percentage(row);

                        update_table_total();
                        update_grand_total();
                        update_table_sr_number();

                        //Check if multipler is present then multiply it when a new row is added.
                        if(__getUnitMultiplier(row) > 1){
                            row.find('select.sub_unit').trigger('change');
                        }

                        //trigger onchange
                        $("body").find('#elementTriggerChangeOnLoad').change();
                        var inputNumberRo = $(".quantity-up").closest('.input-number').find('input');
                        if(inputNumberRo.length > 0){
                            inputNumberRo.change();
                        }
                        getFormatNumber();
                    });
                if ($(result).find('.purchase_quantity').length) {
                    $('#row_count').val(
                        $(result).find('.purchase_quantity').length + parseInt(row_count)
                    );
                }
            },
        });
    }
}

function update_purchase_entry_row_values(row) {
    if (typeof row != 'undefined') {
        var quantity = __read_number(row.find('.purchase_quantity'), true);
        var area = __read_number(row.find('.area_hidden'), true);
        var unit_cost_price = __read_number(row.find('.purchase_unit_cost_hidden'), true);
        var row_subtotal_before_tax = area * unit_cost_price;

        var tax_rate = parseFloat(
            $('option:selected', row.find('.purchase_line_tax_id')).attr('data-tax_amount')
        );

        var unit_product_tax = __calculate_amount('percentage', tax_rate, unit_cost_price);

        var unit_cost_price_after_tax = unit_cost_price + unit_product_tax;
        var row_subtotal_after_tax = area * unit_cost_price_after_tax;

        row.find('.row_subtotal_before_tax').text(
            __currency_trans_from_en(row_subtotal_before_tax, false, true)
        );
        __write_number(row.find('.row_subtotal_before_tax_hidden'), row_subtotal_before_tax, true);
        __write_number(row.find('.purchase_product_unit_tax'), unit_product_tax, true);
        row.find('.purchase_product_unit_tax_text').text(
            __currency_trans_from_en(unit_product_tax, false, true)
        );
        row.find('.purchase_unit_cost_after_tax').text(
            __currency_trans_from_en(unit_cost_price_after_tax, true)
        );
        row.find('.row_subtotal_after_tax').text(
            __currency_trans_from_en(row_subtotal_after_tax, false, true)
        );
        __write_number(row.find('.row_subtotal_after_tax_hidden'), row_subtotal_after_tax, true);

        row.find('.expiry_datepicker').each(function() {
            $(this).datepicker({
                autoclose: true,
                format: datepicker_date_format,
            });
        });
        return row;
    }
}

// function update_purchase_entry_row_values(row) {
//     if (typeof row != 'undefined') {
//         var quantity = __read_number(row.find('.purchase_quantity'), true);
//         var area = __read_number(row.find('.area'), true);
//         console.log('area: ', area);
//
//         var unit_cost_price = __read_number(row.find('.purchase_unit_cost'), true);
//         var x = row.find('.purchase_unit_cost').val();
//         console.log('x: ', x);
//         console.log('unit_cost_price: ', unit_cost_price);
//
//         var row_subtotal_before_tax = area * unit_cost_price;
//         var tax_rate = parseFloat(
//             $('option:selected', row.find('.purchase_line_tax_id')).attr('data-tax_amount')
//         );
//
//         var unit_product_tax = __calculate_amount('percentage', tax_rate, unit_cost_price);
//
//
//         var unit_cost_price_after_tax = unit_cost_price + unit_product_tax;
//         console.log('unit_cost_price_after_tax: ', unit_cost_price_after_tax);
//         // var row_subtotal_after_tax = area * unit_cost_price_after_tax;
//         var row_subtotal_after_tax = area * unit_cost_price_after_tax;
//
//         row.find('.row_subtotal_before_tax').text(
//             __currency_trans_from_en(row_subtotal_before_tax, false, true)
//         );
//         __write_number(row.find('.row_subtotal_before_tax_hidden'), row_subtotal_before_tax, true);
//         __write_number(row.find('.purchase_product_unit_tax'), unit_product_tax, true);
//         row.find('.purchase_product_unit_tax_text').text(
//             __currency_trans_from_en(unit_product_tax, false, true)
//         );
//         row.find('.purchase_unit_cost_after_tax').text(
//             __currency_trans_from_en(unit_cost_price_after_tax, true)
//         );
//         row.find('.row_subtotal_after_tax').text(
//             __currency_trans_from_en(row_subtotal_after_tax, false, true)
//         );
//         __write_number(row.find('.row_subtotal_after_tax_hidden'), row_subtotal_after_tax, true);
//
//         row.find('.expiry_datepicker').each(function() {
//             $(this).datepicker({
//                 autoclose: true,
//                 format: datepicker_date_format,
//             });
//         });
//         return row;
//     }
// }

function update_row_price_for_exchange_rate(row) {
    var exchange_rate = $('input#exchange_rate').val();

    if (exchange_rate == 1) {
        return true;
    }

    var purchase_unit_cost_without_discount =
        __read_number(row.find('.purchase_unit_cost_without_discount'), true) / exchange_rate;
    __write_number(
        row.find('.purchase_unit_cost_without_discount'),
        purchase_unit_cost_without_discount,
        true
    );

    var purchase_unit_cost = __read_number(row.find('.purchase_unit_cost'), true) / exchange_rate;
    __write_number(row.find('.purchase_unit_cost'), purchase_unit_cost, true);

    var row_subtotal_before_tax_hidden =
        __read_number(row.find('.row_subtotal_before_tax_hidden'), true) / exchange_rate;
    row.find('.row_subtotal_before_tax').text(
        __currency_trans_from_en(row_subtotal_before_tax_hidden, false, true)
    );
    __write_number(
        row.find('input.row_subtotal_before_tax_hidden'),
        row_subtotal_before_tax_hidden,
        true
    );

    var purchase_product_unit_tax =
        __read_number(row.find('.purchase_product_unit_tax'), true) / exchange_rate;
    __write_number(row.find('input.purchase_product_unit_tax'), purchase_product_unit_tax, true);
    row.find('.purchase_product_unit_tax_text').text(
        __currency_trans_from_en(purchase_product_unit_tax, false, true)
    );

    var purchase_unit_cost_after_tax =
        __read_number(row.find('.purchase_unit_cost_after_tax'), true) / exchange_rate;
    __write_number(
        row.find('input.purchase_unit_cost_after_tax'),
        purchase_unit_cost_after_tax,
        true
    );

    var row_subtotal_after_tax_hidden =
        __read_number(row.find('.row_subtotal_after_tax_hidden'), true) / exchange_rate;
    __write_number(
        row.find('input.row_subtotal_after_tax_hidden'),
        row_subtotal_after_tax_hidden,
        true
    );
    row.find('.row_subtotal_after_tax').text(
        __currency_trans_from_en(row_subtotal_after_tax_hidden, false, true)
    );
}

function iraqi_dinnar_selling_price_adjustment(row) {
    var default_sell_price = __read_number(row.find('input.default_sell_price'), true);

    //Adjsustment
    var remaining = default_sell_price % 250;
    if (remaining >= 125) {
        default_sell_price += 250 - remaining;
    } else {
        default_sell_price -= remaining;
    }

    __write_number(row.find('input.default_sell_price'), default_sell_price, true);

    update_inline_profit_percentage(row);
}

function update_inline_profit_percentage(row) {
    //Update Profit percentage
    var default_sell_price = __read_number(row.find('input.default_sell_price'), true);
    var exchange_rate = $('input#exchange_rate').val();
    default_sell_price_in_base_currency = default_sell_price / parseFloat(exchange_rate);

    var purchase_after_tax = __read_number(row.find('input.purchase_unit_cost_after_tax'), true);
    var profit_percent = __get_rate(purchase_after_tax, default_sell_price_in_base_currency);
    __write_number(row.find('input.profit_percent'), profit_percent, true);
}

function update_table_total() {
    var total_quantity = 0;
    var total_weight = 0;
    var total_price = 0;

    $('#purchase_entry_table tbody')
        .find('tr')
        .each(function() {
            total_quantity += __read_number($(this).find('.area_hidden'), true);
            total_weight += __read_number($(this).find('.weight_hidden'), true);
            total_price += __read_number($(this).find('.total_purchase_price_hidden'), true);
        });

    total_price = Math.round(total_price/1000)*1000;

    __write_size($('#total_quantity'), total_quantity);
    __write_weight($('#total_weight'), total_weight);
    $('#total_price_hidden').val(total_price);
    __write_number($('#total_price'), total_price);
}

function update_grand_total() {
    var st_before_tax = __read_number($('input#st_before_tax_input'), true);
    var total_subtotal = __read_number($('input#total_subtotal_input'), true);

    //Calculate Discount
    var discount_type = $('select#discount_type').val();
    var discount_amount = __read_number($('input#discount_amount'), true);
    var discount = __calculate_amount(discount_type, discount_amount, total_subtotal);
    $('#discount_calculated_amount').text(__currency_trans_from_en(discount, true, true));

    //Calculate Tax
    // var tax_rate = parseFloat($('option:selected', $('#tax_id')).data('tax_amount'));
    // var tax_rate = __read_number($('input#tax_id'), true);
    // var tax = __calculate_amount('fixed', tax_rate, total_subtotal - discount);
    // __write_number($('input#tax_amount'), tax);
    // $('#tax_calculated_amount').text(__currency_trans_from_en(tax, true, true));

    var tax_rate = parseFloat($('option:selected', $('#tax_id')).data('tax_amount'));
    var tax = __calculate_amount('percentage', tax_rate, total_subtotal - discount);
    __write_number($('input#tax_amount'), tax);
    $('#tax_calculated_amount').val(__currency_trans_from_en(tax, false, true));

    //Calculate shipping
    var shipping_charges = __read_number($('input#shipping_charges'), true);
    $('#shipping_charges_text').text(__currency_trans_from_en(shipping_charges, true, true));

    //Calculate Final total
    grand_total = total_subtotal - discount + tax + shipping_charges;

    __write_number($('input#grand_total_hidden'), grand_total, true);

    var payment = __read_number($('input.payment-amount'), true);

    var due = grand_total - payment;
    // __write_number($('input.payment-amount'), grand_total, true);

    $('#grand_total').text(__currency_trans_from_en(grand_total, true, true));

    $('#payment_due').text(__currency_trans_from_en(due, true, true));

    //__currency_convert_recursively($(document));
}
$(document).on('change', 'input.payment-amount', function() {
    var payment = __read_number($(this), true);
    var grand_total = __read_number($('input#grand_total_hidden'), true);
    var bal = grand_total - payment;
    $('#payment_due').text(__currency_trans_from_en(bal, true, true));
});

function update_table_sr_number() {
    var sr_number = 1;
    $('table#purchase_entry_table tbody')
        .find('.sr_number')
        .each(function() {
            $(this).text(sr_number);
            sr_number++;
        });
}

$(document).on('click', 'button#submit_purchase_form', function(e) {
    e.preventDefault();

    //Check if product is present or not.
    if ($('table#purchase_entry_table tbody tr').length <= 0) {
        toastr.warning(LANG.no_products_added);
        $('input#search_product').select();
        return false;
    }

    $('form#add_purchase_form').validate({
        rules: {
            ref_no: {
                remote: {
                    url: '/purchases/check_ref_number',
                    type: 'post',
                    data: {
                        ref_no: function() {
                            return $('#ref_no').val();
                        },
                        contact_id: function() {
                            return $('#supplier_id').val();
                        },
                        purchase_id: function() {
                            if ($('#purchase_id').length > 0) {
                                return $('#purchase_id').val();
                            } else {
                                return '';
                            }
                        },
                    },
                },
            },
        },
        messages: {
            ref_no: {
                remote: LANG.ref_no_already_exists,
            },
        },
    });

    if ($('form#add_purchase_form').valid()) {
        $(this).attr('disabled', true);
        $('form#add_purchase_form').submit();
    }
});

function toggle_search() {
    if ($('#location_id').val()) {
        $('#search_product').removeAttr('disabled');
        $('#search_product').focus();
    } else {
        $('#search_product').attr('disabled', true);
    }
}

$(document).on('change', '#location_id', function() {
    toggle_search();
    $('#purchase_entry_table tbody').html('');
    update_table_total();
    update_grand_total();
    update_table_sr_number();
});

$(document).on('shown.bs.modal', '.quick_add_product_modal', function(){
    var selected_location = $('#location_id').val();
    if (selected_location) {
        $('.quick_add_product_modal').find('#product_locations').val([selected_location]).trigger("change");
    }
});

