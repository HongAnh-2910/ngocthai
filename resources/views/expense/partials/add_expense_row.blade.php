<div class="modal-dialog" role="document">
    <div class="modal-content">

        {!! Form::open(['url' => action('ExpenseController@storeExpenseRow'), 'method' => 'post', 'id' => 'expense_add_form']) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'expense.add_new_expense' )</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                @if(is_null($default_location))
                    <div class="form-group col-sm-12">
                        {!! Form::label('location', __('messages.location') . ':*') !!}
                        {!! Form::select('select_location_id', $business_locations, null, ['class' => 'form-control',
                        'placeholder' => __('lang_v1.select_location'),
                        'id' => 'select_location_id',
                        'required', 'autofocus'], $bl_attributes); !!}
                    </div>
                @endif
                {!! Form::hidden('location_id', !empty($default_location) ? $default_location->id : null , ['id' => 'location_id', 'data-receipt_printer_type' => !empty($default_location->receipt_printer_type) ? $default_location->receipt_printer_type : 'browser', 'data-default_accounts' => !empty($default_location) ? $default_location->default_payment_accounts : '']); !!}

                <div class="form-group col-sm-12 expense_transaction_date">
                    {!! Form::label('expense_transaction_date_row', __('receipt.date') . ':*') !!}
                    <input type="text" name="transaction_date" value="{{ @format_datetime($transaction_date) }}" class="form-control expense_transaction_date_row" style="width: 100%" id="expense_transaction_date_row" readonly required>
                </div>

                <div class="form-group col-sm-12">
                    {!! Form::label('expense_type', __( 'expense.type' ) . ':*') !!}
                    {!! Form::select('type', \App\TransactionExpense::$TYPES, \App\TransactionReceipt::INCOME, ['class' => 'form-control expense_row', 'required', 'id' => 'expense_type']); !!}
                </div>

                <div class="form-group col-sm-12 expense_customer" style="display: none">
                    {!! Form::label('expense_customer_row', __( 'expense.customer' ) . ':*') !!}
                    {!! Form::select('contact_id', $users, null,
                        [
                            'class' => 'form-control expense_customer_row select2',
                            'required',
                            'id' => 'expense_customer_row',
                            'style' => 'width: 100%',
                            'disabled'
                        ]);
                    !!}
                </div>

                <div class="form-group col-sm-12 expense_description">
                    {!! Form::label('note', __( 'expense.content' ) . ':') !!}
                    {!! Form::textarea('note',
                        null,
                        [
                            'class' => 'form-control expense_description_row',
                            'rows' => 3
                        ]);
                    !!}
                </div>
                <div class="form-group col-sm-12">
                    {!! Form::label('total_money', __( 'expense.total_money' ) . ':*') !!}
                    {!! Form::text('total_money',
                        0,
                        [
                            'class' => 'form-control input_number expense_total_row',
                            'required',
                        ]);
                    !!}
                </div>

                <div class="form-group col-sm-12">
                    {!! Form::label('expense_method_row', __( 'expense.payment_method' ) . ':*') !!}
                    {!! Form::select('method', ['cash' => __('lang_v1.cash'), 'bank_transfer' => __('lang_v1.bank_transfer')], null,
                        [
                            'class' => 'form-control expense_payment_types_dropdown expense_method_row',
                            'required',
                            'id' => 'method',
                        ]);
                    !!}
                </div>
                <div class="form-group col-sm-12 expense_bank_account" style="display: none">
                    {!! Form::label('bank_account', __( 'lang_v1.bank_account_number' ) . ':') !!}
                    {!! Form::text('bank_account',
                        null,
                        [
                            'class' => 'form-control expense_bank_account_row',
                            'disabled'
                        ]);
                    !!}
                </div>
                @if(!empty($accounts))
                    <div class="form-group col-sm-12">
                        {!! Form::label("account_id" , __('lang_v1.payment_account') . ':') !!}
                        {!! Form::select("account_id", $accounts, !empty($payment_line->account_id) ? $payment_line->account_id : '' , ['class' => 'form-control select2 account_id', 'id' => "account_id", 'style' => 'width:100%;']); !!}
                    </div>
                @endif
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>

        {!! Form::close() !!}

    </div>
</div>
<script>
    $(document).ready(function (){
        /*$('#expense_transaction_date_row').daterangepicker({
            singleDatePicker: true,
            locale: {
                format: "DD/MM/YYYY"
            },
        });*/

        $('.expense_customer_row').select2();

        $('select#select_location_id').change(function() {
            if ($('select#select_location_id').length == 1) {
                $('input#location_id').val($('select#select_location_id').val());
                $('input#location_id').data(
                    'receipt_printer_type',
                    $('select#select_location_id')
                        .find(':selected')
                        .data('receipt_printer_type')
                );
            }
        });
    });
</script>
