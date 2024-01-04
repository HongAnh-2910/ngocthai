@if(!session('business.enable_price_tax'))
    @php
        $default = 0;
        $class = 'hide';
    @endphp
@else
    @php
        $default = null;
        $class = '';
    @endphp
@endif

<div class="table-responsive">
    <table class="table table-bordered add-product-price-table table-condensed {{$class}}">
        <tr>
            <th class="price_by_plate_label">@lang('product.selling_price_by_meter_square')</th>
            <th class="price_by_plate" style="display: block">@lang('product.selling_price_by_plate')</th>
            {{--@if(empty($quick_add))
                <th>@lang('lang_v1.product_image')</th>
            @endif--}}
        </tr>
        <tr>
            <td>
                {!! Form::label('single_dsp', __('product.default_selling_price') .':*') !!}
                {!! Form::text('single_dsp', $default, ['class' => 'form-control input-sm dsp input_number', 'id' => 'single_dsp', 'required']); !!}
                {!! Form::text('single_dsp_inc_tax', $default, ['class' => 'form-control input-sm hide input_number', 'id' => 'single_dsp_inc_tax', 'required']); !!}

                @if($price_groups->count())
                    @foreach($price_groups as $price_group)
                        <br>
                        {!! Form::label('single_price_groups_'. $price_group->id, $price_group->name .':') !!}
                        {!! Form::text('single_price_groups['. $price_group->id .']', null, ['class' => 'form-control input-sm input_number', 'id' => 'single_price_groups_'. $price_group->id]); !!}
                    @endforeach
                @endif
            </td>
            <td class="price_by_plate" style="display: block">
                {!! Form::label('default_sell_price_by_plate', __('product.default_selling_price') .':*') !!}
                {!! Form::text('default_sell_price_by_plate', $default, ['class' => 'form-control input-sm input_number default_sell_price_by_plate', 'id' => 'default_sell_price_by_plate', 'required']); !!}

                @if($price_groups->count())
                    @foreach($price_groups as $price_group)
                        <br>
                        {!! Form::label('single_price_groups_by_plate_'. $price_group->id, $price_group->name .':') !!}
                        {!! Form::text('single_price_groups_by_plate['. $price_group->id .']', null, ['class' => 'form-control input-sm input_number', 'id' => 'single_price_groups_'. $price_group->id]); !!}
                    @endforeach
                @endif
            </td>
            {{--@if(empty($quick_add))
                <td>
                    <div class="form-group">
                        {!! Form::label('variation_images', __('lang_v1.product_image') . ':') !!}
                        {!! Form::file('variation_images[]', ['class' => 'variation_images', 'accept' => 'image/*', 'multiple']); !!}
                        <small><p class="help-block">@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)]) <br> @lang('lang_v1.aspect_ratio_should_be_1_1')</p></small>
                    </div>
                </td>
            @endif--}}
        </tr>
    </table>
</div>
