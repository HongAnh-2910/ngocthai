<tr>
    <td>
        <div class="form-group">
            {!! Form::select('category_ids['. $value .']',
                $categories,
                null,
                [
                    'placeholder' => __('messages.please_select'),
                    'class' => 'form-control category_row category_ids',
                    'required',
                    'id'    => 'category_id_' . $value,
                    'data-index' => $value
                ]);
            !!}
        </div>
    </td>
{{--    <td>--}}
{{--        {!! Form::select('sub_category_ids['. $value .']',--}}
{{--            [],--}}
{{--            null,--}}
{{--            [--}}
{{--                'placeholder' => __('messages.please_select'),--}}
{{--                'class' => 'form-control select2 sub_category_ids',--}}
{{--                'id'    => 'sub_category_id_' . $value,--}}
{{--                'data-index' => $value--}}
{{--            ]);--}}
{{--        !!}--}}
{{--    </td>--}}
    <td>
        {!! Form::number('quantities['. $value .']',
            null,
            [
                'class' => 'form-control',
            ]);
        !!}
    </td>
    <td>
        <i class="fa fa-times remove_row text-danger" title="Remove" style="cursor:pointer;"></i>
    </td>
</tr>
