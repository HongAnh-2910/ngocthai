$(document).ready(function() {
    if($('form#add_deliver_form').length > 0){
        deliver_form = $('form#add_deliver_form');
    }else{
        deliver_form = $('form#edit_deliver_form');

        /*//Update cut order number
        $('#select_plate_deliver_table').find('tr.deliver_row').each(function(){
            let unit_type = $(this).find('.unit_type').val();

            if(unit_type == 'area' || unit_type == 'meter'){
                let remaining_plates_json = $(this).find('.remaining_plates').val();
                let remaining_plates = JSON.parse(remaining_plates_json);

                remaining_plates.forEach(function(remaining_plate){
                    if(remaining_plate.next_id !== ''){
                        let next_tr = $(`#select_plate_deliver_table #${remaining_plate.next_id}`);

                        if(typeof next_tr !== 'undefined'){
                            let next_remaining_plates_json = next_tr.find('.remaining_plates').val();
                            let next_remaining_plates = JSON.parse(next_remaining_plates_json);
                            if(next_remaining_plates.length === 0){
                                next_tr.find('.cut_plate_sort').html(`(${remaining_plate.order_number + 1})`);
                            }
                        }
                    }
                });
            }
        });*/
    }
    deliver_form_validator = deliver_form.validate();

    /*$(document).on('change', '#select_plate_deliver_table .is_cut_input', function() {
        let tr = $(this).closest('tr');
        let unit_type = tr.find('.unit_type').val();
        let is_cut = $(this).is(':checked');
        let before_remaining_widths_text;
        let after_remaining_widths_text;
        let current_remaining_width_text;

        if(is_cut == true){
            tr.find('.is_cut_hidden').val(1);

            tr.find('.remaining_width_text').each(function(){
                $(this).removeClass('hide');
            });

            tr.find('.remaining_width_if_not_cut_text').each(function(){
                $(this).addClass('hide');
            });

            before_remaining_widths_text = tr.find('.remaining_widths_if_not_cut_hidden').val();
            after_remaining_widths_text = tr.find('.remaining_widths_if_cut_hidden').val();
            current_remaining_width_text = tr.find('.remaining_widths_if_cut_hidden').val();
        }else{
            tr.find('.is_cut_hidden').val(0);

            tr.find('.remaining_width_text').each(function(){
                $(this).addClass('hide');
            });

            tr.find('.remaining_width_if_not_cut_text').each(function(){
                $(this).removeClass('hide');
            });

            before_remaining_widths_text = tr.find('.remaining_widths_if_cut_hidden').val();
            after_remaining_widths_text = tr.find('.remaining_widths_if_not_cut_hidden').val();
            current_remaining_width_text = tr.find('.remaining_widths_if_not_cut_hidden').val();
        }

        let before_remaining_widths = JSON.parse(before_remaining_widths_text);
        let after_remaining_widths = JSON.parse(after_remaining_widths_text);
        let changed_remaining_widths = after_remaining_widths;

        $.each(before_remaining_widths, function(before_index, before_remaining_width){
            let is_exist = false;

            $.each(after_remaining_widths, function(after_index, after_remaining_width){
                if(after_remaining_width['width'] == before_remaining_width['width']){
                    is_exist = true;
                    changed_remaining_widths[after_index]['quantity'] -= before_remaining_width['quantity'];
                }
            });

            if(!is_exist){
                changed_remaining_widths.push({
                    'width': before_remaining_width['width'],
                    'quantity': before_remaining_width['quantity'] * -1
                });
            }
        });

        let plate_stock_id = tr.attr('data-plate_stock_id');
        let selected_remaining_widths_element = $('#selected_remaining_widths_'+ plate_stock_id);

        if(selected_remaining_widths_element.val() != undefined){
            let selected_remaining_widths_index = selected_remaining_widths_element.attr('data-index');
            let selected_remaining_widths_text = selected_remaining_widths_element.val();
            let selected_remaining_widths = JSON.parse(selected_remaining_widths_text);

            if(changed_remaining_widths.length > 0) {
                let result_remaining_widths = selected_remaining_widths;

                $.each(changed_remaining_widths, function (changed_index, changed_remaining_width) {
                    let is_exist = false;

                    $.each(selected_remaining_widths, function (selected_index, selected_remaining_width) {
                        if (selected_remaining_width['width'] == changed_remaining_width['width']) {
                            is_exist = true;
                            result_remaining_widths[selected_index]['quantity'] += changed_remaining_width['quantity'];
                        }
                    });

                    if (!is_exist) {
                        result_remaining_widths.push({
                            width: changed_remaining_widths['width'],
                            quantity: changed_remaining_widths['quantity']
                        });
                    }
                });

                let result_remaining_widths_text = JSON.stringify(result_remaining_widths);
                selected_remaining_widths_element.attr('data-value_'+ selected_remaining_widths_index, result_remaining_widths_text);
                selected_remaining_widths_element.val(result_remaining_widths_text);
            }
        }

        //Update remaining plates
        let row_id = tr.attr('id');
        let remaining_plates_json = $('#remaining_plates_'+ row_id).val();

        if(remaining_plates_json != undefined){
            let current_remaining_width = JSON.parse(current_remaining_width_text);
            let old_remaining_plates = JSON.parse(remaining_plates_json);
            let new_remaining_plates = [];
            let order_number = 1;
            let prev_id = '';

            if(old_remaining_plates.length > 0){
                order_number = old_remaining_plates[0].order_number;
                prev_id = old_remaining_plates[0].prev_id;
            }

            $.each(current_remaining_width, function(index, value){
                new_remaining_plates.push({
                    width: value.width,
                    quantity: value.quantity,
                    plate_stock_id: plate_stock_id,
                    order_number: order_number,
                    id: row_id,
                    next_id: '',
                    prev_id: prev_id,
                });
            });

            let new_remaining_plates_json = JSON.stringify(new_remaining_plates);
            $('#remaining_plates_'+ row_id).val(new_remaining_plates_json);
        }

        // Update for new print template
        let plates_for_print_json = tr.find('.plates_for_print').val();
        let plates_for_print = JSON.parse(plates_for_print_json);
        let plates_for_print_old;
        let plates_for_print_new;

        if(is_cut == true){
            plates_for_print_old = plates_for_print.not_cut;
            plates_for_print_new = plates_for_print.cut;
        }else{
            plates_for_print_old = plates_for_print.cut;
            plates_for_print_new = plates_for_print.not_cut;
        }

        let plates_sort_order_json = $('#plates_sort_order').val();
        let plates_sort_order = JSON.parse(plates_sort_order_json);
        let new_plates_sort_order = plates_sort_order;
        let is_exist = false;

        // console.log('plates_sort_order', JSON.stringify(plates_sort_order));
        // console.log('plates_for_print_old', plates_for_print_old);
        // console.log('plates_for_print_new', plates_for_print_new);

        plates_for_print_old.forEach(function(plate_for_print_old, plate_for_print_old_index){
            plates_sort_order.every(function(plate_sort_order, plate_sort_order_index){
                if(plate_sort_order.plate_stock_id == plate_stock_id) {
                    if(plate_for_print_old.is_origin == 1){
                        // console.log('if');

                        if(plate_for_print_old.remaining_width == plate_sort_order.remaining_width && plate_sort_order.deliver_plates.length == 1 && plate_sort_order.deliver_plates[0].deliver_width == plate_for_print_old.deliver_width){
                            // console.log('if2');

                            if(plates_for_print_new[plate_for_print_old_index] == plate_sort_order.remaining_width &&  plates_for_print_new[plate_for_print_old_index].deliver_width == plate_sort_order.deliver_plates[0].deliver_width){
                                is_exist = true;
                                new_plates_sort_order[plate_sort_order_index].selected_quantity += plates_for_print_new[plate_for_print_old_index].selected_quantity - plate_for_print_old.selected_quantity;
                                new_plates_sort_order[plate_sort_order_index].remaining_quantity += plates_for_print_new[plate_for_print_old_index].remaining_quantity - plate_for_print_old.remaining_quantity;
                                new_plates_sort_order[plate_sort_order_index].deliver_plates[0].deliver_quantity += plates_for_print_new[plate_for_print_old_index].deliver_quantity - plate_for_print_old.deliver_quantity;
                            }else{
                                new_plates_sort_order[plate_sort_order_index].selected_quantity -= plate_for_print_old.selected_quantity;
                                new_plates_sort_order[plate_sort_order_index].remaining_quantity -= plate_for_print_old.remaining_quantity;
                                new_plates_sort_order[plate_sort_order_index].deliver_plates[0].deliver_quantity -= plate_for_print_old.deliver_quantity;
                            }

                            return false;
                        }
                    }else{
                        let deliver_plates_last_index = new_plates_sort_order[plate_sort_order_index].deliver_plates.length - 1;

                        if(plate_for_print_old.remaining_width == plate_sort_order.remaining_width && plate_for_print_old.deliver_width == plate_sort_order.deliver_plates[deliver_plates_last_index].deliver_width){
                            is_exist = true;

                            // console.log('deliver_plates_before', new_plates_sort_order[plate_sort_order_index].deliver_plates);

                            if(deliver_plates_last_index > 0 && plates_for_print_new[plate_for_print_old_index].deliver_width == plate_sort_order.deliver_plates[deliver_plates_last_index - 1].deliver_width){
                                // console.log('if3');
                                new_plates_sort_order[plate_sort_order_index].deliver_plates.pop();
                                new_plates_sort_order[plate_sort_order_index].deliver_plates[deliver_plates_last_index - 1].deliver_quantity += plates_for_print_new[plate_for_print_old_index].deliver_quantity;
                            }else{
                                // console.log('else3');
                                new_plates_sort_order[plate_sort_order_index].deliver_plates[deliver_plates_last_index].deliver_quantity -= plate_for_print_old.deliver_quantity;
                                new_plates_sort_order[plate_sort_order_index].deliver_plates.push({
                                    deliver_width: plates_for_print_new[plate_for_print_old_index].deliver_width,
                                    deliver_quantity: plates_for_print_new[plate_for_print_old_index].deliver_quantity,
                                });
                            }


                            new_plates_sort_order[plate_sort_order_index].remaining_width = plates_for_print_new[plate_for_print_old_index].remaining_width;
                            new_plates_sort_order[plate_sort_order_index].remaining_quantity += plates_for_print_new[plate_for_print_old_index].remaining_quantity - plate_for_print_old.remaining_quantity;

                            // console.log('deliver_plates_after', new_plates_sort_order[plate_sort_order_index].deliver_plates);

                            return false;
                        }
                    }
                }
                return true;
            });
        });

        // console.log(is_exist);
        // console.log('new_plates_sort_order', JSON.stringify(new_plates_sort_order));

        if(!is_exist){
            plates_for_print_new.forEach(function(item){
                new_plates_sort_order.push({
                    plate_stock_id: plate_stock_id,

                    selected_width: item.selected_width,
                    selected_quantity: item.selected_quantity,

                    deliver_plates: [
                        {
                            deliver_width: item.deliver_width,
                            deliver_quantity: item.deliver_quantity,
                        }
                    ],

                    remaining_width: item.remaining_width,
                    remaining_quantity: item.remaining_quantity,
                });
            });
        }

        //Remove empty plates
        let new2_plates_sort_order = [];
        new_plates_sort_order.forEach(function(plate, index){
            if(plate.selected_quantity !== 0){
                new2_plates_sort_order.push(plate);
            }
        });

        //Combine same selected plates and deliver plates
        let new3_plates_sort_order = [];

        // console.log('before', new2_plates_sort_order);
        // console.log(JSON.stringify(new2_plates_sort_order));
        // console.log(new2_plates_sort_order);

        new2_plates_sort_order.forEach(function(new2_plate_sort_order, new2_plate_sort_order_index){
            let is_exist = false;

            //Combine same deliver plates
            // console.log('new2_plate_sort_order.deliver_plates old__', new2_plate_sort_order.deliver_plates);
            let old_deliver_plates = new2_plate_sort_order.deliver_plates;
            new2_plate_sort_order.deliver_plates = [];
            // console.log('new2_plate_sort_order.deliver_plates old__', new2_plate_sort_order.deliver_plates);

            old_deliver_plates.forEach(function(old_deliver_plate, old_deliver_plate_index){
                let deliver_plate_exist = false;

                new2_plate_sort_order.deliver_plates.every(function(new_deliver_plate, new_deliver_plate_index){
                    if(old_deliver_plate.deliver_width == new_deliver_plate.deliver_width){
                        deliver_plate_exist = true;

                        new2_plate_sort_order.deliver_plates[new_deliver_plate_index].deliver_quantity += old_deliver_plate.deliver_quantity;

                        return false;
                    }

                    return true;
                });

                if(!deliver_plate_exist){
                    new2_plate_sort_order.deliver_plates.push(old_deliver_plate);
                }
            });
            // console.log('new2_plate_sort_order.deliver_plates new__', new2_plate_sort_order.deliver_plates);

            //Combine same selected plates
            new3_plates_sort_order.every(function(new3_plate_sort_order, new3_plate_sort_order_index){
                if(new2_plate_sort_order.plate_stock_id == new3_plate_sort_order.plate_stock_id && new2_plate_sort_order.selected_width == new3_plate_sort_order.selected_width && new2_plate_sort_order.remaining_width == new3_plate_sort_order.remaining_width){
                    let deliver_plates_valid = true;

                    if(new2_plate_sort_order.deliver_plates.length == new3_plate_sort_order.deliver_plates.length){
                        new2_plate_sort_order.deliver_plates.every(function(deliver_plate, deliver_plate_index){
                            if(new2_plate_sort_order.deliver_plates[deliver_plate_index].deliver_width !== new3_plate_sort_order.deliver_plates[deliver_plate_index].deliver_width){
                                deliver_plates_valid = false;
                                return false;
                            }

                            return true;
                        });
                    }

                    if(deliver_plates_valid){
                        is_exist = true;
                        new3_plates_sort_order[new3_plate_sort_order_index].selected_quantity += new2_plate_sort_order.selected_quantity;
                        new3_plates_sort_order[new3_plate_sort_order_index].remaining_quantity += new2_plate_sort_order.remaining_quantity;

                        new2_plate_sort_order.deliver_plates.forEach(function(deliver_plate, deliver_plate_index){
                            new3_plates_sort_order[new3_plate_sort_order_index].deliver_plates[deliver_plate_index].deliver_quantity += new2_plates_sort_order[new2_plate_sort_order_index].deliver_plates[deliver_plate_index].deliver_quantity;
                        });

                        return false;
                    }
                }

                return true;
            });

            if(!is_exist){
                new3_plates_sort_order.push(new2_plate_sort_order);
            }
        });

        // console.log('after', new3_plates_sort_order);
        // console.log(JSON.stringify(new3_plates_sort_order));
        // console.log('______');

        new3_plates_sort_order = JSON.stringify(new3_plates_sort_order);
        $('#plates_sort_order').val(new3_plates_sort_order);
    });*/

    $('button#submit-deliver, button#save-and-print').click(function(e) {
        let data_sell_lines = null;
        let data_transaction = null;
        let transaction_id = $('.transaction_id').val();
        let location_id = $('#location_id').val();
        $.ajax({
            async: false,
            type: 'get',
            url: '/stock-to-deliver/check-invoice-update',
            dataType: 'json',
            data: {
                transaction_id: transaction_id,
                location_id: location_id
            },
            success: function (result) {
                if (result.success) {
                    data_sell_lines = result.data.sell_lines;
                    data_transaction = result.data.transaction;
                }
            }
        });

        let error = false;
        let data = $('#select_plate_deliver_table .product_row');

        if (data.length != data_sell_lines.length || data_transaction.status == 'cancel') {
            toastr.error(LANG.invoice_updated);
            error = true;
        } else {
            let data_old_formatted = [];
            let data_new_formatted = [];
            for (let value of data_sell_lines) {
                removeEmpty(value);
                data_old_formatted.push(removeEmpty(value));
            }

            let old_value = JSON.parse($('.sell_details').val());
            for (let value of old_value) {
                removeEmpty(value);
                data_new_formatted.push(removeEmpty(value));
            }

            let old_sell_lines = JSON.stringify(data_old_formatted);
            let new_sell_lines = JSON.stringify(data_new_formatted);

            if (old_sell_lines !== new_sell_lines) {
                toastr.error(LANG.invoice_updated);
                error = true;
            }
        }

        $('#select_plate_deliver_table .product_row').each(function(){
            let tr = $(this);
            let row_index = tr.attr('data-row_index');
            let quantity = __read_number(tr.find('.quantity'));
            let unit_type = tr.find('.unit_type').val();
            let base_unit_multiplier = __read_number(tr.find('.base_unit_multiplier'));
            if(!base_unit_multiplier){
                base_unit_multiplier = 1;
            }
            let total_deliver_quantity = 0;

            $('#select_plate_deliver_table .deliver_row_'+ row_index).find('.deliver_quantity').each(function(){
                total_deliver_quantity += __read_number($(this));
            });

            if(total_deliver_quantity < quantity){
                tr.attr('style', 'color:#f00;');
                let message =  LANG.not_enough_plate_to_be_cut;
                if(unit_type == 'pcs'){
                    message =  LANG.not_enough_pcs_to_be_sale;
                }
                toastr.warning(message);
                error = true;
            }else{
                tr.removeAttr('style');
            }
        });

        if ($(this).attr('id') == 'save-and-print') {
            $('#is_save_and_print').val(1);
        } else {
            $('#is_save_and_print').val(0);
        }

        if (deliver_form.valid() && !error) {
            $('#submit-deliver').attr('disabled', true);
            $('#save-and-print').attr('disabled', true);

            let all_selected_remaining_widths = [];
            deliver_form.find('.selected_remaining_widths').each(function(){
                let index = $(this).attr('data-index');
                let cut_plates = [];
                let i;

                for(i = 0; i <= index; i++){
                    cut_plates.push({
                        'index': i,
                        'row_id': $(this).attr('data-row_id_'+ i),
                        'value': $(this).attr('data-value_'+ i),
                    });
                }

                all_selected_remaining_widths.push({
                    'plate_stock_id':  $(this).attr('data-plate_stock_id'),
                    'value': $(this).val(),
                    'index': index,
                    'cut_plates': cut_plates,
                });
            });

            let all_selected_remaining_widths_json = JSON.stringify(all_selected_remaining_widths);
            // console.log(all_selected_remaining_widths_json);
            deliver_form.append('<textarea name="all_selected_remaining_widths" style="display:none">'+ all_selected_remaining_widths_json +'</textarea>');

            //Sort plates for print
            let plates_sort_order_json = $('#plates_sort_order').val();
            let plates_sort_order = JSON.parse(plates_sort_order_json);

            plates_sort_order.sort(function(a, b){
                return a.plate_stock_id - b.plate_stock_id;
            });

            plates_sort_order_json = JSON.stringify(plates_sort_order);
            $('#plates_sort_order').val(plates_sort_order_json);
            // console.log(plates_sort_order_json);
            // return;

            window.onbeforeunload = null;
            deliver_form.submit();
        }
    });

    function removeEmpty(value) {
        Object.keys(value).forEach(key =>
            (value[key] && typeof value[key] === 'object') && removeEmpty(value[key]) ||
            (value[key] === undefined || value[key] === null) && delete value[key]
        );
        return value;
    }

    $('#plate_stock_deliver_table').on('click', '.select_plate_button', function() {
        let tr = $('#select_plate_deliver_table').find('tr.selected');
        let row_index = tr.attr('data-row_index');
        let width = __read_number(tr.find('.width'));
        let height = __read_number(tr.find('.height'));
        let quantity = __read_number(tr.find('.quantity'));
        let transaction_sell_line_id = tr.find('.transaction_sell_line_id').val();
        let unit_type = tr.find('.unit_type').val();
        let base_unit_multiplier = __read_number(tr.find('.base_unit_multiplier'));
        if(!base_unit_multiplier){
            base_unit_multiplier = 1;
        }
        let plate_stock_id = $(this).data('plate_stock_id');
        let qty_available = $(this).data('qty_available');

        let total_deliver_quantity = 0;
        let remaining_widths = '';

        //Check stock if enough plate
        $('#select_plate_deliver_table .deliver_row_'+ row_index).find('.deliver_quantity').each(function(){
            total_deliver_quantity += __read_number($(this));
        });
        if(total_deliver_quantity >= quantity){
            swal({
                title: LANG.enough_plate_to_be_cut,
                icon: 'warning',
                dangerMode: true,
            });
            return;
        }

        //Get remaining widths
        if($('#selected_remaining_widths_'+ plate_stock_id).val() != undefined){
            remaining_widths = $('#selected_remaining_widths_'+ plate_stock_id).val();
        }

        let current_plate_stock_ids = $('#select_plate_deliver_table .deliver_row_'+ row_index).map(function(){
            return $(this).attr('data-plate_stock_id');
        }).get();
        if(current_plate_stock_ids.indexOf(plate_stock_id) >= 0){
            swal({
                title: LANG.scroll_selected_already_exists,
                icon: 'warning',
                dangerMode: true,
            });
            return;
        }

        //Check stock if have same plates
        let total_selected_quantity = 0;

        $('#select_plate_deliver_table .deliver_row').each(function(){
            let selected_plate_stock_id = $(this).data('plate_stock_id');
            let selected_quantity = __read_number($(this).find('.deliver_selected_quantity'));
            let selected_width = __read_number($(this).find('.deliver_origin_width'));
            let origin_width = __read_number($(this).find('.origin_width'));

            if(plate_stock_id == selected_plate_stock_id && selected_width == origin_width){
                total_selected_quantity += selected_quantity;
            }
        });

        let current_remaining_widths_element = $('#selected_remaining_widths_' + plate_stock_id);
        let current_remaining_widths_text;
        if(current_remaining_widths_element.val() != undefined) {
            current_remaining_widths_text = current_remaining_widths_element.val();
        }else{
            current_remaining_widths_text = '';
        }

        let row_insert_after = tr;
        let tr_last = $('#select_plate_deliver_table .deliver_row_'+ row_index).last();

        if(tr_last.attr('data-plate_stock_id') != undefined){
            row_insert_after = tr_last;
        }
        let row_insert_after_id = row_insert_after.attr('id');

        //Get entry row
        $.ajax({
            method: 'POST',
            url: '/stock-to-deliver/get_sell_entry_row',
            dataType: 'json',
            data: {
                plate_stock_id: plate_stock_id,
                width: width,
                height: height,
                quantity: quantity,
                total_deliver_quantity: total_deliver_quantity,
                total_selected_quantity: total_selected_quantity,
                remaining_widths: remaining_widths,
                row_index: row_index,
                transaction_sell_line_id: transaction_sell_line_id,
                current_remaining_widths_text: current_remaining_widths_text,
                row_insert_after_id: row_insert_after_id,
            },
            success: function(result) {
                if(result.success){
                    let unit_type = tr.find('.unit_type').val();
                    if(unit_type == 'area' || unit_type == 'meter'){
                        var container = $('.choose_plate_manually');
                        container.html(result.data).modal('show');
                    }else{
                        let row_insert = $(result.data).insertAfter(row_insert_after);
                        let new_plates_sort_order = [];
                        let is_exist = false;
                        let new_selected_quantity = parseInt(row_insert.find('.selected_quantity').val());

                        if(typeof $('#plates_sort_order').val() === 'undefined'){
                            deliver_form.append('<input type="hidden" name="plates_sort_order" id="plates_sort_order" value="">');
                        }else {
                            let plates_sort_order_json = $('#plates_sort_order').val();
                            let plates_sort_order = JSON.parse(plates_sort_order_json);
                            new_plates_sort_order = plates_sort_order;

                            plates_sort_order.every(function(plate_sort_order, index){
                                if(plate_sort_order.plate_stock_id == plate_stock_id) {
                                    is_exist = true;
                                    new_plates_sort_order[index].selected_quantity += new_selected_quantity;
                                    new_plates_sort_order[index].deliver_plates[0].deliver_quantity += new_selected_quantity;
                                    return false;
                                }
                                return true;
                            });
                        }

                        if(!is_exist){
                            let transaction_sell_line_id = parseInt(row_insert.find('.transaction_sell_line_id').val());

                            new_plates_sort_order.push({
                                transaction_sell_line_id,
                                plate_stock_id: plate_stock_id,

                                selected_width: 1,
                                selected_quantity: new_selected_quantity,

                                deliver_plates: [
                                    {
                                        deliver_width: 1,
                                        deliver_quantity: new_selected_quantity,
                                    }
                                ],

                                remaining_width: 1,
                                remaining_quantity: 0,
                            });
                        }

                        let new_plates_sort_order_json = JSON.stringify(new_plates_sort_order);
                        $('#plates_sort_order').val(new_plates_sort_order_json);
                    }
                }else{
                    swal({
                        title: result.message,
                        icon: 'warning',
                    });
                }
            },
        });

        $('#plate_stock_deliver_modal').modal('toggle');
    });

    $(document).on('click', '#payment-button', function() {
        let payment_form = $(this).closest('form');
        let confirmed = $(this).data('confirmed');

        payment_form.find('#confirmed').val(confirmed)
        ;
        payment_form.submit();
    });

    /*function highlightPrevPlate(remaining_plate){
        let order_number = remaining_plate.order_number;
        do {
            if(remaining_plate.prev_id !== ''){
                let prev_tr = $(`#${remaining_plate.prev_id}`);
                let prev_remaining_plates = prev_tr.find('.remaining_plates').val();
                prev_remaining_plates = JSON.parse(prev_remaining_plates);

                for(const prev_index in prev_remaining_plates){
                    let prev_remaining_plate = prev_remaining_plates[prev_index];

                    prev_tr.find('.cut_plate_sort').html(` (${prev_remaining_plate.order_number})`);
                    if(remaining_plate.prev_id !== ''){

                    }
                }
            }
        } while (order_number > 0);
    }*/

    $(document).on('click', '.remove_sell_entry_row', function() {
        //Validate plate line need to remove
        let tr = $(this).closest('tr');
        let unit_type = tr.find('.unit_type').val();
        let remaining_plates = [];

        if(unit_type == 'area' || unit_type == 'meter'){
            remaining_plates = tr.find('.remaining_plates').val();
            remaining_plates = JSON.parse(remaining_plates);

            for(const index in remaining_plates){
                let remaining_plate = remaining_plates[index];

                if(remaining_plate.next_id !== ''){
                    // $('.cut_plate_sort').removeClass('hide');
                    toastr.error(LANG.deliver_from_same_plate_error);
                    return;
                }
            }
        }

        //Remove plate line
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(value => {
            if (value) {
                let plate_stock_id = parseInt(tr.data('plate_stock_id'));
                let plates_sort_order_json = $('#plates_sort_order').val();
                let plates_sort_order = JSON.parse(plates_sort_order_json);
                let new_plates_sort_order = [];

                if((unit_type == 'area' || unit_type == 'meter')){
                    const selected_remaining_widths_element = $('#selected_remaining_widths_'+ plate_stock_id);
                    const remaining_widths_index = selected_remaining_widths_element.attr('data-index');
                    const row_id = tr.attr('id');

                    //TODO: Remove this plate in plates_sort_order (for print)
                    new_plates_sort_order = plates_sort_order.filter(plate => parseInt(plate.plate_stock_id) !== plate_stock_id);

                    //TODO: Remove remaining width
                    if(typeof selected_remaining_widths_element !== 'undefined'){
                        if(remaining_widths_index > 0){
                            //Get selected_remaining_widths
                            const selected_remaining_widths_index = selected_remaining_widths_element.attr('data-index');
                            const current_plates = [];
                            let new_index = 0;

                            for(let i = 0; i <= selected_remaining_widths_index; i++){
                                const item_row_id = selected_remaining_widths_element.attr('data-row_id_'+ i);

                                const cut_plate = {
                                    index: new_index,
                                    row_id: item_row_id,
                                    value: JSON.parse(selected_remaining_widths_element.attr('data-value_'+ i)),
                                };

                                if(cut_plate.row_id !== row_id){
                                    current_plates[new_index] = cut_plate;
                                    new_index++;
                                }
                            }

                            //Get all remaining_plates
                            let all_plates = {};
                            let origin_height = parseFloat(tr.find('.origin_height').val());

                            $('#select_plate_deliver_table').find('tr.deliver_row').each(function(){
                                const item_plate_stock_id = $(this).attr('data-plate_stock_id');

                                if (item_plate_stock_id == plate_stock_id){
                                    const item_id = $(this).attr('id');
                                    const order_row_id = $(this).find('').val();
                                    const order_row = $(`#${order_row_id}`);

                                    let item_remaining_plates = $(this).find('.remaining_plates').val();
                                    if (typeof item_remaining_plates !== 'undefined'){
                                        item_remaining_plates = JSON.parse(item_remaining_plates);
                                    }else{
                                        item_remaining_plates = [];
                                    }

                                    const item_is_cut = parseInt($(this).find('.is_cut_hidden').val());
                                    let item_remaining_widths;
                                    const item_remaining_widths_if_cut = JSON.parse($(this).find('.remaining_widths_if_cut_hidden').val());
                                    const item_remaining_widths_if_not_cut = JSON.parse($(this).find('.remaining_widths_if_not_cut_hidden').val());

                                    console.log('item_is_cut', item_is_cut, typeof item_is_cut);
                                    if (item_is_cut == 1){
                                        item_remaining_widths = item_remaining_widths_if_cut;
                                    }else{
                                        console.log('not cut');
                                        item_remaining_widths = item_remaining_widths_if_not_cut;
                                    }
                                    console.log('item_remaining_widths', item_remaining_widths);

                                    const item_origin_width = parseFloat($(this).find('.origin_width').val());
                                    const item_selected_width = parseFloat($(this).find('.selected_width').val());
                                    let item_cut_from_same_plate = false;

                                    if(item_selected_width !== item_origin_width){
                                        item_cut_from_same_plate = true;
                                    }

                                    all_plates[item_id] = {
                                        order_width: parseFloat(order_row.find('.width_input').val()),
                                        order_height: origin_height,
                                        order_quantity: parseFloat(order_row.find('.quantity_input').val()),
                                        selected_width: item_selected_width,
                                        selected_quantity: parseFloat($(this).find('.selected_quantity').val()),
                                        deliver_width: parseFloat($(this).find('.quantity').val()),
                                        row_id: item_id,
                                        row_index: $(this).find('.row_index').val(),
                                        transaction_sell_line_id: parseInt($(this).find('.transaction_sell_line_id').val()),
                                        remaining_widths: item_remaining_widths,
                                        origin_width: item_origin_width,
                                        enabled_not_cut: parseInt($(this).find('.enabled_not_cut').val()),
                                        is_cut: item_is_cut,
                                        plates_for_print: JSON.parse($(this).find('.plates_for_print').val()),
                                        cut_from_same_plate: item_cut_from_same_plate,
                                        remaining_plates: item_remaining_plates,
                                    };
                                }
                            });

                            //Delete old selected_remaining_widths
                            selected_remaining_widths_element.remove();

                            //Update new selected_remaining_widths
                            const result_after_delete_row = updateAfterDeleteRow(current_plates, all_plates, new_plates_sort_order, plate_stock_id);
                            new_plates_sort_order = result_after_delete_row.new_plates_sort_order;
                        }else{
                            //Delete old selected_remaining_widths
                            selected_remaining_widths_element.remove();
                        }
                    }
                } else {
                    //TODO: Update for new print template
                    let quantity = parseInt(tr.find('.deliver_quantity').html());

                    plates_sort_order.forEach(function(plate_sort_order){
                        if(plate_sort_order.plate_stock_id === plate_stock_id){
                            if(quantity < plate_sort_order.selected_quantity){
                                plate_sort_order.selected_quantity -= quantity;
                                plate_sort_order.deliver_plates[0].deliver_quantity -= quantity;
                                new_plates_sort_order.push(plate_sort_order);
                            }
                        }else{
                            new_plates_sort_order.push(plate_sort_order);
                        }
                    });
                }

                let new_plates_sort_order_json = JSON.stringify(new_plates_sort_order);
                $('#plates_sort_order').val(new_plates_sort_order_json);

                // $('.cut_plate_sort').addClass('hide');
                tr.remove();
            }
        });
    });

    var plate_stock_deliver_cols = [
        { data: 'sku', name: 'p.sku' },
        { data: 'product', name: 'p.name' },
        { data: 'height', name: 'height' },
        { data: 'width', name: 'width' },
        { data: 'stock', name: 'stock', searchable: false},
        { data: 'expect_stock', name: 'expect_stock', searchable: false},
        { data: 'warehouses', name: 'warehouses', orderable: false},
        { data: 'is_origin', name: 'is_origin' },
        { data: 'action', name: 'action', searchable: false, orderable: false },
    ];

    plate_stock_deliver_table = $('#plate_stock_deliver_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/sells/plate-stock',
            data: function(d) {
                d.location_id = $('#location_id').val();
                d.category_id = $('#filter_category_id').val();
                d.variation_id = $('#filter_variation_id').val();
                d.width = $('#filter_plate_width').val();
                d.height = $('#filter_plate_height').val();
            },
        },
        columns: plate_stock_deliver_cols,
    });

    $('#select_location_id, #plate_stock_deliver_filter_form #view_stock_filter, #plate_stock_deliver_filter_form #filter_plate_width, #plate_stock_deliver_filter_form #filter_plate_height, #plate_stock_deliver_filter_form #filter_variation_id'
    ).change(function() {
        plate_stock_deliver_table.ajax.reload();
    });

    function get_stock_deliver_report_details(rowData, location_id, category_id, order_width, order_height, order_quantity) {
        var div = $('<div/>')
            .addClass('loading')
            .text('Loading...');
        $.ajax({
            url: '/sells/plate-stock-detail/' + rowData.variation_id,
            dataType: 'html',
            data: {
                'location_id': location_id,
                'category_id': category_id,
                'width': rowData.width,
                'height': rowData.height,
                'order_width': order_width,
                'order_height': order_height,
                'order_quantity': order_quantity,
                'is_origin': rowData.is_origin,
                'layout': 'stock_deliver',
            },
            success: function(data) {
                div.html(data).removeClass('loading');
            },
        });

        return div;
    }

    let deliverDetailRows = [];

    $('#plate_stock_deliver_table tbody').on('click', '.view_detail', function() {
        var tr = $(this).closest('tr');
        var row = plate_stock_deliver_table.row(tr);
        var idx = $.inArray(tr.attr('id'), deliverDetailRows);
        let location_id = $('#location_id').val();
        let category_id = $('#filter_category_id').val();
        let order_width = $('#plate_stock_filter_box #order_width').val();
        let order_height = $('#plate_stock_filter_box #order_height').val();
        let order_quantity = $('#plate_stock_filter_box #order_quantity').val();

        if (row.child.isShown()) {
            $(this)
                .find('i')
                .removeClass('fa-eye-slash')
                .addClass('fa-eye');
            row.child.hide();

            // Remove from the 'open' array
            deliverDetailRows.splice(idx, 1);
        } else {
            $(this)
                .find('i')
                .removeClass('fa-eye')
                .addClass('fa-eye-slash');

            row.child(get_stock_deliver_report_details(row.data(), location_id, category_id, order_width, order_height, order_quantity)).show();

            // Add to the 'open' array
            if (idx === -1) {
                deliverDetailRows.push(tr.attr('id'));
            }
        }
    });

    // On each draw, loop over the `deliverDetailRows` array and show any child rows
    plate_stock_deliver_table.on('draw', function() {
        $.each(deliverDetailRows, function(i, id) {
            $('#' + id + ' .view_detail').trigger('click');
        });
    });

    $('#select_plate_deliver_table').on('click', '.select_product_button', function(){
        let tr = $(this).closest('tr');
        let unit_type = tr.find('.unit_type').val();
        let variation_id = tr.find('.variation_id').val();
        let category_id = tr.find('.category_id').val();
        let width = __read_number(tr.find('.width_input'));
        let height = __read_number(tr.find('.height_input'));
        let quantity = __read_number(tr.find('.quantity'));

        $('#plate_stock_deliver_filter_form #order_width').val(width);
        $('#plate_stock_deliver_filter_form #order_height').val(height);
        $('#plate_stock_deliver_filter_form #order_quantity').val(quantity);

        $('#plate_stock_deliver_filter_form #filter_category_id').val(category_id);
        getProductsByCategory(category_id, variation_id);

        if(unit_type == 'area') {
            $('#plate_stock_deliver_filter_form #filter_plate_width').val(width);
            $('#plate_stock_deliver_filter_form #filter_plate_height').val(height);

            $('#plate_stock_filter_box').show();
            $('#plate_stock_deliver_filter_form #category_box').show();
            $('#plate_stock_deliver_filter_form #variation_box').show();
            $('#plate_stock_deliver_filter_form #plate_height_box').show();
            $('#plate_stock_deliver_filter_form #plate_width_box').show();
        }else if(unit_type == 'meter') {
            $('#plate_stock_deliver_filter_form #filter_plate_width').val(width);
            $('#plate_stock_deliver_filter_form #filter_plate_height').val(1);

            $('#plate_stock_filter_box').show();
            $('#plate_stock_deliver_filter_form #category_box').show();
            $('#plate_stock_deliver_filter_form #variation_box').show();
            $('#plate_stock_deliver_filter_form #plate_height_box').hide();
            $('#plate_stock_deliver_filter_form #plate_width_box').show();
        }else{
            $('#plate_stock_deliver_filter_form #filter_plate_width').val(1);
            $('#plate_stock_deliver_filter_form #filter_plate_height').val(1);

            $('#plate_stock_filter_box').hide();
        }

        $('#select_plate_deliver_table').find('tr.selected').removeClass('selected');
        tr.addClass('selected');
        $('#plate_stock_deliver_modal input:checkbox').not(this).prop('checked', false);

        plate_stock_deliver_table.ajax.reload();
    });

    function getProductsByCategory(category_id, variation_id = '') {
        return new Promise(function(){
            $.ajax({
                method: 'get',
                url: '/products/get-product-by-cate',
                dataType: 'json',
                data: {
                    category_id: category_id
                },
                success: function (result) {
                    let key = Object.keys(result);

                    $('#filter_variation_id').empty();
                    $('#filter_variation_id').append('<option value="">' + LANG.all + '</option>');

                    for (let i = 0; i < key.length; i++) {
                        let selected = '';
                        if(result[i].id == variation_id){
                            selected = 'selected';
                        }
                        $('#filter_variation_id').append('<option value="' + result[i].id + '" '+ selected +'>' + result[i].product_name + '</option>');
                    }
                }
            }).then(function(){
                if(variation_id != ''){
                    $('#plate_stock_deliver_filter_form #filter_variation_id').val(variation_id);
                }
            }).then(function(){
                plate_stock_deliver_table.ajax.reload();
            });
        });
    }

    $('#plate_stock_deliver_filter_form #filter_category_id').change(function() {
        let category_id = $(this).val();
        $('#plate_stock_deliver_filter_form #filter_variation_id').val('');
        getProductsByCategory(category_id);
        plate_stock_deliver_table.ajax.reload();
    });

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
                                    $('#plate_stock_deliver_modal').modal('show');
                                    plate_stock_deliver_table.ajax.reload();
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
});

