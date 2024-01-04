@if(in_array($plate_line->product->unit->type, ['area', 'meter']))
    @include('stock_deliver.partials.edit_sell_by_area_entry_row')
@elseif($plate_line->product->unit->type == 'pcs')
    @include('stock_deliver.partials.edit_sell_by_pcs_entry_row')
@endif