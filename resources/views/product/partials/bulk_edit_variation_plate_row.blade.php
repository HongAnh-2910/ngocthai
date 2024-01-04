<td></td>
<td>
{{--	@dd($variation->default_sell_price_by_plate, $variation->default_sell_price)--}}
	<div class="input-group" style="margin: auto">
		{!! Form::text('products[' . $product->id . '][variations][' . $variation->id . '][default_sell_price_by_plate]', @num_format($variation->default_sell_price_by_plate), ['placeholder' => __('product.exc_of_tax'), 'class' => 'form-control input-sm input_number sp_exc_tax required']); !!}
	</div>

	<div class="input-group hidden">
		<span class="input-group-addon"><small>@lang('product.inc_of_tax')</small></span>
		{!! Form::text('products[' . $product->id . '][variations][' . $variation->id . '][default_sell_price_by_plate]', @num_format($variation->default_sell_price_by_plate), ['placeholder' => __('product.dpp_inc_tax'), 'class' => 'form-control input-sm input_number sp_inc_tax']); !!}
	</div>
</td>
<td style="text-align: left;">
	@foreach($price_groups as $k => $v)
		@php
			$price_grp = $variation->group_prices->filter(function($item) use($k) {
			    return $item->price_group_id == $k;
			})->first();
		@endphp
		<div class="input-group" style="width: 100%;">
			<span style="width: 20%;text-align: left" class="input-group-addon"><small>{{$v}}</small></span>
			{!! Form::text('products[' . $product->id . '][variations][' . $variation->id . '][group_prices][plate][' . $k . ']', !empty($price_grp) ? @num_format($price_grp->price_by_plate) : 0, ['class' => 'form-control input-sm input_number']); !!}
		</div>
	@endforeach
</td>
