<tr class="deliver_row deliver_row_{{ $row_index }}" data-plate_stock_id="{{ $plate_stock->id }}" id="{{ $row_id }}">
    {!! Form::hidden('products['.$row_index.'][plate_stock]['.$row_id.'][selected_plate_stock_id]', $plate_stock->id) !!}
    {!! Form::hidden('products['.$row_index.'][plate_stock]['.$row_id.'][transaction_sell_line_id]', $transaction_sell_line_id, ['class' => 'transaction_sell_line_id']) !!}
    {!! Form::hidden('products['.$row_index.'][plate_stock]['.$row_id.'][unit_type]', $plate_stock->product->unit->type, ['class' => 'unit_type']) !!}
    {!! Form::hidden('products['.$row_index.'][plate_stock]['.$row_id.'][row_id]', $row_id) !!}
    {!! Form::hidden('products['.$row_index.'][plate_stock]['.$row_id.'][row_index]', $row_index) !!}

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
        <span class="deliver_quantity">{{ @num_format($selected_quantity) }}</span> {{ $plate_stock->product->unit->short_name ? $plate_stock->product->unit->short_name : $plate_stock->product->unit->actual_name }}
        {!! Form::hidden('products['.$row_index.'][plate_stock]['.$row_id.'][selected_quantity]', $selected_quantity, ['class' => 'selected_quantity']) !!}
    </td>
    <td><i class="fa fa-times remove_sell_entry_row text-danger" title="@lang('messages.delete')" style="cursor:pointer;"></i></td>
</tr>
