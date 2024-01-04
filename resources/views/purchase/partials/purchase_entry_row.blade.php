@foreach( $variations as $variation)
    <tr>
        <td><span class="sr_number"></span></td>
        @if(session('business.enable_lot_number'))
            <td style="width: 100px">
                {!! Form::text('purchases[' . $row_count . '][lot_number]', null, ['class' => 'form-control input-sm']); !!}
            </td>
        @endif
        <td>
            {{ $product->name }} - {{ $product->sku }}
            @if( $product->type == 'variable' )
                <br/>
                (<b>{{ $variation->product_variation->name }}</b> : {{ $variation->name }})
            @endif
        </td>

        <td style="width: 150px !important;">
            {!! Form::hidden('purchases[' . $row_count . '][product_id]', $product->id, ['class' => 'product_id']); !!}
            {!! Form::hidden('purchases[' . $row_count . '][variation_id]', $variation->id , ['class' => 'hidden_variation_id']); !!}

            @php
                $check_decimal = 'false';
                if($product->unit->allow_decimal == 0){
                    $check_decimal = 'true';
                }
                $currency_precision = config('constants.currency_precision', 2);
                $quantity_precision = config('constants.quantity_precision', 2);
            @endphp

            <div class="form-group row">
                <div class="col-sm-8">
                    @if(!empty($sub_units))
                        <select name="purchases[{{$row_count}}][sub_unit_id]" class="form-control input-sm sub_unit" id="elementTriggerChangeOnLoad" style="width: 150px !important">
                            @foreach($sub_units as $key => $value)
                                <option value="{{$key}}" data-width="{{ $value['width'] ? $value['width'] : 0 }}" data-height="{{ $value['height'] ? $value['height'] : 0 }}" data-multiplier="{{$value['multiplier']}}">
                                    {{$value['name']}}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <span class="unit">{{ $product->unit->short_name }}</span>
                    @endif
                </div>
            </div>
            <input type="hidden" class="base_unit_cost" value="{{$variation->default_purchase_price}}">
            <input type="hidden" class="base_unit_selling_price" value="{{$variation->sell_price_inc_tax}}">
            <input type="hidden" name="purchases[{{$row_count}}][product_unit_id]" value="{{$product->unit->id}}" class="product_unit_id">
        </td>

        <td style="width: 100px">
            @if($product->unit->type == 'area')
                <span class="height_box">{{ @size_format($product->sub_unit->height) }}</span>
            @endif
            {!! Form::hidden('purchases[' . $row_count . '][height]', $product->unit->type == 'area' ? $product->sub_unit->height : 1, ['class' => 'height']) !!}
        </td>

        <td style="width: 100px">
            @if(in_array($product->unit->type, ['area', 'meter']))
                {!! Form::number('purchases[' . $row_count . '][width]',
                @size_format($product->sub_unit->width), ['class' => 'form-control input-sm width text-center input_decimal', 'required']); !!}
            @else
                {!! Form::hidden('purchases[' . $row_count . '][width]', 1, ['class' => 'width']) !!}
            @endif
        </td>

        <td style="width: 120px">
            {!! Form::text('purchases[' . $row_count . '][purchase_price_inc_tax]',
            0, ['class' => 'form-control input-sm input_number purchase_price text-center', 'required']); !!}
        </td>

        <td style="width: 150px">
            <div class="input-group input-number">
                @php
                    $multiplier = 1;
                    $allow_decimal = true;
                    if($product->sub_unit['allow_decimal'] != 1) {
                        $allow_decimal = false;
                    }
                @endphp
                <span class="input-group-btn"><button type="button" class="input-sm btn btn-default btn-flat quantity-down"><i class="fa fa-minus text-danger"></i></button></span>
                <input type="text" data-min="1" required
                       class="input-sm form-control text-center purchase_quantity mousetrap input_quantity"
                       value="1" name="purchases[{{$row_count}}][quantity]" min="1"
                       data-rule-required="true"
                       data-msg-required="@lang('validation.custom-messages.this_field_is_required')"
                >
                <span class="input-group-btn"><button type="button" class="input-sm btn btn-default btn-flat quantity-up"><i class="fa fa-plus text-success"></i></button></span>
            </div>
        </td>

        <td>
            <input type="hidden" class="input-sm area_hidden" name="purchases[{{$row_count}}][area]" value="">
            @if($product->unit->type == 'area')
                <span class="area">{{ @size_format($product->sub_unit->height * $product->sub_unit->width * 1) }}</span> m<sup>2</sup>
            @endif
        </td>

        <td>
            <input type="hidden" class="weight_hidden" name="weight" value="{{ $product->weight }}">
            @if(in_array($product->unit->type, ['area', 'meter']))
                <span class="weight">{{ @weight_format($product->weight) }}</span> kg
            @endif
        </td>

        <td>
            <input type="hidden" class="total_purchase_price_hidden" name="total_purchase_price" value="0">
            <span class="total_purchase_price">0</span> đ
        </td>

        <td>
            {!! Form::select("purchases[". $row_count ."][warehouse_id]", $warehouses, null, ['class' => 'form-control input-sm warehouse_id', 'required', 'placeholder' => __('messages.please_select')]); !!}
        </td>
        <td>
            @if(in_array($product->unit->type, ['area', 'meter']))
                {!! Form::checkbox("purchases[". $row_count ."][is_origin]", true, true, ['class' => 'is_origin']); !!}
            @endif
        </td>
        <td><i class="fa fa-times remove_purchase_entry_row text-danger" title="Remove" style="cursor:pointer;"></i></td>
        <?php $row_count++ ;?>
    </tr>
@endforeach

<input type="hidden" id="row_count" value="{{ $row_count }}">
