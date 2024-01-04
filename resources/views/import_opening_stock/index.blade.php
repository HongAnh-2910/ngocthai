@extends('layouts.app')
@section('title', __('lang_v1.import_opening_stock'))

@section('content')
<br/>
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.import_opening_stock')</h1>
</section>

<!-- Main content -->
<section class="content">

@if (session('notification') || !empty($notification))
    <div class="row">
        <div class="col-sm-12">
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                @if(!empty($notification['msg']))
                    {{$notification['msg']}}
                @elseif(session('notification.msg'))
                    {{ session('notification.msg') }}
                @endif
              </div>
          </div>
      </div>
@endif
    <div class="row">
        <div class="col-sm-12">
            @component('components.widget', ['class' => 'box-primary'])
                {!! Form::open(['url' => action('ImportOpeningStockController@store'), 'method' => 'post', 'enctype' => 'multipart/form-data' ]) !!}
                    <div class="row">
                        <div class="col-sm-6">
                        <div class="col-sm-8">
                            <div class="form-group">
                                {!! Form::label('name', __( 'product.file_to_import' ) . ':') !!}
{{--                                @show_tooltip(__('lang_v1.tooltip_import_opening_stock'))--}}
                                {!! Form::file('products_csv', ['accept'=> '.xls, .xlsx, .csv', 'required' => 'required']); !!}
                              </div>
                        </div>
                        <div class="col-sm-4">
                        <br>
                            <button type="submit" class="btn btn-primary">@lang('messages.submit')</button>
                        </div>
                        </div>
                    </div>

                {!! Form::close() !!}
                <br><br>
                <div class="row">
                    <div class="col-sm-4">
                        <a href="{{ asset('files/import_opening_stock_csv_template_2.xlsx') }}" class="btn btn-success"><i class="fa fa-download"></i> @lang('lang_v1.download_template_file')</a>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.instructions')])
            <strong>@lang('lang_v1.instruction_line1')</strong><br>@lang('lang_v1.instruction_line2')
            <br><br>
            <table class="table table-striped">
                <tr>
                    <th>@lang('lang_v1.col_no')</th>
                    <th>@lang('lang_v1.col_name')</th>
                    <th>@lang('lang_v1.instruction')</th>
                </tr>
                <tr>
                    <td>1</td>
                    <td>@lang('product.sku') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                    <td>@lang('lang_v1.product_sku')</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>@lang('product.width') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                    <td>@lang('warehouse.width') <small class="text-muted">(@lang('lang_v1.width_note'))</small></td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>@lang('product.height') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                    <td>@lang('warehouse.height') <small class="text-muted">(@lang('lang_v1.height_note'))</small></td>
                </tr>
                <tr>
                    <td>4</td>
                    <td>@lang('lang_v1.quantity') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                    <td>@lang('warehouse.quantity')</td>
                </tr>
                <tr>
                    <td>5</td>
                    <td>@lang('warehouse.business_location') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                    <td>@lang('warehouse.business_location')</td>
                </tr>
                <tr>
                    <td>6</td>
                    <td>@lang('business.warehouse') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                    <td>@lang('warehouse.warehouse')</td>
                </tr>
                <tr>
                    <td>7</td>
                    <td>@lang('warehouse.is_origin') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                    <td>@lang('warehouse.is_origin_for_import')</td>
                </tr>
            </table>
        @endcomponent
        </div>
    </div>
</section>
<!-- /.content -->

@endsection
