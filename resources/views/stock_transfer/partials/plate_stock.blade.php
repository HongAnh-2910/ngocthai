<div class="modal fade" id="plate_stock_deliver_modal" tabindex="-1" role="dialog" aria-labelledby="plate_stock_deliver_modal_label">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">
                    @lang('stock_adjustment.select_plate')
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        @component('components.filters', ['title' => __('report.filters')])
                            {!! Form::open(['url' => action('ReportController@getStockReport'), 'method' => 'get', 'id' => 'plate_stock_deliver_filter_form' ]) !!}
                            {!! Form::hidden('width', '', ['id' => 'width']) !!}
                            {!! Form::hidden('height', '', ['id' => 'height']) !!}
                            <div class="col-md-4">
                                <div class="form-group">
                                    {!! Form::label('transfer_warehouse_id', __('lang_v1.transfer_warehouse') . ':') !!}
                                    {!! Form::select('transfer_warehouse_id', $warehouse, null, ['placeholder' => __('messages.all'), 'class' => 'form-control', 'style' => 'width:100%']); !!}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    {!! Form::label('category_id', __('category.category') . ':') !!}
                                    {!! Form::select('category', $categories, null, ['placeholder' => __('messages.all'), 'class' => 'form-control', 'style' => 'width:100%', 'id' => 'category_id']); !!}
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('variation_id', __('sale.product') . ':') !!}
                                    {!! Form::select('variation_id', $products, null, ['placeholder' => __('messages.all'), 'class' => 'form-control', 'style' => 'width:100%']); !!}
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('plate_height',  __('sale.height') . ':') !!}
                                    {!! Form::text('plate_height', null, ['class' => 'form-control', 'placeholder' => __('sale.height')]); !!}
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    {!! Form::label('plate_width',  __('sale.width') . ':') !!}
                                    {!! Form::text('plate_width', null, ['class' => 'form-control', 'placeholder' => __('sale.width')]); !!}
                                </div>
                            </div>
                            {{--<div class="col-sm-3">
                                <div class="form-group">
                                    {!! Form::label('quantity',  __('sale.plate_quantity') . ':') !!}
                                    {!! Form::text('quantity', null, ['class' => 'form-control', 'placeholder' => __('sale.plate_quantity')]); !!}
                                </div>
                            </div>--}}
                            {!! Form::close() !!}
                        @endcomponent
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        @component('components.widget', ['class' => 'box-primary'])
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="plate_stock_deliver_table">
                                    <thead>
                                    <tr>
                                        <th>@lang('product.sku')</th>
                                        <th>@lang('business.product')</th>
                                        <th>@lang('sale.height')</th>
                                        <th>@lang('sale.width')</th>
                                        <th>@lang('report.current_stock')</th>
                                        <th>@lang('purchase.warehouse')</th>
                                        <th>@lang('sale.is_origin')</th>
                                        <th>@lang('messages.view_detail')</th>
                                    </tr>
                                    </thead>
                                </table>
                            </div>
                        @endcomponent
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="select_plates">@lang('messages.select_plates')</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            </div>
        </div>
    </div>
</div>
