<div class="modal-dialog modal-xl no-print" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action('SellPosController@confirmExport', ['id' => $sell->id]), 'method' => 'POST']) !!}
        <div class="modal-header">
            <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="modalTitle"> @lang('lang_v1.stock_deliver_details') (<b>@lang('sale.invoice_no')
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
            </div>
            <br>
            {!! Form::hidden('transaction_id', $sell->id, ['class' => 'transaction_id']) !!}
            {!! Form::hidden('sell_details', json_encode($sell_details, JSON_UNESCAPED_UNICODE), ['class' => 'sell_details']) !!}
            <div class="row">
                <div class="col-sm-12 col-xs-12">
                    <div class="table-responsive">
                        @include('stock_deliver.partials.sale_line_details')
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            @if(in_array($sell->export_status, ['pending']) && auth()->user()->can('confirm_export'))
                <button type="button" id="confirm-export-button" class="btn btn-primary">@lang( 'messages.confirm_export' )</button>
                <a href="{{ action("SellController@editStockDeliver", [$sell->id]) }}" class="btn btn-success">@lang( 'messages.edit_export' )</a>
            @endif
            <button type="button" class="btn btn-default no-print"
                    data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>
        {!! Form::close() !!}
    </div>
</div>
