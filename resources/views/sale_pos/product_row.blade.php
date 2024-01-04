<tr class="product_row" data-row_index="{{$row_count}}">
	<td><span class="sr_number"></span></td>
	@if(session('business.enable_lot_number'))
	<td style="width: 100px">
		{!! Form::text('products[' . $row_count . '][lot_number]', null, ['class' => 'form-control input-sm']); !!}
	</td>
	@endif
	<td class="name_product">
		@php
			$product_name = $product->product_name . ' - ' . $product->sub_sku ;
			if(!empty($product->brand)){ $product_name .= ' ' . $product->brand ;}
		@endphp
		{!! $product_name !!}
		<input type="hidden" class="enable_sr_no" value="{{$product->enable_sr_no}}">
		<input type="hidden"
			class="product_type"
			name="products[{{$row_count}}][product_type]"
			value="{{$product->product_type}}">

		@php
			$hide_tax = 'hide';
	        if(session()->get('business.enable_inline_tax') == 1){
	            $hide_tax = '';
	        }
	        $edit = isset($action) && $action == 'edit';
			$tax_id = $product->tax_id;
			$item_tax = !empty($product->item_tax) ? $product->item_tax : 0;
		@endphp

		<!-- Description modal end -->
		@if(in_array('modifiers' , $enabled_modules))
			<div class="modifiers_html">
				@if(!empty($product->product_ms))
					@include('restaurant.product_modifier_set.modifier_for_product', array('edit_modifiers' => true, 'row_count' => $loop->index, 'product_ms' => $product->product_ms ) )
				@endif
			</div>
		@endif

		@php
			$max_qty_rule = $product->qty_available;
			$max_qty_msg = __('validation.custom-messages.quantity_not_available', ['qty'=> $product->formatted_qty_available, 'unit' => $product->unit  ]);
		@endphp
	</td>

	<td style="width: 110px">
		<!-- If edit then transaction sell lines will be present -->
		@if(!empty($product->transaction_sell_lines_id))
			<input type="hidden" name="products[{{$row_count}}][transaction_sell_lines_id]" class="form-control" value="{{$product->transaction_sell_lines_id}}">
		@endif

		<input type="hidden" name="products[{{$row_count}}][product_id]" class="form-control product_id" value="{{$product->product_id}}">

		<input type="hidden" value="{{$product->variation_id}}"
			name="products[{{$row_count}}][variation_id]" class="row_variation_id">

		<input type="hidden" value="{{$product->enable_stock}}"
			name="products[{{$row_count}}][enable_stock]">

		@if(empty($product->quantity))
			@php
				$product->quantity = 1;
			@endphp
		@endif

		@php
			$multiplier = 1;
			$allow_decimal = true;
			if($product->unit_allow_decimal != $default_unit_id) {
				$allow_decimal = false;
			}
		@endphp

		@foreach($sub_units as $key => $value)
			@if(!empty($product->sub_unit_id) && $product->sub_unit_id == $key)
				@php
					$multiplier = 1;
					if($value['multiplier']){
						$multiplier = $value['multiplier'];
					}
					$max_qty_rule = $max_qty_rule / $multiplier;
					$unit_name = $value['name'];
					$max_qty_msg = __('validation.custom-messages.quantity_not_available', ['qty'=> $max_qty_rule, 'unit' => $unit_name  ]);

					if(!empty($product->lot_no_line_id)){
						$max_qty_msg = __('lang_v1.quantity_error_msg_in_lot', ['qty'=> $max_qty_rule, 'unit' => $unit_name  ]);
					}

					if($value['allow_decimal']) {
						$allow_decimal = true;
					}
				@endphp
			@endif
		@endforeach

		@php
			$check_decimal = 'false';
            if($product->unit_allow_decimal == 0){
                $check_decimal = 'true';
            }
            $currency_precision = config('constants.currency_precision', 2);
            $quantity_precision = config('constants.quantity_precision', 2);
		@endphp

		<div class="form-group row area_div">
			<div class="col-sm-8">
				@if(!empty($sub_units))
					<select name="products[{{$row_count}}][sub_unit_id]" class="form-control input-sm sub_unit" id="elementTriggerChangeOnLoad" style="width: 150px !important">
						@foreach($sub_units as $key => $value)
							<option value="{{$key}}" data-is_default="{{ $value['is_default'] }}" data-width="{{ $value['width'] ?? 0 }}" data-height="{{ $value['height'] ?? 0 }}" data-multiplier="{{$value['multiplier']}}" data-type="{{ $value['type'] }}"
									{{ ($edit && $key == $product->sub_unit_id) ? 'selected' : '' }}
							>
								{{$value['name']}}
							</option>
						@endforeach
					</select>
				@else
					<span class="unit">{{ $product->unit }}</span>
					{!! Form::hidden('products['. $row_count .'][sub_unit_id]', $product->sub_unit_id) !!}
				@endif
			</div>
		</div>

		<input type="hidden" name="products[{{$row_count}}][product_unit_id]" value="{{$product->unit_id}}" class="product_unit_id">
		<input type="hidden" class="base_unit_multiplier" name="products[{{$row_count}}][base_unit_multiplier]" value="{{$multiplier}}">
		<input type="hidden" class="hidden_base_unit_sell_price" value="{{$product->default_sell_price / $multiplier}}">

		@if($product->product_type == 'combo')
			@foreach($product->combo_products as $k => $combo_product)
				@if(isset($action) && $action == 'edit')
					@php
						$combo_product['qty_required'] = $combo_product['quantity'] / $product->quantity_ordered;

						$qty_total = $combo_product['quantity'];
					@endphp
				@else
					@php
						$qty_total = $combo_product['qty_required'];
					@endphp
				@endif

				<input type="hidden"
					name="products[{{$row_count}}][combo][{{$k}}][product_id]"
					value="{{$combo_product['product_id']}}">

					<input type="hidden"
					name="products[{{$row_count}}][combo][{{$k}}][variation_id]"
					value="{{$combo_product['variation_id']}}">

					<input type="hidden"
					class="combo_product_qty"
					name="products[{{$row_count}}][combo][{{$k}}][quantity]"
					data-unit_quantity="{{$combo_product['qty_required']}}"
					value="{{$qty_total}}">

					@if(isset($action) && $action == 'edit')
						<input type="hidden"
							name="products[{{$row_count}}][combo][{{$k}}][transaction_sell_lines_id]"
							value="{{$combo_product['id']}}">
					@endif

			@endforeach
		@endif
	</td>

	@php
		if(in_array($product->unit_type, ['area', 'meter'])){
    		if($product->unit_type == 'area'){
    		    $height = $product->height ? $product->height : $product->sub_unit_height;
    		}else{
    		    $height = 1;
    		}
    		$width = $product->width ? $product->width : $product->sub_unit_width;
		}else{
    		$height = 1;
    		$width = 1;
		}

		$multiplier = $edit ? $product->base_unit_multiplier : 1;
		if(!$multiplier){
			$multiplier = 1;
		}

		$quantity = $edit ? $product->quantity_line : 1;
		$area = $width * $height * $quantity;
		$weight = $area * $product->weight;
		$unit_price_before_discount_by_area =  $edit ? $product->unit_price_before_discount : $product->default_sell_price;
		$unit_price_before_discount_by_plate =  $edit ? $product->unit_price_before_discount : $product->default_sell_price_by_plate;
		$discount_amount = $edit ? $product->line_discount_amount : 0;
		$unit_price_before_discount = $unit_price_before_discount_by_area;
		$unit_price_after_discount =  $unit_price_before_discount - $discount_amount;
		$total_price = (($edit && $product->is_default_unit) || !$edit) ? $unit_price_after_discount * $area : $unit_price_after_discount * $quantity;
        $total_price = round_int($total_price, 4);
	@endphp
	<td style="width: 100px">
		@if($product->unit_type =='area')
			<span class="height_box">{{ @size_format($height) }}</span>
		@endif
		{!! Form::hidden('products[' . $row_count . '][height]', $height, ['class' => 'height']) !!}
	</td>

	<td style="width: 100px">
		@if(in_array($product->unit_type, ['area', 'meter']))
			{!! Form::number('products[' . $row_count . '][width]', @size_format($width),
				['class' => 'form-control input-sm width text-center input_decimal', 'required', ($edit && in_array($product->unit_type, ['area', 'meter']) && $product->base_unit_id) ? 'readonly' : '']); !!}
		@else
			{!! Form::hidden('products[' . $row_count . '][width]', 1, ['class' => 'width']); !!}
		@endif
	</td>

	<td style="width: 130px">
		@if($product->unit_type != 'service')
			<div class="input-group input-number">
				<span class="input-group-btn"><button type="button" class="input-sm btn btn-default btn-flat quantity-down"><i class="fa fa-minus text-danger"></i></button></span>
				<input
						type="text"
						data-min="1"
						required="required"
						class="input-sm form-control text-center pos_quantity mousetrap input_quantity"
						value="{{ intval($quantity) }}"
						name="products[{{$row_count}}][quantity]"
						data-rule-required="true"
						data-msg-required="@lang('validation.custom-messages.this_field_is_required')"
				/>
				<span class="input-group-btn"><button type="button" class="input-sm btn btn-default btn-flat quantity-up"><i class="fa fa-plus text-success"></i></button></span>
			</div>
		@else
			{!! Form::hidden('products[' . $row_count . '][quantity]', 1, ['class' => 'pos_quantity']); !!}
		@endif
	</td>

	<td>
		@if($product->unit_type == 'area')
			<span class="area">{{ @size_format($area) }}</span> m<sup>2</sup>
		@endif
		<input type="hidden" class="form-control input-sm area_hidden input_number mousetrap" name="products[{{$row_count}}][area]"
		   value="@if($edit)
					{{ $product->unit_type == 'area' ? $area : 0 }}
				@else
					{{ $product->unit_type == 'area' ? $product->sub_unit_width * $product->sub_unit_height : 0 }}
				@endif">
	</td>

	<td>
		@if(in_array($product->unit_type, ['area', 'meter']))
			<span class="weight">{{ @size_format($weight) }}</span> kg
		@endif
		<input type="hidden" class="form-control weight_per_square_meter_hidden" name="weight_per_square_meter" value="{{ $product->weight }}">
		<input type="hidden" class="form-control weight_hidden" name="weight" value="{{ $weight }}">
	</td>

	<td>
		@if($product->unit_type != 'service')
			<span class="unit_price">{{ @num_format($unit_price_before_discount) }}</span> đ
			{!! Form::hidden('products[' . $row_count . '][default_sell_price]', $unit_price_before_discount, ['class' => 'default_sell_price']); !!}
		@endif
	</td>

	<td style="width: 110px">
		{!! Form::hidden('products[' . $row_count . '][line_discount_type]', 'fixed', ['class' => 'discount_type_row']); !!}
		@if($product->unit_type != 'service')
			{!! Form::text('products[' . $row_count . '][line_discount_amount]', @num_format($discount_amount),
				[
					'class' => 'form-control input-sm text-center discount_amount_row input_number',
					'data-max_value' => $unit_price_before_discount,
					/*'data-max_msg' => __('validation.custom-messages.discount_not_allow', ['qty'=> ($product->is_default_unit ? $unit_price_before_discount : $unit_price_before_discount_by_plate)]),*/
					'required'
				]);
			!!}
		@else
			{!! Form::hidden('products[' . $row_count . '][line_discount_amount]', 0, ['class' => 'discount_amount_row']); !!}
		@endif
	</td>

	<td>
		<input type="hidden" name="products[{{$row_count}}][unit_price_inc_tax]" class="form-control pos_unit_price_inc_tax_hidden input_number" value="{{ $unit_price_after_discount }}">
		@if($product->unit_type != 'service')
			<span class="pos_unit_price_inc_tax">{{ @num_format($unit_price_after_discount) }}</span> đ
		@endif
	</td>

	<td class="text-center">
		<input type="hidden" class="form-control pos_line_total input_number" value="{{ $total_price }}">
		@if($product->unit_type == 'service')
			{!! Form::text('products[' . $row_count . '][default_sell_price]', @num_format($unit_price_before_discount),
			[
				'class' => 'form-control input-sm text-center line_total_service input_number',
				'required'
			]);
		!!}
		@else
			<span class="pos_line_total_text">{{ @num_format($total_price) }}</span> đ
		@endif
	</td>

	<td class="text-center">
		<a href="#" class="btn btn-xs btn-primary duplicate_current_row"><i class="fa fa-copy"></i> @lang('messages.duplicate')</a>
		<a href="#" class="btn btn-xs btn-danger pos_remove_row"><i class="fa fa-times"></i> @lang('messages.delete')</a>
		<input type="hidden" value="<?= $max_qty_rule ?? '0'?>" name="maxQtyRule">
	</td>
</tr>