function updateAfterDeleteRow(selected_plates, all_plates, plates_sort_order, plate_stock_id){
    const new_selected_plates = [];
    const new_plates_sort_order = JSON.parse(JSON.stringify(plates_sort_order));

    for (let index = 0; index < selected_plates.length; index++){
        const selected_plate = selected_plates[index];
        const row_id = selected_plate.row_id;
        const plate = all_plates[row_id];
        const row_index = plate.row_index;
        let prev_id = '';
        const is_cut = plate.is_cut;
        let remaining_widths = plate.remaining_widths;
        let deliver_width = plate.deliver_width;

        console.log('index', index);
        console.log('is_cut', is_cut);
        console.log('remaining_widths', remaining_widths);
        console.log('new_selected_plates', new_selected_plates);

        //TODO: Calculate new selected_remaining_widths
        let old_remaining_widths;
        if (index === 0){
            old_remaining_widths = [];
        }else{
            old_remaining_widths = new_selected_plates[index - 1].value;
        }

        if (plate.cut_from_same_plate){
            old_remaining_widths = old_remaining_widths.reduce((prevValue, currentValue) => {
                if (currentValue.width == plate.selected_width){
                    currentValue.quantity -= plate.selected_quantity;
                }

                if(currentValue.quantity > 0){
                    prevValue = prevValue.concat(currentValue);
                }

                return prevValue;
            }, []);
        }

        const merge_remaining_widths = old_remaining_widths.concat(remaining_widths);
        const check_remaining_widths = {};

        for(const merge_remaining_width of merge_remaining_widths){
            const width_text = merge_remaining_width.width.toString();

            if (typeof check_remaining_widths[width_text] !== 'undefined'){
                check_remaining_widths[width_text] += merge_remaining_width.quantity;
            }else{
                check_remaining_widths[width_text] = merge_remaining_width.quantity;
            }
        }

        const new_remaining_widths = [];
        for (const width in check_remaining_widths){
            const quantity = check_remaining_widths[width];
            new_remaining_widths.push({
                width: parseFloat(width),
                quantity: quantity,
            });
        }

        const new_remaining_widths_text = JSON.stringify(new_remaining_widths);

        new_selected_plates.push({
            ...selected_plate,
            value: new_remaining_widths,
        });

        //TODO: Set selected_remaining_widths
        if(typeof $('#selected_remaining_widths_'+ plate_stock_id).val() === 'undefined'){
            deliver_form.append('<input type="hidden" name="selected_remaining_widths['+ plate_stock_id + ']" id="selected_remaining_widths_'+ plate_stock_id + '" class="selected_remaining_widths" data-plate_stock_id="'+ plate_stock_id + '" value="">');
        }

        let current_remaining_widths_element = $('#selected_remaining_widths_'+ plate_stock_id);
        let current_remaining_widths_index =  current_remaining_widths_element.attr('data-index');

        if (typeof current_remaining_widths_index === 'undefined'){
            current_remaining_widths_index = 0;
        }else{
            current_remaining_widths_index = parseInt(current_remaining_widths_index) + 1;
        }

        current_remaining_widths_element.val(new_remaining_widths_text);
        current_remaining_widths_element.attr('data-index', current_remaining_widths_index);
        current_remaining_widths_element.attr('data-value_'+ current_remaining_widths_index, new_remaining_widths_text);
        current_remaining_widths_element.attr('data-row_id_'+ current_remaining_widths_index, row_id);

        //TODO: Update remaining_plates
        if(typeof $('#remaining_plates_'+ row_id).val() === 'undefined'){
            $(`#${row_id}`).append('<input type="hidden" name="products['+ row_index +'][plate_stock]['+ row_id + '][remaining_plates]" id="remaining_plates_'+ row_id + '" class="remaining_plates" value="">');
        }

        let order_number = 1;
        if(current_remaining_widths_index > 0){
            $('#select_plate_deliver_table').find('.remaining_plates').each(function(){
                let prev_remaining_plates_element = $(this);

                if(prev_remaining_plates_element.attr('id') !== `remaining_plates_${row_id}`){
                    let prev_tr = prev_remaining_plates_element.closest('tr');
                    let prev_remaining_plates_json = prev_remaining_plates_element.val();
                    let prev_remaining_plates = JSON.parse(prev_remaining_plates_json);
                    let exist = false;

                    $.each(prev_remaining_plates, function(index, value){
                        if(value.width == deliver_width){
                            prev_id = prev_tr.attr('id');
                            prev_remaining_plates[index]['next_id'] = row_id;
                            order_number = prev_remaining_plates[index]['order_number'] + 1;
                            prev_remaining_plates_json = JSON.stringify(prev_remaining_plates);
                            prev_remaining_plates_element.val(prev_remaining_plates_json);
                            exist = true;

                            return false;
                        }
                    });

                    if(exist){
                        return false;
                    }
                }
            });
        }

        let remaining_plates = [];
        $.each(remaining_widths, function(index, value){
            remaining_plates.push({
                width: value.width,
                quantity: value.quantity,
                plate_stock_id: plate_stock_id,
                order_number: order_number,
                id: row_id,
                next_id: '',
                prev_id: prev_id,
            });
        });

        let remaining_plates_json = JSON.stringify(remaining_plates);
        let remaining_plates_element = $('#remaining_plates_'+ row_id);

        remaining_plates_element.val(remaining_plates_json);

        //TODO: Update new plates_sort_order
        let current_plates_for_print = plate.plates_for_print;

        let plates_for_print = [];
        if (is_cut == 1){
            plates_for_print = current_plates_for_print.cut;
        }else{
            plates_for_print = current_plates_for_print.not_cut;
        }

        let is_exist = false;
        let transaction_sell_line_id = plate.transaction_sell_line_id;
        const origin_width = plate.origin_width;
        const selected_quantity = $('#quantity_before_cut_hidden').val();
        let plate_sort_order_for_cut_from_same_plate = null;

        plates_for_print.forEach(function(plate_for_print){
            plates_sort_order.every(function(plate_sort_order, index){
                if(plate_sort_order.plate_stock_id == plate_stock_id) {
                    if(plate_for_print.is_origin == 1){
                        if(plate_for_print.remaining_width == plate_sort_order.remaining_width && plate_sort_order.deliver_plates.length === 1 && plate_sort_order.deliver_plates[0].deliver_width == plate_for_print.deliver_width){
                            is_exist = true;
                            new_plates_sort_order[index].selected_quantity += plate_for_print.selected_quantity;
                            new_plates_sort_order[index].remaining_quantity += plate_for_print.remaining_quantity;
                            new_plates_sort_order[index].deliver_plates[0].deliver_quantity += plate_for_print.deliver_quantity;
                            return false;
                        }
                    }else{
                        if(plate_for_print.selected_width == plate_sort_order.remaining_width){
                            if (plate_sort_order.remaining_quantity > plate_for_print.remaining_quantity){
                                new_plates_sort_order[index].selected_quantity -= plate_for_print.selected_quantity;
                                new_plates_sort_order[index].remaining_quantity -= plate_for_print.remaining_quantity;

                                if (plate_sort_order.deliver_plates.length > 0){
                                    const deliver_plates_last_index = plate_sort_order.deliver_plates.length - 1;
                                    new_plates_sort_order[index].deliver_plates[deliver_plates_last_index].deliver_quantity = plate_sort_order.deliver_plates[deliver_plates_last_index].deliver_quantity - plate_for_print.deliver_quantity;

                                    //Get plate_sort_order_for_cut_from_same_plate
                                    console.log('plate_sort_order.deliver_plates[deliver_plates_last_index].deliver_quantity - new_plates_sort_order[index].deliver_plates[deliver_plates_last_index].deliver_quantity', plate_sort_order.deliver_plates[deliver_plates_last_index].deliver_quantity, new_plates_sort_order[index].deliver_plates[deliver_plates_last_index].deliver_quantity);
                                    const deliver_plates = plate_sort_order.deliver_plates;

                                    deliver_plates[deliver_plates.length - 1] = {
                                        ...deliver_plates[deliver_plates.length - 1],
                                        deliver_quantity: plate_sort_order.deliver_plates[deliver_plates_last_index].deliver_quantity - new_plates_sort_order[index].deliver_plates[deliver_plates_last_index].deliver_quantity,
                                    }

                                    deliver_plates.push({
                                        deliver_width: plate_for_print.deliver_width,
                                        deliver_quantity: plate_for_print.deliver_quantity,
                                    });

                                    plate_sort_order_for_cut_from_same_plate = {
                                        ...plate_sort_order,
                                        transaction_sell_line_id,
                                        selected_quantity: plate_for_print.selected_quantity,
                                        remaining_width: plate_for_print.remaining_width,
                                        remaining_quantity: plate_for_print.remaining_quantity,
                                        deliver_plates,
                                    };

                                    is_exist = true;
                                }

                                return false;
                            }else{
                                is_exist = true;
                                new_plates_sort_order[index].remaining_width = plate_for_print.remaining_width;
                                new_plates_sort_order[index].remaining_quantity = plate_for_print.remaining_quantity;

                                new_plates_sort_order[index].deliver_plates.push({
                                    deliver_width: plate_for_print.deliver_width,
                                    deliver_quantity: plate_for_print.deliver_quantity,
                                });
                                return false;
                            }
                        }
                    }
                }
                return true;
            });
        });

        //Update if is cut from same plate
        if(plate_sort_order_for_cut_from_same_plate) {
            new_plates_sort_order.push(plate_sort_order_for_cut_from_same_plate);
        }

        if(!is_exist){
            let transaction_sell_line_id = plate.transaction_sell_line_id;

            plates_for_print.forEach(function(item){
                new_plates_sort_order.push({
                    transaction_sell_line_id: transaction_sell_line_id,
                    plate_stock_id: plate_stock_id,

                    selected_width: item.selected_width,
                    selected_quantity: item.selected_quantity,

                    deliver_plates: [
                        {
                            deliver_width: item.deliver_width,
                            deliver_quantity: item.deliver_quantity,
                        }
                    ],

                    remaining_width: item.remaining_width,
                    remaining_quantity: item.remaining_quantity,

                    is_origin: item.is_origin,
                });
            });
        }
    }

    // console.log('new_selected_plates', new_selected_plates);

    return {
        new_plates_sort_order,
    }
}
