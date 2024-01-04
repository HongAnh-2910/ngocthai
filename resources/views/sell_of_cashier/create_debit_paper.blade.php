<div class="modal-dialog modal-lg no-print" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="modalTitle"> @lang('lang_v1.add_debit_paper') (<b>@lang('sale.invoice_no')
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
                        {{$sell->service_staff->user_full_name ?? ''}}<br>
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
            <div style="border-top: 1px solid #e5e5e5;margin: 15px 0px"></div>
            <div class="row">
                <div class="col-sm-12">
                    {!! Form::open(['action' => ['SellPosController@storeDebitPaper', $sell->id], 'method' => 'post', 'id' => 'store_debit_paper_form', 'files' => true]) !!}
                        <div class="form-group row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('documents', __('lang_v1.image') . ':') !!}
                                    {!! Form::file('documents[]', ['class' => 'documents', 'accept' => 'image/*', 'multiple']); !!}
                                    <small><p class="help-block">@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])</p></small>
                                </div>
                            </div>
                            <div class="col-sm-8">
                                @foreach($sell->media as $media)
                                    <div class="img-thumbnail">
                                        <span class="badge bg-red delete-media" data-href="{{ action('SellController@deleteMedia', ['media_id' => $media->id])}}">x</span>
                                        {!! $media->thumbnail([100, 100], 'view_image') !!}
                                    </div>
                                @endforeach
                                <div id="imgModal" class="modal" style="display: none; /* Hidden by default */
														  position: fixed; /* Stay in place */
														  /*z-index: 1; !* Sit on top *!*/
														  padding-top: 100px; /* Location of the box */
														  left: 0;
														  top: 0;
														  width: 100%; /* Full width */
														  height: 100%; /* Full height */
														  overflow: auto; /* Enable scroll if needed */
														  background-color: rgb(0,0,0); /* Fallback color */
														  background-color: rgba(0,0,0,0.9);">

                                    <!-- The Close Button -->
                                    <span class="close" style="position: absolute;
                                      top: 15px;
                                      right: 35px;
                                      color: #f1f1f1;
                                      font-size: 40px;
                                      font-weight: bold;
                                      transition: 0.3s;">&times;</span>

                                                <!-- Modal Content (The Image) -->
                                                <img class="modal-content" id="img_popup" style="margin: auto;
                                                                                  display: block;
                                                                                  width: 80%;
                                                                                  max-width: 700px; animation-name: zoom;
                                                                                  animation-duration: 0.6s;">

                                            </div>
                            </div>
                        </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary store_debit_paper_button">@lang('messages.update')</button>
            <button type="button" class="btn btn-default no-print"
                    data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>
    </div>
</div>
<script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
<script type="text/javascript">
    $(document).ready(function() {
        // Get the modal
        var modal = document.getElementById("imgModal");

        var modalImg = document.getElementById("img_popup");
        var captionText = document.getElementById("caption");

        $('.view_image').each(function () {
            $(this).click(function () {
                /*modal.style.display = "block";
                modalImg.src = $(this).attr('src');
                captionText.innerHTML = this.alt;*/
                window.open($(this).attr('src'));
            })
        })

        var span = document.getElementsByClassName("close")[0];
        span.onclick = function() {
            modal.style.display = "none";
        }
    })
</script>