<tr class="sub_row_{{ $row_index }} sub_row" data-row_index="{{ $row_index }}" data-sub_row_index="{{ $sub_row_index }}">
    {!! Form::hidden('products['. $row_index .'][return_plates]['. $sub_row_index .'][type]', $plate_line->sell_line->sub_unit->type, ['class' => 'type']) !!}
    {!! Form::hidden('products['. $row_index .'][return_plates]['. $sub_row_index .'][old_width]', $plate_line->width, ['class' => 'old_width']) !!}
    {!! Form::hidden('products['. $row_index .'][return_plates]['. $sub_row_index .'][old_base_unit_id]', $plate_line->sell_line->sub_unit->base_unit_id ? $plate_line->sell_line->sub_unit->base_unit_id : '', ['class' => 'old_base_unit_id']) !!}

    <td colspan="8"></td>
    <td>
        @if($plate_line->sell_line->sub_unit->type == 'area')
            {!! Form::number('products['. $row_index .'][return_plates]['. $sub_row_index .'][height]', @size_format($plate_line->height), ['class' => 'form-control input-sm input_decimal text-center new_height', 'readonly']) !!}
        @else
            {!! Form::hidden('products['. $row_index .'][return_plates]['. $sub_row_index .'][height]', 1, ['class' => 'new_height']) !!}
        @endif
    </td>
    <td>
        @if(in_array($plate_line->sell_line->sub_unit->type, ['area', 'meter']))
            {!! Form::number('products['. $row_index .'][return_plates]['. $sub_row_index .'][width]', @size_format($plate_line->width), ['class' => 'form-control input-sm input_decimal text-center new_width', 'required', $plate_line->sell_line->sub_unit->base_unit_id ? 'readonly' : '']) !!}
        @else
            {!! Form::hidden('products['. $row_index .'][return_plates]['. $sub_row_index .'][width]', 1, ['class' => 'new_width']) !!}
        @endif
    </td>
    <td>
        {!! Form::text('products['. $row_index .'][return_plates]['. $sub_row_index .'][quantity]', @num_format($plate_line->quantity), ['class' => 'form-control input-sm input_number text-center new_quantity', 'required']) !!}
    </td>
    <td>
        @if($plate_line->sell_line->sub_unit->type == 'area')
            <span class="new_area">{{ @size_format($plate_line->width * $plate_line->height * $plate_line->quantity) }}</span> m<sup>2</sup>
        @endif
    </td>
    <td>
        {!! Form::select('products['. $row_index .'][return_plates]['. $sub_row_index .'][sell_price_type]', $return_price_types, null, ['class' => 'form-control input-sm sell_price_type']) !!}
    <td>
        <span class="new_price">{{ @num_format($plate_line->sell_line->unit_price) }}</span> đ
        {!! Form::hidden('products['. $row_index .'][return_plates]['. $sub_row_index .'][unit_price_hidden]', $plate_line->sell_line->unit_price, ['class' => 'unit_price_hidden']) !!}
    </td>
    <td>
        <span class="new_total_price">{{ (in_array($plate_line->sell_line->sub_unit->type, ['area', 'meter']) && $plate_line->sell_line->sub_unit->base_unit_id) ? @num_format($plate_line->sell_line->unit_price * $plate_line->quantity) : @num_format($plate_line->sell_line->unit_price * $plate_line->quantity * $plate_line->width * $plate_line->height) }}</span> đ
    </td>
    <td>
        {!! Form::select('products['. $row_index .'][return_plates]['. $sub_row_index .'][warehouse_id]', $warehouses, null, ['class' => 'form-control input-sm warehouse_id', 'required', 'placeholder' => __('messages.please_select')]); !!}
    </td>
    <td>
        <i class="fa fa-times remove_sell_return_entry_row text-danger" title="@lang('messages.delete')" style="cursor:pointer;"></i>
    </td>
</tr>
