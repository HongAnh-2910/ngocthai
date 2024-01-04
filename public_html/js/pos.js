//global variable all product search
var allProductSearch = [];

$(document).ready(function() {
    set_location();

    $(document).on('change', 'input.line_total_service', function() {
        pos_total_row();
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
    });

    //Plate Stock
    var plate_stock_cols = [
        { data: 'sku', name: 'p.sku' },
        { data: 'product', name: 'p.name' },
        { data: 'height', name: 'height' },
        { data: 'width', name: 'width' },
        { data: 'stock', name: 'stock', searchable: false},
        { data: 'warehouses', name: 'warehouses', orderable: false },
        { data: 'is_origin', name: 'is_origin' },
        { data: 'action', name: 'action', searchable: false, orderable: false },
    ];

    plate_stock_table = $('#plate_stock_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/sells/plate-stock',
            data: function(d) {
                d.location_id = $('#select_location_id').val();
                d.warehouse_id = $('#warehouse_id').val();
                d.category_id = $('#category_id').val();
                d.variation_id = $('#variation_id').val();
                d.width = $('#plate_width').val();
                d.height = $('#plate_height').val();
                d.quantity = $('#plate_quantity').val();
            },
        },
        columns: plate_stock_cols,
    });

    $('#select_location_id, #plate_stock_filter_form #category_id, #plate_stock_filter_form #view_stock_filter, #plate_stock_filter_form #plate_width, #plate_stock_filter_form #plate_height, #plate_stock_filter_form #variation_id, #plate_stock_filter_form #plate_quantity, #plate_stock_filter_form #warehouse_id'
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
                'layout': 'sale_pos',
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

    customer_set = false;
    //Prevent enter key function except texarea
    $('form').on('keyup keypress', function(e) {
        var keyCode = e.keyCode || e.which;
        if (keyCode === 13 && e.target.tagName != 'TEXTAREA') {
            e.preventDefault();
            return false;
        }
    });

    //For edit pos form
    if ($('form#edit_pos_sell_form').length > 0) {
        pos_total_row();
        pos_form_obj = $('form#edit_pos_sell_form');
    } else {
        pos_form_obj = $('form#add_pos_sell_form');
    }
    if ($('form#edit_pos_sell_form').length > 0 || $('form#add_pos_sell_form').length > 0) {
        initialize_printer();
    }

    $('select#select_location_id').change(function() {
        reset_pos_form();
        update_table_sr_number();

        var default_price_group = $(this).find(':selected').data('default_price_group')
        if (default_price_group) {
            if($("#price_group option[value='" + default_price_group + "']").length > 0) {
                $("#price_group").val(default_price_group);
                $("#price_group").change();
            }
        }

        //Set default price group
        if ($('#default_price_group').length) {
            var dpg = default_price_group ?
                default_price_group : 0;
            $('#default_price_group').val(dpg);
        }

        var payment_settings = $('select#select_location_id')
            .find(':selected')
            .data('default_payment_accounts');
        payment_settings = payment_settings ? payment_settings : [];
        enabled_payment_types = [];
        for (var key in payment_settings) {
            if (payment_settings[key] && payment_settings[key]['is_enabled']) {
                enabled_payment_types.push(key);
            }
        }
        $(".payment_types_dropdown > option").each(function() {
            if ($(this).val()) {
                if (enabled_payment_types.indexOf($(this).val()) != -1) {
                    $(this).removeClass('hide');
                } else {
                    $(this).addClass('hide');
                }
            }
        });

        if ($('#types_of_service_id').length) {
            $('#types_of_service_id').change();
        }
    });

    //get customer
    $('#customer_id').select2({
        ajax: {
            url: '/contacts/customers',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term, // search term
                    page: params.page,
                    is_edit: $('form#edit_sell_form').length > 0,
                    price_group: $('#price_group').val()
                };
            },
            processResults: function(data) {
                return {
                    results: data,
                };
            },
        },
        templateResult: function (data) {
            var template = data.text + "<br>" + LANG.mobile + ": " + data.mobile;
            if (typeof(data.total_rp) != "undefined") {
                var rp = data.total_rp ? data.total_rp : 0;
                template += "<br><i class='fa fa-gift text-success'></i> " + rp;
            }

            return  template;
        },
        minimumInputLength: 1,
        language: {
            noResults: function() {
                var name = $('#customer_id')
                    .data('select2')
                    .dropdown.$search.val();
                return (
                    '<button type="button" data-name="' +
                    name +
                    '" class="btn btn-link add_new_customer"><i class="fa fa-plus-circle fa-lg" aria-hidden="true"></i>&nbsp; ' +
                    __translate('add_name_as_new_customer', { name: name }) +
                    '</button>'
                );
            },
        },
        escapeMarkup: function(markup) {
            return markup;
        },
    });

    $('#customer_id').on('select2:selecting', function(e) {
        var data = e.params.args.data;

        //Change price group when change customer
        if($('#add_sell_form').length > 0){
            var curr_val = 0;
            if(data.selling_price_group_id != -1){
                curr_val = data.selling_price_group_id;
            }
            var prev_value = $('input#hidden_price_group').val();

            if (curr_val != prev_value && $('table#pos_table tbody tr').length > 0) {
                toastr.warning(LANG.clear_product_when_change_customer);
                reset_pos_form();
            }
        }
    });

    /*$('#customer_id').on('select2:selecting', function(e) {
        var data = e.params.args.data;
        var oldValue = $(this).val();

        //Change price group when change customer
        if($('#add_sell_form').length > 0){
            var curr_val = 0;
            if(data.selling_price_group_id != -1){
                curr_val = data.selling_price_group_id;
            }
            var prev_value = $('input#hidden_price_group').val();

            if (curr_val != prev_value && $('table#pos_table tbody tr').length > 0) {
                swal({
                    title: LANG.sure,
                    text: LANG.form_will_get_reset,
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(willDelete => {
                    if (willDelete) {
                        reset_pos_form();
                    } else { // cancel
                        $(this).val(oldValue).trigger('change');
                    }
                });
            }
        }
    });*/

    $('#customer_id').on('select2:select', function(e) {
        var data = e.params.data;
        if (data.pay_term_number) {
            $('input#pay_term_number').val(data.pay_term_number);
        } else {
            $('input#pay_term_number').val('');
        }

        if (data.pay_term_type) {
            $('#pay_term_type').val(data.pay_term_type);
        } else {
            $('#pay_term_type').val('');
        }

        if (data.mobile) {
            $('#phone_contact').val(data.mobile);
        } else {
            $('#phone_contact').val('');
        }

        if (data.name) {
            $('#delivered_to').val(data.name);
        } else {
            $('#delivered_to').val('');
        }

        if (data.shipping_address) {
            $('#shipping_address').val(data.shipping_address);
        } else {
            $('#shipping_address').val('');
        }

        //Change price group when change customer
        if($('#add_sell_form').length > 0){
            var curr_val = 0;
            if(data.selling_price_group_id != -1){
                $('#price_group').attr('disabled', true);
                curr_val = data.selling_price_group_id;
            }else{
                $('#price_group').removeAttr('disabled');
            }

            $('input#default_price_group').val(curr_val);
            $('input#hidden_price_group').val(curr_val);
            $('select#price_group')
                .val(curr_val)
                .change();
        }
    });

    set_default_customer();

    //Add Product
    $('#search_product')
        .autocomplete({
            source: function(request, response) {
                var price_group = '';
                var search_fields = [];
                $('.search_fields:checked').each(function(i){
                    search_fields[i] = $(this).val();
                });

                if ($('#price_group').length > 0) {
                    price_group = $('#price_group').val();
                }
                $.getJSON(
                    '/products/list',
                    {
                        price_group: price_group,
                        location_id: $('input#location_id').val(),
                        term: request.term,
                        not_for_selling: 0,
                        search_fields: search_fields
                    },
                    response
                );
            },
            minLength: 2,
            response: function(event, ui) {
                allProductSearch = ui.content;
                if (ui.content.length == 1) {
                    ui.item = ui.content[0];
                    if (ui.item.qty_available > 0) {
                        $(this)
                            .data('ui-autocomplete')
                            ._trigger('select', 'autocompleteselect', ui);
                        $(this).autocomplete('close');
                    }
                } else if (ui.content.length == 0) {
                    toastr.error(LANG.no_products_found);
                    $('input#search_product').select();
                }
            },
            focus: function(event, ui) {
                if (ui.item.qty_available <= 0) {
                    return false;
                }
            },
            select: function(event, ui) {
                var searched_term = $(this).val();
                var is_overselling_allowed = false;
                if($('input#is_overselling_allowed').length) {
                    is_overselling_allowed = true;
                }

                if (ui.item.enable_stock != 1 || ui.item.qty_available > 0 || is_overselling_allowed) {
                    $(this).val(null);

                    //Pre select lot number only if the searched term is same as the lot number
                    var purchase_line_id = ui.item.purchase_line_id && searched_term == ui.item.lot_number ? ui.item.purchase_line_id : null;
                    pos_product_row(ui.item.variation_id, purchase_line_id);
                } else {
                    alert(LANG.out_of_stock);
                }
            },
        })
        .autocomplete('instance')._renderItem = function(ul, item) {
        var is_overselling_allowed = false;
        if($('input#is_overselling_allowed').length) {
            is_overselling_allowed = true;
        }
        if (item.enable_stock == 1 && item.qty_available <= 0 && !is_overselling_allowed) {
            var string = '<li class="ui-state-disabled">' + item.name;
            if (item.type == 'variable') {
                string += ' - ' + item.variation;
            }
            var selling_price = item.selling_price;
            if (item.variation_group_price) {
                selling_price = item.variation_group_price;
            }
            string +=
                ' (' +
                item.sub_sku +
                ')' +
                '<br> Giá: ' +
                selling_price +
                ' (Out of stock) </li>';
            return $(string).appendTo(ul);
        } else {
            var string = '<div>' + item.name;
            if (item.type == 'variable') {
                string += ' - ' + item.variation;
            }

            var selling_price = __currency_trans_from_en(item.selling_price, false);
            if (item.variation_group_price) {
                selling_price = __currency_trans_from_en(item.variation_group_price, false);
            }

            string += ' (' + item.sub_sku + ')' + '<br> Giá bán: ' + selling_price + ' đ';
            if (item.enable_stock == 1) {
                if(item.qty_available != undefined){
                    var qty_available = parseFloat(item.qty_available);
                }else{
                    var qty_available = 0;
                }
                // var qty_available = __currency_trans_from_en(item.qty_available, false, false, __currency_precision, true);
                // string += ' |  Tồn kho: ' + qty_available.toFixed(2) + ' m<sup>2</sup>';
            }
            string += '</div>';

            return $('<li>')
                .append(string)
                .appendTo(ul);
        }
    };

    //If change in unit price update price including tax and line total
    $('table#pos_table tbody').on('change', 'input.pos_unit_price', function() {
        var unit_price = __read_number($(this));
        var tr = $(this).parents('tr');

        //calculate discounted unit price
        var discounted_unit_price = calculate_discounted_unit_price(tr);

        var tax_rate = tr
            .find('select.tax_id')
            .find(':selected')
            .data('rate');
        var quantity = __read_number(tr.find('input.pos_quantity'));

        var unit_price_inc_tax = __add_percent(discounted_unit_price, tax_rate);
        var line_total = quantity * unit_price_inc_tax;

        __write_number(tr.find('input.pos_unit_price_inc_tax'), unit_price_inc_tax);
        __write_number(tr.find('input.pos_line_total'), line_total, false, 2);
        tr.find('span.pos_line_total_text').text(__currency_trans_from_en(line_total, true));
        pos_each_row(tr);
        pos_total_row();
        round_row_to_iraqi_dinnar(tr);
    });

    //If change in tax rate then update unit price according to it.
    $('table#pos_table tbody').on('change', 'select.tax_id', function() {
        var tr = $(this).parents('tr');

        var tax_rate = tr
            .find('select.tax_id')
            .find(':selected')
            .data('rate');
        var unit_price_inc_tax = __read_number(tr.find('input.pos_unit_price_inc_tax'));
        var discounted_unit_price = __get_principle(unit_price_inc_tax, tax_rate);
        var unit_price = get_unit_price_from_discounted_unit_price(tr, discounted_unit_price);
        __write_number(tr.find('input.pos_unit_price'), unit_price);
        pos_each_row(tr);
    });

    //If change in unit price including tax, update unit price
    $('table#pos_table tbody').on('change', 'input.pos_unit_price_inc_tax', function() {
        var unit_price_inc_tax = __read_number($(this));

        if (iraqi_selling_price_adjustment) {
            unit_price_inc_tax = round_to_iraqi_dinnar(unit_price_inc_tax);
            __write_number($(this), unit_price_inc_tax);
        }

        var tr = $(this).parents('tr');

        var tax_rate = tr
            .find('select.tax_id')
            .find(':selected')
            .data('rate');
        var quantity = __read_number(tr.find('input.pos_quantity'));

        var line_total = quantity * unit_price_inc_tax;
        var discounted_unit_price = __get_principle(unit_price_inc_tax, tax_rate);
        var unit_price = get_unit_price_from_discounted_unit_price(tr, discounted_unit_price);

        __write_number(tr.find('input.pos_unit_price'), unit_price);
        __write_number(tr.find('input.pos_line_total'), line_total, false, 2);
        tr.find('span.pos_line_total_text').text(__currency_trans_from_en(line_total, true));

        pos_each_row(tr);
        pos_total_row();
    });

    //Change max quantity rule if lot number changes
    $('table#pos_table tbody').on('change', 'select.lot_number', function() {
        var qty_element = $(this)
            .closest('tr')
            .find('input.pos_quantity');

        var tr = $(this).closest('tr');
        var multiplier = 1;
        var unit_name = '';
        var sub_unit_length = tr.find('select.sub_unit').length;
        if (sub_unit_length > 0) {
            var select = tr.find('select.sub_unit');
            multiplier = parseFloat(select.find(':selected').data('multiplier'));
            unit_name = select.find(':selected').data('unit_name');
        }
        var allow_overselling = qty_element.data('allow-overselling');
        if ($(this).val() && !allow_overselling) {
            var lot_qty = $('option:selected', $(this)).data('qty_available');
            var max_err_msg = $('option:selected', $(this)).data('msg-max');

            if (sub_unit_length > 0) {
                lot_qty = lot_qty / multiplier;
                var lot_qty_formated = __number_f(lot_qty, false);
                max_err_msg = __translate('lot_max_qty_error', {
                    max_val: lot_qty_formated,
                    unit_name: unit_name,
                });
            }

            qty_element.attr('data-rule-max-value', lot_qty);
            qty_element.attr('data-msg-max-value', max_err_msg);

            qty_element.rules('add', {
                'max-value': lot_qty,
                messages: {
                    'max-value': max_err_msg,
                },
            });
        } else {
            var default_qty = qty_element.data('qty_available');
            var default_err_msg = qty_element.data('msg_max_default');
            if (sub_unit_length > 0) {
                default_qty = default_qty / multiplier;
                var lot_qty_formated = __number_f(default_qty, false);
                default_err_msg = __translate('pos_max_qty_error', {
                    max_val: lot_qty_formated,
                    unit_name: unit_name,
                });
            }

            qty_element.attr('data-rule-max-value', default_qty);
            qty_element.attr('data-msg-max-value', default_err_msg);

            qty_element.rules('add', {
                'max-value': default_qty,
                messages: {
                    'max-value': default_err_msg,
                },
            });
        }
        qty_element.trigger('change');
    });

    //Change in row discount type or discount amount
    $('table#pos_table tbody').on(
        'change',
        'select.row_discount_type, input.row_discount_amount',
        function() {
            var tr = $(this).parents('tr');

            //calculate discounted unit price
            var discounted_unit_price = calculate_discounted_unit_price(tr);

            var tax_rate = tr
                .find('select.tax_id')
                .find(':selected')
                .data('rate');
            var quantity = tr.find('input.area_hidden').val();

            var unit_price_inc_tax = __add_percent(discounted_unit_price, tax_rate);
            var line_total = quantity * unit_price_inc_tax;

            // __write_number(tr.find('input.pos_unit_price_inc_tax'), unit_price_inc_tax);
            // __write_number(tr.find('input.pos_line_total'), line_total, false, 2);
            tr.find('input.pos_unit_price_inc_tax').val(unit_price_inc_tax);
            tr.find('input.pos_line_total').val(line_total);
            tr.find('span.pos_line_total_text').text(__currency_trans_from_en(line_total, true));
            pos_each_row(tr);
            pos_total_row();
            round_row_to_iraqi_dinnar(tr);
        }
    );

    //Remove row on click on remove row
    $('table#pos_table tbody').on('click', '.pos_remove_row', function() {
        $(this)
            .parents('tr')
            .remove();

        //Update is_service_order field
        var is_service_order = 0;
        $('table#pos_table tbody').find('tr.product_row').each(function () {
            var selected_option = $(this).find('.sub_unit :selected');
            var type = selected_option.data('type');

            if(type == 'service'){
                is_service_order = 1;
            }
        });
        $('#is_service_order').val(is_service_order);

        pos_total_row();
        update_table_sr_number();
    });

    //Remove row on click on remove row
    $('table#pos_table tbody').on('click', '.duplicate_current_row', function() {
        let html = $(this).parents('tr')
        let variation_id = html.find('td input.row_variation_id').val()
        pos_product_row(variation_id)
        //Update is_service_order field
        var is_service_order = 0;
        $('table#pos_table tbody').find('tr.product_row').each(function () {
            var selected_option = $(this).find('.sub_unit :selected');
            var type = selected_option.data('type');

            if(type == 'service'){
                is_service_order = 1;
            }
        });
        $('#is_service_order').val(is_service_order);

        pos_total_row();
        update_table_sr_number();
    });

    //Cancel the invoice
    $('button#pos-cancel').click(function() {
        reset_pos_form();
    });

    //Save invoice as draft
    $('button#pos-draft').click(function() {
        //Check if product is present or not.
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        var is_valid = isValidPosForm();
        if (is_valid != true) {
            return;
        }

        var data = pos_form_obj.serialize();
        data = data + '&status=draft';
        var url = pos_form_obj.attr('action');

        disable_pos_form_actions();
        $.ajax({
            method: 'POST',
            url: url,
            data: data,
            dataType: 'json',
            success: function(result) {
                enable_pos_form_actions();
                if (result.success == 1) {
                    reset_pos_form();
                    toastr.success(result.msg);
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });

    //Save invoice as Quotation
    $('button#pos-quotation').click(function() {
        //Check if product is present or not.
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        var is_valid = isValidPosForm();
        if (is_valid != true) {
            return;
        }

        var data = pos_form_obj.serialize();
        data = data + '&status=quotation';
        var url = pos_form_obj.attr('action');

        disable_pos_form_actions();
        $.ajax({
            method: 'POST',
            url: url,
            data: data,
            dataType: 'json',
            success: function(result) {
                enable_pos_form_actions();
                if (result.success == 1) {
                    reset_pos_form();
                    toastr.success(result.msg);

                    //Check if enabled or not
                    if (result.receipt.is_enabled) {
                        pos_print(result.receipt);
                    }
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });

    //Finalize invoice, open payment modal
    $('button#pos-finalize').click(function() {
        //Check if product is present or not.
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        if ($('#reward_point_enabled').length) {
            var validate_rp = isValidatRewardPoint();
            if (!validate_rp['is_valid']) {
                toastr.error(validate_rp['msg']);
                return false;
            }
        }

        $('#modal_payment').modal('show');
    });

    $('#modal_payment').one('shown.bs.modal', function() {
        $('#modal_payment')
            .find('input')
            .filter(':visible:first')
            .focus()
            .select();
        if ($('form#edit_pos_sell_form').length == 0) {
            $(this).find('#method_0').change();
        }
    });

    //Finalize without showing payment options
    $('button.pos-express-finalize').click(function() {

        //Check if product is present or not.
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        if ($('#reward_point_enabled').length) {
            var validate_rp = isValidatRewardPoint();
            if (!validate_rp['is_valid']) {
                toastr.error(validate_rp['msg']);
                return false;
            }
        }

        var pay_method = $(this).data('pay_method');

        //If pay method is credit sale submit form
        if (pay_method == 'credit_sale') {
            $('#is_credit_sale').val(1);
            pos_form_obj.submit();
            return true;
        } else {
            if ($('#is_credit_sale').length) {
                $('#is_credit_sale').val(0);
            }
        }

        //Check for remaining balance & add it in 1st payment row
        var total_payable = __read_number($('input#final_total_input'));
        var total_paying = __read_number($('input#total_paying_input'));
        if (total_payable > total_paying) {
            var bal_due = total_payable - total_paying;

            var first_row = $('#payment_rows_div')
                .find('.payment-amount')
                .first();
            var first_row_val = __read_number(first_row);
            first_row_val = first_row_val + bal_due;
            __write_number(first_row, first_row_val);
            first_row.trigger('change');
        }

        //Change payment method.
        var payment_method_dropdown = $('#payment_rows_div')
            .find('.payment_types_dropdown')
            .first();

        payment_method_dropdown.val(pay_method);
        payment_method_dropdown.change();
        if (pay_method == 'card') {
            $('div#card_details_modal').modal('show');
        } else if (pay_method == 'suspend') {
            $('div#confirmSuspendModal').modal('show');
        } else {
            pos_form_obj.submit();
        }
    });

    $('div#card_details_modal').on('shown.bs.modal', function(e) {
        $('input#card_number').focus();
    });

    $('div#confirmSuspendModal').on('shown.bs.modal', function(e) {
        $(this)
            .find('textarea')
            .focus();
    });

    //on save card details
    $('button#pos-save-card').click(function() {
        $('input#card_number_0').val($('#card_number').val());
        $('input#card_holder_name_0').val($('#card_holder_name').val());
        $('input#card_transaction_number_0').val($('#card_transaction_number').val());
        $('select#card_type_0').val($('#card_type').val());
        $('input#card_month_0').val($('#card_month').val());
        $('input#card_year_0').val($('#card_year').val());
        $('input#card_security_0').val($('#card_security').val());

        $('div#card_details_modal').modal('hide');
        pos_form_obj.submit();
    });

    $('button#pos-suspend').click(function() {
        $('input#is_suspend').val(1);
        $('div#confirmSuspendModal').modal('hide');
        pos_form_obj.submit();
        $('input#is_suspend').val(0);
    });

    //fix select2 input issue on modal
    $('#modal_payment')
        .find('.select2')
        .each(function() {
            $(this).select2({
                dropdownParent: $('#modal_payment'),
            });
        });

    $('button#add-payment-row').click(function() {
        var row_index = $('#payment_row_index').val();
        var location_id = $('input#location_id').val();
        $.ajax({
            method: 'POST',
            url: '/sells/pos/get_payment_row',
            data: { row_index: row_index, location_id: location_id },
            dataType: 'html',
            success: function(result) {
                if (result) {
                    var appended = $('#payment_rows_div').append(result);

                    var total_payable = __read_number($('input#final_total_input'));
                    var total_paying = __read_number($('input#total_paying_input'));
                    var b_due = total_payable - total_paying;
                    $(appended)
                        .find('input.payment-amount')
                        .focus();
                    $(appended)
                        .find('input.payment-amount')
                        .last()
                        .val(__currency_trans_from_en(b_due, false))
                        .change()
                        .select();
                    __select2($(appended).find('.select2'));
                    $(appended).find('#method_' + row_index).change();
                    $('#payment_row_index').val(parseInt(row_index) + 1);
                }
            },
        });
    });

    $(document).on('click', '.remove_payment_row', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                $(this)
                    .closest('.payment_row')
                    .remove();
                calculate_balance_due();
            }
        });
    });

    pos_form_validator = pos_form_obj.validate({
        submitHandler: function(form) {
            // var total_payble = __read_number($('input#final_total_input'));
            // var total_paying = __read_number($('input#total_paying_input'));
            var cnf = true;

            //Ignore if the difference is less than 0.5
            if ($('input#in_balance_due').val() >= 0.5) {
                cnf = confirm(LANG.paid_amount_is_less_than_payable);
                // if( total_payble > total_paying ){
                // 	cnf = confirm( LANG.paid_amount_is_less_than_payable );
                // } else if(total_payble < total_paying) {
                // 	alert( LANG.paid_amount_is_more_than_payable );
                // 	cnf = false;
                // }
            }

            if (cnf) {
                disable_pos_form_actions();

                var data = $(form).serialize();
                data = data + '&status=final';
                var url = $(form).attr('action');
                $.ajax({
                    method: 'POST',
                    url: url,
                    data: data,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success == 1) {
                            $('#modal_payment').modal('hide');
                            toastr.success(result.msg);

                            reset_pos_form();

                            //Check if enabled or not
                            if (result.receipt.is_enabled) {
                                pos_print(result.receipt);
                            }
                        } else {
                            toastr.error(result.msg);
                        }

                        enable_pos_form_actions();
                    },
                });
            }
            return false;
        },
    });

    $(document).on('change', '.payment-amount', function() {
        calculate_balance_due();
    });

    //Update discount
    $('button#posEditDiscountModalUpdate').click(function() {
        //Close modal
        $('div#posEditDiscountModal').modal('hide');

        //Update values
        $('input#discount_type').val($('select#discount_type_modal').val());
        __write_number($('input#discount_amount'), __read_number($('input#discount_amount_modal')));

        if ($('#reward_point_enabled').length) {
            var reward_validation = isValidatRewardPoint();
            if (!reward_validation['is_valid']) {
                toastr.error(reward_validation['msg']);
                $('#rp_redeemed_modal').val(0);
                $('#rp_redeemed_modal').change();
            }
            updateRedeemedAmount();
        }

        pos_total_row();
    });

    //Shipping
    $('button#posShippingModalUpdate').click(function() {
        //Close modal
        $('div#posShippingModal').modal('hide');

        //update shipping details
        $('input#shipping_details').val($('#shipping_details_modal').val());

        $('input#shipping_address').val($('#shipping_address_modal').val());
        $('input#shipping_status').val($('#shipping_status_modal').val());
        $('input#delivered_to').val($('#delivered_to_modal').val());

        //Update shipping charges
        __write_number(
            $('input#shipping_charges'),
            __read_number($('input#shipping_charges_modal'))
        );

        //$('input#shipping_charges').val(__read_number($('input#shipping_charges_modal')));

        pos_total_row();
    });

    $('#posShippingModal').on('shown.bs.modal', function() {
        $('#posShippingModal')
            .find('#shipping_details_modal')
            .filter(':visible:first')
            .focus()
            .select();
    });

    $(document).on('shown.bs.modal', '.row_edit_product_price_model', function() {
        $('.row_edit_product_price_model')
            .find('input')
            .filter(':visible:first')
            .focus()
            .select();
    });

    //Update Order tax
    $('button#posEditOrderTaxModalUpdate').click(function() {
        //Close modal
        $('div#posEditOrderTaxModal').modal('hide');

        var tax_obj = $('select#order_tax_modal');
        var tax_id = tax_obj.val();
        var tax_rate = tax_obj.find(':selected').data('rate');

        $('input#tax_rate_id').val(tax_id);

        __write_number($('input#tax_calculation_amount'), tax_rate);
        pos_total_row();
    });

    $(document).on('click', '.add_new_customer', function() {
        $('#customer_id').select2('close');
        var name = $(this).data('name');
        $('.contact_modal')
            .find('input#name')
            .val(name);
        $('.contact_modal')
            .find('select#contact_type')
            .val('customer')
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
                if($('form#edit_sell_form').length > 0){
                    var sell_price_group = $('#price_group').val();
                    var customer_price_group = $(form).find('#selling_price_group_id').val();

                    if(customer_price_group != -1 && customer_price_group != sell_price_group){
                        toastr.error(LANG.sell_price_not_match);
                        return;
                    }
                }

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
                            $('select#customer_id').append(
                                $('<option>', { value: result.data.id, text: result.data.name })
                            );
                            $('select#customer_id')
                                .val(result.data.id)
                                .trigger('change');

                            if($('form#add_sell_form').length > 0 && result.data.selling_price_group_id != -1){
                                $('select#price_group').append(
                                    $('<option>', { value: result.data.selling_price_group_id, text: result.data.price_group_name })
                                );
                                $('select#price_group')
                                    .val(result.data.selling_price_group_id)
                                    .trigger('change');

                                $('select#price_group').attr('disabled', true);
                            }

                            $('#phone_contact').val(result.data.mobile);
                            $('#delivered_to').val(result.data.name);
                            $('#shipping_address').val(result.data.shipping_address);

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

    //Updates for add sell
    $('select#discount_type, input#rp_redeemed_amount').change(function() {
        pos_total_row();
    });

    $(document).on('change keyup', 'input#shipping_charges, input#discount_amount, input#order_tax, input#deposit_amount, input#cod_amount', function() {
        pos_total_row();
    });

    $('select#tax_rate_id').change(function() {
        var tax_rate = $(this)
            .find(':selected')
            .data('rate');
        __write_number($('input#tax_calculation_amount'), tax_rate);
        pos_total_row();
    });
    /*//Datetime picker
    $('#transaction_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });*/

    //Direct sell submit
    sell_form = $('form#add_sell_form');
    if ($('form#edit_sell_form').length) {
        sell_form = $('form#edit_sell_form');
        pos_total_row();
    }
    sell_form_validator = sell_form.validate();

    $('button#submit-sell, button#save-and-print').click(function(e) {
        let error = false;

        //Check if product is present or not.
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        //Check is service order
        let is_service_order = $('#is_service_order').val();

        $('table#pos_table tbody').find('tr.product_row').each(function () {
            var selected_option = $(this).find('.sub_unit :selected');
            var type = selected_option.data('type');

            if(type != 'service' && is_service_order == 1){
                toastr.error(LANG.service_order_not_valid);
                $(this).css('color', 'red');
                error = true;
            }
        });

        $('table#pos_table tbody tr').find('.pos_quantity').each(function () {
            var quantity_element = $(this);
            if (__read_number(quantity_element)  % 1 != 0) {
                toastr.error(LANG.quantity_valid);
                quantity_element.css('color', 'red');
                error = true;
            }

            if (__read_number(quantity_element)  <= 0) {
                toastr.error(LANG.quantity_must_be_greater_than_0);
                quantity_element.css('color', 'red');
                error = true;
            }
        });

        if ($(this).attr('id') == 'save-and-print') {
            $('#is_save_and_print').val(1);
        } else {
            $('#is_save_and_print').val(0);
        }

        if (__number_uf($('#total_payable').html()) < 0) {
            toastr.warning(LANG.discount_over_total_price);
            return false;
        }

        if ($('#reward_point_enabled').length) {
            var validate_rp = isValidatRewardPoint();
            if (!validate_rp['is_valid']) {
                toastr.error(validate_rp['msg']);
                return false;
            }
        }

        //Check line discount amount
        $('table#pos_table tbody').find('tr').each(function () {
            var tr = $(this);
            var discount_amount_input = __read_number(tr.find('.discount_amount_row'));
            var discount_amount_max = tr.find('.discount_amount_row').attr('data-max_value');

            if(discount_amount_input > discount_amount_max){
                toastr.error(LANG.discount_amount_not_allow + (new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'VND' }).format(discount_amount_max)));
                tr.find('.discount_amount_row').focus();
                error = true;
                return false;
            }
        });

        if (sell_form.valid() && !error) {
            $('button#submit-sell').attr('disabled', true);
            $('button#save-and-print').attr('disabled', true);
            window.onbeforeunload = null;
            sell_form.submit();
        }

        return;
    });

    //Show product list.
    get_product_suggestion_list(
        $('select#product_category').val(),
        $('select#product_brand').val(),
        $('input#location_id').val(),
        null
    );
    $('select#product_category, select#product_brand').on('change', function(e) {
        $('input#suggestion_page').val(1);
        var location_id = $('input#location_id').val();
        if (location_id != '' || location_id != undefined) {
            get_product_suggestion_list(
                $('select#product_category').val(),
                $('select#product_brand').val(),
                $('input#location_id').val(),
                null
            );
        }
    });

    $(document).on('click', 'div.product_box', function() {
        //Check if location is not set then show error message.
        if ($('input#location_id').val() == '') {
            toastr.warning(LANG.select_location);
        } else {
            pos_product_row($(this).data('variation_id'));
        }
    });

    $(document).on('shown.bs.modal', '.row_description_modal', function() {
        $(this)
            .find('textarea')
            .first()
            .focus();
    });

    //Press enter on search product to jump into last quantty and vice-versa
    $('#search_product').keydown(function(e) {
        var key = e.which;
        if (key == 9) {
            // the tab key code
            e.preventDefault();
            if ($('#pos_table tbody tr').length > 0) {
                $('#pos_table tbody tr:last')
                    .find('input.pos_quantity')
                    .focus()
                    .select();
            }
        }
    });
    $('#pos_table').on('keypress', 'input.pos_quantity', function(e) {
        var key = e.which;
        if (key == 13) {
            // the enter key code
            $('#search_product').focus();
        }
    });

    $('#exchange_rate').change(function() {
        var curr_exchange_rate = 1;
        if ($(this).val()) {
            curr_exchange_rate = __read_number($(this));
        }
        var total_payable = __read_number($('input#final_total_input'));
        var shown_total = total_payable * curr_exchange_rate;
        shown_total = Math.round(shown_total/1000)*1000;
        $('span#total_payable').text(__currency_trans_from_en(shown_total, false));
    });

    $('select#price_group').change(function() {
        //If types of service selected then price group dropdown has no effect
        if ($('#types_of_service_price_group').length > 0 &&
            $('#types_of_service_price_group').val()) {
            return false;
        }
        var curr_val = $(this).val();
        var prev_value = $('input#hidden_price_group').val();
        $('input#hidden_price_group').val(curr_val);
        if (curr_val != prev_value && $('table#pos_table tbody tr').length > 0) {
            swal({
                title: LANG.sure,
                text: LANG.form_will_get_reset,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(willDelete => {
                if (willDelete) {
                    if ($('form#edit_pos_sell_form').length > 0) {
                        $('table#pos_table tbody').html('');
                        pos_total_row();
                    } else {
                        reset_pos_form();
                    }

                    $('input#hidden_price_group').val(curr_val);
                    $('select#price_group')
                        .val(curr_val)
                        .change();
                } else {
                    $('input#hidden_price_group').val(prev_value);
                    $('select#price_group')
                        .val(prev_value)
                        .change();
                }
            });
        }
    });

    //Quick add product
    $(document).on('click', 'button.pos_add_quick_product', function() {
        var url = $(this).data('href');
        var container = $(this).data('container');
        $.ajax({
            url: url + '?product_for=pos',
            dataType: 'html',
            success: function(result) {
                $(container)
                    .html(result)
                    .modal('show');
                $('.os_exp_date').datepicker({
                    autoclose: true,
                    format: 'dd-mm-yyyy',
                    clearBtn: true,
                });
            },
        });
    });

    $(document).on('change', 'form#quick_add_product_form input#single_dpp', function() {
        var unit_price = __read_number($(this));
        $('table#quick_product_opening_stock_table tbody tr').each(function() {
            var input = $(this).find('input.unit_price');
            __write_number(input, unit_price);
            input.change();
        });
    });

    $(document).on('quickProductAdded', function(e) {
        //Check if location is not set then show error message.
        if ($('input#location_id').val() == '') {
            toastr.warning(LANG.select_location);
        } else {
            pos_product_row(e.variation.id);
        }
    });

    $('div.view_modal').on('show.bs.modal', function() {
        __currency_convert_recursively($(this));
    });

    //Calculate area
    async function calculateArea(tr){
        var selected_option = tr.find('.sub_unit :selected');
        var type = selected_option.data('type');
        var quantity = tr.find('.pos_quantity').val();
        let output = true;

        if(type == 'area' || type == 'meter'){
            var unit_is_default = selected_option.data('is_default');
            var sub_unit_id = tr.find('.sub_unit').val();
            var product_id = tr.find('.product_id').val();
            var width = __read_number(tr.find('.width'));
            var height = __read_number(tr.find('.height'));
            var weight = __read_number(tr.find('.weight_hidden'));
            var price_group = $('#price_group').val();
            var default_sell_price = tr.find('.default_sell_price').val();

            let output = await $.ajax({
                method: 'POST',
                url: '/products/calculate-area',
                dataType: 'json',
                data: {
                    sub_unit_id : sub_unit_id,
                    product_id: product_id,
                    width : width,
                    height : height,
                    quantity : quantity,
                    weight: weight,
                    price_group: price_group,
                    default_sell_price: default_sell_price,
                },
                tryCount : 0,
                retryLimit : 1000,
                success: function(result) {
                    if (result.success) {
                        var data = result.data;
                        var discount_amount_max;

                        tr.find('.area_hidden').val(data.area);
                        __write_size(tr.find('.area'), data.area);

                        tr.find('.weight_hidden').val(data.weight);
                        __write_weight(tr.find('.weight'), data.weight);

                        if (unit_is_default == 0) {
                            tr.find('.default_sell_price').val(data.defaul_unit_price_by_plate);
                            __write_number(tr.find('.unit_price'), data.defaul_unit_price_by_plate);

                            tr.find('.pos_unit_price_inc_tax_hidden').val(data.defaul_unit_price_by_plate);
                            __write_number(tr.find('.pos_unit_price_inc_tax'), data.defaul_unit_price_by_plate);

                            discount_amount_max = data.defaul_unit_price_by_plate;
                            tr.find('.discount_amount_row').attr('data-max_value', data.defaul_unit_price_by_plate);
                        } else {
                            tr.find('.default_sell_price').val(data.default_unit_price);
                            __write_number(tr.find('.unit_price'), data.default_unit_price);

                            tr.find('.pos_unit_price_inc_tax_hidden').val(data.default_unit_price);
                            __write_number(tr.find('.pos_unit_price_inc_tax'), data.default_unit_price);

                            discount_amount_max = data.default_unit_price;
                        }

                        //Check line discount amount
                        tr.find('.discount_amount_row').attr('data-max_value', discount_amount_max);
                        var discount_amount_input = __read_number(tr.find('.discount_amount_row'));

                        if(discount_amount_input > discount_amount_max){
                            tr.find('.discount_amount_row').css('border-color', '#ff0000');
                            toastr.error(LANG.discount_amount_not_allow + (new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'VND' }).format(discount_amount_max)));
                        }else{
                            tr.find('.discount_amount_row').css('border-color', '#d2d6de');
                        }

                        if (this.tryCount > 0){
                            calculateAmount(tr);
                            pos_total_row();
                            adjustComboQty(tr);
                        }
                    }
                },
                error : function(xhr, textStatus, errorThrown ) {
                    this.tryCount++;
                    if (this.tryCount <= this.retryLimit) {
                        swal({
                            title: LANG.loss_internet_connect_title,
                            text: LANG.loss_internet_connect_message,
                            icon: 'warning',
                            showCancelButton: false,
                            showConfirmButton: false,
                            dangerMode: true,
                            // allowOutsideClick: false,
                        }).then(willDelete => {
                            $.ajax(this);
                            return;

                            /*if (willDelete) {
                                $.ajax(this);
                                return;
                            }*/
                        });
                    }
                    return;
                }
            });
        }else{
            tr.find('.area_hidden').val(0);
            __write_size(tr.find('.area'), 0);
        }

        // pos_total_row();
        return output;
    }

    function calculateAmount(tr){
        var sub_unit_id = tr.find('.sub_unit').val();
        var product_unit_id = tr.find('.product_unit_id').val();
        var width = tr.find('.width').val();
        var height = tr.find('.height').val();
        var quantity_element = tr.find('.pos_quantity');
        var discount_type = tr.find('.discount_type_row').val();
        var discount_amount = __read_number(tr.find('.discount_amount_row'));
        var type = tr.find('.sub_unit :selected').data('type');
        var unit_is_default = tr.find('.sub_unit :selected').data('is_default');

        //Update line total and check for quantity not greater than max quantity

        var entered_qty = __read_number(quantity_element);

        var line_total;

        var default_sell_price = __read_number(tr.find('.unit_price'));

        var unit_price_inc_discount = default_sell_price - discount_amount;

        __write_number(tr.find('span.pos_unit_price_inc_tax'), unit_price_inc_discount);

        if(type == 'area' || type == 'meter'){
            line_total = width * height * entered_qty * unit_price_inc_discount;
            if (unit_is_default == 0) {
                line_total = entered_qty * unit_price_inc_discount;
            }
        }else{
            line_total = entered_qty * unit_price_inc_discount;
        }
        line_total = round_init(line_total, 3)
        tr.find('input.pos_line_total').val(line_total);
        __write_number(tr.find('span.pos_line_total_text'), line_total);

        // pos_total_row();
        // adjustComboQty(tr);
    }

    function calculateAreaWithAmount(tr){
        calculateArea(tr).then(function(){
            calculateAmount(tr);
            pos_total_row();
            adjustComboQty(tr);
        });
    }

    $('table#pos_table').on('change keyup', '.width', function() {
        var tr = $(this).closest('tr');
        calculateAreaWithAmount(tr);
    });

    $('table#pos_table').on('change', '.height', function() {
        var tr = $(this).closest('tr');
        calculateAreaWithAmount(tr);
    });

    $('table#pos_table').on('change keyup', '.discount_amount_row', function() {
        var tr = $(this).closest('tr');
        calculateAreaWithAmount(tr);
    });

    $('table#pos_table tbody').on('change keyup', 'input.pos_quantity', function() {
        var tr = $(this).closest('tr');
        calculateAreaWithAmount(tr);
    });

    let productProperties = [];
    $('table#pos_table').on('change', 'select.sub_unit', function() {
        var tr = $(this).closest('tr');
        var base_unit_selling_price = tr.find('input.hidden_base_unit_sell_price').val();

        var selected_option = $(this).find(':selected');

        var multiplier = 1;
        if(selected_option.data('multiplier')){
            multiplier = parseFloat(selected_option.data('multiplier'));
        }

        var allow_decimal = parseInt(selected_option.data('allow_decimal'));

        tr.find('input.base_unit_multiplier').val(multiplier);

        var unit_sp = base_unit_selling_price * multiplier;

        var sp_element = tr.find('input.pos_unit_price');
        __write_number(sp_element, unit_sp);

        sp_element.change();

        var qty_element = tr.find('input.pos_quantity');
        var base_max_avlbl = qty_element.data('qty_available');
        var error_msg_line = 'pos_max_qty_error';
        var sub_unit_id = tr.find('.sub_unit').val();
        var product_unit_id = tr.find('.product_unit_id').val();
        var unit_price_by_square_meter = tr.find('.default_sell_price').val();
        var discount_amount = __read_number(tr.find('.discount_amount_row'));
        var unit_price_inc_discount_by_square_meter = unit_price_by_square_meter - discount_amount;

        //Check if select weight unit
        var type = selected_option.data('type');
        var is_default = selected_option.data('is_default');

        if(type == 'weight'){
            var weight_per_square_meter = __read_number(tr.find('.weight_per_square_meter_hidden'));
            var quantity = qty_element.val();
            var total_weight = quantity * multiplier;
            var total_area = total_weight / weight_per_square_meter;
            var unit_price_by_weight = unit_price_by_square_meter * total_area;
            var unit_price_inc_discount_by_weight = unit_price_by_weight - discount_amount;
            var total_price = unit_price_inc_discount_by_weight * total_area;

            tr.find('.width').hide();
            __write_weight(tr.find('.weight'), total_weight);
            __write_size(tr.find('.area'), total_area);
            __write_number(tr.find('.pos_line_total_text'), total_price);
            __write_number(tr.find('.unit_price'), unit_price_by_weight);
        }else{
            tr.find('.width').show();
            let productId = tr.find('.row_variation_id').val();
            if (sub_unit_id == product_unit_id) {
                if(typeof(productProperties[productId]) != "undefined") {
                    tr.find('.width').val((productProperties[productId].width));
                    tr.find('.height').val((productProperties[productId].height));
                }
                tr.find('.width').prop('readonly', false);
                tr.find('.height').prop('readonly', false);
                if (is_default == 0) {
                    tr.find('.width').prop('readonly', true);
                } else {
                    tr.find('.width').prop('readonly', false);
                }
            } else {
                let properties = {
                    height: tr.find('.height').val(),
                    width: tr.find('.width').val()
                };

                productProperties[productId] = properties;

                tr.find('.width').val(($(this).find(':selected').data('width')));
                tr.find('.height').val(($(this).find(':selected').data('height')));

                // tr.find('.width').val(($(this).find(':selected').data('width')).toFixed(2));
                // tr.find('.height').val(($(this).find(':selected').data('height')).toFixed(2));

                tr.find('.width').prop('readonly', true);
                tr.find('.height').prop('readonly', true);

                if ((type == 'area' || type == 'meter') && is_default == 1) {
                    tr.find('.width').val((properties.width));
                    tr.find('.width').prop('readonly', false);
                }
            }

            __write_number(tr.find('.unit_price'), unit_price_by_square_meter);
        }

        calculateAreaWithAmount(tr);

        if (tr.find('select.lot_number').length > 0) {
            var lot_select = tr.find('select.lot_number');
            if (lot_select.val()) {
                base_max_avlbl = lot_select.find(':selected').data('qty_available');
                error_msg_line = 'lot_max_qty_error';
            }
        }

        qty_element.attr('data-decimal', allow_decimal);
        var abs_digit = true;
        if (allow_decimal) {
            abs_digit = false;
        }
        qty_element.rules('add', {
            abs_digit: abs_digit,
        });

        if (base_max_avlbl) {
            var max_avlbl = parseFloat(base_max_avlbl) / multiplier;
            var formated_max_avlbl = __number_f(max_avlbl);
            var unit_name = selected_option.data('unit_name');
            var max_err_msg = __translate(error_msg_line, {
                max_val: formated_max_avlbl,
                unit_name: unit_name,
            });
            qty_element.attr('data-rule-max-value', max_avlbl);
            qty_element.attr('data-msg-max-value', max_err_msg);
            qty_element.rules('add', {
                'max-value': max_avlbl,
                messages: {
                    'max-value': max_err_msg,
                },
            });
            qty_element.trigger('change');
        }
    });

    //Confirmation before page load.
    window.onbeforeunload = function() {
        if($('form#edit_pos_sell_form').length == 0){
            if($('table#pos_table tbody tr').length > 0) {
                return LANG.sure;
            } else {
                return null;
            }
        }
    }
    $(window).resize(function() {
        var win_height = $(window).height();
        div_height = __calculate_amount('percentage', 63, win_height);
        $('div.pos_product_div').css('min-height', div_height + 'px');
        $('div.pos_product_div').css('max-height', div_height + 'px');
    });

    //Used for weighing scale barcode
    $('#weighing_scale_modal').on('shown.bs.modal', function (e) {

        //Attach the scan event
        onScan.attachTo(document, {
            suffixKeyCodes: [13], // enter-key expected at the end of a scan
            reactToPaste: true, // Compatibility to built-in scanners in paste-mode (as opposed to keyboard-mode)
            onScan: function(sCode, iQty) {
                console.log('Scanned: ' + iQty + 'x ' + sCode);
                // $('input#weighing_scale_barcode').val(sCode);
                $('button#weighing_scale_submit').trigger('click');
            },
            onScanError: function(oDebug) {
                // console.log(oDebug);
            },
            minLength: 2
            // onKeyDetect: function(iKeyCode){ // output all potentially relevant key events - great for debugging!
            //     console.log('Pressed: ' + iKeyCode);
            // }
        });

        $('input#weighing_scale_barcode').focus();
    });

    $('#weighing_scale_modal').on('hide.bs.modal', function (e) {
        //Detach from the document once modal is closed.
        onScan.detachFrom(document);
    });

    $('button#weighing_scale_submit').click(function(){

        var price_group = '';
        if ($('#price_group').length > 0) {
            price_group = $('#price_group').val();
        }

        if($('#weighing_scale_barcode').val().length > 0){
            pos_product_row(null, null, $('#weighing_scale_barcode').val());
            $('#weighing_scale_modal').modal('hide');
            $('input#weighing_scale_barcode').val('');
        } else{
            $('input#weighing_scale_barcode').focus();
        }
    });
});

