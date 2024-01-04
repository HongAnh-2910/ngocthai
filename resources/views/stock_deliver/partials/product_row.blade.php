<tr class="product_row" data-row_index="{{$row_count}}" id="row_{{ $row_count }}">
	{!! Form::hidden('products['.$row_count.'][transaction_sell_line_id]', $sell_line->id, ['class' => 'transaction_sell_line_id']) !!}
	{!! Form::hidden('products['.$row_count.'][variation_id]', $sell_line->variation_id, ['class' => 'variation_id']) !!}
	{!! Form::hidden('products['.$row_count.'][category_id]', $sell_line->category_id, ['class' => 'category_id']) !!}
	{!! Form::hidden('products['.$row_count.'][unit_type]', $sell_line->unit_type, ['class' => 'unit_type']) !!}
	{!! Form::hidden('products['.$row_count.'][base_unit_multiplier]', $sell_line->base_unit_multiplier, ['class' => 'base_unit_multiplier']) !!}
	{!! Form::hidden('products['.$row_count.'][width]', $sell_line->width, ['class' => 'width_input']) !!}
	{!! Form::hidden('products['.$row_count.'][height]', $sell_line->height, ['class' => 'height_input']) !!}
	{!! Form::hidden('products['.$row_count.'][quantity]', $sell_line->quantity, ['class' => 'quantity_input']) !!}

	<td>{{$row_count + 1}}</td>
	<td class="name_product">
		@php
			$product_name = $sell_line->product_name . ' - ' . $sell_line->sub_sku ;
			if(!empty($sell_line->brand)){ $product_name .= ' ' . $sell_line->brand ;}
		@endphp
		{!! $product_name !!}
	</td>
	<td style="width: 100px">
		@if($sell_line->unit_type == 'area')
			<span class="height">{{ $sell_line->height ? @size_format($sell_line->height) : @size_format($sell_line->sub_unit_height) }}</span>
		@endif
	</td>
	<td style="width: 100px">
		@if(in_array($sell_line->unit_type, ['area', 'meter']))
			<span class="width">{{ $sell_line->width ? @size_format($sell_line->width) : @size_format($sell_line->sub_unit_width) }}</span>
		@endif
	</td>
	<td style="width: 80px">
		<span class="quantity">{{ @num_format($sell_line->quantity_line) }}</span> {{ in_array($sell_line->unit_type, ['area', 'meter']) ? __('unit.plate') : $sell_line->unit_name }}
	</td>
	<td colspan="9" style="text-align: left;">
		<a href="#" class="btn btn-xs btn-primary select_product_button" data-toggle="modal" data-target="#plate_stock_deliver_modal">
			<i class="glyphicon glyphicon-edit"></i> @lang('messages.select_product')
		</a>
	</td>
</tr>
