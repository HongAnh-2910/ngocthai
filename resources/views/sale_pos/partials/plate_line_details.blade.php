<td style="text-align: left">
    @php
        $product_name = $plate_line->selected_plate_stock->product->name ;
        if( $plate_line->selected_plate_stock->product->type == 'variable'){
            $product_name .= ' ('. $plate_line->selected_plate_stock->variation->name .')';
        }
        $product_name .= ' - '.$plate_line->selected_plate_stock->variation->sub_sku;
    @endphp
    {!! $product_name !!}
</td>
<td>
    {{ $sell_line->sub_unit->type == 'area' ? @size_format($plate_line->height) : '' }}
</td>
<td>
    {{ in_array($sell_line->sub_unit->type, ['area', 'meter']) ? @size_format($plate_line->width) : '' }}
</td>
<td>
    {{ @num_format($plate_line->quantity) }} {{ in_array($sell_line->sub_unit->type, ['area', 'meter']) ? __('unit.plate') : $sell_line->sub_unit->actual_name }}
</td>
<td>
    {{ $plate_line->selected_plate_stock->warehouse->name }}
</td>
<td>
    {{ $plate_line->is_cut ? __('sale.cut_option_yes') : __('sale.cut_option_no') }}
</td>
