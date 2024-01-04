<div class="modal fade" id="plate_stock_deliver_modal" tabindex="-1" role="dialog" aria-labelledby="plate_stock_deliver_modal_label">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">
                    @lang('messages.select_product')
                </h4>
            </div>
            <div class="modal-body">
                <div class="row" id="plate_stock_filter_box">
                    <div class="col-md-12">
                        @component('components.filters', ['title' => __('report.filters')])
                            {!! Form::open(['url' => action('ReportController@getStockReport'), 'method' => 'get', 'id' => 'plate_stock_deliver_filter_form' ]) !!}
                            {!! Form::hidden('order_width', '', ['id' => 'order_width']) !!}
                            {!! Form::hidden('order_height', '', ['id' => 'order_height']) !!}
                            {!! Form::hidden('order_quantity', '', ['id' => 'order_quantity']) !!}
                            <div class="col-md-3" id="category_box">
                                <div class="form-group">
                                    {!! Form::label('category_id', __('category.category') . ':') !!}
                                    {!! Form::select('category', $categories, null, ['placeholder' => __('messages.all'), 'class' => 'form-control', 'style' => 'width:100%', 'id' => 'filter_category_id']); !!}
                                </div>
                            </div>
                            <div class="col-sm-3" id="variation_box">
                                <div class="form-group">
                                    {!! Form::label('variation_id', __('sale.product') . ':') !!}
                                    {!! Form::select('variation_id', $products, null, ['placeholder' => __('messages.all'), 'class' => 'form-control', 'id' => 'filter_variation_id', 'style' => 'width:100%']); !!}
                                </div>
                            </div>
                            <div class="col-sm-3" id="plate_height_box">
                                <div class="form-group">
                                    {!! Form::label('plate_height',  __('sale.height') . ':') !!}
                                    {!! Form::text('plate_height', null, ['class' => 'form-control', 'placeholder' => __('sale.height'), 'id' => 'filter_plate_height']); !!}
                                </div>
                            </div>
                            <div class="col-sm-3"id="plate_width_box">
                                <div class="form-group">
                                    {!! Form::label('plate_width',  __('sale.width') . ':') !!}
                                    {!! Form::text('plate_width', null, ['class' => 'form-control', 'placeholder' => __('sale.width'), 'id' => 'filter_plate_width']); !!}
                                </div>
                            </div>
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
                                        <th>@lang('report.expect_stock')</th>
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
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            </div>
        </div>
    </div>
</div>
