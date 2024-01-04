<div class="table-responsive">
    <table class="table table-bordered table-striped ajax_view" id="supplier_purchase_table" style="width: 100%;">
        <thead>
        <tr>
            <th>@lang('messages.date')</th>
            <th>@lang('purchase.ref_no')</th>
            <th>@lang('purchase.location')</th>
            <th>@lang('purchase.purchase_status')</th>
            <th>@lang('purchase.grand_total')</th>
        </tr>
        </thead>
        <tfoot>
        <tr class="bg-gray font-17 text-center footer-total">
            <td colspan="3"><strong>@lang('sale.total'):</strong></td>
            <td id="footer_status_count"></td>
            <td><span class="display_currency" id="footer_purchase_total" data-currency_symbol ="true"></span></td>
        </tr>
        </tfoot>
    </table>
</div>
