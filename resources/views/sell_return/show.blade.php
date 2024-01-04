<div class="modal-dialog modal-xl no-print show_return_sell" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action('SellReturnController@approveReturnSell'), 'method' => 'POST']) !!}
        {!! Form::hidden('id', $sell->id, ['class' => 'sell_line_id']) !!}
        <div class="modal-header">
            <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="modalTitle"> @lang('lang_v1.sell_return') (<b>@lang('sale.invoice_no'):</b> {{ $sell->return_parent->invoice_no }})
            </h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-sm-6 col-xs-6">
                    <h4>@lang('lang_v1.sell_return_details'):</h4>
                    <strong>@lang('lang_v1.return_date'):</strong> {{@format_date($sell->return_parent->transaction_date)}}<br>
                    <strong>@lang('contact.customer'):</strong> {{ $sell->contact->name }} <br>
                    <strong>@lang('purchase.business_location'):</strong> {{ $sell->location->name }} <br>
                    <strong>@lang('sale.return_note'):</strong> {{ $sell->return_parent->return_note }}
                </div>
                <div class="col-sm-6 col-xs-6">
                    <h4>@lang('lang_v1.sell_details'):</h4>
                    <strong>@lang('sale.invoice_no'):</strong> {{ $sell->invoice_no }} <br>
                    <strong>@lang('messages.date'):</strong> {{@format_date($sell->transaction_date)}}
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-sm-12 col-xs-12">
                    <div class="table-responsive">
                        <table class="table bg-gray text-center" id="table-deatils-sell-return">
                            <thead>
                            <tr class="bg-green">
                                <th rowspan="2">#</th>
                                <th rowspan="2">@lang( 'product.product_name' )</th>
                                <th colspan="5">@lang('lang_v1.customer_order')</th>
                                <th colspan="7">@lang('lang_v1.customer_return')</th>
                            </tr>
                            <tr class="bg-green">
                                <th>@lang( 'product.height' )</th>
                                <th>@lang( 'product.width' )</th>
                                <th>@lang( 'purchase.purchase_quantity' )</th>
                                <th>@lang( 'purchase.purchase_total_quantity' )</th>
                                <th>@lang( 'purchase.return_price' )</th>

                                <th>@lang( 'product.height' )</th>
                                <th>@lang( 'product.width' )</th>
                                <th>@lang( 'purchase.purchase_quantity' )</th>
                                <th>@lang( 'purchase.purchase_total_quantity' )</th>
                                <th>@lang( 'purchase.return_price' )</th>
                                <th>@lang( 'lang_v1.return_subtotal' )</th>
                                <th>@lang( 'purchase.warehouse' )</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php

                            @endphp
                                @php
                                    $row_index = 0;
                                @endphp
                                @foreach($sell->plate_lines as $plate_line)
                                    @if(!$plate_line->plate_line_return->isEmpty())
                                        @php
                                            $new_unit_price	= $plate_line->selected_plate_stock->variation->default_sell_price;
                                            $new_unit_price_by_plate = $plate_line->selected_plate_stock->variation->default_sell_price_by_plate;

                                            if($sell->price_group){
                                                $price_group_id = $sell->price_group->id;
                                                $group_prices = $plate_line->selected_plate_stock->variation->group_prices;
                                                foreach ($group_prices as $group_price){
                                                    if($group_price->price_group_id == $price_group_id){
                                                        $new_unit_price	= $group_price->price_inc_tax;
                                                        $new_unit_price_by_plate = $group_price->price_by_plate;
                                                        break;
                                                    }
                                                }
                                            }
                                        @endphp
                                        {!! Form::hidden('products['. $row_index .'][sell_line_id]', $sell->id, ['class' => 'sell_line_id']) !!}
                                        {!! Form::hidden('products['. $row_index .'][plate_stock_id]', $plate_line->selected_plate_stock->id, ['class' => 'plate_stock_id']) !!}
                                        {!! Form::hidden('products['. $row_index .'][plate_line_id]', $plate_line->id, ['class' => 'plate_line_id']) !!}
                                        {!! Form::hidden('products['. $row_index .'][variation_id]', $plate_line->variation_id, ['class' => 'variation_id']) !!}
                                        {!! Form::hidden('products['. $row_index .'][product_id]', $plate_line->product_id, ['class' => 'product_id']) !!}
                                        {!! Form::hidden('products['. $row_index .'][unit_price]', $sell->unit_price, ['class' => 'old_unit_price']) !!}
                                        {!! Form::hidden('products['. $row_index .'][new_unit_price]', $new_unit_price, ['class' => 'new_unit_price']) !!}
                                        {!! Form::hidden('products['. $row_index .'][new_unit_price_by_plate]', $new_unit_price_by_plate, ['class' => 'new_unit_price_by_plate']) !!}
                                        @php
                                            $plate_line_first = null;
                                            $plate_line_remaining = [];

                                                if(count($plate_line->plate_line_return) > 1){
                                                    $rowspan = 'rowspan='. count($plate_line->plate_line_return);
                                                }else{
                                                    $rowspan = '';
                                                }

                                            if(!empty($plate_line->plate_line_return)){
                                                foreach($plate_line->plate_line_return as $key => $sell_return){
                                                    if($key == 0){
                                                        $plate_line_first =  $sell_return;
                                                    }else{
                                                        $plate_line_remaining[] = $sell_return;
                                                    }
                                                }
                                            }
                                        @endphp
                                        {{--<tr>
                                            <td {{ $rowspan }} style="vertical-align: middle">
                                                {{ $loop->iteration }}
                                            </td>
                                            <td {{ $rowspan }} style="vertical-align: middle; text-align: left">
                                                {{ $plate_line->product->name }}
                                                @if( $plate_line->product->type == 'variable')
                                                    - {{ $plate_line->variations->product_variation->name}}
                                                    - {{ $plate_line->variations->name}}
                                                @endif
                                            </td>
                                            <td {{ $rowspan }} style="vertical-align: middle">
                                                {{ $plate_line->sell_line->sub_unit->type == 'area' ? @size_format($plate_line->height) : '' }}
                                            </td>
                                            <td {{ $rowspan }} style="vertical-align: middle">
                                                {{ in_array($plate_line->sell_line->sub_unit->type, ['area', 'meter']) ? @size_format($plate_line->width) : '' }}
                                            </td>
                                            <td {{ $rowspan }} style="vertical-align: middle">
                                                {{ in_array($plate_line->sell_line->sub_unit->type, ['area', 'meter']) ? @number_format($plate_line->quantity) . ' ' . __('unit.plate') : @number_format($plate_line->quantity) . ' ' . $plate_line->sell_line->sub_unit->actual_name }}
                                            </td>
                                            <td {{ $rowspan }} style="vertical-align: middle">
                                                {{ $plate_line->sell_line->sub_unit->type == 'area' ? @size_format($plate_line->height * $plate_line->width * $plate_line->quantity).' m2' : '' }}
                                            </td>
                                            <td {{ $rowspan }} style="vertical-align: middle">
                                                {{ @number_format($plate_line->sell_line->unit_price) }} đ
                                            </td>
                                        </tr>--}}
                                        {{--@php
                                            if(!empty($plate_line->plate_line_return)){
                                                foreach($plate_line->plate_line_return as $key => $sell_return){
                                                    if($key == 0){
                                                        $plate_line_first =  $sell_return;
                                                    }else{
                                                        $plate_line_remaining[] = $sell_return;
                                                    }
                                                }
                                            }
                                        @endphp--}}
                                        <tr>
                                            <td {{ $rowspan }} style="vertical-align: middle">
                                                {{ $loop->iteration }}
                                            </td>
                                            <td {{ $rowspan }} style="vertical-align: middle">
                                                {{ $plate_line->product->name }}
                                                @if( $plate_line->product->type == 'variable')
                                                    - {{ $plate_line->variations->product_variation->name}}
                                                    - {{ $plate_line->variations->name}}
                                                @endif
                                            </td>
                                            <td {{ $rowspan }} style="vertical-align: middle">
                                                {{ $plate_line->sell_line->sub_unit->type == 'area' ? @size_format($plate_line->height) : '' }}
                                            </td>
                                            <td {{ $rowspan }} style="vertical-align: middle">
                                                {{ in_array($plate_line->sell_line->sub_unit->type, ['area', 'meter']) ? @size_format($plate_line->width) : '' }}
                                            </td>
                                            <td {{ $rowspan }} style="vertical-align: middle">
                                                {{ in_array($plate_line->sell_line->sub_unit->type, ['area', 'meter']) ? @number_format($plate_line->quantity) . ' ' . __('unit.plate') : @number_format($plate_line->quantity) . ' ' . $plate_line->sell_line->sub_unit->actual_name }}
                                            </td>
                                            <td {{ $rowspan }} style="vertical-align: middle">
                                                {{ $plate_line->sell_line->sub_unit->type == 'area' ? @size_format($plate_line->height * $plate_line->width * $plate_line->quantity).' m2' : '' }}
                                            </td>
                                            <td {{ $rowspan }} style="vertical-align: middle">
                                                {{ @number_format($plate_line->sell_line->unit_price) }} đ
                                            </td>

                                            @if($plate_line_first)
                                                @include('sell_return.partials.plate_line_return_details',  ['sell_return' => $plate_line_first])
                                            @endif
                                        </tr>

                                        @if(!empty($plate_line_remaining))
                                            @foreach($plate_line_remaining as $key => $sell_return)
                                                <tr>
                                                    @include('sell_return.partials.plate_line_return_details', ['sell_return' => $sell_return, 'row_index' => $row_index, 'sell' => $sell, 'key' => $key, 'plate_line' => $plate_line])
                                                </tr>
                                            @endforeach
                                        @endif
                                        @php
                                            $row_index += 1;
                                        @endphp
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6 col-sm-offset-6 col-xs-6 col-xs-offset-6">
                    <table class="table">
                        <tr>
                            @php
                                $total_return = $sell->return_parent->total_before_tax;
                                if ($sell->return_parent->discount_type == 'percentage') {
                                    $discount_amount = $sell->return_parent->total_before_tax * ($sell->return_parent->discount_amount / 100);
                                } else {
                                    $discount_amount = $sell->return_parent->discount_amount;
                                }
                            @endphp

                            <th>@lang('lang_v1.net_total_amount_return'): </th>
                            <td></td>
                            <td><span class="display_currency pull-right" data-currency_symbol="true">{{ @num_format($total_return) }} đ</span></td>
                        </tr>
                        <tr>
                            <th>@lang('lang_v1.expense_customer_return'): </th>
                            <td><b>(-)</b></td>
                            <td class="text-right">
                                @if($sell->return_parent->discount_type == 'percentage')
                                    <strong><small>{{ @size_format($sell->return_parent->discount_amount) }}%</small></strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                @endif
                                <span class="display_currency pull-right" data-currency_symbol="true">{{ @number_format($discount_amount) }} đ</span>
                            </td>
                        </tr>
                        <tr>
                            <th>@lang('lang_v1.shop_return_amount'): </th>
                            <td><b>(+)</b></td>
                            <td class="text-right">
                                <span class="display_currency pull-right" data-currency_symbol="true">{{ @num_format($sell->return_parent->shop_return_amount) }} đ</span>
                            </td>
                        </tr>
                        <tr>
                            <th>@lang('lang_v1.return_total'):</th>
                            <td></td>
                            <td><span class="display_currency pull-right" data-currency_symbol="true" >{{ @num_format($sell->return_parent->final_total) }} đ</span></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            @if(in_array($sell->return_parent->status, ['pending']) && auth()->user()->can('approval_sell_return'))
                <button type="button" id="approval-return-button" class="btn btn-success">@lang( 'messages.approve' )</button>
                <button type="button" data-href="{{action('SellReturnController@rejectReturnSell', ['id' => $sell->return_parent->id])}}" {{--data-dismiss="modal"--}} class="btn btn-danger reject_sell_return"> @lang( 'messages.reject' )</button>
            @endif
        </div>
        {!! Form::close() !!}
    </div>
</div>
