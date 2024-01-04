$(document).ready(function() {
    $(document).on('click', '.approve_remaining_payment', function(e) {
        let tr = $(this).closest('tr');
        let approve_remaining_payment_element = $(this);
        let amount_approve = __read_number(tr.find('.amount_approve'));

        $.ajax({
            url: $(this).data('href'),
            dataType: 'json',
            success: function(result) {
                if(result.success){
                    approve_remaining_payment_element.hide();
                    tr.find('.reject_remaining_payment').hide();
                    tr.removeAttr('style');
                    tr.find('.approval_status').html(LANG.confirmed);
                    sell_table.ajax.reload();
                    toastr.success(result.msg);
                    let total_bank = result.data.update_total_bank + amount_approve;
                    $('.total_bank').text(__currency_trans_from_en(total_bank, true, false));
                }else{
                    toastr.error(result.msg);
                }
            },
        });
    });

    $(document).on('click', '.reject_remaining_payment', function(e) {
        let tr = $(this).closest('tr');

        $.ajax({
            url: $(this).data('href'),
            dataType: 'json',
            success: function(result) {
                if(result.success){
                    tr.remove();
                    sell_table.ajax.reload();
                    toastr.success(result.msg);
                }else{
                    toastr.error(result.msg);
                }
            },
        });
    });

    $(document).on('click', '.add_payment_modal', function(e) {
        e.preventDefault();
        var container = $('.payment_modal');

        $.ajax({
            url: $(this).attr('href'),
            dataType: 'json',
            success: function(result) {
                if (result.status == 'due') {
                    container.html(result.view).modal('show');
                    __currency_convert_recursively(container);
                    /*$('#paid_on').datetimepicker({
                        format: moment_date_format + ' ' + moment_time_format,
                        ignoreReadonly: true,
                    });*/
                    container.find('form#transaction_payment_add_form').validate();
                    getFormatNumber();
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });
    $(document).on('click', '.edit_payment', function(e) {
        e.preventDefault();
        var container = $('.edit_payment_modal');

        $.ajax({
            url: $(this).data('href'),
            dataType: 'html',
            success: function(result) {
                container.html(result).modal('show');
                __currency_convert_recursively(container);
                container.find('form#transaction_payment_add_form').validate();
                getFormatNumber();
            },
        });
    });

    $(document).on('click', '.view_payment_modal', function(e) {
        e.preventDefault();
        var container = $('.payment_modal');

        $.ajax({
            url: $(this).attr('href'),
            dataType: 'html',
            success: function(result) {
                $(container)
                    .html(result)
                    .modal('show');
                __currency_convert_recursively(container);
            },
        });
    });

    $(document).on('click', '.view_expense_modal', function(e) {
        e.preventDefault();
        $('div.expense_modal').load($(this).attr('href'), function() {
            $(this).modal('show');
        });
    });

    $(document).on('click', '.delete_payment', function(e) {
        swal({
            title: LANG.sure,
            text: LANG.confirm_delete_payment,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                $.ajax({
                    url: $(this).data('href'),
                    method: 'delete',
                    dataType: 'json',
                    success: function(result) {
                        if (result.success === true) {
                            $('div.payment_modal').modal('hide');
                            $('div.edit_payment_modal').modal('hide');
                            toastr.success(result.msg);
                            if (typeof purchase_table != 'undefined') {
                                purchase_table.ajax.reload();
                            }
                            if (typeof sell_table != 'undefined') {
                                sell_table.ajax.reload();
                            }
                            if (typeof expense_table != 'undefined') {
                                expense_table.ajax.reload();
                            }
                            if (typeof sell_return_table != 'undefined') {
                                sell_return_table.ajax.reload();
                            }
                            if (typeof ob_payment_table != 'undefined') {
                                ob_payment_table.ajax.reload();
                            }
                            // project Module
                            if (typeof project_invoice_datatable != 'undefined') {
                                project_invoice_datatable.ajax.reload();
                            }
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });

    //view single payment
    $(document).on('click', '.view_payment', function() {
        var url = $(this).data('href');
        var container = $('.view_modal');
        $.ajax({
            method: 'GET',
            url: url,
            dataType: 'html',
            success: function(result) {
                $(container)
                    .html(result)
                    .modal('show');
                __currency_convert_recursively(container);
            },
        });
    });
});
