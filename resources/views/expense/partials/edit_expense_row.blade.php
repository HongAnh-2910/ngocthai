<div class="modal-dialog" role="document">
    <div class="modal-content">

        {!! Form::open(['url' => action('ExpenseController@updateExpenseRow', [$expense->id]), 'method' => 'post', 'id' => 'expense_update_form']) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'expense.edit_expense' )</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="form-group col-sm-12">
                    {!! Form::label('expense_type', __( 'expense.type' ) . ':*') !!}
                    {!! Form::select('type', \App\TransactionExpense::$TYPES, $expense->type, ['class' => 'form-control expense_row', 'required', 'id' => 'expense_type']); !!}
                </div>

                <div class="form-group col-sm-12 expense_customer" style="{{ $expense->type == 'return_customer' ? 'display: block' : 'display: none' }}">
                    {!! Form::label('expense_customer_row', __( 'expense.customer' ) . ':*') !!}
                    {!! Form::select('contact_id', $users, $expense->contact_id,
                        [
                            'class' => 'form-control expense_customer_row select2',
                            $expense->type == 'return_customer' ? 'required' : '',
                            'id' => 'expense_customer_row',
                            'style' => 'width: 100%'
                        ]);
                    !!}
                </div>

                <div class="form-group col-sm-12 expense_description">
                    {!! Form::label('note', __( 'expense.content' ) . ':') !!}
                    {!! Form::textarea('note',
                        $expense->note,
                        [
                            'class' => 'form-control expense_description_row',
                            'rows' => 3
                        ]);
                    !!}
                </div>
                <div class="form-group col-sm-12">
                    {!! Form::label('total_money', __( 'expense.total_money' ) . ':*') !!}
                    {!! Form::text('total_money',
                        number_format($expense->total_money),
                        [
                            'class' => 'form-control input_number expense_total_row',
                            'required'
                        ]);
                    !!}
                </div>

                <div class="form-group col-sm-12">
                    {!! Form::label('expense_method_row', __( 'expense.payment_method' ) . ':*') !!}
                    {!! Form::select('method', ['cash' => __('lang_v1.cash'), 'bank_transfer' => __('lang_v1.bank_transfer')], $method->payment_lines[0]->method,
                        [
                            'class' => 'form-control expense_payment_types_dropdown expense_method_row',
                            'required',
                            'id' => 'expense_method_row',
                        ]);
                    !!}
                </div>
                <div class="form-group col-sm-12 expense_bank_account" style="{{ $method->payment_lines[0]->method == 'bank_transfer' ? 'display: block' : 'display: none' }}">
                    {!! Form::label('bank_account', __( 'expense.account_bank' ) . ':') !!}
                    {!! Form::text('bank_account',
                        $method->payment_lines[0]->bank_account_number,
                        [
                            'class' => 'form-control expense_bank_account_row'
                        ]);
                    !!}
                </div>
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
        $('.expense_customer_row').select2();
    });
</script>