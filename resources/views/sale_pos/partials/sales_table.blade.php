<div class="table-responsive">
    <table class="table table-bordered table-striped" id="sell_contact_table" style="width: 100%;">
        <thead>
            <tr>
                <th rowspan="2" style="text-align: center;">@lang('sale.invoice_no')</th>
                <th rowspan="2" style="text-align: center;">@lang('business.days')</th>
                <th rowspan="2" style="text-align: center;">@lang('sale.product')</th>
                <th rowspan="2" style="text-align: center;">@lang('sale.note')</th>
                <th colspan="2" style="text-align: center;">@lang('purchase.amount')</th>
                <th colspan="2" style="text-align: center;">@lang('lang_v1.balance_amount')</th>
            </tr>
            <tr>
                <th style="text-align: center;">@lang('account.debit')</th>
                <th style="text-align: center;">@lang('account.credit')</th>
                <th style="text-align: center;">@lang('account.debit')</th>
                <th style="text-align: center;">@lang('account.credit')</th>
            </tr>

            <tr>
                <td colspan="6" style="text-align: center;font-weight: bold">@lang('lang_v1.opening_balance')</td>
                <td>
                    <span class="total_debt" style="font-weight: bold">{{ $total_debt >= 0 ? number_format($total_debt).' đ' : '--' }}</span>
                </td>
                <td>
                    <span class="total_credit" style="font-weight: bold">{{ $total_debt < 0 ? number_format(abs($total_debt)).' đ' : '--' }}</span>
                </td>
            </tr>
        </thead>
        <tbody>
            @foreach($sell_customer as $sell)
                <tr>
                    <td>{!! $sell->invoice_no_html !!}</td>
                    <td>{{ @format_datetime($sell->transaction_date) }}</td>
                    <td>{!! $sell->product_sku_html !!}</td>
                    <td>{!! $sell->note !!}</td>
                    <td>{!! $sell->final_total_html !!}</td>
                    <td>{!! $sell->total_sell_paid_html !!}</td>
                    <td>{!! $sell->total_due_customer_html !!}</td>
                    <td>{!! $sell->total_due_business_html !!}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="bg-gray font-17 footer-total text-center">
                <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                <td><span class="display_currency" id="final_total_footer" data-currency_symbol ="true">{{ $all_final_total }}</span></td>
                <td><span class="display_currency" id="total_sell_paid_footer" data-currency_symbol ="true">{{ $all_total_sell_paid }}</span></td>
                <td colspan="2"></td>
            </tr>
            <tr class="bg-gray font-17 footer-total text-center">
                <td colspan="6"><strong>@lang('sale.ending_balance'):</strong></td>
                <td><span class="display_currency" id="total_ending_balance_debt" data-currency_symbol ="true">{{ $total_ending_balance_debt }}</span></td>
                <td><span class="display_currency" id="total_ending_balance_positive" data-currency_symbol ="true">{{ $total_ending_balance_positive }}</span></td>
            </tr>
        </tfoot>
    </table>
</div>
