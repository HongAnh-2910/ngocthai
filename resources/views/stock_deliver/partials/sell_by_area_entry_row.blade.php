<tr class="deliver_row deliver_row_{{ $row_index }}" data-plate_stock_id="{{ $plate_stock->id }}" id="{{ $row_id }}">
    {!! Form::hidden('products['.$row_index.'][plate_stock]['.$row_id.'][selected_plate_stock_id]', $plate_stock->id, ['class' => 'selected_plate_stock_id']) !!}
    {!! Form::hidden('products['.$row_index.'][plate_stock]['.$row_id.'][transaction_sell_line_id]', $transaction_sell_line_id, ['class' => 'transaction_sell_line_id']) !!}
    {!! Form::hidden('products['.$row_index.'][plate_stock]['.$row_id.'][width]', $width, ['class' => 'width']) !!}
    {!! Form::hidden('products['.$row_index.'][plate_stock]['.$row_id.'][plates_if_not_cut]', $plates_if_not_cut, ['class' => 'plates_if_not_cut']) !!}
    {!! Form::hidden('products['.$row_index.'][plate_stock]['.$row_id.'][plates_for_print]', $plates_for_print, ['class' => 'plates_for_print']) !!}
    {!! Form::hidden('products['.$row_index.'][plate_stock]['.$row_id.'][unit_type]', $plate_stock->product->unit->type, ['class' => 'unit_type']) !!}
    {!! Form::hidden('products['.$row_index.'][plate_stock]['.$row_id.'][origin_width]', $plate_stock->width, ['class' => 'origin_width']) !!}
    {!! Form::hidden('products['.$row_index.'][plate_stock]['.$row_id.'][origin_height]', $plate_stock->height, ['class' => 'origin_height']) !!}
    {!! Form::hidden('products['.$row_index.'][plate_stock]['.$row_id.'][selected_width]', $selected_width, ['class' => 'selected_width']) !!}
    {!! Form::hidden('products['.$row_index.'][plate_stock]['.$row_id.'][quantity]', $quantity_after_cut, ['class' => 'quantity']) !!}
    {!! Form::hidden('products['.$row_index.'][plate_stock]['.$row_id.'][selected_quantity]', $quantity_before_cut, ['class' => 'selected_quantity']) !!}
    {!! Form::hidden('products['.$row_index.'][plate_stock]['.$row_id.'][row_id]', $row_id, ['class' => 'row_id']) !!}
    {!! Form::hidden('products['.$row_index.'][plate_stock]['.$row_id.'][row_index]', $row_index, ['class' => 'row_index']) !!}

    <td colspan="5"></td>
    <td>
        <span class="deliver_warehouse">{{ $plate_stock->warehouse->name }}</span>
    </td>
    <td>
        @php
            $product_name = $plate_stock->product->name . ' - ' . $plate_stock->variation->sub_sku ;
        @endphp
        {!! $product_name !!}
    </td>
    <td style="width: 80px">
        <span class="deliver_origin_height">{{ $plate_stock->product->unit->type == 'area' ? @size_format($plate_stock->height) : '' }}</span>
    </td>
    <td style="width: 80px">
        <span class="deliver_origin_width">{{ @size_format($selected_width) }}</span>
    </td>
    <td style="width: 80px">
        <span class="deliver_selected_quantity">{{ @num_format($quantity_before_cut) }}</span>
    </td>
    <td style="width: 80px">
        <span class="deliver_quantity">{{ @num_format($quantity_after_cut) }}</span>
    </td>
    <td>
        {!! Form::hidden('products['.$row_index.'][plate_stock]['.$row_id.'][remaining_widths]', $remaining_widths_json, ['class' => 'remaining_widths_hidden']) !!}
        {!! Form::hidden('products['.$row_index.'][plate_stock]['.$row_id.'][remaining_widths_if_cut]', $remaining_widths_if_cut_json, ['class' => 'remaining_widths_if_cut_hidden']) !!}
        {!! Form::hidden('products['.$row_index.'][plate_stock]['.$row_id.'][remaining_widths_if_not_cut]', $remaining_widths_if_not_cut_json, ['class' => 'remaining_widths_if_not_cut_hidden']) !!}
        <span class="deliver_remaining_width">
            @php
                $show_cut = false;
                $show_not_cut = false;

                foreach($remaining_widths as $remaining_width){
                    if($remaining_width['cut'] > 0){
                        $show_cut = true;
                    }

                    if($remaining_width['not_cut'] > 0){
                        $show_not_cut = true;
                    }
                }
            @endphp

            @if($show_cut)
                @foreach($remaining_widths as $remaining_width)
                    @php
                        $prefix = '';
                        if(count($remaining_widths) > 1){
                            $prefix = '- '.__('unit.roll').' '.$loop->iteration.': ';
                        }

                        $allow_cut_text = '';
                        if($remaining_width['not_cut'] == 0 && $remaining_width['cut'] != 0){
                            $allow_cut_text = ' ('. __('sale.enabled_not_cut') .')';
                        }
                    @endphp
                    <p class="remaining_width_text {{ !$is_cut ? 'hide' : '' }}">{{ $prefix }}{{ @size_format($remaining_width['cut']) }}m{{ $allow_cut_text }}</p>
                @endforeach
            @else
                <p class="remaining_width_text {{ !$is_cut ? 'hide' : '' }}">0m</p>
            @endif

            @if($show_not_cut)
                @foreach($remaining_widths as $remaining_width)
                    @php
                        $prefix = '';
                        if(count($remaining_widths) > 1){
                            $prefix = '- '.__('unit.roll').' '.$loop->iteration.': ';
                        }

                        $allow_cut_text = '';
                        if($remaining_width['not_cut'] == 0 && $remaining_width['cut'] != 0){
                            $allow_cut_text = ' ('. __('sale.enabled_not_cut') .')';
                        }
                    @endphp
                    <p class="remaining_width_if_not_cut_text {{ $is_cut ? 'hide' : '' }}">{{ $prefix }}{{ @size_format($remaining_width['not_cut']) }}m{{ $allow_cut_text }}</p>
                @endforeach
            @else
                <p class="remaining_width_if_not_cut_text {{ $is_cut ? 'hide' : '' }}">0m</p>
            @endif
        </span>
{{--        <span class="cut_plate_sort hide"></span>--}}
    </td>
    <td>
        {!! Form::hidden('products['.$row_index.'][plate_stock]['.$row_id.'][enabled_not_cut]', $enabled_not_cut, ['class' => 'enabled_not_cut']) !!}
        {!! Form::hidden('products['.$row_index.'][plate_stock]['.$row_id.'][is_cut]', $is_cut, ['class' => 'is_cut_hidden']) !!}
        <span>{{ $is_cut ? __('sale.cut_option_yes') : __('sale.cut_option_no') }}</span>
        {{--{!! Form::checkbox('is_cut_input', 1, $is_cut, ['class' => 'is_cut_input', (!$enabled_not_cut || $quantity_after_cut > $quantity_before_cut) ? 'disabled' : '']) !!}--}}
    </td>
    <td><i class="fa fa-times remove_sell_entry_row text-danger" title="@lang('messages.delete')" style="cursor:pointer;"></i></td>
</tr>
