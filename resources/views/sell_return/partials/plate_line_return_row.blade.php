@if($line['unit_type'] == 'pcs')
    <td></td>
@else
    <td class="text-right">
        @lang('unit.plate') {{ @size_format($plate_line['height']) }}m x {{ @size_format($plate_line['width']) }}m
    </td>
@endif
<td class="text-right">
    {{@number_format($plate_line['quantity'])}}{{ $line['unit_type'] == 'pcs' ? 'C' : 'T' }}
    {{--{{ $line['unit_type'] == 'pcs' ? $plate_line['quantity'] . ' '. $line['units'] : $plate_line['quantity'] . ' ' . __('unit.plate') }}--}}
</td>
@if($line['unit_type'] == 'pcs')
    <td></td>
@else
    <td class="text-right">
        {{ @size_format($plate_line['height'] * $plate_line['width'] * $plate_line['quantity']) }}
    </td>
@endif
<td class="text-right">
    {{ @number_format($plate_line['unit_price']) }}
</td>
<td class="text-right">
    {{ ($plate_line['sell_price_type'] == 'new_by_plate' || ($plate_line['sell_price_type'] == 'old' && $line['base_unit_id'])) ? @number_format($plate_line['unit_price'] * $plate_line['quantity']) : @number_format($plate_line['unit_price'] * $plate_line['height'] * $plate_line['width'] * $plate_line['quantity']) }}
</td>