<div class="modal-header">
    <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
    </button>
    <h4 class="modal-title" id="modalTitle"> @lang('purchase.purchase_details') (<b>@lang('purchase.ref_no'):</b>
        #{{ $purchase->ref_no }})
    </h4>
</div>
<div class="modal-body">
    <div class="row">
        <div class="col-sm-12">
            <p class="pull-right"><b>@lang('messages.date'):</b> {{ @format_date($purchase->transaction_date) }}</p>
        </div>
    </div>
    <div class="row invoice-info">
        <div class="col-sm-4 invoice-col">
            @lang('purchase.supplier'):
            <address>
                <strong>@lang('purchase.supplier_name'):</strong> {{ $purchase->contact->supplier_business_name }}
                <br><strong>@lang('purchase.contact_name'):</strong> {{ $purchase->contact->name }}
                @if(!empty($purchase->contact->landmark) || !empty($purchase->contact->city) || !empty($purchase->contact->state) || !empty($purchase->contact->country))
                    <br><strong>@lang('purchase.address'):</strong> {{implode(', ', array_filter([$purchase->contact->landmark, $purchase->contact->city, $purchase->contact->state, $purchase->contact->country]))}}
                @endif
                @if(!empty($purchase->contact->tax_number))
                    <br><strong>@lang('contact.tax_no'):</strong> {{$purchase->contact->tax_number}}
                @endif
                @if(!empty($purchase->contact->mobile))
                    <br><strong>@lang('contact.mobile'):</strong> {{$purchase->contact->mobile}}
                @endif
                @if(!empty($purchase->contact->email))
                    <br><strong>@lang('business.email'):</strong> {{$purchase->contact->email}}
                @endif
            </address>
            @if($purchase->document_path)
                <a href="{{$purchase->document_path}}"
                   download="{{$purchase->document_name}}" class="btn btn-sm btn-success pull-left no-print">
                    <i class="fa fa-download"></i>
                    &nbsp;{{ __('purchase.download_document') }}
                </a>
            @endif
        </div>

        <div class="col-sm-4 invoice-col">
            @lang('business.business_location'):
            <address>
                <strong>@lang('purchase.location_name'):</strong> {{ $purchase->location->name }}
                @if(!empty($purchase->location->landmark) || !empty($purchase->location->city) || !empty($purchase->location->state) || !empty($purchase->location->country))
                    <br><strong>@lang('purchase.address'):</strong> {{implode(', ', array_filter([$purchase->location->landmark, $purchase->location->city, $purchase->location->state, $purchase->location->country]))}}
                @endif

                {{--@if(!empty($purchase->business->tax_number_1))
                    <br>{{$purchase->business->tax_label_1}}: {{$purchase->business->tax_number_1}}
                @endif

                @if(!empty($purchase->business->tax_number_2))
                    <br>{{$purchase->business->tax_label_2}}: {{$purchase->business->tax_number_2}}
                @endif--}}

                @if(!empty($purchase->location->mobile))
                    <br><strong>@lang('contact.mobile'):</strong> {{$purchase->location->mobile}}
                @endif
                @if(!empty($purchase->location->email))
                    <br><strong>@lang('business.email'):</strong> {{$purchase->location->email}}
                @endif
            </address>
        </div>

        <div class="col-sm-4 invoice-col">
            <b>@lang('purchase.ref_no'):</b> #{{ $purchase->ref_no }}<br/>
            <b>@lang('messages.date'):</b> {{ @format_date($purchase->transaction_date) }}<br/>
{{--            <b>@lang('purchase.purchase_status'):</b> {{ __('lang_v1.' . $purchase->status) }}<br>--}}
{{--            <b>@lang('purchase.payment_status'):</b> {{ __('lang_v1.' . $purchase->payment_status) }}<br>--}}
        </div>
    </div>

    <br>
    <div class="row">
        <div class="col-sm-12 col-xs-12">
            <div class="table-responsive">
                <table class="table bg-gray">
                    <thead>
                    <tr class="bg-green">
                        <th>#</th>
                        @if(session('business.enable_lot_number'))
                            <th>@lang('lang_v1.lot_number')</th>
                        @endif
                        <th>@lang('sale.product')</th>
{{--                        <th class="text-right">@lang( 'lang_v1.unit_cost' )</th>--}}
                        <th class="text-right">@lang( 'product.height' )</th>
                        <th class="text-right">@lang( 'product.width' )</th>
                        <th class="text-right">@lang('purchase.purchase_quantity')</th>
                        <th class="text-right">@lang('purchase.warehouse')</th>
                        <th class="text-right">@lang('purchase.is_origin')</th>
                        <th class="text-right">@lang('purchase.purchase_total_quantity')</th>
{{--                        <th class="text-right">@lang( 'lang_v1.discounts' )</th>--}}
                        <th class="text-right">@lang( 'lang_v1.unit_cost' )</th>
                        @if(session('business.enable_product_expiry'))
                            <th>@lang('product.mfg_date')</th>
                            <th>@lang('product.exp_date')</th>
                        @endif
                        <th class="text-right">@lang('sale.subtotal')</th>
                    </tr>
                    </thead>
                    @php
                        $total_before_tax = 0.00;
                    @endphp

                    @foreach($purchase->purchase_lines as $purchase_line)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            @if(session('business.enable_lot_number'))
                                <td>{{$purchase_line->lot_number ?? '--'}}</td>
                            @endif
                            <td>
                                {{ $purchase_line->product->name }} - {{ $purchase_line->product->sku }}
                                @if( $purchase_line->product->type == 'variable')
                                    - {{ $purchase_line->variations->product_variation->name}}
                                    - {{ $purchase_line->variations->name}}
                                @endif
                            </td>
                            <td class="text-right">
                                @if($purchase_line->product->unit->type == 'area')
                                    <span>{{ $purchase_line->height ? @size_format($purchase_line->height) : '' }}</span> m
                                @endif
                            </td>
                            <td class="text-right">
                                @if(in_array($purchase_line->product->unit->type, ['area', 'meter']))
                                    <span>{{ $purchase_line->width ? @size_format($purchase_line->width) : '' }}</span> m
                                @endif
                            </td>
                            <td class="text-right">
                                <span>{{ $purchase_line->quantity_line ? @num_format($purchase_line->quantity_line) : '' }}</span>
                            </td>
                            <td class="text-right">{{ $purchase_line->warehouse->name }}</td>
                            <td class="text-right">{{ $purchase_line->is_origin ? __('sale.origin_plate') : '' }}</td>
                            <td class="text-right">
                                @if($purchase_line->product->unit->type == 'area')
                                    <span>{{ @size_format($purchase_line->quantity) }}</span> m<sup>2</sup>
                                @endif
                            </td>
                            {{--<td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $purchase_line->discount_percent }}</span>
                            </td>--}}
                            <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $purchase_line->purchase_price_inc_tax }}</span>
                            </td>
                            @php
                                $sp = $purchase_line->variations->default_sell_price;
                                if(!empty($purchase_line->sub_unit->base_unit_multiplier)) {
                                  $sp = $sp * $purchase_line->sub_unit->base_unit_multiplier;
                                }
                            @endphp
                            @if(session('business.enable_product_expiry'))
                                <td>
                                    @if( !empty($purchase_line->product->expiry_period_type) )
                                        @if(!empty($purchase_line->mfg_date))
                                            {{ @format_date($purchase_line->mfg_date) }}
                                        @endif
                                    @else
                                        @lang('product.not_applicable')
                                    @endif
                                </td>
                                <td>
                                    @if( !empty($purchase_line->product->expiry_period_type) )
                                        @if(!empty($purchase_line->exp_date))
                                            {{ @format_date($purchase_line->exp_date) }}
                                        @endif
                                    @else
                                        @lang('product.not_applicable')
                                    @endif
                                </td>
                            @endif
                            <td class="text-right"><span class="display_currency"
                                                         data-currency_symbol="true">{{ $purchase_line->purchase_price_inc_tax * $purchase_line->quantity }}</span>
                            </td>
                        </tr>
                        @php
                            $total_before_tax += ($purchase_line->quantity * $purchase_line->purchase_price_inc_tax);
                        @endphp
                    @endforeach
                </table>
            </div>
        </div>
    </div>
    <br>
    <div class="row">
        {{--<div class="col-sm-12 col-xs-12">
            <h4>{{ __('sale.payment_info') }}:</h4>
        </div>--}}
        <div class="col-md-6 col-sm-12 col-xs-12">
            {{--<strong>@lang('purchase.shipping_details'):</strong><br>
            <p class="well well-sm no-shadow bg-gray">
                @if($purchase->shipping_details)
                    {{ $purchase->shipping_details }}
                @else
                    --
                @endif
            </p>--}}
            <strong>@lang('purchase.additional_notes'):</strong><br>
            <p class="well well-sm no-shadow bg-gray">
                @if($purchase->additional_notes)
                    {{ $purchase->additional_notes }}
                @else
                    --
                @endif
            </p>
            {{--<div class="table-responsive">
                <table class="table">
                    <tr class="bg-green">
                        <th>#</th>
                        <th>{{ __('messages.date') }}</th>
                        <th>{{ __('purchase.ref_no') }}</th>
                        <th>{{ __('sale.amount') }}</th>
                        <th>{{ __('sale.payment_mode') }}</th>
                        <th>{{ __('sale.payment_note') }}</th>
                    </tr>
                    @php
                        $total_paid = 0;
                    @endphp
                    @forelse($purchase->payment_lines as $payment_line)
                        @php
                            $total_paid += $payment_line->amount;
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ @format_date($payment_line->paid_on) }}</td>
                            <td>{{ $payment_line->payment_ref_no }}</td>
                            <td><span class="display_currency"
                                      data-currency_symbol="true">{{ $payment_line->amount }}</span></td>
                            <td>{{ $payment_methods[$payment_line->method] ?? '' }}</td>
                            <td>@if($payment_line->note)
                                    {{ ucfirst($payment_line->note) }}
                                @else
                                    --
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">
                                @lang('purchase.no_payments')
                            </td>
                        </tr>
                    @endforelse
                </table>
            </div>--}}
        </div>
        <div class="col-md-6 col-sm-12 col-xs-12">
            <div class="table-responsive">
                <table class="table">
                    {{--<tr>
                        <th>@lang('purchase.net_total_amount'):</th>
                        <td></td>
                        <td><span class="display_currency pull-right"
                                  data-currency_symbol="true">{{ $total_before_tax }}</span></td>
                    </tr>--}}
                    {{--<tr>
                        <th>@lang('purchase.discount'):</th>
                        <td>
                            <b>(-)</b>
                            @if($purchase->discount_type == 'percentage')
                                ({{$purchase->discount_amount}} %)
                            @endif
                        </td>
                        <td>
                          <span class="display_currency pull-right" data-currency_symbol="true">
                            @if($purchase->discount_type == 'percentage')
                                  {{$purchase->discount_amount * $total_before_tax / 100}}
                              @else
                                  {{$purchase->discount_amount}}
                              @endif
                          </span>
                        </td>
                    </tr>--}}
                    {{--<tr>
                        <th>@lang('purchase.purchase_tax'):</th>
                        <td><b>(+)</b></td>
                        <td class="text-right">
                            @if(!empty($purchase_taxes))
                                @foreach($purchase_taxes as $k => $v)
                                    <strong><small>{{$k}}</small></strong> &nbsp;&nbsp;<span
                                            class="display_currency pull-right"
                                            data-currency_symbol="true">{{ $v }}</span><br>
                                @endforeach
                            @else
                                <span class="display_currency pull-right" data-currency_symbol="true">0</span>
                            @endif
                        </td>
                    </tr>--}}
                    {{--@if( !empty( $purchase->shipping_charges ) )
                        <tr>
                            <th>@lang('purchase.additional_shipping_charges'):</th>
                            <td><b>(+)</b></td>
                            <td><span class="display_currency pull-right"
                                      data-currency_symbol="true">{{ $purchase->shipping_charges }}</span></td>
                        </tr>
                    @endif--}}
                    <tr>
                        <th>@lang('purchase.purchase_total'):</th>
                        <td></td>
                        <td><span class="display_currency pull-right"
                                  data-currency_symbol="true">{{ $purchase->final_total }}</span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    {{--<div class="row">
        <div class="col-sm-6">
            <strong>@lang('purchase.shipping_details'):</strong><br>
            <p class="well well-sm no-shadow bg-gray">
                @if($purchase->shipping_details)
                    {{ $purchase->shipping_details }}
                @else
                    --
                @endif
            </p>
        </div>
        <div class="col-sm-6">
            <strong>@lang('purchase.additional_notes'):</strong><br>
            <p class="well well-sm no-shadow bg-gray">
                @if($purchase->additional_notes)
                    {{ $purchase->additional_notes }}
                @else
                    --
                @endif
            </p>
        </div>
    </div>--}}

    {{-- Barcode --}}
    <div class="row print_section">
        <div class="col-xs-12">
            <img class="center-block"
                 src="data:image/png;base64,{{DNS1D::getBarcodePNG($purchase->ref_no, 'C128', 2,30,array(39, 48, 54), true)}}">
        </div>
    </div>
</div>
