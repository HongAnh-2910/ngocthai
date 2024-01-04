@foreach($plate_stocks as $plate_stock)
    <tr class="product_row" data-plate_stock_id="{{ $plate_stock->id }}">
        {!! Form::hidden('plate_stocks['.$plate_stock->id.'][id]', $plate_stock->id) !!}
        {!! Form::hidden('plate_stocks['.$plate_stock->id.'][warehouse_id_before]', $plate_stock->warehouse_id, ['class' => 'warehouse_id_before']) !!}
        {!! Form::hidden('plate_stocks['.$plate_stock->id.'][unit_type]', $plate_stock->product->unit->type, ['class' => 'unit_type']) !!}
        {!! Form::hidden('plate_stocks['.$plate_stock->id.'][origin_width]', in_array($plate_stock->product->unit->type, ['area', 'meter']) ? $plate_stock->width : 1, ['class' => 'origin_width']) !!}
        {!! Form::hidden('plate_stocks['.$plate_stock->id.'][origin_height]', $plate_stock->product->unit->type == 'area' ? $plate_stock->height : 1, ['class' => 'origin_height']) !!}
        {!! Form::hidden('plate_stocks['.$plate_stock->id.'][weight]', $plate_stock->product->weight ? $plate_stock->product->weight : 0, ['class' => 'weight']) !!}
        <td>
            @php
                $product_name = $plate_stock->product->name . ' - ' . $plate_stock->variation->sub_sku ;
            @endphp
            {!! $product_name !!}
        </td>
        <td style="width: 80px">
            <span class="current_height">{{ $plate_stock->product->unit->type == 'area' ? @size_format($plate_stock->height) : '' }}</span>
        </td>
        <td style="width: 80px">
            <span class="current_width">{{ in_array($plate_stock->product->unit->type, ['area', 'meter']) ? @size_format($plate_stock->width) : '' }}</span>
        </td>
        <td style="width: 80px">
            <span class="current_stock">{{ number_format($plate_stock->qty_available) }}</span>
        </td>
        <td style="width: 150px">
            {{ $plate_stock->warehouse->name }}
        </td>
        <td style="width: 100px">
            <input type="number" class="form-control adjustment_height input_decimal {{ $plate_stock->product->unit->type != 'area' ? 'hide' : '' }}" value="{{ $plate_stock->height }}" name="plate_stocks[{{$plate_stock->id}}][height]"
                   required
                   min="0">
        </td>
        <td style="width: 100px">
            <input type="number" class="form-control adjustment_width input_decimal input_quantity {{ !in_array($plate_stock->product->unit->type, ['area', 'meter']) ? 'hide' : '' }}" value="1" name="plate_stocks[{{$plate_stock->id}}][width]"
                   required
                   min="0">
        </td>
        <td style="width: 150px">
            <input type="text" class="form-control adjustment_quantity input_quantity" value="1" name="plate_stocks[{{$plate_stock->id}}][quantity]"
                   data-rule-required="true" data-msg-required="@lang('validation.custom-messages.this_field_is_required')"
{{--                   min="0"--}}
                   data-rule-abs_digit="true" data-msg-abs_digit="@lang('lang_v1.decimal_value_not_allowed')" data-decimal=0
                   data-rule-max-value="{{$plate_stock->qty_available}}" data-msg-max-value="@lang('validation.custom-messages.quantity_not_available', ['qty'=> $plate_stock->qty_available, 'unit' => 'Tấm'  ])"
                   data-qty_available="{{$plate_stock->qty_available}}" data-msg_max_default="@lang('validation.custom-messages.quantity_not_available', ['qty'=> $plate_stock->qty_available, 'unit' => 'Tấm'  ])">
        </td>
        <td>
            @if(in_array($plate_stock->product->unit->type, ['area', 'meter']))
                <span class="weight_adjustment_box"><span class="weight_adjustment">0</span> kg</span>
            @else
                <span class="pcs_adjustment_box"><span class="pcs_adjustment">0</span> cái</span>
            @endif
        </td>
        <td><i class="fa fa-times remove_sell_entry_row text-danger" title="@lang('messages.delete')" style="cursor:pointer;"></i></td>
    </tr>
@endforeach
