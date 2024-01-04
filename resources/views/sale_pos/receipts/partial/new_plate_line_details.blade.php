@if($type == 'first')
    <td {{ $rowspan }}>{{ $order_line['warehouse'] }}</td>
    <td {{ $rowspan }}>
        @if($order_line['unit_type'] == 'pcs')
            {{$order_line['name']}}
        @else
            {{$order_line['name']}}{{ !empty($order_line['variation']) ? ' - '.$order_line['variation'] : '' }} {{ @size_format($order_line['selected_height']) }}m
        @endif
    </td>
@endif

@if($order_line['unit_type'] == 'pcs')
    <td colspan="6">{{ $order_line['selected_quantity'] }}{{ $order_line['unit_name'] }}</td>
@else
    <td>{{ @size_format($plate_line['deliver_width']) }}m</td>
    <td>
        {{ @num_format($plate_line['deliver_quantity']) }}{{ $order_line['unit_name'] }}
    </td>

    @if($type == 'first')
        <td {{ $rowspan }}>{{ @size_format($order_line['selected_width']) }}m</td>
        <td {{ $rowspan }}>{{ @num_format($order_line['selected_quantity']) }}{{ $order_line['unit_name'] }}</td>
        <td {{ $rowspan }}>
            @if($order_line['remaining_width'] > 0)
                {{ $order_line['remaining_quantity'] }} @lang('unit.plate_lowercase') {{ $order_line['remaining_width'] }}m
            @else
                0m
            @endif
        </td>
    @endif
@endif