function get_product_suggestion_list(category_id, brand_id, location_id, url = null) {

    if($('div#product_list_body').length == 0) {
        return false;
    }

    if (url == null) {
        url = '/sells/pos/get-product-suggestion';
    }
    $('#suggestion_page_loader').fadeIn(700);
    var page = $('input#suggestion_page').val();
    if (page == 1) {
        $('div#product_list_body').html('');
    }
    if ($('div#product_list_body').find('input#no_products_found').length > 0) {
        $('#suggestion_page_loader').fadeOut(700);
        return false;
    }
    $.ajax({
        method: 'GET',
        url: url,
        data: {
            category_id: category_id,
            brand_id: brand_id,
            location_id: location_id,
            page: page,
        },
        dataType: 'html',
        success: function(result) {
            $('div#product_list_body').append(result);
            $('#suggestion_page_loader').fadeOut(700);
        },
    });
}

//Get recent transactions
function get_recent_transactions(status, element_obj) {
    if (element_obj.length == 0) {
        return false;
    }

    $.ajax({
        method: 'GET',
        url: '/sells/pos/get-recent-transactions',
        data: { status: status },
        dataType: 'html',
        success: function(result) {
            element_obj.html(result);
            __currency_convert_recursively(element_obj);
        },
    });
}

//global variable
var resultAjaxAddProduct = null;

