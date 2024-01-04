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

<div class="col-sm-12"><br>
    <div class="table-responsive">
        <table class="table table-bordered add-product-price-table table-condensed {{$class}}">
            <tr>
                <th class="price_by_plate_label">{{ in_array($product_type, ['area', 'meter']) ? __('product.selling_price_by_meter_square') : __('business.sell_price_tax') }}</th>
                <th class="price_by_plate" {{ !in_array($product_type, ['area', 'meter']) ? 'style=display:none' : '' }}>@lang('product.selling_price_by_plate')</th>
                {{--<th>@lang('lang_v1.product_image')</th>--}}
            </tr>
            @foreach($product_deatails->variations as $variation )
                @if($loop->first)
                    <input type="hidden" name="single_variation_id" value="{{$variation->id}}">
                    <tr>
                        <td>
                            {!! Form::label('single_dsp_inc_tax', __('product.default_selling_price') .':') !!}
                            {!! Form::text('single_dsp_inc_tax', @number_format($variation->sell_price_inc_tax), ['class' => 'form-control input-sm input_number', 'placeholder' => __('product.inc_of_tax'), 'id' => 'single_dsp_inc_tax', 'required']); !!}

                            @foreach($price_groups as $price_group)
                                <br>
                                {!! Form::label('single_price_groups_'. $price_group->id, $price_group->name .':') !!}
                                {!! Form::text('single_price_groups['. $price_group->id .']', @number_format($price_group->price_inc_tax), ['class' => 'form-control input-sm input_number', 'id' => 'single_price_groups_'. $price_group->id]); !!}
                            @endforeach
                        </td>
                        <td  class="price_by_plate" {{ !in_array($product_type, ['area', 'meter']) ? 'style=display:none' : '' }}{{--style="display: {{ $product_type == 'pcs' ? 'none' : 'block' }}"--}}>
                            {!! Form::label('default_sell_price_by_plate', __('product.default_selling_price') .':') !!}
                            {!! Form::text('default_sell_price_by_plate', @number_format($variation->default_sell_price_by_plate), ['class' => 'form-control input-sm input_number sell_by_plate_format', 'placeholder' => __('product.inc_of_tax'), 'id' => 'single_dsp_inc_tax', 'required']); !!}

                            @foreach($price_groups as $price_group)
                                <br>
                                {!! Form::label('single_price_groups_by_plate_'. $price_group->id, $price_group->name .':') !!}
                                {!! Form::text('single_price_groups_by_plate['. $price_group->id .']', @number_format($price_group->price_by_plate), ['class' => 'form-control input-sm input_number sell_by_plate_format', 'id' => 'single_price_groups_by_plate_'. $price_group->id]); !!}
                            @endforeach
                        </td>
                        {{--<td>
                            @php
                                $action = !empty($action) ? $action : '';
                            @endphp
                            @if($action !== 'duplicate')
                                @foreach($variation->media as $media)
                                    <div class="img-thumbnail">
                                        <span class="badge bg-red delete-media" data-href="{{ action('ProductController@deleteMedia', ['media_id' => $media->id])}}"><i class="fa fa-close"></i></span>
                                        {!! $media->thumbnail() !!}
                                    </div>
                                @endforeach
                            @endif
                            <div class="form-group">
                                {!! Form::label('variation_images', __('lang_v1.product_image') . ':') !!}
                                {!! Form::file('variation_images[]', ['class' => 'variation_images', 'accept' => 'image/*', 'multiple']); !!}
                                <small><p class="help-block">@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)]) <br> @lang('lang_v1.aspect_ratio_should_be_1_1')</p></small>
                            </div>
                        </td>--}}
                    </tr>
                @endif
            @endforeach
        </table>
    </div>
</div>
