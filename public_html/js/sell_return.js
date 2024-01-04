$(document).ready(function() {
    /*$('form#sell_return_form').validate();
    update_sell_return_total();

    $(document).on('change keyup', 'input.return_qty, #discount_amount, #discount_type', function(){
        update_sell_return_total()
    });*/

    $(document).on('change', 'input.return_qty', function(){
        var tr = $(this).closest('tr');
        var quantity_element = $(this);
        var row_index = tr.data('row_index');
        var width = __read_number(tr.find('.width'));
        var height = __read_number(tr.find('.height'));
        var quantity = __read_number(quantity_element);
        var old_area = parseFloat(tr.find('.old_area').html());
        var new_area = width * height * quantity;
        var max_quantity = old_area /  (width * height);

        if (quantity > 0) {
            tr.find('.warehouse_id').attr('required', true)
        } else {
            tr.find('.warehouse_id').attr('required', false)
        }

        if(new_area > old_area){
            quantity_element.css('border-color', '#f00');
            $('#sell_return_submit').attr('disabled', true);
            toastr.error(__translate('return_quantity_not_available', {quantity: max_quantity}));
            quantity_element.focus();
        }else{
            quantity_element.css('border-color', '#d2d6de');
            $('#sell_return_submit').removeAttr('disabled');
        }
    });

    function update_sell_return_total(){
        var net_return = 0;
        $('table#sell_return_table tbody tr').each( function(){
            var width = __read_number($(this).find('.width'));
            var height = __read_number($(this).find('.height'));
            var quantity = __read_number($(this).find('input.return_qty'));
            var unit_price = __read_number($(this).find('input.unit_price'));
            var subtotal = width * height * quantity * unit_price;
            $(this).find('.return_subtotal').text(__currency_trans_from_en(subtotal, true));
            net_return += subtotal;
        });
        var discount = 0;
        if($('#discount_type').val() == 'fixed'){
            discount = __read_number($("#discount_amount"));
        } else if($('#discount_type').val() == 'percentage'){
            var discount_percent = __read_number($("#discount_amount"));
            discount = __calculate_amount('percentage', discount_percent, net_return);
        }
        var discounted_net_return = net_return - discount;

        var tax_percent = $('input#tax_percent').val();
        var total_tax = __calculate_amount('percentage', tax_percent, discounted_net_return);
        var net_return_inc_tax = Math.round((total_tax + discounted_net_return)/1000)*1000;

        $('input#tax_amount').val(total_tax);
        $('span#total_return_discount').text(__currency_trans_from_en(discount, true));
        $('span#total_return_tax').text(__currency_trans_from_en(total_tax, true));
        $('span#net_return').text(__currency_trans_from_en(net_return_inc_tax, true));
    }

    function sell_return_total(tr, discount_type, discount_amount) {
        var net_amount = 0;
        var net_amount_discount = 0;

        $('table#sell_return_table tbody tr').each(function() {
            var new_total_price = __read_number($(this).find('span.new_total_price'));
            net_amount += new_total_price;
        });

        $('span#total_before_tax').text(__currency_trans_from_en(net_amount, true));
        $('input.total_before_tax_hidden').val(net_amount);

        if (discount_type == 'fixed') {
            net_amount_discount = discount_amount;
        } else {
            net_amount_discount = (discount_amount / 100) * net_amount;
        }

        var net_return = Math.round((net_amount - net_amount_discount)/1000)*1000;
        $('input.total_sell_return').val(net_return);
        $('span#total_return_discount').text(__currency_trans_from_en(net_amount_discount, true));
        $('span#net_return').html(__currency_trans_from_en(net_return, true));
    }

    function change_discount() {
        var discount_type = $('#discount_type').val();
        var discount_amount = __read_number($('.discount_amount'));
        var net_amount = 0;
        var net_amount_discount = 0;
        var invoice_discount = $('.invoice_discount').val();
        var shop_return_amount = __read_number($('.shop_return_amount'));

        $('table#sell_return_table tbody tr').each(function() {
            var new_total_price = __read_number($(this).find('span.new_total_price'));
            net_amount += new_total_price;
        });

        $('span#total_before_tax').text(__currency_trans_from_en(net_amount, true));
        $('input.total_before_tax_hidden').val(net_amount);

        if (discount_type == 'fixed') {
            net_amount_discount = discount_amount;
        } else {
            net_amount_discount = (discount_amount / 100) * (net_amount - invoice_discount);
        }

        var net_return = Math.round((net_amount - net_amount_discount - invoice_discount + shop_return_amount)/1000)*1000;

        __write_number($('#total_shop_return_amount'), shop_return_amount);
        $('input.total_sell_return').val(net_return);
        $('.total_return_discount_hidden').val(net_amount_discount);
        $('span#total_return_discount').text(__currency_trans_from_en(net_amount_discount, true));
        $('span#net_return').html(__currency_trans_from_en(net_return, true));
    }

    function sell_return_total_row(tr) {
        var sell_price_type = tr.find('.sell_price_type').val();
        var width = tr.find('.new_width').val();
        var height = tr.find('.new_height').val();
        var quantity = tr.find('.new_quantity').val();
        var unit_price = tr.find('.unit_price_hidden').val();
        var old_base_unit_id = tr.find('.old_base_unit_id').val();
        var unit_type = tr.find('.type').val();
        var is_area = false;
        var total_price;

        if(sell_price_type == 'old'){
            if((unit_type == 'area' || unit_type == 'meter') && old_base_unit_id == ''){
                is_area = true;
            }
        }else if(sell_price_type == 'new'){
            is_area = true;
        }

        if(is_area){
            total_price = width * height * quantity * unit_price;
        }else{
            total_price = quantity * unit_price;
        }

        __write_number(tr.find('span.new_total_price'), total_price);
    }

    //Change sell price type
    function changeSellPriceType(tr){
        let sell_price_type = tr.find('.sell_price_type').val();
        let row_index = tr.data('row_index');
        let tr_parent = $('#sell_return_table #row_'+ row_index);

        if(sell_price_type == 'new'){
            let new_unit_price = tr_parent.find('.new_unit_price').val();

            tr.find('.new_width').removeAttr('readonly');
            __write_number(tr.find('.new_price'), new_unit_price);
            tr.find('.unit_price_hidden').val(new_unit_price);
        }else if(sell_price_type == 'new_by_plate'){
            let old_width = __read_number(tr.find('.old_width'));
            let new_unit_price = tr_parent.find('.new_unit_price_by_plate').val();

            tr.find('.new_width').val(old_width);
            tr.find('.new_width').attr('readonly', true);
            __write_number(tr.find('.new_price'), new_unit_price);
            tr.find('.unit_price_hidden').val(new_unit_price);
        }else{
            //Sell return by old selling price
            let old_unit_price = tr_parent.find('.old_unit_price').val();
            let unit_type = tr.find('.type').val();
            let old_base_unit_id = tr.find('.old_base_unit_id').val();

            if((unit_type == 'area' || unit_type == 'meter') && old_base_unit_id == ''){
                tr.find('.new_width').removeAttr('readonly');
            }else{
                let old_width = __read_number(tr.find('.old_width'));

                tr.find('.new_width').val(old_width);
                tr.find('.new_width').attr('readonly', true);
            }

            __write_number(tr.find('.new_price'), old_unit_price);
            tr.find('.unit_price_hidden').val(old_unit_price);
        }
    }

    //Calculate area
    function calculateArea(tr){
        var width = tr.find('.new_width').val();
        var height = tr.find('.new_height').val();
        var quantity = tr.find('.new_quantity').val();
        var area = width * height * quantity;
        __write_size(tr.find('.new_area'), area);
    }

    $('table#sell_return_table').on('change', '.new_width, .new_quantity', function() {
        var tr = $(this).closest('tr');
        calculateArea(tr);
        sell_return_total_row(tr);
        change_discount();
    });

    $('table#sell_return_table').on('change', '.sell_price_type', function() {
        var tr = $(this).closest('tr');
        changeSellPriceType(tr);
        sell_return_total_row(tr);
        change_discount();
    });

    //Change discount type
    function changeDiscountType(discount_type) {
        if (discount_type == 'percentage'){
            $('#discount_amount_label').html(LANG.discount_type_percentage);
        } else {
            $('#discount_amount_label').html(LANG.discount_type_fixed);
        }
    }

    $(document).on('change', '#discount_type, .discount_amount, .shop_return_amount', function() {
        change_discount();
    });

    async function getSellReturnEntryRow(tr){
        let row_index = tr.data('row_index');
        let plate_line_id = tr.find('.plate_line_id').val();

        let sub_row_index = 0;
        let tr_insert_after = tr;
        let tr_last = $('#sell_return_table .sub_row_'+ row_index).last();
        if(tr_last.data('sub_row_index') != undefined){
            sub_row_index = tr_last.data('sub_row_index') + 1;
            tr_insert_after = tr_last;
        }
        tr.find('.new_quantity').prop('required', true);
        tr.find('.new_width').prop('required', true);
        tr.find('.new_height').prop('required', true);

        $output = await $.ajax({
            method: 'POST',
            url: '/sell-return/get_sell_return_entry_row',
            dataType: 'html',
            data: {
                plate_line_id: plate_line_id,
                row_index: row_index,
                sub_row_index: sub_row_index
            },
            success: function(result) {
                var new_total_price = __read_number($(result).find('span.new_total_price'));
                var net_return = __read_number($('span#net_return'));
                var new_net_return = Math.round((new_total_price + net_return)/1000)*1000;
                $('.total_sell_return').val(new_net_return);
                $('span#net_return').html(__currency_trans_from_en(new_net_return, true));

                if(result != ''){
                    let error = '';

                    if(result == 'permission_denied'){
                        error = LANG.permission_denied;
                    }

                    if(error == ''){
                        $(result).insertAfter(tr_insert_after);
                        getFormatNumber();
                    }else{
                        swal({
                            title: error,
                            icon: 'warning',
                        });
                    }
                }
            },
        });

        return $output;
    }

    //Add plate need to return
    $('#sell_return_table').on('click', '.add_plate_return', function() {
        let tr = $(this).closest('tr');
        getSellReturnEntryRow(tr).then(function () {
            change_discount();
        });
    });

    $(document).on('click', '.remove_sell_return_entry_row', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(value => {
            if (value) {
                var tr = $(this).closest('tr');
                change_discount();
                var new_total_price = __read_number(tr.find('span.new_total_price'));
                var net_return = __read_number($('span#net_return'));
                var new_net_return = Math.round((net_return - new_total_price)/1000)*1000;

                $('span#net_return').html(__currency_trans_from_en(new_net_return, true));
                $('.total_sell_return').val(new_net_return);
                tr.remove();
                change_discount();
            }
        });
    });

    //For edit pos form
    if ($('form#sell_return_form').length > 0) {
        pos_form_obj = $('form#sell_return_form');
    } else {
        pos_form_obj = $('form#add_pos_sell_form');
    }
    /*if ($('form#sell_return_form').length > 0 || $('form#add_pos_sell_form').length > 0) {
        initialize_printer();
    }*/

    /*//Date picker
    $('#transaction_date').datepicker({
        autoclose: true,
        format: datepicker_date_format,
    });*/

    $('form#sell_return_form').validate({
        submitHandler: function(form) {
            var error = false;
            $('table#sell_return_table tbody tr').each(function() {
                let tr = $(this);
                let row_index = tr.attr('data-row_index');
                let total_return_width = 0;
                let total_return_area = 0;
                var total_new_quantity = 0;
                let return_quantity = 0;
                let old_width = parseFloat(tr.find('.old_width').html());
                let old_area = parseFloat(tr.find('.old_area').html());
                let old_quantity = __read_number(tr.find('.old_quantity'));
                let new_width = $('#sell_return_table .sub_row_' + row_index).find('.new_width');
                let type = $('#sell_return_table .sub_row_' + row_index).find('.type');
                let new_quantity = $('#sell_return_table .sub_row_' + row_index).find('.new_quantity');
                let new_area = $('#sell_return_table .sub_row_' + row_index).find('.new_area');
                if (new_width.length > 0) {
                    total_return_width = new_width.val();
                    if (total_return_width <= 0) {
                        tr.attr('style', 'color:#f00;');
                        toastr.warning(LANG.sell_width_return_must_integer);
                        error = true;
                    } else {
                        tr.removeAttr('style');
                    }
                }

                if (new_quantity.length > 0) {
                    return_quantity = new_quantity.val();
                    if (return_quantity % 1 != 0) {
                        tr.attr('style', 'color:#f00;');
                        toastr.warning(LANG.sell_quantity_return_must_integer);
                        error = true;
                    } else {
                        tr.removeAttr('style');
                    }
                }

                if (total_return_width > old_width) {
                    tr.attr('style', 'color:#f00;');
                    toastr.warning(LANG.sell_width_return_must_equal_sold);
                    error = true;
                } else {
                    tr.removeAttr('style');
                }

                if (new_area.length > 0) {
                    new_area.each(function(){
                        total_return_area += parseFloat($(this).html());
                    });
                }

                if (total_return_area > old_area) {
                    tr.attr('style', 'color:#f00;');
                    toastr.warning(LANG.sell_area_return_must_equal_sold);
                    error = true;
                } else {
                    tr.removeAttr('style');
                }

                if (type.val() == 'pcs' && old_quantity != 0) {
                    if (new_quantity.length > 0) {
                        new_quantity.each(function(){
                            total_new_quantity += __read_number($(this));
                        });
                        if (total_new_quantity > old_quantity) {
                            tr.attr('style', 'color:#f00;');
                            toastr.warning(LANG.sell_quantity_must_equal_sold);
                            error = true;
                        } else {
                            tr.removeAttr('style');
                        }
                    }
                }
            });

            let row_sell_return = $('#sell_return_table .sub_row');
            if (!row_sell_return.length) {
                toastr.error(LANG.enter_sell_return);
                error = true;
            }
            if (!error) {
                $('#sell_return_submit').attr('disabled', true);
                return true;
            }
            return false;
        },
    });
});