//variation_id is null when weighing_scale_barcode is used.
function pos_product_row(variation_id = null, purchase_line_id = null, weighing_scale_barcode = null) {

    //Get item addition method
    var item_addtn_method = 0;
    var add_via_ajax = true;

    if (variation_id != null && $('#item_addition_method').length) {
        item_addtn_method = $('#item_addition_method').val();
    }

    if (item_addtn_method == 0) {
        add_via_ajax = true;
    } else {
        var is_added = false;

        //Search for variation id in each row of pos table
        $('#pos_table tbody')
            .find('tr')
            .each(function() {
                var row_v_id = $(this)
                    .find('.row_variation_id')
                    .val();
                var enable_sr_no = $(this)
                    .find('.enable_sr_no')
                    .val();
                var modifiers_exist = false;
                if ($(this).find('input.modifiers_exist').length > 0) {
                    modifiers_exist = true;
                }

                if (
                    row_v_id == variation_id &&
                    enable_sr_no !== '1' &&
                    !modifiers_exist &&
                    !is_added
                ) {
                    add_via_ajax = false;
                    is_added = true;

                    //Increment product quantity
                    qty_element = $(this).find('.pos_quantity');
                    var qty = __read_number(qty_element);
                    __write_number(qty_element, qty + 1);
                    qty_element.change();

                    round_row_to_iraqi_dinnar($(this));

                    $('input#search_product')
                        .focus()
                        .select();
                }
            });
    }

    if (add_via_ajax) {
        var product_row = $('input#product_row_count').val();
        var location_id = $('input#location_id').val();
        var customer_id = $('select#customer_id').val();
        var is_direct_sell = false;
        if (
            $('input[name="is_direct_sale"]').length > 0 &&
            $('input[name="is_direct_sale"]').val() == 1
        ) {
            is_direct_sell = true;
        }

        var price_group = '';
        if ($('#price_group').val()) {
            price_group = parseInt($('#price_group').val());
        }

        //If default price group present
        if ($('#default_price_group').length > 0 &&
            !price_group) {
            price_group = $('#default_price_group').val();
        }

        //If types of service selected give more priority
        if ($('#types_of_service_price_group').length > 0 &&
            $('#types_of_service_price_group').val()) {
            price_group = $('#types_of_service_price_group').val();
        }

        $.ajax({
            method: 'GET',
            url: '/sells/pos/get_product_row/' + variation_id + '/' + location_id,
            async: false,
            data: {
                product_row: product_row,
                customer_id: customer_id,
                is_direct_sell: is_direct_sell,
                price_group: price_group,
                purchase_line_id: purchase_line_id,
                weighing_scale_barcode: weighing_scale_barcode
            },
            dataType: 'json',
            success: function(result) {
                if (result.success) {
                    $('table#pos_table tbody')
                        .append(result.html_content)
                        .find('input.pos_quantity');
                    //increment row count
                    $('input#product_row_count').val(parseInt(product_row) + 1);
                    var this_row = $('table#pos_table tbody')
                        .find('tr')
                        .last();
                    pos_each_row(this_row);

                    //Check if is service order
                    var selected_option = this_row.find('.sub_unit :selected');
                    var type = selected_option.data('type');
                    if(type == 'service'){
                        $('#is_service_order').val(1);
                    }

                    //For initial discount if present
                    var line_total = __read_number(this_row.find('input.pos_line_total'));
                    this_row.find('span.pos_line_total_text').text(__currency_trans_from_en(line_total, false));

                    pos_total_row();
                    update_table_sr_number();

                    //Check if multipler is present then multiply it when a new row is added.
                    if(__getUnitMultiplier(this_row) > 1){
                        this_row.find('select.sub_unit').trigger('change');
                    }

                    if (result.enable_sr_no == '1') {
                        var new_row = $('table#pos_table tbody')
                            .find('tr')
                            .last();
                        new_row.find('.add-pos-row-description').trigger('click');
                    }

                    round_row_to_iraqi_dinnar(this_row);
                    __currency_convert_recursively(this_row);

                    $('input#search_product')
                        .focus()
                        .select();

                    //Used in restaurant module
                    if (result.html_modifier) {
                        $('table#pos_table tbody')
                            .find('tr')
                            .last()
                            .find('td:first')
                            .append(result.html_modifier);
                    }

                    //scroll bottom of items list
                    $(".pos_product_div").animate({ scrollTop: $('.pos_product_div').prop("scrollHeight")}, 1000);
                    getFormatNumber();
                } else {
                    toastr.error(result.msg);
                    $('input#search_product')
                        .focus()
                        .select();
                }
            },
        });
    }
}

