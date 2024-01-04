@php
    $new_unit_price	= $sell_return->variation->default_sell_price;
    $new_unit_price_by_plate = $sell_return->variation->default_sell_price_by_plate;

    if($sell->price_group){
        $price_group_id = $sell->price_group->id;
        $group_prices = $sell_return->variation->group_prices;
        foreach ($group_prices as $group_price){
            if($group_price->price_group_id == $price_group_id){
                $new_unit_price	= $group_price->price_inc_tax;
                $new_unit_price_by_plate = $group_price->price_by_plate;
                break;
            }
        }
    }
@endphp

{!! Form::hidden('products['. $row_index .'][return_plates]['. $key .'][type]', $sell_return->variation->product->unit->type, ['class' => 'type']) !!}
{!! Form::hidden('products['. $row_index .'][return_plates]['. $key .'][old_width]', $plate_line->width, ['class' => 'old_width']) !!}
{!! Form::hidden('products['. $row_index .'][return_plates]['. $key .'][sell_return_id]', $sell_return->id, ['class' => 'sell_return_id']) !!}
<td>
    @if($sell_return->variation->product->unit->type == 'area')
        {!! Form::hidden('products['. $row_index .'][return_plates]['. $key .'][height]', @size_format($sell_return->height), ['class' => 'new_height', 'readonly']) !!}
    @else
        {!! Form::hidden('products['. $row_index .'][return_plates]['. $key .'][height]', 1, ['class' => 'new_height', 'readonly']) !!}
    @endif
    {{ $sell_return->variation->product->unit->type == 'area' ? @size_format($sell_return->height) : '' }}
</td>
<td>
    @if(in_array($sell_return->variation->product->unit->type, ['area', 'meter']))
        {!! Form::hidden('products['. $row_index .'][return_plates]['. $key .'][width]', @size_format($sell_return->width), ['class' => 'new_width', ($sell_return->sell_price_type == 'new_by_plate' || ($sell_return->sell_price_type == 'old' && $plate_line->sell_line->sub_unit->base_unit_id)) ? 'readonly' : '']) !!}
    @else
        {!! Form::hidden('products['. $row_index .'][return_plates]['. $key .'][width]', 1, ['class' => 'new_width']) !!}
    @endif
    {{ in_array($sell_return->variation->product->unit->type, ['area', 'meter']) ? @size_format($sell_return->width) : '' }}
</td>
<td>
    {!! Form::hidden('products['. $row_index .'][return_plates]['. $key .'][quantity]', @number_format($sell_return->quantity), ['class' => 'new_quantity']) !!}
    {{ in_array($sell_return->variation->product->unit->type, ['area', 'meter']) ? @number_format($sell_return->quantity) . ' ' . __('unit.plate') : @number_format($sell_return->quantity) . ' ' . $sell_return->variation->product->unit->actual_name }}
</td>
<td>
    {{ $sell_return->variation->product->unit->type == 'area' ? @size_format($sell_return->height * $sell_return->width * $sell_return->quantity).' m2' : '' }}
</td>
<td>
    {{ @number_format($sell_return->unit_price) }} đ
    {!! Form::hidden('products['. $row_index .'][return_plates]['. $key .'][unit_price_hidden]', $sell_return->unit_price, ['class' => 'unit_price_hidden']) !!}
</td>

<td>
    {!! Form::hidden('products['. $row_index .'][return_plates]['. $key .'][sell_price_type]', $sell_return->sell_price_type, ['class' => 'form-control input-sm sell_price_type']) !!}
    {{ ($sell_return->sell_price_type == 'new_by_plate' || ($sell_return->sell_price_type == 'old' && $plate_line->sell_line->sub_unit->base_unit_id)) ? @number_format($sell_return->unit_price * $sell_return->quantity) : @number_format($sell_return->unit_price * $sell_return->height * $sell_return->width * $sell_return->quantity) }} đ

</td>
<td>
    {!! Form::hidden('products['. $row_index .'][return_plates]['. $key .'][warehouse_id]', $sell_return->warehouse->id, ['class' => 'form-control input-sm']) !!}
    {{ $sell_return->warehouse->name }}
</td>
