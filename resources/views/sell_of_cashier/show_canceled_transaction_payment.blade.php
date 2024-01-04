<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title no-print">
                @lang( 'messages.payment_has_been_canceled' )
            </h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-xs-6">
                    <b>@lang('purchase.amount'):</b>
                    {{ $notification_data['type'] == 'expense' ? @num_format($notification_data['amount'] * -1) : @num_format($notification_data['amount']) }}đ
                    <br>

                    <strong>@lang('lang_v1.payment_method'):</strong>
                    {{ $payment_types[$notification_data['method']] ?? '' }}<br>
                </div>
                <div class="col-xs-6">
                    <b>@lang('purchase.payment_approve'):</b>
                    @lang('sale.cancelled')<br>

                    @if($transaction->type == 'sell')
                        <strong>@lang('sale.invoice_no'):</strong>
                        <button type="button" class="btn btn-xs bg-success btn-modal" data-container=".sell_modal" data-href="{{ action('SellController@show', [$transaction->id]) }}">{{ $transaction->invoice_no }}</button>
                    @endif
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default no-print" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>
    </div>
</div>