function cbAddProduct(result){
    if(typeof result == "undefined"){
        return false;
    }
    if(result == null){
        return false;
    }
    var product_row = $('input#product_row_count').val();

    $('table#pos_table tbody')
        .append(result.html_content)
        .find('input.pos_quantity');

    //increment row count
    $('input#product_row_count').val(parseInt(product_row) + 1);
    var this_row = $('table#pos_table tbody')
        .find('tr')
        .last();
    pos_each_row(this_row);
    update_table_sr_number();

    //For initial discount if present
    var line_total = __read_number(this_row.find('input.pos_line_total'));
    this_row.find('span.pos_line_total_text').text(line_total);

    // pos_total_row();

    //Check if multipler is present then multiply it when a new row is added.
    if(__getUnitMultiplier(this_row) > 1){
        this_row.find('select.sub_unit').trigger('change');
    }

    if (result.enable_sr_no == '1') {
        var new_row = $('table#pos_table tbody')
            .find('tr')
            .last();
        new_row.find('.add-pos-row-description').trigger('click');
    }

    round_row_to_iraqi_dinnar(this_row);
    __currency_convert_recursively(this_row);

    $('input#search_product')
        .focus()
        .select();

    //Used in restaurant module
    if (result.html_modifier) {
        $('table#pos_table tbody')
            .find('tr')
            .last()
            .find('td:first')
            .append(result.html_modifier);
    }

    //scroll bottom of items list
    $(".pos_product_div").animate({ scrollTop: $('.pos_product_div').prop("scrollHeight")}, 1000);

    //trigger onchange
    $("body").find('#elementTriggerChangeOnLoad').change();
    var inputNumberRo = $(".quantity-up").closest('.input-number').find('input');
    if(inputNumberRo.length > 0){
        inputNumberRo.change();
    }
    resultAjaxAddProduct = null;
}

