<br>
<div class="row">
	<div class="col-md-12">
		<div class="table-responsive">
			<table class="table bg-gray" id="single_product_details_table">
				<tr class="bg-green">
					<th rowspan="2" style="text-align: center;vertical-align: middle;width: 20%">@lang('product.product_name')</th>
					<th rowspan="2" style="text-align: center;vertical-align: middle;width: 10%">@lang('product.sku')</th>
					@can('access_default_selling_price')
				        <th colspan="2" style="text-align: center;width: 35%">{{ $product->unit->type != 'pcs' ? __('product.selling_price_by_meter') : __('product.selling_price') }}</th>
						@if($product->unit->type != 'pcs')
							<th colspan="2" style="text-align: center;width: 35%">@lang('product.selling_price_by_plate')</th>
						@endif
				    @endcan
			        {{--<th>@lang('lang_v1.variation_images')</th>--}}
				</tr>
				<tr class="bg-green">
					@if(!empty($allowed_group_prices))
						<th style="text-align: center;width: 17.5%">@lang('barcode.default')</th>
						<th style="text-align: center;width: 17.5%">@lang('lang_v1.group_prices')</th>
						@if($product->unit->type != 'pcs')
							<th style="text-align: center;width: 17.5%">@lang('barcode.default')</th>
							<th style="text-align: center;width: 17.5%">@lang('lang_v1.group_prices')</th>
						@endif
					@endif
				</tr>
				@foreach($product->variations as $variation)
				<tr>
					@can('access_default_selling_price')
						<td>{{ $product->name }}</td>
						<td>{{ $product->sku }}</td>
						<td>
{{--							<span class="display_currency" data-currency_symbol="true">{{ $variation->sell_price_inc_tax }}</span>--}}
							<span>{{ number_format($variation->sell_price_inc_tax) }} đ</span>
						</td>
					@endcan
					@if(!empty($allowed_group_prices))
			        	<td class="td-full-width">
			        		@foreach($allowed_group_prices as $key => $value)
			        			<strong>{{$value}}</strong> - @if(!empty($group_price_details[$variation->id][$key]))
{{--			        				<span class="display_currency" data-currency_symbol="true">{{ $group_price_details[$variation->id][$key] }}</span>--}}
									<span>{{ number_format($group_price_details[$variation->id][$key]) }} đ</span>
			        			@else
			        				0 đ
			        			@endif
			        			<br>
			        		@endforeach
			        	</td>
			        @endif
					@if($product->unit->type != 'pcs')
						@can('access_default_selling_price')
							<td>
								<span>{{ number_format($variation->default_sell_price_by_plate) }} đ</span>
							</td>
						@endcan
						@if(!empty($allowed_group_prices))
							<td class="td-full-width">
								@foreach($allowed_group_prices as $key => $value)
									<strong>{{$value}}</strong> - @if(!empty($group_price_details_by_plate[$variation->id][$key]))
										<span>{{ number_format($group_price_details_by_plate[$variation->id][$key]) }} đ</span>
									@else
										0 đ
									@endif
									<br>
								@endforeach
							</td>
						@endif
					@endif
			        {{--<td>
			        	@foreach($variation->media as $media)
			        		{!! $media->thumbnail([60, 60], 'img-thumbnail') !!}
			        	@endforeach
			        </td>--}}
				</tr>
				@endforeach
			</table>
		</div>
	</div>
</div>