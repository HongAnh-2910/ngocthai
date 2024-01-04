<div class="modal-dialog modal-xl no-print" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="modalTitle"> @lang('sale.sell_details') (<b>@lang('sale.invoice_no')
                    :</b> {{ $sell->invoice_no }})
            </h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-xs-12">
                    <p class="pull-right"><b>@lang('messages.date'):</b> {{ @format_date($sell->transaction_date) }}</p>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-4">
                    <b>{{ __('sale.invoice_no') }}:</b> #{{ $sell->invoice_no }}<br>
                    <b>{{ __('sale.status') }}:</b>
                    @if($sell->status == 'draft' && $sell->is_quotation == 1)
                        {{ __('lang_v1.quotation') }}
                    @else
                        {{ __('sale.' . $sell->status) }}
                    @endif
                    <br>
                    <b>{{ __('sale.payment_status') }}
                        :</b> @if(!empty($sell->payment_status)){{ __('lang_v1.' . $sell->payment_status) }}<br>
                    @endif
                </div>
                <div class="col-sm-4">
                    <b>{{ __('sale.customer_name') }}:</b> {{ $sell->contact->name }}<br>
                    @if(!empty($sell->billing_address()))
                        <b>{{ __('business.address') }}:</b> {{$sell->billing_address()}}
                    @else
                        <b>{{ __('business.address') }}:</b> {{implode(", ", array_filter([$sell->contact->landmark, $sell->contact->city, $sell->contact->state, $sell->contact->country]))}}
                        @if($sell->contact->mobile)
                            <br>
                            <b>{{__('contact.mobile')}}:</b> {{ $sell->contact->mobile }}
                        @endif
                        @if($sell->contact->alternate_number)
                            <br>
                            <b>{{__('contact.alternate_contact_number')}}:</b> {{ $sell->contact->alternate_number }}
                        @endif
                        @if($sell->contact->landline)
                            <br>
                            <b>{{__('contact.landline')}}:</b> {{ $sell->contact->landline }}
                        @endif
                    @endif
                </div>
                <div class="col-sm-4">
                    @if(in_array('service_staff' ,$enabled_modules))
                        <strong>@lang('restaurant.service_staff'):</strong>
                        {{empty($shippers->shipper) ? '' : $shippers->shipper}}<br>
                    @endif

                    <strong>@lang('sale.deliver_status'):</strong>
                    <span class="label {{ $sell->is_deliver ? 'bg-green' : 'bg-yellow' }}">{{ $sell->is_deliver ? __('sale.delivered') : __('sale.not_delivery') }}</span>

                    <br><strong>@lang('sale.shipping_status'):</strong>
                    <span class="label @if(!empty($shipping_status_colors[$sell->shipping_status])) {{$shipping_status_colors[$sell->shipping_status]}} @else {{'bg-yellow'}} @endif">{{$shipping_statuses[$sell->shipping_status] ?? __('sale.not_shipping') }}</span>

                    @if(!empty($sell->delivered_to))
                        <br><strong>@lang('lang_v1.delivered_to'): </strong> {{$sell->delivered_to}}
                    @endif

                    @if(!empty($sell->shipping_address))
                        <br><b>@lang('lang_v1.shipping_address'):</b> {{$sell->shipping_address}}
                    @endif

                    @if(!empty($sell->phone_contact))
                        <br><strong>@lang('lang_v1.phone_contact'): </strong> {{$sell->phone_contact}}
                    @endif

                    @if(in_array('types_of_service' ,$enabled_modules))
                        @php
                            $custom_labels = json_decode(session('business.custom_labels'), true);
                        @endphp
                        @if(!empty($sell->types_of_service))
                            <strong>@lang('lang_v1.types_of_service'):</strong>
                            {{$sell->types_of_service->name}}<br>
                        @endif
                    @endif
                </div>
                <div class="col-sm-4">
                    @if(!empty($sell->media->toArray()))
                        <strong>Hình ảnh (Giấy ký nợ,...):</strong>
                        <br>
                        @foreach($sell->media as $media)
                            <div class="img-thumbnail sell_document">
                                {!! $media->thumbnail([100, 100]) !!}
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-sm-12 col-xs-12">
                    <h4>{{ __('sale.products') }}:</h4>
                </div>

                <div class="col-sm-12 col-xs-12">
                    <div class="table-responsive">
                        @include('sale_pos.partials.sale_line_details')
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12 col-xs-12">
                    <h4>{{ __('sale.payment_info') }}:</h4>
                </div>
                <div class="col-md-6 col-sm-12 col-xs-12">
                    <div class="table-responsive" id="table-total-final-bill">
                        @php
                            $total_paid = 0;
                        @endphp
                        @foreach($sell->payment_lines as $payment_line)
                            @if($payment_line->amount > 0 && $payment_line->approval_status == 'approved')
                                @php
                                    if($payment_line->is_return == 1){
                                      $total_paid -= $payment_line->amount;
                                    } else {
                                      $total_paid += $payment_line->amount;
                                    }
                                @endphp
                            @endif
                        @endforeach
                        <table class="table bg-gray">
                            <tr>
                                <th>{{ __('sale.total') }}: </th>
                                <td></td>
                                <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $sell->total_before_tax }}</span></td>
                            </tr>
                            <tr>
                                <th>{{ __('sale.discount') }}:</th>
                                <td><b>(-)</b></td>
                                <td><span class="display_currency pull-right priceDiscount" data-currency_symbol="true">{{ $sell->discount_amount }}</span></td>
                            </tr>
                            @if(in_array('types_of_service' ,$enabled_modules) && !empty($sell->packing_charge))
                                <tr>
                                    <th>{{ __('lang_v1.packing_charge') }}:</th>
                                    <td><b>(+)</b></td>
                                    <td>
                                        <div class="pull-right"><span class="display_currency"
                                                                      @if( $sell->packing_charge_type == 'fixed') data-currency_symbol="true" @endif>{{ $sell->packing_charge }}</span> @if( $sell->packing_charge_type == 'percent') {{ '%'}} @endif
                                        </div>
                                    </td>
                                </tr>
                            @endif
                            @if(session('business.enable_rp') == 1 && !empty($sell->rp_redeemed) )
                                <tr>
                                    <th>{{session('business.rp_name')}}:</th>
                                    <td><b>(-)</b></td>
                                    <td><span class="display_currency pull-right"
                                              data-currency_symbol="true">{{ $sell->rp_redeemed_amount }}</span></td>
                                </tr>
                            @endif
                            <tr>
                                <th>{{ __('sale.order_tax') }}:</th>
                                <td><b>(+)</b></td>
                                <td class="text-right">
                                    @if(!empty($order_taxes))
                                        @foreach($order_taxes as $k => $v)
                                            <strong>
                                                <small>{{$k}}</small>
                                            </strong>
                                            &nbsp;&nbsp;
                                            <span class="display_currency priceOrderTaxes pull-right" data-currency_symbol="true" data-price="{{ (int)$v }}">{{ $v }}</span>
                                            <br>
                                        @endforeach
                                    @else
                                        <span class="display_currency priceOrderTaxes pull-right" data-price="0" data-currency_symbol="true">{{ $sell->tax_amount }}</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>{{ __('sale.shipping') }}:</th>
                                <td><b>(+)</b></td>
                                <td><span class="display_currency priceShipping pull-right"
                                          data-currency_symbol="true" data-price="{{ (int)$sell->shipping_charges }}">{{ $sell->shipping_charges }}</span></td>
                            </tr>
                            {{--<tr>
                                <th>{{ __('sale.vat') }}:</th>
                                <td><b>(+)</b></td>
                                <td><span class="display_currency priceShipping pull-right"
                                          data-currency_symbol="true" data-price="{{ (int)$sell->vat_money }}">{{ $sell->vat_money }}</span></td>
                            </tr>--}}
                            <tr>
                                <th>{{ __('sale.total_payable') }}:</th>
                                <td></td>
                                <td><span class="display_currency total_payable pull-right"
                                          data-currency_symbol="true" data-price="{{ $sell->final_total }}">{{ $sell->final_total }}</span></td>
                            </tr>
                            <tr>
                                <th>{{ __('sale.total_paid') }}:</th>
                                <td></td>
                                <td><span class="display_currency total_paid pull-right"
                                          data-currency_symbol="true">{{ $total_paid }}</span></td>
                            </tr>
                            <tr>
                                <th>{{ __('sale.total_remaining') }}:</th>
                                <td></td>
                                <td>
                                    <!-- Converting total paid to string for floating point substraction issue -->
                                    @php
                                        $total_paid = (string) $total_paid;
                                    @endphp
                                    <span class="display_currency pull-right"
                                          data-currency_symbol="true">{{ $sell->final_total - $total_paid }}</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="col-md-6 col-sm-12 col-xs-12">
                    <div class="table-responsive">
                        <table class="table bg-gray text-center" id="table-view-total-bill">
                            <tr class="bg-green">
                                <th>#</th>
                                <th>{{ __('messages.date') }}</th>
                                <th>{{ __('purchase.ref_no') }}</th>
                                <th>{{ __('sale.amount') }}</th>
                                <th>{{ __('sale.payment_mode') }}</th>
                                <th>{{ __('purchase.payment_approve') }}</th>
                                <th>{{ __('lang_v1.bank_account_number') }}</th>
                                <th>{{ __('sale.payment_note') }}</th>
                            </tr>
                            @php
                                $total_paid = 0;
                            @endphp
                            @foreach($sell->payment_lines as $payment_line)
                                @if($payment_line->amount > 0)
                                    @php
                                        if($payment_line->approval_status == 'approved'){
                                            if($payment_line->is_return == 1){
                                              $total_paid -= $payment_line->amount;
                                            } else {
                                              $total_paid += $payment_line->amount;
                                            }
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ @format_date($payment_line->paid_on) }}</td>
                                        <td>{{ $payment_line->payment_ref_no }}</td>
                                        <td><span class="display_currency display_price_bill"
                                                  data-currency_symbol="true">{{ $payment_line->amount }}</span></td>
                                        <td>
                                            {{ $payment_types[$payment_line->method] ?? '' }}
                                            @if($payment_line->is_return == 1)
                                                <br/>
                                                ( {{ __('lang_v1.change_return') }} )
                                            @endif
                                        </td>
                                        <td>{{ $approval_statuses[$payment_line->approval_status] }}</td>
                                        <td>{{ $payment_line->bank_account_number }}</td>
                                        <td>@if($payment_line->note)
                                                {{ ucfirst($payment_line->note) }}
                                            @else
                                                --
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6">
                    <strong>{{ __( 'sale.sell_note')}}:</strong><br>
                    <p class="well well-sm no-shadow bg-gray">
                        @if($sell->additional_notes)
                            {{ $sell->additional_notes }}
                        @else
                            --
                        @endif
                    </p>
                </div>
                <div class="col-sm-6">
                    <strong>{{ __( 'sale.staff_note')}}:</strong><br>
                    <p class="well well-sm no-shadow bg-gray">
                        @if($sell->staff_note)
                            {{ $sell->staff_note }}
                        @else
                            --
                        @endif
                    </p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#" class="print-invoice btn btn-primary" data-href="{{route('sell.printInvoice', [$sell->id])}}"><i
                        class="fa fa-print" aria-hidden="true"></i> @lang("messages.print")</a>
            <button type="button" class="btn btn-default no-print"
                    data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        var element = $('div.modal-xl');
        __currency_convert_recursively(element);
        // When the user clicks on <span> (x), close the modal
        $('.sell_document').each(function () {
            $(this).click(function () {
                window.open($(this).find('img').attr('src'));
            })
        })
    });
</script>
