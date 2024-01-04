<tr class="deliver_row deliver_row_{{ $plate_line->row_index }}" data-plate_stock_id="{{ $plate_stock->id }}" id="{{ $plate_line->row_id }}">
    {!! Form::hidden('products['.$plate_line->row_index.'][plate_stock]['.$plate_line->row_id.'][selected_plate_stock_id]', $plate_stock->id, ['class' => 'selected_plate_stock_id']) !!}
    {!! Form::hidden('products['.$plate_line->row_index.'][plate_stock]['.$plate_line->row_id.'][transaction_sell_line_id]', $plate_line->transaction_sell_line_id, ['class' => 'transaction_sell_line_id']) !!}
    {!! Form::hidden('products['.$plate_line->row_index.'][plate_stock]['.$plate_line->row_id.'][width]', $plate_line->width, ['class' => 'width']) !!}
    {!! Form::hidden('products['.$plate_line->row_index.'][plate_stock]['.$plate_line->row_id.'][plates_if_not_cut]', $plate_line->plates_if_not_cut, ['class' => 'plates_if_not_cut']) !!}
    {!! Form::hidden('products['.$plate_line->row_index.'][plate_stock]['.$plate_line->row_id.'][plates_for_print]', $plate_line->plates_for_print, ['class' => 'plates_for_print']) !!}
    {!! Form::hidden('products['.$plate_line->row_index.'][plate_stock]['.$plate_line->row_id.'][unit_type]', $plate_stock->product->unit->type, ['class' => 'unit_type']) !!}
    {!! Form::hidden('products['.$plate_line->row_index.'][plate_stock]['.$plate_line->row_id.'][origin_width]', $plate_stock->width, ['class' => 'origin_width']) !!}
    {!! Form::hidden('products['.$plate_line->row_index.'][plate_stock]['.$plate_line->row_id.'][origin_height]', $plate_stock->height, ['class' => 'origin_height']) !!}
    {!! Form::hidden('products['.$plate_line->row_index.'][plate_stock]['.$plate_line->row_id.'][selected_width]', $plate_line->selected_width, ['class' => 'selected_width']) !!}
    {!! Form::hidden('products['.$plate_line->row_index.'][plate_stock]['.$plate_line->row_id.'][quantity]', $plate_line->quantity, ['class' => 'quantity']) !!}
    {!! Form::hidden('products['.$plate_line->row_index.'][plate_stock]['.$plate_line->row_id.'][selected_quantity]', $plate_line->selected_quantity, ['class' => 'selected_quantity']) !!}
    {!! Form::hidden('products['.$plate_line->row_index.'][plate_stock]['.$plate_line->row_id.'][row_id]', $plate_line->row_id, ['class' => 'row_id']) !!}
    {!! Form::hidden('products['.$plate_line->row_index.'][plate_stock]['.$plate_line->row_id.'][row_index]', $plate_line->row_index, ['class' => 'row_index']) !!}

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
        <span class="deliver_origin_width">{{ @size_format($plate_line->selected_width) }}</span>
    </td>
    <td style="width: 80px">
        <span class="deliver_selected_quantity">{{ @num_format($plate_line->selected_quantity) }}</span>
    </td>
    <td style="width: 80px">
        <span class="deliver_quantity">{{ @num_format($plate_line->quantity) }}</span>
    </td>
    <td>
        {!! Form::hidden('products['.$plate_line->row_index.'][plate_stock]['.$plate_line->row_id.'][remaining_widths]', $plate_line->remaining_widths, ['class' => 'remaining_widths_hidden']) !!}
        {!! Form::hidden('products['.$plate_line->row_index.'][plate_stock]['.$plate_line->row_id.'][remaining_widths_if_cut]', $plate_line->remaining_widths_if_cut, ['class' => 'remaining_widths_if_cut_hidden']) !!}
        {!! Form::hidden('products['.$plate_line->row_index.'][plate_stock]['.$plate_line->row_id.'][remaining_widths_if_not_cut]', $plate_line->remaining_widths_if_not_cut, ['class' => 'remaining_widths_if_not_cut_hidden']) !!}
        <span class="deliver_remaining_width">
            @php
                $remaining_widths = json_decode($plate_line->remaining_widths, true);
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
                    <p class="remaining_width_text {{ !$plate_line->is_cut ? 'hide' : '' }}">{{ $prefix }}{{ @size_format($remaining_width['cut']) }}m{{ $allow_cut_text }}</p>
                @endforeach
            @else
                <p class="remaining_width_text {{ !$plate_line->is_cut ? 'hide' : '' }}">0m</p>
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
                    <p class="remaining_width_if_not_cut_text {{ $plate_line->is_cut ? 'hide' : '' }}">{{ $prefix }}{{ @size_format($remaining_width['not_cut']) }}m{{ $allow_cut_text }}</p>
                @endforeach
            @else
                <p class="remaining_width_if_not_cut_text {{ $plate_line->is_cut ? 'hide' : '' }}">0m</p>
            @endif
        </span>

        @php
            $order_number_text = '';
            if(!empty($plate_line->remaining_plates)){
                $remaining_plates = json_decode($plate_line->remaining_plates, true);

                foreach ($remaining_plates as $remaining_plate){
                    if($remaining_plate['order_number'] == 1 && !empty($remaining_plate['next_id']) || $remaining_plate['order_number'] > 1){
                        $order_number_text = "({$remaining_plate['order_number']})";
                    }
                }
            }
        @endphp
{{--        <span class="cut_plate_sort hide">{{ $order_number_text }}</span>--}}
    </td>
    <td>
        <?php
            /*$is_cut_disabled = !$plate_line->enabled_not_cut || $plate_line->quantity > $plate_line->selected_quantity;

            if(!$is_cut_disabled){
                $remaining_plates = json_decode($plate_line->remaining_plates, true);

                foreach($remaining_plates as $remaining_plate){
                    if(!empty($remaining_plate['next_id'])){
                        $is_cut_disabled = true;
                    }
                }
            }*/
        ?>
        {!! Form::hidden('products['.$plate_line->row_index.'][plate_stock]['.$plate_line->row_id.'][enabled_not_cut]', $plate_line->enabled_not_cut, ['class' => 'enabled_not_cut']) !!}
        {!! Form::hidden('products['.$plate_line->row_index.'][plate_stock]['.$plate_line->row_id.'][is_cut]', $plate_line->is_cut, ['class' => 'is_cut_hidden']) !!}
        <span>{{ $plate_line->is_cut ? __('sale.cut_option_yes') : __('sale.cut_option_no') }}</span>
        {{--{!! Form::checkbox('is_cut_input', 1, $plate_line->is_cut, ['class' => 'is_cut_input', $is_cut_disabled ? 'disabled' : '']) !!}--}}
    </td>
    <td><i class="fa fa-times remove_sell_entry_row text-danger" title="@lang('messages.delete')" style="cursor:pointer;"></i></td>
    {!! Form::hidden('products['.$plate_line->row_index.'][plate_stock]['.$plate_line->row_id.'][remaining_plates]', $plate_line->remaining_plates, ['id' => 'remaining_plates_'. $plate_line->row_id, 'class' => 'remaining_plates']) !!}
</tr>