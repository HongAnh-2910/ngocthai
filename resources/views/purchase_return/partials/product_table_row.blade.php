<tr class="product_row">
    @if(session('business.enable_lot_number'))
        <td>
            <input type="text" name="products[{{$row_index}}][lot_number]" class="form-control" value="{{$product->lot_number}}">
        </td>
    @endif
    <td>
        {{$product->product_name}} - {{$product->sub_sku}}
    </td>
    @if(session('business.enable_product_expiry'))
        <td>
            <input type="text" name="products[{{$row_index}}][exp_date]" class="form-control expiry_datepicker" value="@if(!empty($product->exp_date)){{@format_date($product->exp_date)}}@endif" readonly>
        </td>
    @endif
    <td>
        <input type="hidden" name="products[{{$row_index}}][product_id]" class="form-control product_id" value="{{$product->product_id}}">

        <input type="hidden" value="{{$product->variation_id}}"
            name="products[{{$row_index}}][variation_id]">

        <input type="hidden" value="{{$product->enable_stock}}"
            name="products[{{$row_index}}][enable_stock]">

        @if(!empty($edit))
            <input type="hidden" value="{{$product->purchase_line_id}}"
            name="products[{{$row_index}}][purchase_line_id]">
            @php
                $qty = $product->quantity_returned;
                $purchase_price = $product->purchase_price;
            @endphp
        @else
            @php
                $qty = 1;
                $purchase_price = $product->last_purchased_price;
            @endphp
        @endif

{{--        <input type="text" class="form-control product_quantity input_number input_quantity" value="{{@format_quantity($qty)}}" name="products[{{$row_index}}][quantity]"--}}
{{--        @if($product->unit_allow_decimal == 1) data-decimal=1 @else data-rule-abs_digit="true" data-msg-abs_digit="@lang('lang_v1.decimal_value_not_allowed')" data-decimal=0 @endif--}}
{{--        data-rule-required="true" data-msg-required="@lang('validation.custom-messages.this_field_is_required')" @if($product->enable_stock) data-rule-max-value="{{$product->qty_available}}" data-msg-max-value="@lang('validation.custom-messages.quantity_not_available', ['qty'=> $product->formatted_qty_available, 'unit' => $product->unit  ])"--}}
{{--        data-qty_available="{{$product->qty_available}}"--}}
{{--        data-msg_max_default="@lang('validation.custom-messages.quantity_not_available', ['qty'=> $product->formatted_qty_available, 'unit' => $product->unit  ])"--}}
{{--         @endif >--}}
{{--        {{$product->unit}}--}}

        <div class="form-group row area_div">
            {{--                {!! Form::label('area', __( 'unit.total_area' ), ['class' => 'col-sm-4 col-form-label']) !!}--}}
            <div class="col-sm-8">
                {{--                    {!! Form::text('purchases[' . $row_count . '][area]', $height, ['class' => 'form-control input-sm area input_number mousetrap', 'readonly']); !!}--}}
                @if(!empty($sub_units))
                    <select name="purchases[{{$row_index}}][sub_unit_id]" class="form-control input-sm sub_unit" id="elementTriggerChangeOnLoad" style="width: 150px !important">
                        @foreach($sub_units as $key => $value)
                            <option value="{{$key}}" data-width="{{ $value['width'] ?? 0 }}" data-height="{{ $value['height'] ?? 0 }}" data-multiplier="{{$value['multiplier']}}">
                                {{$value['name']}}
                            </option>
                        @endforeach
                    </select>
                @else
                    <span class="unit">{{ $product->unit->short_name }}</span>
                @endif
            </div>
        </div>

{{--        @if(!empty($sub_units))--}}
{{--            @foreach($sub_units as $key => $sub_unit)--}}
{{--                @if($sub_units[$key]['height'] != null && $sub_units[$key]['width'] != null)--}}
{{--                    <div class="form-group row width_div">--}}
{{--                        {!! Form::label('width', __( 'unit.width'), ['class' => 'col-sm-4 col-form-label']) !!}--}}
{{--                        <div class="col-sm-8">--}}
{{--                            {!! Form::text('purchases[' . $row_index . '][width]', (isset($action) && $action == 'edit') ? ($product->quantity / $sub_units[$key]['height'] / $product->quantity_line) : 1, ['class' => 'form-control input-sm width input_number mousetrap', 'required']); !!}--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <div class="form-group row height_div">--}}
{{--                        {!! Form::label('height', __( 'unit.height' ), ['class' => 'col-sm-4 col-form-label']) !!}--}}
{{--                        <div class="col-sm-8">--}}
{{--                            {!! Form::text('purchases[' . $row_index . '][height]', $sub_units[$key]['height'], ['class' => 'form-control input-sm height input_number mousetrap', 'required', 'readonly' => 'readonly']); !!}--}}
{{--                        </div>--}}

{{--                        @php--}}
{{--                            $weight = $sub_units[$key]['height'] * 1;--}}
{{--                            $height = $sub_units[$key]['height'];--}}

{{--                        @endphp--}}
{{--                    </div>--}}

