<div class="row">
    <div class="col-xs-12 col-sm-10 col-sm-offset-1">
        <div class="table-responsive">
            <table class="table table-condensed bg-gray">
                <tr>
                    <th>@lang('sale.location')</th>
                    <th>@lang('sale.warehouse')</th>
                    <th>@lang('report.current_stock')</th>
                    <th>@lang('report.expect_stock')</th>
                    <th>@lang('messages.action')</th>
                </tr>
                @foreach( $plate_stocks as $plate_stock )
                    <tr>
                        <td>{{ $plate_stock->location }}</td>
                        <td>{{$plate_stock->warehouse}}</td>
                        <td>{{number_format($plate_stock->stock)}} {{ $plate_stock->unit_name }}</td>
                        <td {{ $plate_stock->expect_stock != $plate_stock->stock ? 'style=color:orangered;' : '' }}>{{number_format($plate_stock->expect_stock)}} {{ $plate_stock->unit_name }}</td>
                        <td>
                            <button type="button" class="btn btn-xs btn-primary view_history" data-plate_stock_id="{{ $plate_stock->id }}"><i class="fa fa-history"></i> @lang('messages.view_history')</button>
                            @if($plate_stock->unit_type == 'area')
                                <a href="{{ action('SellPosController@getReverseSize', [$plate_stock->id]) }}" class="btn btn-xs btn-success reverse_size_button"><i class="fa fa-undo"></i> @lang('sale.reverse_size')</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
</div>
