@foreach( $variations as $variation)
    <tr>
        <td><span class="sr_number"></span></td>
        <td>
            {{ $product->name }} ({{$variation->sub_sku}})
            @if( $product->type == 'variable' )
                <br/>
                (<b>{{ $variation->product_variation->name }}</b> - {{ $variation->name }})
            @endif
        </td>
        <td>
            {!! Form::hidden('purchases[' . $row_count . '][product_id]', $product->id ); !!}
            {!! Form::hidden('purchases[' . $row_count . '][variation_id]', $variation->id , ['class' => 'hidden_variation_id']); !!}

            @php
                $check_decimal = 'false';
                if($product->unit->allow_decimal == 0){
                    $check_decimal = 'true';
                }
                $currency_precision = config('constants.currency_precision', 2);
                $quantity_precision = config('constants.quantity_precision', 2);
            @endphp
            {!! Form::text('purchases[' . $row_count . '][quantity]', $variation->product->unit->type == 'pcs' ? 1 : @size_format(1), ['class' => 'form-control input-sm purchase_quantity input_number mousetrap', 'required', 'data-rule-abs_digit' => $check_decimal, 'data-msg-abs_digit' => __('lang_v1.decimal_value_not_allowed')]); !!}
            <input type="hidden" class="base_unit_cost" value="{{$variation->default_purchase_price}}">
            <input type="hidden" class="base_unit_selling_price" value="{{$variation->sell_price_inc_tax}}">

            <input type="hidden" name="purchases[{{$row_count}}][product_unit_id]" value="{{$product->unit->id}}">
{{--            {{ $product->unit->short_name }}--}}
            {{--@if(!empty($sub_units))
                <br>
                <select name="purchases[{{$row_count}}][sub_unit_id]" class="form-control input-sm sub_unit">
                    @foreach($sub_units as $key => $value)
                        <option value="{{$key}}" data-multiplier="{{$value['multiplier']}}">
                            {{$value['name']}}
                        </option>
                    @endforeach
                </select>
            @else
                {{ $product->unit->short_name }}
            @endif--}}
        </td>
        <td class="hide">
            {{ $variation->sell_price_inc_tax}}
            {{--<input type="hidden" class="sell_price_inc_tax" value="">--}}
        </td>
        <td>
            @if($product->unit->type == \App\Unit::PCS)
                {{ $product->unit->actual_name }}
            @else
                m<sup>2</sup>
            @endif
{{--            {{ $product->unit->type == \App\Unit::PCS ?  : "m<sup>2</sup>" }}--}}
            {{--<input type="hidden" class="sell_price_inc_tax" value="">--}}
        </td>
        <td class="hide">
            <span class="row_subtotal_after_tax display_currency">0</span>
            {{--<input type="hidden" class="row_subtotal_after_tax_hidden" value=0>--}}
        </td>
        <?php $row_count++ ;?>

        <td><i class="fa fa-times remove_purchase_entry_row text-danger" title="Remove" style="cursor:pointer;"></i></td>
    </tr>
@endforeach

<input type="hidden" id="row_count" value="{{ $row_count }}">
