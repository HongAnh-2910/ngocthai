@extends('layouts.app')
@section('title', __('product.import_products'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('product.import_products')
    </h1>
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
                {!! Form::open(['url' => action('ImportProductsController@store'), 'method' => 'post', 'enctype' => 'multipart/form-data' ]) !!}
                    <div class="row">
                        <div class="col-sm-6">
                        <div class="col-sm-8">
                            <div class="form-group">
                                {!! Form::label('name', __( 'product.file_to_import' ) . ':') !!}
                                {!! Form::file('products_csv', ['accept'=> '.xls', 'required' => 'required']); !!}
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
                        <a href="{{ asset('files/import_products_csv_template (1).xls') }}" class="btn btn-success"><i class="fa fa-download"></i> @lang('lang_v1.download_template_file')</a>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.instructions')])
                <strong>@lang('lang_v1.instruction_line1')</strong><br>
                    @lang('lang_v1.instruction_line2')
                    <br><br>
                <table class="table table-striped">
                    <tr>
                        <th>@lang('lang_v1.col_no')</th>
                        <th>@lang('lang_v1.col_name')</th>
                        <th>@lang('lang_v1.instruction')</th>
                    </tr>
                    <tr>
                        <td>1</td>
                        <td>@lang('product.product_name') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>@lang('lang_v1.name_ins')</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>@lang('product.base_unit') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>Chỉ được nhập 1 đơn vị</td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>@lang('product.brand') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.brand_ins') <br></td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td>@lang('product.sku') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.sku_ins')</td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td>@lang('product.category') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('lang_v1.category_ins') <br></td>
                    </tr>
                    <tr>
                        <td>6</td>
                        <td>@lang('product.barcode_type') <small class="text-muted">(@lang('lang_v1.optional'), @lang('lang_v1.default'): C128)</small></td>
                        <td>@lang('lang_v1.barcode_type_ins') <br>
                            <strong>@lang('lang_v1.barcode_type_ins2'): C128, C39, EAN-13, EAN-8, UPC-A, UPC-E, ITF-14</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>7</td>
                        <td>@lang('product.manage_stock') <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td>@lang('lang_v1.manage_stock_ins')<br>
                            <strong>1 = @lang('messages.yes')<br>
                                0 = @lang('messages.no')</strong>
                        </td>
                    </tr>
                    <tr>
                        <td>8</td>
                        <td>@lang('product.alert_quantity') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>@lang('product.alert_quantity')</td>
                    </tr>
                    <tr>
                        <td>9</td>
                        <td>@lang('lang_v1.thickness') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>10</td>
                        <td>@lang('lang_v1.m_to_kg') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                    </tr>
                    <tr>
                        <td>11</td>
                        <td>@lang('lang_v1.product_description') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td></td>
                        <td></td>
                    </tr>

                    <tr>
                        <td>12</td>
                        <td>@lang('lang_v1.not_for_selling') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td><strong>1 = @lang('messages.yes')<br>
                                0 = @lang('messages.no')</strong><br>
                        </td>
                    </tr>


                    <tr>
                        <td>13</td>
                        <td> Giá bán mặc định (mét vuông / mét dài / cái / dịch vụ) <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td> Giá bán mặc định theo mét vuông / mét dài / cái / dịch vụ</td>
                    </tr>
                    <tr>
                        <td>14</td>
                        <td>Giá bán theo nhóm khách hàng <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>Theo thứ tự từ trên xuống dưới của phầm mềm. Giữa các nhóm giá bán phân cách nhau bởi dấu "|", không điền sẽ mặc định các nhóm giá bán = 0
                            <br>
                            VD: 2000|3000|...
                        </td>
                    </tr>
                    <tr>
                        <td>15</td>
                        <td> Giá bán mặc định theo tấm <small class="text-muted">(@lang('lang_v1.required'))</small></td>
                        <td> Giá bán mặc định theo tấm nguyên</td>
                    </tr>
                    <tr>
                        <td>16</td>
                        <td>Giá bán theo nhóm khách hàng <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>Theo thứ tự từ trên xuống dưới của phầm mềm. Giữa các nhóm giá bán phân cách nhau bởi dấu "|", không điền sẽ mặc định các nhóm giá bán = 0
                            <br>
                            VD: 2000|3000|...
                        </td>
                    </tr>
                    <tr>
                        <td>17</td>
                        <td>@lang('lang_v1.position') <small class="text-muted">(@lang('lang_v1.optional'))</small></td>
                        <td>{!! __('lang_v1.position_help_text') !!}</td>
                    </tr>
                </table>
            @endcomponent
        </div>
    </div>
</section>
<!-- /.content -->
@endsection
