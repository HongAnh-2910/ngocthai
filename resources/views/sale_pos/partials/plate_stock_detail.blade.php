<div class="row">
    <div class="col-xs-12 col-sm-10 col-sm-offset-1">
        <div class="table-responsive">
            <table class="table table-condensed bg-gray">
                <tr>
                    <th>@lang('sale.location')</th>
                    <th>@lang('sale.warehouse')</th>
                    <th>@lang('report.current_stock')</th>
                </tr>
                @foreach( $plate_stocks as $plate_stock )
                    <tr>
                        <td>{{ $plate_stock->location }}</td>
                        <td>{{$plate_stock->warehouse}}</td>
                        <td>{{number_format($plate_stock->stock)}} {{ $plate_stock->unit_name }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
</div>
