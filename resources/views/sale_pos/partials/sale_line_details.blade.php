<table class="table bg-gray text-center" id="table-detail-bills">
    <thead>
        <tr class="bg-green">
            <th rowspan="2">#</th>
            <th colspan="8">@lang('sale.customer_order')</th>
            <th colspan="6">@lang('sale.deliver_order')</th>
        </tr>
        <tr class="bg-green">
            <th>@lang( 'product.product_name' )</th>
            <th>@lang( 'product.height' )</th>
            <th>@lang( 'product.width' )</th>
            <th>@lang( 'purchase.purchase_quantity' )</th>
            <th>@lang( 'lang_v1.unit_cost' )</th>
            <th>@lang( 'sale.discount' )</th>
            <th>@lang( 'purchase.real_unit_cost' )</th>
            <th>@lang( 'sale.total_amount' )</th>

            <th>@lang( 'product.product_name' )</th>
            <th>@lang( 'product.height' )</th>
            <th>@lang( 'product.width' )</th>
            <th>@lang( 'sale.plate_quantity' )</th>
            <th>@lang( 'sale.warehouse' )</th>
            <th>@lang( 'sale.cut_option' )</th>
        </tr>
    </thead>
    <tbody>
    @foreach($sell->sell_lines as $sell_line)
        @php
            $plate_line_first = null;
            $plate_line_remaining = [];

            if(count($sell_line->plate_lines) > 1){
                $rowspan = 'rowspan='. count($sell_line->plate_lines);
            }else{
                $rowspan = '';
            }

            if(!empty($sell_line->plate_lines)){
                foreach($sell_line->plate_lines as $key => $plate_line){
                    if($key == 0){
                        $plate_line_first =  $plate_line;
                    }else{
                        $plate_line_remaining[] = $plate_line;
                    }
                }
            }

            $multiplier = $sell_line->sub_unit->base_unit_multiplier;
            if(!$multiplier){
                $multiplier = 1;
            }

            $is_default_unit = \App\Unit::find($sell_line->sub_unit_id)->is_default;

            $area = $sell_line->sub_unit->type == 'weight' ? ($sell_line->quantity_line * $multiplier) / $sell_line->product->weight : $sell_line->width * $sell_line->height * $sell_line->quantity_line;
            $unit_price_before_discount =  $sell_line->sub_unit->type == 'weight' ? $sell_line->unit_price_before_discount * $area : $sell_line->unit_price_before_discount;
            $unit_price_after_discount =  $unit_price_before_discount - $sell_line->line_discount_amount;
            $total_price = $sell_line->sub_unit->type == 'weight' ? $unit_price_after_discount * $sell_line->quantity_line : $unit_price_after_discount * $area;
            if ($is_default_unit == 0) {
                $total_price = $unit_price_after_discount * $sell_line->quantity_line;
            }
            $total_price = round_int($total_price, env('DIGIT', 4));
        @endphp
        <tr style="vertical-align: center">
            <td {{ $rowspan }}>{{ $loop->iteration }}</td>
            <td class="name_product" style="text-align: left" {{ $rowspan }}>
                @php
                    $product_name = $sell_line->product->name ;
                    if( $sell_line->product->type == 'variable'){
                        $product_name .= ' ('. $sell_line->variations->name .')';
                    }
                    $product_name .= ' - '.$sell_line->variations->sub_sku;
                @endphp
                {!! $product_name !!}
            </td>
            <td {{ $rowspan }}>
                {{ $sell_line->sub_unit->type == 'area' ? ($sell_line->height ? @size_format($sell_line->height) : '') : '' }}
            </td>
            <td {{ $rowspan }}>
                {{ in_array($sell_line->sub_unit->type, ['area', 'meter']) ? ($sell_line->width ? @size_format($sell_line->width) : '') : '' }}
            </td>
            <td {{ $rowspan }}>
                @if($sell_line->sub_unit->type != 'service')
                    {{ @num_format($sell_line->quantity_line) }} {{ in_array($sell_line->sub_unit->type, ['area', 'meter']) ? ($sell_line->sub_unit->base_unit_id ? __('unit.plate'). ' ('. __('lang_v1.origin') .')' : __('unit.plate')) : $sell_line->sub_unit->actual_name }}
                @endif
            </td>
            <td {{ $rowspan }}>
                @if($sell_line->sub_unit->type != 'service')
                    {{ @num_format($unit_price_before_discount) }} đ
                @endif
            </td>
            <td {{ $rowspan }}>
                @if($sell_line->sub_unit->type != 'service')
                    {{ @num_format($sell_line->line_discount_amount) }} đ
                @endif
            </td>
            <td {{ $rowspan }}>
                @if($sell_line->sub_unit->type != 'service')
                {{ @num_format($unit_price_after_discount) }} đ
                @endif
            </td>
            <td {{ $rowspan }}>
                {{ @num_format($total_price) }} đ
            </td>

            @if($plate_line_first)
                @include('sale_pos.partials.plate_line_details',  ['plate_line' => $plate_line_first, 'sell_line' => $sell_line])
            @endif
        </tr>

        @if(!empty($plate_line_remaining))
            @foreach($plate_line_remaining as $plate_line)
                <tr>
                    @include('sale_pos.partials.plate_line_details',  ['plate_line' => $plate_line, 'sell_line' => $sell_line])
                </tr>
            @endforeach
        @endif
    @endforeach
    </tbody>
</table>