function retainProduct() {
    cbAddProduct(resultAjaxAddProduct);
    $("#modalSuggestProduct").find(".btn-retain-product").off('click', retainProduct);
}

function setProductSuggest($elm) {
    $("#modalSuggestProduct").modal('hide');
    $("body").find(".modal-backdrop").remove();

    var liElm = $elm.closest('li');
    var product_id = liElm.attr('data-product-id');
    var purchase_line_id = liElm.attr('data-purchase-line-id');

    if(purchase_line_id == "null"){
        purchase_line_id = null;
    }

    $('input#product_row_count').val()

    pos_product_row(product_id, purchase_line_id);

}

//Update values for each row
function pos_each_row(row_obj) {
    var unit_price = __read_number(row_obj.find('input.pos_unit_price'));

    var discounted_unit_price = calculate_discounted_unit_price(row_obj);
    var tax_rate = row_obj
        .find('select.tax_id')
        .find(':selected')
        .data('rate');

    // console.log(discounted_unit_price);

    var unit_price_inc_tax =
        discounted_unit_price + __calculate_amount('percentage', tax_rate, discounted_unit_price);
    __write_number(row_obj.find('input.pos_unit_price_inc_tax'), unit_price_inc_tax);

    var discount = __read_number(row_obj.find('input.row_discount_amount'));

    if (discount > 0) {
        var qty = __read_number(row_obj.find('input.pos_quantity'));
        var line_total = qty * unit_price_inc_tax;
        __write_number(row_obj.find('input.pos_line_total'), line_total);
    }

    //var unit_price_inc_tax = __read_number(row_obj.find('input.pos_unit_price_inc_tax'));

    __write_number(row_obj.find('input.item_tax'), unit_price_inc_tax - discounted_unit_price);
}

