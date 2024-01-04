@extends('layouts.app')
@section('title', __('target.edit_target'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('target.edit_target')</h1>
</section>

<!-- Main content -->
<section class="content">

  <!-- Page level currency setting -->
  <input type="hidden" id="p_code" value="{{$currency_details->code}}">
  <input type="hidden" id="p_symbol" value="{{$currency_details->symbol}}">
  <input type="hidden" id="p_thousand" value="{{$currency_details->thousand_separator}}">
  <input type="hidden" id="p_decimal" value="{{$currency_details->decimal_separator}}">

  @include('layouts.partials.error')

  {!! Form::open(['url' =>  action('TargetController@update' , [$target->id] ), 'method' => 'PUT', 'id' => 'add_target_form', 'files' => true ]) !!}
  {{--{!! Form::hidden('start_date', $target->start_date, ['id' => 'start_date']); !!}
  {!! Form::hidden('end_date', $target->end_date, ['id' => 'end_date']); !!}--}}
  @component('components.widget', ['class' => 'box-primary'])
    <div class="row">
      <div class="col-md-3">
        <div class="form-group">
          {!! Form::label('start_date', __('target.start_date') . ':*') !!}
          <div class="input-group">
            <span class="input-group-addon">
              <i class="fa fa-calendar"></i>
            </span>
            {!! Form::text('start_date', $target->start_date, ['class' => 'form-control datepicker', 'readonly', 'required']); !!}
          </div>
        </div>
        {{--<div class="form-group">
          {!! Form::label('date_range', __('report.date_range') . ':') !!}
          {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
        </div>--}}
      </div>
      <div class="col-md-3">
        <div class="form-group">
          {!! Form::label('end_date', __('target.end_date') . ':*') !!}
          <div class="input-group">
            <span class="input-group-addon">
              <i class="fa fa-calendar"></i>
            </span>
            {!! Form::text('end_date', $target->end_date, ['class' => 'form-control datepicker', 'readonly', 'required']); !!}
          </div>
        </div>
        {{--<div class="form-group">
          {!! Form::label('date_range', __('report.date_range') . ':') !!}
          {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
        </div>--}}
      </div>
      <div class="col-sm-3">
        <div class="form-group">
          {!! Form::label('target_type', __('target.type') . ':') !!}
          {!! Form::select('type', $types, $target->type, ['class' => 'form-control', 'id' => 'target_type']); !!}
        </div>
      </div>
      <div class="col-sm-3" id="amount-box">
        <div class="form-group">
          {!! Form::label('amount', __('target.amount') . ':') !!}
          {!! Form::input('text', 'amount', number_format($target->amount), ['class' => 'form-control input_number']); !!}
        </div>
      </div>
      <div class="col-sm-3" id="profit-box">
        <div class="form-group">
          {!! Form::label('profit', __('target.profit') . ':') !!}
          {!! Form::input('text', 'profit', number_format($target->profit), ['class' => 'form-control input_number']); !!}
        </div>
      </div>
    </div>
  @endcomponent

  @component('components.widget', ['class' => 'box-primary', 'id' => 'category-box'])
    <div class="row">
      <div class="col-sm-12">
        <p class="text-center"><b>Mục tiêu theo danh mục sản phẩm</b></p>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-12">
        <table class="table bg-gray" id="add_category_table">
          <thead>
          <tr class="bg-green">
            <th>@lang('product.category')</th>
{{--            <th>@lang('product.sub_category')</th>--}}
            <th>@lang('target.product_quantity') (m<sup>2</sup>)</th>
            <th></th>
          </tr>
          </thead>
          <tbody>
          @if (!$target->target_category_lines->isEmpty())
            @foreach($target->target_category_lines as $key => $target_category_line)
              <tr>
                <td>
                  <div class="form-group">
                    {!! Form::select('category_ids['. $key .']',
                        $categories,
                        $target_category_line->category_id,
                        [
                            'placeholder' => __('messages.please_select'),
                            'class' => 'form-control category_row category_ids',
                            'required',
                            'id'    => 'category_id_' . $key,
                            'data-index' => $key
                        ]);
                    !!}
                  </div>
                </td>
{{--                <td>--}}
{{--                  {!! Form::select('sub_category_ids['. $key .']',--}}
{{--                      $target_category_line->sub_category_id ? \App\Category::where('parent_id', $target_category_line->category_id)->pluck('name', 'id')->toArray() : [],--}}
{{--                      $target_category_line->sub_category_id,--}}
{{--                      [--}}
{{--                          'placeholder' => __('messages.please_select'),--}}
{{--                          'class' => 'form-control sub_category_ids',--}}
{{--                          'id'    => 'sub_category_id_' . $key,--}}
{{--                          'data-index' => $key--}}
{{--                      ]);--}}
{{--                  !!}--}}
{{--                </td>--}}
                <td>
                  {!! Form::input('text', 'quantities['. $key .']',
                      @size_format($target_category_line->quantity),
                      [
                          'class' => 'form-control',
                      ]);
                  !!}
                </td>
                <td>
                  <i class="fa fa-times remove_row text-danger" title="Remove" style="cursor:pointer;"></i>
                </td>
              </tr>
            @endforeach
          @else
            <tr>
              <td>
                <div class="form-group">
                  {!! Form::select('category_ids[0]',
                      $categories,
                      null,
                      [
                          'placeholder' => __('messages.please_select'),
                          'class' => 'form-control category_ids',
                          'required',
                          'id' => 'category_id_0',
                          'data-index' => 0
                      ]);
                  !!}
                </div>
              </td>
{{--              <td>--}}
{{--                <div class="form-group">--}}
{{--                  {!! Form::select('sub_category_ids[0]',--}}
{{--                      [],--}}
{{--                      null,--}}
{{--                      [--}}
{{--                          'placeholder' => __('messages.please_select'),--}}
{{--                          'class' => 'form-control sub_category_ids',--}}
{{--                          'id' => 'sub_category_id_0',--}}
{{--                          'data-index' => 0--}}
{{--                      ]);--}}
{{--                  !!}--}}
{{--                </div>--}}
{{--              </td>--}}
              <td>
                {!! Form::number('quantities[0]',
                    null,
                    [
                        'class' => 'form-control',
                    ]);
                !!}
              </td>
              <td>
                <i class="fa fa-plus-circle add_row text-danger" title="Add" style="cursor:pointer;"></i>
              </td>
            </tr>
          @endif
          </tbody>
        </table>
      </div>
    </div>
    <input type="hidden" id="row_count_category" value="1">
  @endcomponent

  @component('components.widget', ['class' => 'box-primary', 'id' => 'product-box'])
    <div class="row">
      <div class="col-sm-8 col-sm-offset-2">
        <div class="form-group">
          <div class="input-group">
                  <span class="input-group-addon">
                    <i class="fa fa-search"></i>
                  </span>
            {!! Form::text('search_product', null, ['class' => 'form-control mousetrap', 'id' => 'search_product', 'placeholder' => __('lang_v1.search_product_placeholder'), 'autofocus']); !!}
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-sm-12">
        @include('target.partials.edit_purchase_entry_row')

        <hr/>
        <div class="pull-right col-md-5">
          <table class="pull-right col-md-12">
            <tr class="hide">
              <th class="col-md-7 text-right">@lang( 'target.total_before_tax' ):</th>
              <td class="col-md-5 text-left">
                <span id="total_st_before_tax" class="display_currency"></span>
                <input type="hidden" id="st_before_tax_input" value=0>
              </td>
            </tr>
            {{--<tr>
              <th class="col-md-7 text-right">@lang( 'target.net_total_amount' ):</th>
              <td class="col-md-5 text-left">
                <span id="total_subtotal" class="display_currency">{{$target->total_before_tax/$target->exchange_rate}}</span>
                <input type="hidden" id="total_subtotal_input" value="{{$target->total_before_tax/$target->exchange_rate}}" name="total_before_tax">
              </td>
            </tr>--}}
          </table>
        </div>

      </div>
    </div>
  @endcomponent

  @component('components.widget', ['class' => 'box-primary'])
    <div class="row">
      <div class="col-sm-12">
        <table class="table">
          <tr>
            <td colspan="4">
              <div class="form-group">
                {!! Form::label('note',__('target.note')) !!}
                {!! Form::textarea('note', $target->note, ['class' => 'form-control', 'rows' => 3]); !!}
              </div>
            </td>
          </tr>

        </table>
      </div>
    </div>

    <div class="row">
      <div class="col-sm-12">
        <button type="button" id="submit_target_form" class="btn btn-primary pull-right btn-flat">@lang('messages.update')</button>
      </div>
    </div>
  @endcomponent
{!! Form::close() !!}
</section>
<!-- /.content -->
<!-- quick product modal -->
<div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>
@endsection

@section('javascript')
  <script src="{{ asset('js/target.js?v=' . $asset_v) }}"></script>
  <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
@endsection
