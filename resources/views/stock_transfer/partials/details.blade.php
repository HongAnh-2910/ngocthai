<div class="row">
	<div class="col-xs-12 col-sm-10 col-sm-offset-1">
		<div class="table-responsive">
			<table class="table table-condensed bg-gray">
				<tr>
					<th>@lang( 'product.product_name' )</th>
					<th>@lang( 'product.height' )</th>
					<th>@lang( 'product.width' )</th>
					<th>@lang( 'purchase.purchase_quantity' )</th>
					<th>@lang( 'purchase.warehouse_from' )</th>
					<th>@lang( 'purchase.warehouse_to' )</th>
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
							{{ $details->unit_type == 'area' ? @size_format($details->height) : '' }}
						</td>
						<td>
							{{ in_array($details->unit_type, ['area', 'meter']) ? @size_format($details->width) : '' }}
						</td>
						<td>
							{{ number_format($details->quantity_line) }}
						</td>
						<td>
							{{ $details->warehouse_from }}
						</td>
						<td>
							{{ $details->warehouse_to }}
						</td>
					</tr>
				@endforeach
			</table>
		</div>
	</div>
</div>
