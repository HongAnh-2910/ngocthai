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
                <th>@lang( 'product.product_name' )</th>
                <th>@lang( 'target.purchase_quantity' )</th>
{{--                <th>@lang( 'target.unit_selling_price') <small>(@lang('product.inc_of_tax'))</small></th>--}}
                <th>@lang( 'target.unit' )</th>
                <th>
                    <i class="fa fa-trash" aria-hidden="true"></i>
                </th>
              </tr>
        </thead>
        <tbody>
    <?php $row_count = 0; ?>
    @foreach($target->target_sale_lines as $target_sale_line)
        <tr>
            <td><span class="sr_number"></span></td>
            <td>
                {{ $target_sale_line->product->name }} ({{$target_sale_line->product->sku}})
                @if( $target_sale_line->product->type == 'variable')
                    <br/>(<b>{{ $target_sale_line->variation->product_variation->name}}</b> : {{ $target_sale_line->variation->name}})
                @endif
            </td>
            <td>
                @php
                    $check_decimal = 'false';
                    if($target_sale_line->product->unit->allow_decimal == 0){
                        $check_decimal = 'true';
                    }
                @endphp
                {!! Form::hidden('purchases[' . $loop->index . '][product_id]', $target_sale_line->product_id ); !!}
                {!! Form::hidden('purchases[' . $loop->index . '][variation_id]', $target_sale_line->variation_id ); !!}
                {!! Form::text('purchases[' . $loop->index . '][quantity]',
                $target_sale_line->product->unit->type == \App\Unit::PCS ? @number_format($target_sale_line->quantity) : @size_format($target_sale_line->quantity),
                ['class' => 'form-control input-sm purchase_quantity input_number mousetrap', 'required', 'data-rule-abs_digit' => $check_decimal, 'data-msg-abs_digit' => __('lang_v1.decimal_value_not_allowed')]); !!}
{{--                {{ $target_sale_line->product->unit->short_name }}--}}
                {{--<input type="hidden" class="base_unit_selling_price" value="{{$target_sale_line->variations->sell_price_inc_tax}}">--}}
            </td>
{{--            <td>--}}
{{--                {{$target_sale_line->variation->sell_price_inc_tax}}--}}
{{--            </td>--}}
            <td>
                {{ $target_sale_line->product->unit->type == \App\Unit::PCS ? $target_sale_line->product->unit->actual_name : "m2"}}
            </td>
            <td><i class="fa fa-times remove_purchase_entry_row text-danger" title="Remove" style="cursor:pointer;"></i></td>
        </tr>
        <?php $row_count = $loop->index + 1 ; ?>
    @endforeach
        </tbody>
    </table>
</div>
<input type="hidden" id="row_count" value="{{ $row_count }}">
