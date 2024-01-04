@php
    $hide_tax = '';
    if( session()->get('business.enable_inline_tax') == 0){
        $hide_tax = 'hide';
    }
    $currency_precision = config('constants.currency_precision', 2);
    $quantity_precision = config('constants.quantity_precision', 2);
@endphp
<div class="table-responsive">
    <table class="table table-condensed table-bordered table-th-green text-center table-striped"
    id="purchase_entry_table">
        <thead>
            <tr>
                <th>#</th>
                @if(session('business.enable_lot_number'))
                    <th>@lang('lang_v1.lot_number')</th>
                @endif
                <th>@lang( 'product.product_name' )</th>
                <th>@lang( 'product.base_unit' )</th>
                <th>@lang( 'product.height' )</th>
                <th>@lang( 'product.width' )</th>
                <th>@lang( 'purchase.purchase_price' )</th>
                <th>@lang( 'purchase.purchase_quantity' )</th>
                <th>@lang( 'purchase.purchase_total_quantity' )</th>
                <th>@lang( 'product.weight' )</th>
                <th>@lang( 'purchase.total_purchase_price' )</th>
                <th>@lang( 'purchase.warehouse' )</th>
                <th>@lang( 'purchase.is_origin' )</th>
                <th><i class="fa fa-trash" aria-hidden="true"></i></th>
            </tr>
        </thead>
        <tbody>
    <?php $row_count = 0; ?>
    @foreach($purchase->purchase_lines as $purchase_line)
        <tr>
            <td><span class="sr_number"></span></td>
            @if(session('business.enable_lot_number'))
                <td style="width: 100px">
                    {!! Form::text('purchases[' . $row_count . '][lot_number]', $purchase_line->lot_number, ['class' => 'form-control input-sm']); !!}
                </td>
            @endif
            <td>
                {{ $purchase_line->product->name }} - {{ $purchase_line->variations->sub_sku }}
                @if( $purchase_line->product->type == 'variable' )
                    <br/>
                    (<b>{{ $purchase_line->variations->product_variation->name }}</b> : {{ $purchase_line->variations->name }})
                @endif
            </td>
            <td>
                {!! Form::hidden('purchases[' . $loop->index . '][product_id]', $purchase_line->product_id, ['class' => 'product_id']); !!}
                {!! Form::hidden('purchases[' . $loop->index . '][variation_id]', $purchase_line->variation_id ); !!}
                {!! Form::hidden('purchases[' . $loop->index . '][purchase_line_id]', $purchase_line->id); !!}

                @php
                    $check_decimal = 'false';
                    if($purchase_line->product->unit->allow_decimal == 0){
                        $check_decimal = 'true';
                    }

                    $base_unit = $purchase_line->product->unit;
                    $sub_units = $base_unit->sub_units;
                    $sub_unit_id = $purchase_line->sub_unit_id;
                    $sub_unit = $sub_units->filter(function ($item) use ($sub_unit_id) {
                        return $item->id == $sub_unit_id;
                    })->first();

                    if (!empty($sub_unit)) {
                        $multiplier = $sub_unit->base_unit_multiplier;
                        $area = $purchase_line->quantity * $multiplier;
                    }else{
                        $area = $purchase_line->quantity;
                    }
                @endphp
                <div class="area_div">
                    @if(!empty($purchase_line->sub_units_options))
                        <select name="purchases[{{$loop->index}}][sub_unit_id]" class="form-control input-sm sub_unit" id="elementTriggerChangeOnLoad">
                            @foreach($purchase_line->sub_units_options as $sub_units_key => $sub_units_value)
                                <option value="{{$sub_units_key}}"
                                        data-multiplier="{{$sub_units_value['multiplier']}}"
                                        data-width="{{ $sub_units_value['width'] ?? 0 }}" data-height="{{ $sub_units_value['height'] ?? 0 }}"
                                        @if($sub_units_key == $purchase_line->sub_unit_id) selected @endif>
                                    {{$sub_units_value['name']}}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <span class="unit">{{ $purchase_line->product->unit->short_name }}</span>
                    @endif
                </div>
            </td>

            <td style="width: 100px">
                @if($purchase_line->product->unit->type == 'area')
                    <span class="height_box">{{ @size_format($purchase_line->height) }}</span>
                @endif
                {!! Form::hidden('purchases[' . $row_count . '][height]', $purchase_line->height, ['class' => 'height']) !!}
            </td>

            <td style="width: 100px">
                @if(in_array($purchase_line->product->unit->type, ['area', 'meter']))
                    {!! Form::number('purchases[' . $row_count . '][width]', @size_format($purchase_line->width), ['class' => 'form-control input-sm width mousetrap text-center input_decimal', 'required']); !!}
                @else
                    {!! Form::hidden('purchases[' . $row_count . '][width]', 1, ['class' => 'width']) !!}
                @endif
            </td>

            <td style="width: 120px">
                {!! Form::text('purchases[' . $row_count . '][purchase_price_inc_tax]',
                @num_format($purchase_line->purchase_price_inc_tax), ['class' => 'form-control input-sm input_number purchase_price text-center', 'required']); !!}
            </td>

            <td style="width: 150px">
                <div class="input-group input-number">
                    @php
                        $multiplier = 1;
                        $allow_decimal = true;
                        if($purchase_line->product->sub_unit['allow_decimal'] != 1) {
                            $allow_decimal = false;
                        }
                    @endphp
                    <span class="input-group-btn"><button type="button" class="input-sm btn btn-default btn-flat quantity-down"><i class="fa fa-minus text-danger"></i></button></span>
                    <input type="text" data-min="1" required min="1"
                           class="input-sm form-control text-center purchase_quantity mousetrap input_quantity"
                           value="{{ $purchase_line->quantity_line }}" name="purchases[{{$row_count}}][quantity]"
                           data-rule-abs_digit="{{ $check_decimal }}"
                           data-msg-abs_digit="@lang('lang_v1.decimal_value_not_allowed')"
                           data-rule-required="true"
                           data-msg-required="@lang('validation.custom-messages.this_field_is_required')"
                    >
                    <span class="input-group-btn"><button type="button" class="input-sm btn btn-default btn-flat quantity-up"><i class="fa fa-plus text-success"></i></button></span>
                </div>
            </td>

            <input type="hidden" class="base_unit_cost" value="{{$purchase_line->variations->default_purchase_price}}">
            <input type="hidden" name="purchases[{{$loop->index}}][product_unit_id]" value="{{$purchase_line->product->unit->id}}" class="product_unit_id">
            <input type="hidden" class="base_unit_selling_price" value="{{$purchase_line->variations->sell_price_inc_tax}}">
            <td>
                <input type="hidden" class="form-control input-sm area_hidden input_number mousetrap" name="purchases[{{$row_count}}][area]" value="{{ ($purchase_line->product->unit->type == 'pcs' ? 0 : $purchase_line->quantity) }}">
                @if($purchase_line->product->unit->type == 'area')
                    <span class="area">{{ @size_format($purchase_line->quantity) }}</span> m<sup>2</sup>
                @endif
            </td>
            <td>
                <input type="hidden" class="form-control weight_hidden" name="weight" value="{{ $purchase_line->product->weight * $purchase_line->quantity }}">
                @if(in_array($purchase_line->product->unit->type, ['area', 'meter']))
                    <span class="weight">{{ @weight_format($purchase_line->product->weight * $purchase_line->quantity) }}</span> kg
                @endif
            </td>
            <td>
                <input type="hidden" class="form-control total_purchase_price_hidden" name="total_purchase_price" value="{{ $purchase_line->purchase_price_inc_tax * $purchase_line->quantity }}">
                <span class="total_purchase_price">{{ @num_format($purchase_line->purchase_price_inc_tax * $purchase_line->quantity) }}</span> đ
            </td>
            <td>
                {!! Form::select("purchases[". $row_count ."][warehouse_id]", $warehouses, $purchase_line->warehouse_id, ['class' => 'form-control input-sm warehouse_id', 'required', 'placeholder' => __('messages.please_select')]); !!}
            </td>
            <td>
                @if(in_array($purchase_line->product->unit->type, ['area', 'meter']))
                    {!! Form::checkbox("purchases[". $row_count ."][is_origin]", true, $purchase_line->is_origin, ['class' => 'is_origin']); !!}
                @endif
            </td>
            <td><i class="fa fa-times remove_purchase_entry_row text-danger" title="@lang('messages.delete')" style="cursor:pointer;"></i></td>
        </tr>
        <?php $row_count = $loop->index + 1 ; ?>
        @endforeach
        </tbody>
    </table>
</div>
<input type="hidden" id="row_count" value="{{ $row_count }}">
