<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action('ExpenseController@updateReceiptRow', [$receipt->id]), 'method' => 'post', 'id' => 'receipt_update_form']) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'expense.edit_receipt' )</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="form-group col-sm-12">
                    {!! Form::label('receipt_type', __( 'expense.type' ) . ':*') !!}
                    {!! Form::select('type', \App\TransactionReceipt::$TYPES, $receipt->type, ['class' => 'form-control receipt_row', 'required', 'id' => 'receipt_type']); !!}
                </div>

                <div class="form-group col-sm-12 receipt_customer" style="{{ $receipt->type == 'receipt' ? 'display: none' : 'display: block' }}">
                    {!! Form::label('receipt_customer_row', __( 'expense.customer' ) . ':*') !!}
                    {!! Form::select('contact_id', $users, $receipt->contact_id,
                        [
                            'class' => 'form-control receipt_customer_row select2',
                            $receipt->type != 'receipt' ? 'required' : '',
                            'id' => 'receipt_customer_row',
                            'style' => 'width: 100%'
                        ]);
                    !!}
                </div>

                <div class="form-group col-sm-12 receipt_description">
                    {!! Form::label('note', __( 'expense.content' ) . ':') !!}
                    {!! Form::textarea('note',
                        $receipt->note,
                        [
                            'class' => 'form-control receipt_description_row',
                            'rows' => 3
                        ]);
                    !!}
                </div>
                <div class="form-group col-sm-12">
                    {!! Form::label('total_money', __( 'expense.total_money' ) . ':*') !!}
                    {!! Form::text('total_money',
                        number_format($receipt->total_money),
                        [
                            'class' => 'form-control input_number receipt_total_row',
                            'required'
                        ]);
                    !!}
                </div>

                <div class="form-group col-sm-12">
                    {!! Form::label('receipt_method_row', __( 'expense.payment_method' ) . ':*') !!}
                    {!! Form::select('method', ['cash' => __('lang_v1.cash'), 'bank_transfer' => __('lang_v1.bank_transfer')], $method->payment_lines[0]->method,
                        [
                            'class' => 'form-control receipt_payment_types_dropdown receipt_method_row',
                            'required',
                            'id' => 'receipt_method_row',
                        ]);
                    !!}
                </div>
                <div class="form-group col-sm-12 receipt_bank_account" style="{{ $method->payment_lines[0]->method == 'bank_transfer' ? 'display: block' : 'display: none' }}">
                    {!! Form::label('bank_account', __( 'expense.account_bank' ) . ':') !!}
                    {!! Form::text('bank_account',
                        $method->payment_lines[0]->bank_account_number,
                        [
                            'class' => 'form-control receipt_bank_account_row'
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
        $('.receipt_customer_row').select2();
    });
</script>