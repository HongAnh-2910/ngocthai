<td>{{ $plate_line['warehouse'] }}</td>
<td>
    @if($order_line['unit_type'] == 'pcs')
        {{$plate_line['name']}} - {{ $plate_line['sku'] }}
    @else
        {{$plate_line['name']}}{{ !empty($plate_line['variation']) ? ' - '.$plate_line['variation'] : '' }} - {{ $plate_line['sku'] }}
    @endif
</td>

@if($order_line['unit_type'] == 'pcs')
    <td colspan="7">{{ $plate_line['quantity'] }}C</td>
@else
    <td>
        @if($order_line['unit_type'] == 'area')
            {{ @size_format($plate_line['height_before_cut']) }}m
        @endif
    </td>
    <td>{{ @size_format($plate_line['width_before_cut']) }}m</td>
    <td>{{ $plate_line['quantity_before'] }}T</td>
    <td>{{ @size_format($plate_line['width']) }}m</td>
    <td>{{ $plate_line['quantity'] }}T</td>
    <td>
        @forelse($plate_line['remaining_widths'] as $remaining_width)
            @php
                $prefix = '';
                $quantity_text = '';

                if($remaining_width['quantity'] > 1 || count($plate_line['remaining_widths']) > 1){
                    $quantity_text = $remaining_width['quantity'].' '.__('unit.plate_lowercase').' ';
                }
            @endphp
            <p>{{ $prefix  }}{{ $quantity_text }}{{ @size_format($remaining_width['width']) }}m</p>
        @empty
            <p>0m</p>
        @endforelse
    </td>
    <td>
        @foreach($plate_line['remaining_widths'] as $remaining_width)
            @php
                $remaining_width_text = '';
                $order_number_text = '';

                if(!empty($remaining_width['next_id']) || !empty($remaining_width['prev_id'])){
                    $order_number_text = '('.$remaining_width['order_number'].') ';

                    if(!empty($remaining_width['prev_id'])){
                        $remaining_width_text = __('sale.cut_from_remaining_plate', ['width' => number_format($plate_line['width_before_cut'], 2)]);
                    }
                }
            @endphp
            <p>{{ $order_number_text }} {{ $remaining_width_text  }}</p>
            @if(!empty($remaining_width['next_id']) || !empty($remaining_width['prev_id']))
                @break
            @endif
        @endforeach
    </td>
@endif
