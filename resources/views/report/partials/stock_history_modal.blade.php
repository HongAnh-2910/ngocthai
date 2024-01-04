<div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'report.stock_history' )</h4>
        </div>

        <div class="modal-body">
            @if(empty($purchase_transactions) && empty($import_stocks) && empty($sell_transactions) && empty($transfer_transactions) && empty($adjustment_transactions) && empty($return_transactions))
                <div class="row">
                    <div class="col-md-12">
                        <p>@lang('report.transaction_not_found')</p>
                    </div>
                </div>
            @else
                @if(!empty($purchase_transactions))
                <div class="row">
                    <div class="col-md-12">
                        @component('components.widget', ['class' => 'box-primary', 'title' => __('report.purchase_history')])
                            <div class="table-responsive">
                                <table class="table table-condensed table-th-green text-center table-bordered table-striped table-responsive">
                                    <thead>
                                    <tr>
                                        <th>@lang('messages.date')</th>
                                        <th>@lang('purchase.ref_no')</th>
                                        <th>@lang('purchase.supplier')</th>
                                        <th>@lang('sale.product')</th>
                                        <th>@lang('sale.height')</th>
                                        <th>@lang('sale.width')</th>
                                        <th>@lang('lang_v1.quantity')</th>
                                        <th>@lang('messages.action')</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($purchase_transactions as $purchase_transaction)
                                            <tr>
                                                <td>{{ @format_datetime($purchase_transaction['transaction_date']) }}</td>
                                                <td>{{ $purchase_transaction['ref_no'] }}</td>
                                                <td>{{ $purchase_transaction['contact_name'] }}</td>
                                                <td>{{ $plate_stock->product->name . ' - ' . $plate_stock->variation->sub_sku }}</td>
                                                <td>
                                                    @if($plate_stock->product->unit->type == 'area')
                                                        {{ @size_format($purchase_transaction['height']) }}m
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(in_array($plate_stock->product->unit->type, ['area', 'meter']))
                                                        {{ @size_format($purchase_transaction['width']) }}m
                                                    @endif
                                                </td>
                                                <td>{{ $purchase_transaction['quantity_line'] }} {{ in_array($plate_stock->product->unit->type, ['area', 'meter']) ? __('unit.roll') : $plate_stock->product->unit->actual_name }}</td>
                                                <td>
                                                    <a href="#" data-href="{{ action('PurchaseController@show', [$purchase_transaction['id']]) }}" class="btn btn-xs btn-success btn-modal" data-container=".view_modal"><i class="fa fa-eye"></i> @lang('messages.view_detail')</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endcomponent
                    </div>
                </div>
                @endif

                @if(!empty($import_stocks))
                    <div class="row">
                        <div class="col-md-12">
                            @component('components.widget', ['class' => 'box-primary', 'title' => __('report.import_stock_history')])
                                <div class="table-responsive">
                                    <table class="table table-condensed table-th-green text-center table-bordered table-striped table-responsive">
                                        <thead>
                                        <tr>
                                            <th>@lang('messages.date')</th>
                                            <th>@lang('sale.product')</th>
                                            <th>@lang('sale.height')</th>
                                            <th>@lang('sale.width')</th>
                                            <th>@lang('lang_v1.quantity')</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($import_stocks as $import_stock)
                                            <tr>
                                                <td>{{ @format_datetime($import_stock['transaction_date']) }}</td>
                                                <td>{{ $plate_stock->product->name . ' - ' . $plate_stock->variation->sub_sku }}</td>
                                                <td>
                                                    @if($plate_stock->product->unit->type == 'area')
                                                        {{ @size_format($import_stock['height']) }}m
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(in_array($plate_stock->product->unit->type, ['area', 'meter']))
                                                        {{ @size_format($import_stock['width']) }}m
                                                    @endif
                                                </td>
                                                <td>{{ $import_stock['quantity_line'] }} {{ in_array($plate_stock->product->unit->type, ['area', 'meter']) ? __('unit.roll') : $plate_stock->product->unit->actual_name }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endcomponent
                        </div>
                    </div>
                @endif

                @if(!empty($sell_transactions))
                <div class="row">
                    <div class="col-md-12">
                        @component('components.widget', ['class' => 'box-primary', 'title' => __('report.sell_history')])
                            <div class="table-responsive">
                                <table class="table table-condensed table-th-green text-center table-bordered table-striped table-responsive">
                                    <thead>
                                    <tr>
                                        <th>@lang('messages.date')</th>
                                        <th>@lang('sale.invoice_no')</th>
                                        <th>@lang('sale.customer_name')</th>
                                        <th>@lang('sale.product')</th>
                                        <th>@lang('sale.height')</th>
                                        <th>@lang('sale.width')</th>
                                        <th>@lang('sale.width_after')</th>
                                        <th>@lang('sale.plate_quantity_after')</th>
                                        <th>@lang('sale.area_remaining')</th>
                                        <th>@lang('sale.warehouse')</th>
                                        <th>@lang('sale.is_origin')</th>
                                        <th>@lang('messages.action')</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sell_transactions as $sell_transaction)
                                            <tr>
                                                <td>{{ @format_datetime($sell_transaction['transaction_date']) }}</td>
                                                <td>{{ $sell_transaction['invoice_no'] }}</td>
                                                <td>{{ $sell_transaction['contact_name'] }}</td>
                                                <td>{{ $plate_stock->product->name . ' - ' . $plate_stock->variation->sub_sku }}</td>
                                                <td>
                                                    @if($plate_stock->product->unit->type == 'area')
                                                        {{ @size_format($sell_transaction['selected_height']) }}m
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(in_array($plate_stock->product->unit->type, ['area', 'meter']))
                                                        {{ @size_format($sell_transaction['selected_width']) }}m
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(in_array($plate_stock->product->unit->type, ['area', 'meter']))
                                                        {{ @size_format($sell_transaction['deliver_width']) }}m
                                                    @endif
                                                </td>
                                                <td>{{ $sell_transaction['deliver_quantity'] }} {{ in_array($plate_stock->product->unit->type, ['area', 'meter']) ? __('unit.plate') : $plate_stock->product->unit->actual_name }}</td>
                                                <td>
                                                    @if(in_array($plate_stock->product->unit->type, ['area', 'meter']))
                                                        {{ @size_format($sell_transaction['remaining_width']) }}m
                                                    @endif
                                                </td>
                                                <td>{{ $plate_stock->warehouse->name }}</td>
                                                <td>{{ $sell_transaction['is_origin'] ? __('sale.origin_plate') : '' }}</td>
                                                <td>
                                                    <a href="#" data-href="{{ action('SellController@show', [$sell_transaction['id']]) }}" class="btn btn-xs btn-success btn-modal" data-container=".view_modal"><i class="fa fa-eye"></i> @lang('messages.view_detail')</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endcomponent
                    </div>
                </div>
                @endif

                @if(!empty($return_transactions))
                    <div class="row">
                        <div class="col-md-12">
                            @component('components.widget', ['class' => 'box-primary', 'title' => __('report.sell_return_history')])
                                <div class="table-responsive">
                                    <table class="table table-condensed table-th-green text-center table-bordered table-striped table-responsive">
                                        <thead>
                                        <tr>
                                            <th>@lang('messages.date')</th>
                                            <th>@lang('sale.invoice_no')</th>
                                            <th>@lang('sale.customer_name')</th>
                                            <th>@lang('sale.product')</th>
                                            <th>@lang('sale.height_order')</th>
                                            <th>@lang('sale.width_order')</th>
                                            <th>@lang('sale.quantity_order')</th>
                                            <th>@lang('sale.width_return')</th>
                                            <th>@lang('sale.quantity_return')</th>
                                            <th>@lang('sale.warehouse_return')</th>
                                            <th>@lang('messages.action')</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($return_transactions as $return_transaction)
                                            <tr>
                                                <td>{{ @format_datetime($return_transaction['transaction_date']) }}</td>
                                                <td>{{ $return_transaction['invoice_no'] }}</td>
                                                <td>{{ $return_transaction['contact_name'] }}</td>
                                                <td>
                                                    @php
                                                        $product_name = $plate_stock->product->name . ' - ' . $plate_stock->variation->sub_sku ;
                                                    @endphp
                                                    {!! $product_name !!}
                                                </td>
                                                <td>{{ $return_transaction['height'] }}m</td>
                                                <td>{{ $return_transaction['width_order'] }}m</td>
                                                <td>{{ $return_transaction['quantity_order'] }}</td>
                                                <td>{{ $return_transaction['width'] }}m</td>
                                                <td>{{ $return_transaction['quantity'] }}</td>
                                                <td>{{ $return_transaction['warehouse'] }}</td>
                                                <td>
                                                    <a href="#" data-href="{{ action('SellReturnController@show', [$return_transaction['return_parent_id']]) }}" class="btn btn-xs btn-success btn-modal" data-container=".view_modal"><i class="fa fa-eye"></i> @lang('messages.view_detail')</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endcomponent
                        </div>
                    </div>
                @endif

                @if(!empty($transfer_transactions))
                    <div class="row">
                        <div class="col-md-12">
                            @component('components.widget', ['class' => 'box-primary', 'title' => __('report.transfer_history')])
                                <div class="table-responsive">
                                    <table class="table table-condensed table-th-green text-center table-bordered table-striped table-responsive">
                                        <thead>
                                        <tr>
                                            <th>@lang('messages.date')</th>
                                            <th>@lang('purchase.ref_no')</th>
                                            <th>@lang('sale.product')</th>
                                            <th>@lang('sale.height')</th>
                                            <th>@lang('sale.width')</th>
                                            <th>@lang('sale.plate_quantity')</th>
                                            <th>@lang( 'purchase.warehouse_from' )</th>
                                            <th>@lang( 'purchase.warehouse_to' )</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($transfer_transactions as $transfer_transaction)
                                            <tr>
                                                <td>{{ @format_datetime($transfer_transaction['transaction_date']) }}</td>
                                                <td>{{ $transfer_transaction['ref_no'] }}</td>
                                                <td>
                                                    @php
                                                        $product_name = $plate_stock->product->name . ' - ' . $plate_stock->variation->sub_sku ;
                                                    @endphp
                                                    {!! $product_name !!}
                                                </td>
                                                <td>{{ $plate_stock->height }}m</td>
                                                <td>{{ $plate_stock->width }}m</td>
                                                <td>{{ @num_format($transfer_transaction['quantity_line']) }}</td>
                                                <td>{{ $transfer_transaction['warehouse_transfer_from'] }}</td>
                                                <td>{{ $plate_stock->warehouse->name }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endcomponent
                        </div>
                    </div>
                @endif

                @if(!empty($adjustment_transactions))
                    <div class="row">
                        <div class="col-md-12">
                            @component('components.widget', ['class' => 'box-primary', 'title' => __('report.adjustment_history')])
                                <div class="table-responsive">
                                    <table class="table table-condensed table-th-green text-center table-bordered table-striped table-responsive">
                                        <thead>
                                        <tr>
                                            <th>@lang('messages.date')</th>
                                            <th>@lang('purchase.ref_no')</th>
                                            <th>@lang('sale.product')</th>
                                            <th>@lang( 'stock_adjustment.before_adjustment_height' )</th>
                                            <th>@lang( 'stock_adjustment.before_adjustment_width' )</th>
                                            <th>@lang( 'stock_adjustment.after_adjustment_height' )</th>
                                            <th>@lang( 'stock_adjustment.after_adjustment_width' )</th>
                                            <th>@lang( 'purchase.purchase_quantity' )</th>
                                            <th>@lang( 'purchase.warehouse' )</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($adjustment_transactions as $adjustment_transaction)
                                            <tr>
                                                <td>{{ @format_datetime($adjustment_transaction['transaction_date']) }}</td>
                                                <td>{{ $adjustment_transaction['ref_no'] }}</td>
                                                <td>
                                                    @php
                                                        $product_name = $plate_stock->product->name . ' - ' . $plate_stock->variation->sub_sku ;
                                                    @endphp
                                                    {!! $product_name !!}
                                                </td>
                                                <td>{{ $adjustment_transaction['before_height'] }}m</td>
                                                <td>{{ $adjustment_transaction['before_width'] }}m</td>
                                                <td>{{ $plate_stock->height }}m</td>
                                                <td>{{ $plate_stock->width }}m</td>
                                                <td>{{ @num_format($adjustment_transaction['quantity_line']) }}</td>
                                                <td>{{ $plate_stock->warehouse->name }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endcomponent
                        </div>
                    </div>
                @endif
            @endif
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->


<script type="text/javascript">
    $(document).ready(function() {

    });
</script>