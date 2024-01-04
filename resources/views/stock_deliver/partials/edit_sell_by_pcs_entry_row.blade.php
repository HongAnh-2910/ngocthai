<tr class="deliver_row deliver_row_{{ $plate_line->row_index }}" data-plate_stock_id="{{ $plate_stock->id }}" id="{{ $plate_line->row_id }}">
    {!! Form::hidden('products['.$plate_line->row_index.'][plate_stock]['.$plate_line->row_id.'][selected_plate_stock_id]', $plate_stock->id) !!}
    {!! Form::hidden('products['.$plate_line->row_index.'][plate_stock]['.$plate_line->row_id.'][transaction_sell_line_id]', $plate_line->transaction_sell_line_id, ['class' => 'transaction_sell_line_id']) !!}
    {!! Form::hidden('products['.$plate_line->row_index.'][plate_stock]['.$plate_line->row_id.'][unit_type]', $plate_stock->product->unit->type, ['class' => 'unit_type']) !!}
    {!! Form::hidden('products['.$plate_line->row_index.'][plate_stock]['.$plate_line->row_id.'][row_id]', $plate_line->row_id) !!}
    {!! Form::hidden('products['.$plate_line->row_index.'][plate_stock]['.$plate_line->row_id.'][row_index]', $plate_line->row_index) !!}

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
    <td colspan="6">
        <span class="deliver_quantity">{{ @num_format($plate_line->quantity) }}</span> {{ $plate_stock->product->unit->short_name ? $plate_stock->product->unit->short_name : $plate_stock->product->unit->actual_name }}
        {!! Form::hidden('products['.$plate_line->row_index.'][plate_stock]['.$plate_line->row_id.'][selected_quantity]', $plate_line->quantity, ['class' => 'selected_quantity']) !!}
    </td>
    <td><i class="fa fa-times remove_sell_entry_row text-danger" title="@lang('messages.delete')" style="cursor:pointer;"></i></td>
</tr>