function initialize_printer() {
    if ($('input#location_id').data('receipt_printer_type') == 'printer') {
        initializeSocket();
    }
}

function pos_print(receipt) {
    //If printer type then connect with websocket
    if (receipt.print_type == 'printer') {
        var content = receipt;
        content.type = 'print-receipt';

        //Check if ready or not, then print.
        if (socket.readyState != 1) {
            initializeSocket();
            setTimeout(function() {
                socket.send(JSON.stringify(content));
            }, 700);
        } else {
            socket.send(JSON.stringify(content));
        }
    } else if (receipt.html_content != '') {
        //If printer type browser then print content
        $('#receipt_section').html(receipt.html_content);
        __currency_convert_recursively($('#receipt_section'));
        setTimeout(function() {
            window.print();
        }, 1000);
    }
}

// //Set the location and initialize printer
// function set_location(){
// 	if($('input#location_id').length == 1){
// 	       $('input#location_id').val($('select#select_location_id').val());
// 	       //$('input#location_id').data('receipt_printer_type', $('select#select_location_id').find(':selected').data('receipt_printer_ty
// 	}

// 	if($('input#location_id').val()){
// 	       $('input#search_product').prop( "disabled", false ).focus();
// 	} else {
// 	       $('input#search_product').prop( "disabled", true );
// 	}

// 	initialize_printer();
// }