function pos_total_row() {
    var total_quantity = 0;
    var price_total = 0;

    $('table#pos_table tbody tr').each(function() {
        var selected_option = $(this).find('.sub_unit :selected');
        var type = selected_option.data('type');
        if(type == 'service'){
            price_total += __read_number($(this).find('input.line_total_service'))
        }else{
            total_quantity += __read_number($(this).find('input.area_hidden'));
            price_total += __read_number($(this).find('input.pos_line_total'));
        }
    });

    //Go through the modifier prices.
    $('input.modifiers_price').each(function() {
        price_total += __read_number($(this));
    });

    //updating shipping charges
    $('span#shipping_charges_amount').text(
        __currency_trans_from_en(__read_number($('input#shipping_charges_modal')), false)
    );

    $('span.total_quantity').each(function() {
        __write_size($(this), total_quantity);
    });

    price_total = Math.round(price_total/1000)*1000;

    $('span.price_total').html(__currency_trans_from_en(price_total, true));
    calculate_billing_details(price_total);
}

function calculate_billing_details(price_total) {
    price_total = parseInt(price_total);
    var discount = pos_discount(price_total);
    $('#total_discount').text(__currency_trans_from_en(discount));
    if ($('#reward_point_enabled').length) {
        total_customer_reward = $('#rp_redeemed_amount').val();
        discount = parseFloat(discount) + parseFloat(total_customer_reward);

        if ($('input[name="is_direct_sale"]').length <= 0) {
            $('span#total_discount').text(__currency_trans_from_en(discount, false));
        }
    }

    // var order_tax = pos_order_tax(price_total, discount);
    // $('#order_tax').val((order_tax));
    var order_tax = __read_number($('input#order_tax'));

    //Add shipping charges.
    var shipping_charges = __read_number($('input#shipping_charges'));
    $('#shipping_charges_text').text(__currency_trans_from_en(shipping_charges));

    //Add packaging charge
    var packing_charge = 0;
    if ($('#types_of_service_id').length > 0 &&
        $('#types_of_service_id').val()) {
        packing_charge = __calculate_amount($('#packing_charge_type').val(),
            __read_number($('input#packing_charge')), price_total);

        $('#packing_charge_text').text(__currency_trans_from_en(packing_charge, false));
    }

    var total_payable = price_total + order_tax - discount + shipping_charges + packing_charge;

    var rounding_multiple = $('#amount_rounding_method').val() ? parseFloat($('#amount_rounding_method').val()) : 0;
    var round_off_data = __round(parseInt(total_payable), rounding_multiple);
    var total_payable_rounded = round_off_data.number;


    var round_off_amount = round_off_data.diff;
    if (round_off_amount != 0) {
        $('span#round_off_text').text(__currency_trans_from_en(round_off_amount, false));
    } else {
        $('span#rounded_by_text').text(0);
    }
    $('input#round_off_amount').val(round_off_amount);

    $('input#final_total_input').val(total_payable_rounded);
    var curr_exchange_rate = 1;
    if ($('#exchange_rate').length > 0 && $('#exchange_rate').val()) {
        curr_exchange_rate = __read_number($('#exchange_rate'));
    }
    var shown_total = total_payable_rounded * curr_exchange_rate;
    shown_total = Math.round(shown_total/1000)*1000;
    $('span#total_payable').text(__currency_trans_from_en(shown_total, true));

    $('span.total_payable_span').text(__currency_trans_from_en(total_payable_rounded, true));

    //Show total payable include deposit & cod
    var deposit_amount = __read_number($('#deposit_amount'));
    var cod_amount = __read_number($('#cod_amount'));
    var total_include_deposit_cod = shown_total - deposit_amount - cod_amount;
    total_include_deposit_cod = Math.round(total_include_deposit_cod/1000)*1000;
    $('span#total_payable_include_deposit_cod').text(__currency_trans_from_en(total_include_deposit_cod, true));

    $(document).trigger('invoice_total_calculated');

    calculate_balance_due();
}

