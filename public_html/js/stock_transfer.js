$(document).ready(function() {
    $(document).on('click', '.remove_sell_entry_row', function() {
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
            }
        });
    });

    var plate_stock_deliver_cols = [
        { data: 'sku', name: 'p.sku' },
        { data: 'product', name: 'p.name' },
        { data: 'height', name: 'height' },
        { data: 'width', name: 'width' },
        { data: 'stock', name: 'stock', searchable: false},
        { data: 'warehouses', name: 'warehouses' },
        { data: 'is_origin', name: 'is_origin' },
        { data: 'action', name: 'action', searchable: false, orderable: false },
    ];

    plate_stock_deliver_table = $('#plate_stock_deliver_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/sells/plate-stock',
            data: function(d) {
                d.location_id = $('#base_location_id').val();
                d.warehouse_id = $('#transfer_warehouse_id').val();
                d.category_id = $('#category_id').val();
                d.variation_id = $('#variation_id').val();
                d.width = $('#plate_width').val();
                d.height = $('#plate_height').val();
                d.quantity = $('#quantity').val();
            },
        },
        columns: plate_stock_deliver_cols,
    });

    $('#transfer_warehouse_id, #base_location_id, #plate_stock_deliver_filter_form #category_id, #plate_stock_deliver_filter_form #view_stock_filter, #plate_stock_deliver_filter_form #plate_width, #plate_stock_deliver_filter_form #plate_height, #plate_stock_deliver_filter_form #variation_id, #plate_stock_deliver_filter_form #quantity'
    ).change(function() {
        plate_stock_deliver_table.ajax.reload();
    });

    function get_stock_deliver_report_details(rowData, location_id, category_id, width, height, warehouse_id) {
        var div = $('<div/>')
            .addClass('loading')
            .text('Loading...');
        $.ajax({
            url: '/sells/plate-stock-detail/' + rowData.variation_id,
            dataType: 'html',
            data: {
                'location_id': location_id,
                'category_id': category_id,
                'warehouse_id': warehouse_id,
                'width': rowData.width,
                'height': rowData.height,
                'is_origin': rowData.is_origin,
                'layout': 'stock_transfer',
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
        let location_id = $('#base_location_id').val();
        let warehouse_id = $('#transfer_warehouse_id').val();
        let category_id = $('#category_id').val();
        let width = $('#plate_width').val();
        let height = $('#plate_height').val();

        if (row.child.isShown()) {
            $(this)
                .find('i')
                .removeClass('fa-eye')
                .addClass('fa-eye-slash');
            row.child.hide();

            // Remove from the 'open' array
            deliverDetailRows.splice(idx, 1);
        } else {
            $(this)
                .find('i')
                .removeClass('fa-eye-slash')
                .addClass('fa-eye');

            row.child(get_stock_deliver_report_details(row.data(), location_id, category_id, width, height, warehouse_id)).show();

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

    $('.select_product_button').click(function(){
        let tr = $(this).closest('tr');
        let product_id = tr.find('.product_id').val();
        let category_id = tr.find('.category_id').val();
        let width = parseFloat(tr.find('.width').html());
        let height = parseFloat(tr.find('.height').html());

        $('#plate_stock_deliver_filter_form #plate_width').val(width);
        $('#plate_stock_deliver_filter_form #plate_height').val(height);

        $('#stock_adjustment_product_table').find('tr.selected').removeClass('selected');
        tr.addClass('selected');
        $('#plate_stock_deliver_modal input:checkbox').not(this).prop('checked', false);

        let is_change = false;
        let current_category_id = $('#category_id').val();
        if(current_category_id != category_id){
            $('#category_id').val(category_id);
            getProductsByCategory(category_id, product_id);
            is_change = true;
        }

        let current_product_id = $('#variation_id').val();
        if(current_product_id != product_id){
            $('#variation_id').val(product_id);
            is_change = true;
        }

        if(is_change){
            plate_stock_deliver_table.ajax.reload();
        }
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

    $('#plate_stock_deliver_filter_form #category_id, #plate_stock_deliver_filter_form #category_id').change(function() {
        let category_id = $(this).val();
        getProductsByCategory(category_id);
    });

    //Change business location
    function changeLocation(location_id){
        if($('select#base_location_id').val() != undefined){
            if ($('select#base_location_id').val()) {
                $('#plate_stock_button').prop('disabled', false);
            } else {
                $('#plate_stock_button').prop('disabled', true);
            }
        }
    }

    $('select#base_location_id').change(function() {
        changeLocation();
    });

    changeLocation();

    //Select multi plate
    $('#plate_stock_deliver_modal').on('click', '#select_plates', function() {
        let tr = $('#stock_adjustment_product_table').find('tr.selected');

        let current_plate_stock_ids = $('#stock_adjustment_product_table .product_row').map(function(){
            return $(this).attr('data-plate_stock_id');
        }).get();
        let select_plate_stock_ids = $("#plate_stock_deliver_table .plate_stock_ids:checked").map(function(){
            return $(this).val();
        }).get();
        let all_plate_stock_ids = current_plate_stock_ids.concat(select_plate_stock_ids);
        var plate_stock_ids = all_plate_stock_ids.filter(function(obj) {
            return current_plate_stock_ids.indexOf(obj) == -1;
        });

        $.ajax({
            method: 'POST',
            url: '/stock-transfers/get_sell_entry_row',
            dataType: 'html',
            data: {
                plate_stock_ids: plate_stock_ids,
            },
            success: function(result) {
                if(result != ''){
                    let error = '';

                    if(result == 'permission_denied'){
                        error = LANG.permission_denied;
                    }

                    if(error == ''){
                        $('#stock_adjustment_product_table tbody').append(result);
                    }else{
                        swal({
                            title: error,
                            icon: 'warning',
                            dangerMode: true,
                        });
                    }
                }
            },
        });

        $('#plate_stock_deliver_modal').modal('toggle');
    });

    /*//Add products
    if ($('#search_product_for_srock_adjustment').length > 0) {
        //Add Product
        $('#search_product_for_srock_adjustment')
            .autocomplete({
                source: function(request, response) {
                    $.getJSON(
                        '/products/list',
                        { location_id: $('#location_id').val(), term: request.term },
                        response
                    );
                },
                minLength: 2,
                response: function(event, ui) {
                    if (ui.content.length == 1) {
                        ui.item = ui.content[0];
                        if (ui.item.qty_available > 0 && ui.item.enable_stock == 1) {
                            $(this)
                                .data('ui-autocomplete')
                                ._trigger('select', 'autocompleteselect', ui);
                            $(this).autocomplete('close');
                        }
                    } else if (ui.content.length == 0) {
                        swal(LANG.no_products_found);
                    }
                },
                focus: function(event, ui) {
                    if (ui.item.qty_available <= 0) {
                        return false;
                    }
                },
                select: function(event, ui) {
                    if (ui.item.qty_available > 0) {
                        $(this).val(null);
                        stock_transfer_product_row(ui.item.variation_id);
                    } else {
                        alert(LANG.out_of_stock);
                    }
                },
            })
            .autocomplete('instance')._renderItem = function(ul, item) {
            if (item.qty_available <= 0) {
                var string = '<li class="ui-state-disabled">' + item.name;
                if (item.type == 'variable') {
                    string += '-' + item.variation;
                }
                string += ' (' + item.sub_sku + ') (Out of stock) </li>';
                return $(string).appendTo(ul);
            } else if (item.enable_stock != 1) {
                return ul;
            } else {
                var string = '<div>' + item.name;
                if (item.type == 'variable') {
                    string += '-' + item.variation;
                }
                string += ' (' + item.sub_sku + ') </div>';
                return $('<li>')
                    .append(string)
                    .appendTo(ul);
            }
        };
    }*/

    /*$('select#location_id').change(function() {
        if ($(this).val()) {
            $('#search_product_for_srock_adjustment').removeAttr('disabled');
        } else {
            $('#search_product_for_srock_adjustment').attr('disabled', 'disabled');
        }
        $('table#stock_adjustment_product_table tbody').html('');
        $('#product_row_index').val(0);
        update_table_total();
    });*/

    /*$(document).on('change', 'input.product_quantity', function() {
        update_table_row($(this).closest('tr'));
    });
    $(document).on('change', 'input.product_unit_price', function() {
        update_table_row($(this).closest('tr'));
    });*/

    /*$(document).on('click', '.remove_product_row', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                $(this)
                    .closest('tr')
                    .remove();
                update_table_total();
            }
        });
    });*/

    /*//Date picker
    $('#transaction_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });*/

    /*jQuery.validator.addMethod(
        'notEqual',
        function(value, element, param) {
            return this.optional(element) || value != param;
        },
        'Please select different location'
    );*/

    /*$('form#stock_transfer_form').validate({
        rules: {
            transfer_location_id: {
                notEqual: function() {
                    return $('select#location_id').val();
                },
            },
        },
    });*/

    $('#save_stock_transfer').click(function(e) {
        e.preventDefault();

        if ($('table#stock_adjustment_product_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        let error = false;

        $('#stock_adjustment_product_table .product_row').each(function(){
            let tr = $(this);
            let warehouse_id_before = tr.find('.warehouse_id_before').val();
            let warehouse_id_after = tr.find('.warehouse_id').val();

            if(warehouse_id_before == warehouse_id_after){
                tr.attr('style', 'color:#f00;');
                toastr.warning(LANG.warehouse_transfer_not_valid);
                error = true;
            }else{
                tr.removeAttr('style');
            }
        });

        if ($('form#stock_transfer_form').valid() && !error) {
            $('#save_stock_transfer').attr('disabled', true);
            $('form#stock_transfer_form').submit();
        }else{
            return false;
        }
    });

    stock_transfer_table = $('#stock_transfer_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/stock-transfers',
        columnDefs: [
            {
                targets: 6,
                orderable: false,
                searchable: false,
            },
        ],
        aaSorting: [[0, 'desc']],
        columns: [
            { data: 'transaction_date', name: 'transaction_date' },
            { data: 'ref_no', name: 'ref_no' },
            { data: 'location_from', name: 'l1.name' },
            { data: 'location_to', name: 'l2.name' },
            { data: 'shipping_charges', name: 'shipping_charges' },
            { data: 'additional_notes', name: 'additional_notes' },
            { data: 'action', name: 'action' },
        ],
        fnDrawCallback: function(oSettings) {
            __currency_convert_recursively($('#stock_transfer_table'));
        },
    });
    var detailRows = [];

    $('#stock_transfer_table tbody').on('click', '.view_stock_transfer', function() {
        var tr = $(this).closest('tr');
        var row = stock_transfer_table.row(tr);
        var idx = $.inArray(tr.attr('id'), detailRows);

        if (row.child.isShown()) {
            $(this)
                .find('i')
                .removeClass('fa-eye')
                .addClass('fa-eye-slash');
            row.child.hide();

            // Remove from the 'open' array
            detailRows.splice(idx, 1);
        } else {
            $(this)
                .find('i')
                .removeClass('fa-eye-slash')
                .addClass('fa-eye');

            row.child(get_stock_transfer_details(row.data())).show();

            // Add to the 'open' array
            if (idx === -1) {
                detailRows.push(tr.attr('id'));
            }
        }
    });

    // On each draw, loop over the `detailRows` array and show any child rows
    stock_transfer_table.on('draw', function() {
        $.each(detailRows, function(i, id) {
            $('#' + id + ' .view_stock_transfer').trigger('click');
        });
    });

    //Delete Stock Transfer
    $(document).on('click', 'button.delete_stock_transfer', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var href = $(this).data('href');
                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success) {
                            toastr.success(result.msg);
                            stock_transfer_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });
});

function stock_transfer_product_row(variation_id) {
    var row_index = parseInt($('#product_row_index').val());
    var location_id = $('select#location_id').val();
    $.ajax({
        method: 'POST',
        url: '/stock-adjustments/get_product_row',
        data: { row_index: row_index, variation_id: variation_id, location_id: location_id },
        dataType: 'html',
        success: function(result) {
            $('table#stock_adjustment_product_table tbody').append(result);
            update_table_total();
            $('#product_row_index').val(row_index + 1);
        },
    });
}

function update_table_total() {
    var table_total = 0;
    $('table#stock_adjustment_product_table tbody tr').each(function() {
        var this_total = parseFloat(__read_number($(this).find('input.product_line_total')));
        if (this_total) {
            table_total += this_total;
        }
    });
    $('input#total_amount').val(table_total);
    $('span#total_adjustment').text(__number_f(table_total));
}

function update_table_row(tr) {
    var quantity = parseFloat(__read_number(tr.find('input.product_quantity')));
    var unit_price = parseFloat(__read_number(tr.find('input.product_unit_price')));
    var row_total = 0;
    if (quantity && unit_price) {
        row_total = quantity * unit_price;
    }
    tr.find('input.product_line_total').val(__number_f(row_total));
    update_table_total();
}

function get_stock_transfer_details(rowData) {
    var div = $('<div/>')
        .addClass('loading')
        .text('Loading...');
    $.ajax({
        url: '/stock-transfers/' + rowData.DT_RowId,
        dataType: 'html',
        success: function(data) {
            div.html(data).removeClass('loading');
        },
    });

    return div;
}

// $('table#stock_adjustment_product_table tbody').on('change', 'input.transfer_quantity', function() {
//     var qty_element = $(this)
//         .closest('tr')
//         .find('input.transfer_quantity');
//
//     if ($(this).val()) {
//         var lot_qty = $(this).data('qty_available');
//         var max_err_msg = $(this).data('msg-max-value');
//
//         if ($(this).val() > lot_qty) {
//             toastr.warning(max_err_msg);
//         }
//     }
//     qty_element.trigger('change');
// });
