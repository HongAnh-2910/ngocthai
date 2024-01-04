<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action('ReduceDebtController@store'), 'method' => 'post', 'id' => 'reduce_debt_form']) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'contact.create_reduce_debt' )</h4>
        </div>

        <div class="modal-body">
            <div class="form-group">
                {!! Form::label('transaction_date', __('contact.reduce_debt_date') . ':') !!}
                {!! Form::text('transaction_date', @format_datetime($transaction_date), ['class' => 'form-control', 'readonly', 'required']); !!}
            </div>
            <div class="form-group">
                {!! Form::label('contact_id', __('contact.customer').':*') !!}
                {!! Form::select('contact_id', $customers, $contact->id, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.please_select'), 'required']); !!}
            </div>
            <div class="form-group">
                {!! Form::label('final_total', __( 'contact.reduce_debt_amount' ) . ':*') !!}
                {!! Form::number('final_total', null, ['class' => 'form-control', 'required', 'placeholder' => __( 'contact.reduce_debt_amount' ) ]); !!}
            </div>
            <div class="form-group">
                {!! Form::label('additional_notes', __( 'contact.reduce_debt_note' ) . ':') !!}
                {!! Form::textarea('additional_notes', null, ['class' => 'form-control','placeholder' => __( 'contact.reduce_debt_note' ), 'rows' => 1]); !!}
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary reduce-debt-button">@lang( 'messages.save' )</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>

        {!! Form::close() !!}

    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script type="text/javascript">
    $(function () {
        $('#final_total').on('change', function(){
            let final_total = parseInt($(this).val());
            if(final_total == 0){
                $('.reduce-debt-button').attr('disabled', true);
                $(this).css('border-color', 'red');
            }else{
                $('.reduce-debt-button').removeAttr('disabled');
                $(this).css('border-color', '#d2d6de');
            }
        });
    })
</script>