function pos_discount(total_amount) {
    var calculation_type = 'fixed';
    var calculation_amount = __read_number($('#discount_amount'));

    var discount = __calculate_amount(calculation_type, calculation_amount, total_amount);

    $('span#total_discount').text(__currency_trans_from_en(discount, false));

    return discount;
}

function pos_order_tax(price_total, discount) {
    var tax_rate_id = $('#tax_rate_id').val();
    var calculation_type = 'percentage';
    var calculation_amount = __read_number($('#tax_calculation_amount'));
    var total_amount = price_total - discount;

    var rate = $( "#tax_rate_id option:selected" ).data('rate');

    if (tax_rate_id) {
        // var order_tax = __calculate_amount(calculation_type, calculation_amount, total_amount);
        var order_tax = total_amount * (rate / 100);
    } else {
        var order_tax = 0;
    }

    $('span#order_tax').text(__currency_trans_from_en(order_tax, false));

    return order_tax;
}

function calculate_balance_due() {
    var total_payable = __read_number($('#final_total_input'));
    var total_paying = 0;
    $('#payment_rows_div')
        .find('.payment-amount')
        .each(function() {
            if (parseFloat($(this).val())) {
                total_paying += __read_number($(this));
            }
        });
    var bal_due = total_payable - total_paying;
    var change_return = 0;

    //change_return
    if (bal_due < 0 || Math.abs(bal_due) < 0.05) {
        __write_number($('input#change_return'), bal_due * -1);
        $('span.change_return_span').text(__currency_trans_from_en(bal_due * -1, true));
        change_return = bal_due * -1;
        bal_due = 0;
    } else {
        __write_number($('input#change_return'), 0);
        $('span.change_return_span').text(__currency_trans_from_en(0, true));
        change_return = 0;
    }

    __write_number($('input#total_paying_input'), total_paying);
    $('span.total_paying').text(__currency_trans_from_en(total_paying, true));

    __write_number($('input#in_balance_due'), bal_due);
    $('span.balance_due').text(__currency_trans_from_en(bal_due, true));

    __highlight(bal_due * -1, $('span.balance_due'));
    __highlight(change_return * -1, $('span.change_return_span'));
}

function isValidPosForm() {
    flag = true;
    $('span.error').remove();

    if ($('select#customer_id').val() == null) {
        flag = false;
        error = '<span class="error">' + LANG.required + '</span>';
        $(error).insertAfter($('select#customer_id').parent('div'));
    }

    if ($('tr.product_row').length == 0) {
        flag = false;
        error = '<span class="error">' + LANG.no_products + '</span>';
        $(error).insertAfter($('input#search_product').parent('div'));
    }

    return flag;
}

function reset_pos_form(){

    //If on edit page then redirect to Add POS page
    if($('form#edit_pos_sell_form').length > 0){
        setTimeout(function() {
            window.location = $("input#pos_redirect_url").val();
        }, 4000);
        return true;
    }

    if(pos_form_obj[0]){
        pos_form_obj[0].reset();
    }
    if(sell_form[0]){
        sell_form[0].reset();
    }
    set_default_customer();
    set_location();

    $('tr.product_row').remove();
    $('span.total_quantity, span.price_total, span#total_discount, span#order_tax, span#total_payable, span#total_payable_include_deposit_cod, span#shipping_charges_amount').text(0);
    $('span.total_payable_span', 'span.total_paying', 'span.balance_due').text(0);

    $('#modal_payment').find('.remove_payment_row').each( function(){
        $(this).closest('.payment_row').remove();
    });

    //Reset discount
    __write_number($('input#discount_amount'), $('input#discount_amount').data('default'));
    $('input#discount_type').val($('input#discount_type').data('default'));

    //Reset tax rate
    $('input#tax_rate_id').val($('input#tax_rate_id').data('default'));
    __write_number($('input#tax_calculation_amount'), $('input#tax_calculation_amount').data('default'));

    $('select.payment_types_dropdown').val('').trigger('change');
    $('#price_group').trigger('change');

    //Reset shipping
    __write_number($('input#shipping_charges'), $('input#shipping_charges').data('default'));
    $('input#shipping_details').val($('input#shipping_details').data('default'));

    if($('input#is_recurring').length > 0){
        $('input#is_recurring').iCheck('update');
    };

    $(document).trigger('sell_form_reset');
}

function set_default_customer() {
    var default_customer_id = $('#default_customer_id').val();
    var default_customer_name = $('#default_customer_name').val();
    var exists = $('select#customer_id option[value=' + default_customer_id + ']').length;
    if (exists == 0) {
        $('select#customer_id').append(
            $('<option>', { value: default_customer_id, text: default_customer_name })
        );
    }

    $('select#customer_id')
        .val(default_customer_id)
        .trigger('change');

    customer_set = true;
}

//Set the location and initialize printer
function set_location() {
    if ($('select#select_location_id').length == 1) {
        $('input#location_id').val($('select#select_location_id').val());
        $('input#location_id').data(
            'receipt_printer_type',
            $('select#select_location_id')
                .find(':selected')
                .data('receipt_printer_type')
        );
    }

    if ($('input#location_id').val()) {
        $('input#search_product')
            .prop('disabled', false)
            .focus();
        $('#plate_stock_button').prop('disabled', false);
    } else {
        $('input#search_product').prop('disabled', true);
        $('#plate_stock_button').prop('disabled', true);
    }

    initialize_printer();
}

function initialize_printer() {
    if ($('input#location_id').data('receipt_printer_type') == 'printer') {
        initializeSocket();
    }
}

$('body').on('click', 'label', function(e) {
    var field_id = $(this).attr('for');
    if (field_id) {
        if ($('#' + field_id).hasClass('select2')) {
            $('#' + field_id).select2('open');
            return false;
        }
    }
});

$('body').on('focus', 'select', function(e) {
    var field_id = $(this).attr('id');
    if (field_id) {
        if ($('#' + field_id).hasClass('select2')) {
            $('#' + field_id).select2('open');
            return false;
        }
    }
});

function round_row_to_iraqi_dinnar(row) {
    if (iraqi_selling_price_adjustment) {
        var element = row.find('input.pos_unit_price_inc_tax');
        var unit_price = round_to_iraqi_dinnar(__read_number(element));
        __write_number(element, unit_price);
        element.change();
    }
}

function pos_print(receipt) {
    //If printer type then connect with websocket
    if (receipt.print_type == 'printer') {
        var content = receipt;
        content.type = 'print-receipt';

        //Check if ready or not, then print.
        if (socket != null && socket.readyState == 1) {
            socket.send(JSON.stringify(content));
        } else {
            initializeSocket();
            setTimeout(function() {
                socket.send(JSON.stringify(content));
            }, 700);
        }

    } else if (receipt.html_content != '') {
        //If printer type browser then print content
        $('#receipt_section').html(receipt.html_content);
        __currency_convert_recursively($('#receipt_section'));
        __print_receipt('receipt_section');
    }
}

function calculate_discounted_unit_price(row) {
    var this_unit_price = __read_number(row.find('input.pos_unit_price'));
    var row_discounted_unit_price = this_unit_price;
    var row_discount_type = row.find('select.row_discount_type').val();
    var row_discount_amount = __read_number(row.find('input.row_discount_amount'));
    if (row_discount_amount) {
        if (row_discount_type == 'fixed') {
            row_discounted_unit_price = this_unit_price - row_discount_amount;
        } else {
            row_discounted_unit_price = __substract_percent(this_unit_price, row_discount_amount);
        }
    }

    // console.log('1233', row_discounted_unit_price);
    return row_discounted_unit_price;
}

function get_unit_price_from_discounted_unit_price(row, discounted_unit_price) {
    var this_unit_price = discounted_unit_price;
    var row_discount_type = row.find('select.row_discount_type').val();
    var row_discount_amount = __read_number(row.find('input.row_discount_amount'));
    if (row_discount_amount) {
        if (row_discount_type == 'fixed') {
            this_unit_price = discounted_unit_price + row_discount_amount;
        } else {
            this_unit_price = __get_principle(discounted_unit_price, row_discount_amount, true);
        }
    }

    return this_unit_price;
}

