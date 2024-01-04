<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title no-print">
                @lang( 'sale.payment_confirm' )
                @if(!empty($payment->payment_ref_no))
                    ( @lang('sale.payment_ref_no'): {{ $payment->payment_ref_no }} )
                @endif
            </h4>
            <h4 class="modal-title visible-print-block">
                @if(!empty($payment->payment_ref_no))
                    ( @lang('sale.payment_ref_no'): {{ $payment->payment_ref_no }} )
                @endif
            </h4>
        </div>
        <div class="modal-body">
            @if(!empty($transaction))
                <div class="row">
                    @if(in_array($transaction->type, ['purchase', 'purchase_return']))
                        <div class="col-xs-6">
                            @lang('purchase.supplier'):
                            <address>
                                <strong>{{ $transaction->contact->supplier_business_name }}</strong>
                                {{ $transaction->contact->name }}
                                @if(!empty($transaction->contact->landmark))
                                    <br>{{$transaction->contact->landmark}}
                                @endif
                                @if(!empty($transaction->contact->city) || !empty($transaction->contact->state) || !empty($transaction->contact->country))
                                    <br>{{implode(',', array_filter([$transaction->contact->city, $transaction->contact->state, $transaction->contact->country]))}}
                                @endif
                                @if(!empty($transaction->contact->tax_number))
                                    <br>@lang('contact.tax_no'): {{$transaction->contact->tax_number}}
                                @endif
                                @if(!empty($transaction->contact->mobile))
                                    <br>@lang('contact.mobile'): {{$transaction->contact->mobile}}
                                @endif
                                @if(!empty($transaction->contact->email))
                                    <br>@lang('business.email'): {{$transaction->contact->email}}
                                @endif
                            </address>
                        </div>
                        <div class="col-xs-6">
                            @lang('business.business'):
                            <address>
                                <strong>{{ $transaction->business->name }}</strong>

                                @if(!empty($transaction->location))
                                    {{ $transaction->location->name }}
                                    @if(!empty($transaction->location->landmark))
                                        <br>{{$transaction->location->landmark}}
                                    @endif
                                    @if(!empty($transaction->location->city) || !empty($transaction->location->state) || !empty($transaction->location->country))
                                        <br>{{implode(',', array_filter([$transaction->location->city, $transaction->location->state, $transaction->location->country]))}}
                                    @endif
                                @endif

                                @if(!empty($transaction->business->tax_number_1))
                                    <br>{{$transaction->business->tax_label_1}}: {{$transaction->business->tax_number_1}}
                                @endif

                                @if(!empty($transaction->business->tax_number_2))
                                    <br>{{$transaction->business->tax_label_2}}: {{$transaction->business->tax_number_2}}
                                @endif

                                @if(!empty($transaction->location))
                                    @if(!empty($transaction->location->mobile))
                                        <br>@lang('contact.mobile'): {{$transaction->location->mobile}}
                                    @endif
                                    @if(!empty($transaction->location->email))
                                        <br>@lang('business.email'): {{$transaction->location->email}}
                                    @endif
                                @endif
                            </address>
                        </div>
                    @else
                        <div class="col-xs-6">
                            @if($transaction->type != 'payroll')
                                @lang('contact.customer'):
                                <address>
                                    <strong>{{ $transaction->contact->name ?? '' }}</strong>

                                    @if(!empty($transaction->contact->landmark))
                                        <br>{{$transaction->contact->landmark}}
                                    @endif
                                    @if(!empty($transaction->contact->city) || !empty($transaction->contact->state) || !empty($transaction->contact->country))
                                        <br>{{implode(',', array_filter([$transaction->contact->city, $transaction->contact->state, $transaction->contact->country]))}}
                                    @endif
                                    @if(!empty($transaction->contact->tax_number))
                                        <br>@lang('contact.tax_no'): {{$transaction->contact->tax_number}}
                                    @endif
                                    @if(!empty($transaction->contact->mobile))
                                        <br>@lang('contact.mobile'): {{$transaction->contact->mobile}}
                                    @endif
                                    @if(!empty($transaction->contact->email))
                                        <br>@lang('business.email'): {{$transaction->contact->email}}
                                    @endif
                                </address>
                            @else
                                @lang('essentials::lang.payroll_for'):
                                <address>
                                    <strong>{{ $transaction->transaction_for->user_full_name }}</strong>
                                    @if(!empty($transaction->transaction_for->address))
                                        <br>{{$transaction->transaction_for->address}}
                                    @endif
                                    @if(!empty($transaction->transaction_for->contact_number))
                                        <br>@lang('contact.mobile'): {{$transaction->transaction_for->contact_number}}
                                    @endif
                                    @if(!empty($transaction->transaction_for->email))
                                        <br>@lang('business.email'): {{$transaction->transaction_for->email}}
                                    @endif
                                </address>
                            @endif
                        </div>
                        <div class="col-xs-6">
                            @lang('business.business'):
                            <address>
                                <strong>{{ $transaction->business->name }}</strong>
                                @if(!empty($transaction->location))
                                    {{ $transaction->location->name }}
                                    @if(!empty($transaction->location->landmark))
                                        <br>{{$transaction->location->landmark}}
                                    @endif
                                    @if(!empty($transaction->location->city) || !empty($transaction->location->state) || !empty($transaction->location->country))
                                        <br>{{implode(',', array_filter([$transaction->location->city, $transaction->location->state, $transaction->location->country]))}}
                                    @endif
                                @endif

                                @if(!empty($transaction->business->tax_number_1))
                                    <br>{{$transaction->business->tax_label_1}}: {{$transaction->business->tax_number_1}}
                                @endif

                                @if(!empty($transaction->business->tax_number_2))
                                    <br>{{$transaction->business->tax_label_2}}: {{$transaction->business->tax_number_2}}
                                @endif

                                @if(!empty($transaction->location))
                                    @if(!empty($transaction->location->mobile))
                                        <br>@lang('contact.mobile'): {{$transaction->location->mobile}}
                                    @endif
                                    @if(!empty($transaction->location->email))
                                        <br>@lang('business.email'): {{$transaction->location->email}}
                                    @endif
                                @endif
                            </address>
                        </div>
                    @endif
                </div>
            @endif
            <div class="row">
                <br>
                <div class="col-xs-6">
                    <strong>@lang('purchase.amount'):</strong>
                    {{$payment->type == 'expense' ? @num_format($payment->amount * -1) : @num_format($payment->amount)}}đ
                    <br>
                    <strong>@lang('lang_v1.payment_method'):</strong>
                    {{ $payment_types[$payment->method] ?? '' }}<br>
                    @if($payment->method == "card")
                        <strong>@lang('lang_v1.card_holder_name'):</strong>
                        {{ $payment->card_holder_name }} <br>
                        <strong>@lang('lang_v1.card_number'):</strong>
                        {{ $payment->card_number }} <br>
                        <strong>@lang('lang_v1.card_transaction_number'):</strong>
                        {{ $payment->card_transaction_number }}
                    @elseif($payment->method == "cheque")
                        <strong>@lang('lang_v1.cheque_number'):</strong>
                        {{ $payment->cheque_number }}
                    @elseif($payment->method == "bank_transfer")

                    @endif

                    @if(!empty($payment->bank_account_number))
                        <strong>@lang('lang_v1.bank_account_number'):</strong>
                        {{ $payment->bank_account_number }}<br/>
                    @endif
                    <strong>@lang('purchase.payment_note'):</strong>
                    {{ $payment->note }}<br/>

                    @if($transaction->type == 'sell')
                        <strong>@lang('sale.invoice_no'):</strong>
                        <button type="button" class="btn btn-xs bg-success btn-modal" data-container=".sell_modal" data-href="{{ action('SellController@show', [$transaction->id]) }}">{{ $transaction->invoice_no }}</button>
                    @endif
                </div>
                <div class="col-xs-6">
                    <b>@lang('purchase.payment_approve'):</b>
                    {!! $approval_status_html !!}
                    <br>
                    @if($user_confirm['type'] == 'both')
                        <b>@lang('sale.cashier_confirm'):</b>
                        {{ $user_confirm['cashier_name'] }}
                        <br>
                        <b>@lang('sale.admin_confirm'):</b>
                        {{ $user_confirm['admin_name'] }}
                        <br>
                    @elseif($user_confirm['type'] == 'cashier')
                        <b>@lang('sale.user_confirm'):</b>
                        {{ $user_confirm['cashier_name'] }}
                        <br>
                    @endif

                    <b>@lang('sale.payment_ref_no'):</b>
                    @if(!empty($payment->payment_ref_no))
                        {{ $payment->payment_ref_no }}
                    @else
                        --
                    @endif
                    <br/>
                    <b>@lang('lang_v1.paid_on'):</b> {{ @format_datetime($payment->paid_on) }}<br/>
                    <br>
                    @if(!empty($payment->document_path))
                        <a href="{{$payment->document_path}}" class="btn btn-success btn-xs no-print" download="{{$payment->document_name}}"><i class="fa fa-download" data-toggle="tooltip" title="{{__('purchase.download_document')}}"></i> {{__('purchase.download_document')}}</a>
                    @endif
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default no-print" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $(document).on( 'click', '.notify_approve_payment', function(e){
            e.preventDefault();
            var container = $('.notify_payment_modal');

            $.ajax({
                url: $(this).data('href'),
                dataType: 'html',
                success: function(result) {
                    container.html(result).modal('show');
                    __currency_convert_recursively(container);
                    container.find('form#transaction_payment_add_form').validate();
                },
            });
        });
    });
</script>