{{--                    <div class="form-group row input-number quantity_div" >--}}
{{--                        {!! Form::label('quantity', __( 'unit.number_of_plate' ), ['class' => 'col-sm-4 col-form-label']) !!}--}}
{{--                        <div class="col-sm-7 input-group input-number" style="margin: auto">--}}
{{--                            <span class="input-group-btn"><button type="button" class="btn btn-default btn-flat quantity-down"><i class="fa fa-minus text-danger"></i></button></span>--}}
{{--                            <input type="text" data-min="1"--}}
{{--                                   class="form-control pos_quantity input_number mousetrap input_quantity"--}}
{{--                                   value="{{ (isset($action) && $action == 'edit') ? $product->quantity_line : 1 }}" name="products[{{$row_index}}][quantity]" data-allow-overselling="@if(empty($pos_settings['allow_overselling'])){{'false'}}@else{{'true'}}@endif"--}}
{{--                                   @if($product->unit_allow_decimal == 1)--}}
{{--                                   data-decimal=1--}}
{{--                                   @else--}}
{{--                                   data-decimal=0--}}
{{--                                   data-rule-abs_digit="true"--}}
{{--                                   data-msg-abs_digit="@lang('lang_v1.decimal_value_not_allowed')"--}}
{{--                                   @endif--}}
{{--                                   data-rule-required="true"--}}
{{--                                   data-msg-required="@lang('validation.custom-messages.this_field_is_required')"--}}
{{--                            >--}}
{{--                            <span class="input-group-btn"><button type="button" class="btn btn-default btn-flat quantity-up"><i class="fa fa-plus text-success"></i></button></span>--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <td class="text-center v-center">--}}
{{--                        <input type="hidden" class="form-control weight" name="weight" value="{{ $product->weight }}">--}}
{{--                        <span class="weight_text">{{ (isset($action) && $action == 'edit') ? round(($product->quantity * $product->weight), 1) : round(($product->weight * $weight), 1) }}</span> kg--}}
{{--                    </td>--}}
{{--                @endif--}}
{{--            @endforeach--}}
{{--        @endif--}}
        <input type="hidden" name="purchases[{{$row_index}}][product_unit_id]" value="{{$product->unit_id}}" class="product_unit_id">
    </td>

    <td>
        {!! Form::text('purchases[' . $row_index . '][height]',
        number_format((float)$product->sub_unit_height, 3, '.', ','), ['class' => 'form-control height input-sm input_number text-center', 'required']); !!}
    </td>

    <td>
        {!! Form::text('purchases[' . $row_index . '][width]',
        number_format((float)$product->sub_unit_width, 3, '.', ','), ['class' => 'form-control input-sm input_number width text-center', 'required']); !!}
    </td>

    <td>
        <div class="input-group input-number">
            <span class="input-group-btn"><button type="button" class="input-sm btn btn-default btn-flat quantity-down"><i class="fa fa-minus text-danger"></i></button></span>
            {{--                {!! Form::text('purchases[' . $row_count . '][quantity]', 1, ['class' => 'form-control input-sm purchase_quantity input_number mousetrap', 'required', 'data-rule-abs_digit' => $check_decimal, 'data-msg-abs_digit' => __('lang_v1.decimal_value_not_allowed')]); !!}--}}
            <input type="text" data-min="1" required
                   class="input-sm form-control text-center purchase_quantity input_number mousetrap input_quantity"
                   value="1" name="purchases[{{$row_index}}][quantity]" min="1"

                   data-rule-required="true"
                   data-msg-required="@lang('validation.custom-messages.this_field_is_required')"
            >
            <span class="input-group-btn"><button type="button" class="input-sm btn btn-default btn-flat quantity-up"><i class="fa fa-plus text-success"></i></button></span>
        </div>
    </td>
    <td>
        <span class="area">{{ number_format((float)$product->sub_unit_height * $product->sub_unit_width * 1, 3, '.', ',') }}</span>
        <input type="hidden" class="form-control input-sm area_hidden input_number mousetrap" name="purchases[{{$row_index}}][area]" value="">
    </td>
    <td>
        <input type="text" readonly name="products[{{$row_index}}][unit_price]" class="form-control product_unit_price input_number" value="{{number_format($product->last_purchased_price)}}">
    </td>
    <td>
        <input type="text" readonly name="products[{{$row_index}}][price]" class="form-control product_line_total" value="{{@num_format($product->sub_unit_height * $product->sub_unit_width * $purchase_price)}}">
    </td>


{{--    <td class="text-center v-center">--}}
{{--        <input type="hidden" name="products[{{$row_index}}][unit_price]" class="form-control product_unit_price" value="{{ number_format($height * $product->last_purchased_price) }}">--}}
{{--        <span class="display_currency pos_line_total_text data-currency_symbol="true">{{ number_format($height * $product->last_purchased_price) }}</span>--}}
{{--    </td>--}}

    <td class="text-center">
        <i class="fa fa-trash remove_product_row cursor-pointer" aria-hidden="true"></i>
    </td>
</tr>