//Update quantity if line subtotal changes
$('table#pos_table tbody').on('change', 'input.pos_line_total', function() {
    var subtotal = __read_number($(this));
    var tr = $(this).parents('tr');
    var quantity_element = tr.find('input.pos_quantity');
    var unit_price_inc_tax = __read_number(tr.find('input.pos_unit_price_inc_tax'));
    var quantity = subtotal / unit_price_inc_tax;
    __write_number(quantity_element, quantity);

    if (sell_form_validator) {
        sell_form_validator.element(quantity_element);
    }
    if (pos_form_validator) {
        pos_form_validator.element(quantity_element);
    }
    tr.find('span.pos_line_total_text').text(__currency_trans_from_en(subtotal, true));

    pos_total_row();
});

$('div#product_list_body').on('scroll', function() {
    if ($(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight) {
        var page = parseInt($('#suggestion_page').val());
        page += 1;
        $('#suggestion_page').val(page);
        var location_id = $('input#location_id').val();
        var category_id = $('select#product_category').val();
        var brand_id = $('select#product_brand').val();

        get_product_suggestion_list(category_id, brand_id, location_id);
    }
});

$(document).on('ifChecked', '#is_recurring', function() {
    $('#recurringInvoiceModal').modal('show');
});

$(document).on('shown.bs.modal', '#recurringInvoiceModal', function() {
    $('input#recur_interval').focus();
});

$(document).on('click', '#select_all_service_staff', function() {
    var val = $('#res_waiter_id').val();
    $('#pos_table tbody')
        .find('select.order_line_service_staff')
        .each(function() {
            $(this)
                .val(val)
                .change();
        });
});

$(document).on('click', '.print-invoice-link', function(e) {
    e.preventDefault();
    $.ajax({
        url: $(this).attr('href') + "?check_location=true",
        dataType: 'json',
        success: function(result) {
            if (result.success == 1) {
                //Check if enabled or not
                if (result.receipt.is_enabled) {
                    pos_print(result.receipt);
                }
            } else {
                toastr.error(result.msg);
            }

        },
    });
});

function getCustomerRewardPoints() {
    if ($('#reward_point_enabled').length <= 0) {
        return false;
    }
    var is_edit = $('form#edit_sell_form').length ||
    $('form#edit_pos_sell_form').length ? true : false;
    if (is_edit && !customer_set) {
        return false;
    }

    var customer_id = $('#customer_id').val();

    $.ajax({
        method: 'POST',
        url: '/sells/pos/get-reward-details',
        data: {
            customer_id: customer_id
        },
        dataType: 'json',
        success: function(result) {
            $('#available_rp').text(result.points);
            $('#rp_redeemed_modal').data('max_points', result.points);
            updateRedeemedAmount();
            $('#rp_redeemed_amount').change()
        },
    });
}

function updateRedeemedAmount(argument) {
    var points = $('#rp_redeemed_modal').val().trim();
    points = points == '' ? 0 : parseInt(points);
    var amount_per_unit_point = parseFloat($('#rp_redeemed_modal').data('amount_per_unit_point'));
    var redeemed_amount = points * amount_per_unit_point;
    $('#rp_redeemed_amount_text').text(__currency_trans_from_en(redeemed_amount, true));
    $('#rp_redeemed').val(points);
    $('#rp_redeemed_amount').val(redeemed_amount);
}

$(document).on('change', 'select#customer_id', function(){
    var default_customer_id = $('#default_customer_id').val();
    if ($(this).val() == default_customer_id) {
        //Disable reward points for walkin customers
        if ($('#rp_redeemed_modal').length) {
            $('#rp_redeemed_modal').val('');
            $('#rp_redeemed_modal').change();
            $('#rp_redeemed_modal').attr('disabled', true);
            $('#available_rp').text('');
            updateRedeemedAmount();
            pos_total_row();
        }
    } else {
        if ($('#rp_redeemed_modal').length) {
            $('#rp_redeemed_modal').removeAttr('disabled');
        }
        getCustomerRewardPoints();
    }
});

$(document).on('change', '#rp_redeemed_modal', function(){
    var points = $(this).val().trim();
    points = points == '' ? 0 : parseInt(points);
    var amount_per_unit_point = parseFloat($(this).data('amount_per_unit_point'));
    var redeemed_amount = points * amount_per_unit_point;
    $('#rp_redeemed_amount_text').text(__currency_trans_from_en(redeemed_amount, true));
    var reward_validation = isValidatRewardPoint();
    if (!reward_validation['is_valid']) {
        toastr.error(reward_validation['msg']);
        $('#rp_redeemed_modal').select();
    }
});

$(document).on('change', '.direct_sell_rp_input', function(){
    updateRedeemedAmount();
    pos_total_row();
});

function isValidatRewardPoint() {
    var element = $('#rp_redeemed_modal');
    var points = element.val().trim();
    points = points == '' ? 0 : parseInt(points);

    var max_points = parseInt(element.data('max_points'));
    var is_valid = true;
    var msg = '';

    if (points == 0) {
        return {
            is_valid: is_valid,
            msg: msg
        }
    }

    var rp_name = $('input#rp_name').val();
    if (points > max_points) {
        is_valid = false;
        msg = __translate('max_rp_reached_error', {max_points: max_points, rp_name: rp_name});
    }

    var min_order_total_required = parseFloat(element.data('min_order_total'));

    var order_total = __read_number($('#final_total_input'));

    if (order_total < min_order_total_required) {
        is_valid = false;
        msg = __translate('min_order_total_error', {min_order: __currency_trans_from_en(min_order_total_required, true), rp_name: rp_name});
    }

    var output = {
        is_valid: is_valid,
        msg: msg,
    }

    return output;
}

function adjustComboQty(tr){
    if(tr.find('input.product_type').val() == 'combo'){
        var qty = __read_number(tr.find('input.pos_quantity'));
        var multiplier = __getUnitMultiplier(tr);

        tr.find('input.combo_product_qty').each(function(){
            $(this).val($(this).data('unit_quantity') * qty * multiplier);
        });
    }
}

$(document).on('change', '#types_of_service_id', function(){
    var types_of_service_id = $(this).val();
    var location_id = $('#location_id').val();

    if(types_of_service_id) {
        $.ajax({
            method: 'POST',
            url: '/sells/pos/get-types-of-service-details',
            data: {
                types_of_service_id: types_of_service_id,
                location_id: location_id
            },
            dataType: 'json',
            success: function(result) {
                //reset form if price group is changed
                var prev_price_group = $('#types_of_service_price_group').val();
                // console.log(prev_price_group);
                // console.log(result.price_group_id);
                // console.log(prev_price_group != result.price_group_id);
                if (prev_price_group != result.price_group_id) {
                    if ($('form#edit_pos_sell_form').length > 0) {
                        $('table#pos_table tbody').html('');
                        pos_total_row();
                    } else {
                        reset_pos_form();
                    }
                }

                if(result.price_group_id) {
                    $('#types_of_service_price_group').val(result.price_group_id);
                    $('#price_group_text').removeClass('hide');
                    $('#price_group_text span').text(result.price_group_name);
                } else {
                    $('#types_of_service_price_group').val('');
                    $('#price_group_text').addClass('hide');
                    $('#price_group_text span').text('');
                }
                $('#types_of_service_id').val(types_of_service_id);
                $('.types_of_service_modal').html(result.modal_html);
                $('.types_of_service_modal').modal('show');
            },
        });
    } else {
        $('.types_of_service_modal').html('');
        $('#types_of_service_price_group').val('');
        $('#price_group_text').addClass('hide');
        $('#price_group_text span').text('');

        if ($('form#edit_pos_sell_form').length > 0) {
            $('table#pos_table tbody').html('');
            pos_total_row();
        } else {
            reset_pos_form();
        }
    }
});

$(document).on('change', 'input#packing_charge', function() {
    pos_total_row();
});

$(document).on('click', '.service_modal_btn', function(e) {
    if ($('#types_of_service_id').val()) {
        $('.types_of_service_modal').modal('show');
    }
});

$(document).on('change', '.payment_types_dropdown', function(e) {
    var default_accounts = $('select#select_location_id').length ?
        $('select#select_location_id')
            .find(':selected')
            .data('default_payment_accounts') : $('#location_id').data('default_accounts');
    var payment_type = $(this).val();
    if (payment_type) {
        var default_account = default_accounts && default_accounts[payment_type]['account'] ?
            default_accounts[payment_type]['account'] : '';
        var payment_row = $(this).closest('.payment_row');
        var row_index = payment_row.find('.payment_row_index').val();

        var account_dropdown = payment_row.find('select#account_' + row_index);
        if (account_dropdown.length && default_accounts) {
            account_dropdown.val(default_account);
            account_dropdown.change();
        }
    }
});

$(document).on('show.bs.modal', '#recent_transactions_modal', function () {
    get_recent_transactions('final', $('div#tab_final'));
});
$(document).on('shown.bs.tab', 'a[href="#tab_quotation"]', function () {
    get_recent_transactions('quotation', $('div#tab_quotation'));
});
$(document).on('shown.bs.tab', 'a[href="#tab_draft"]', function () {
    get_recent_transactions('draft', $('div#tab_draft'));
});

function disable_pos_form_actions(){
    $('div.pos-processing').show();
    $('#pos-save').attr('disabled', 'true');
    $('div.pos-form-actions').find('button').attr('disabled', 'true');
}

function enable_pos_form_actions(){
    $('div.pos-processing').hide();
    $('#pos-save').removeAttr('disabled');
    $('div.pos-form-actions').find('button').removeAttr('disabled');
}

function update_table_sr_number() {
    var sr_number = 1;
    $('table#pos_table tbody')
        .find('.sr_number')
        .each(function() {
            $(this).text(sr_number);
            sr_number++;
        });
}

update_table_sr_number();
