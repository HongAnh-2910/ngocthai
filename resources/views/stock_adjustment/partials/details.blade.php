<div class="row">
	<div class="col-xs-12 col-sm-10 col-sm-offset-1">
		<div class="table-responsive">
			<table class="table table-condensed bg-gray">
				<tr>
					<th>@lang( 'product.product_name' )</th>
					<th>@lang( 'stock_adjustment.before_adjustment_height' )</th>
					<th>@lang( 'stock_adjustment.before_adjustment_width' )</th>
					<th>@lang( 'stock_adjustment.after_adjustment_height' )</th>
					<th>@lang( 'stock_adjustment.after_adjustment_width' )</th>
					<th>@lang( 'stock_adjustment.adjustment_quantity' )</th>
					<th>@lang( 'purchase.warehouse' )</th>
				</tr>
				@foreach( $stock_adjustment_details as $details )
					<tr>
						<td>
							{{ $details->product }}
							@if( $details->type == 'variable')
								{{ '-' . $details->product_variation . '-' . $details->variation }}
							@endif
							( {{ $details->sub_sku }} )
						</td>

						<td>
							{{ $details->unit_type == 'area' ? @size_format($details->before_height) : '' }}
						</td>
						<td>
							{{ in_array($details->unit_type, ['area', 'meter']) ? @size_format($details->before_width) : '' }}
						</td>
						<td>
							{{ $details->unit_type == 'area' ? @size_format($details->after_height) : '' }}
						</td>
						<td>
							{{ in_array($details->unit_type, ['area', 'meter']) ? @size_format($details->after_width) : '' }}
						</td>
						<td>
							{{ number_format($details->quantity_line) }}
						</td>
						<td>
							{{ $details->warehouse }}
						</td>
					</tr>
				@endforeach
			</table>
		</div>
	</div>
</div>