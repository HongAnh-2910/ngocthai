<div class="col-xs-6">
    @component('components.widget')
        <table class="table table-striped">
            <thead>
                <tr class="bg-green">
                    <th colspan="2">@lang('report.revenue')</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <th>{{ __('report.revenue_from_sale') }}:</th>
                    <td>
                        (+) <span class="display_currency" data-currency_symbol="true">{{ $data['total_sell'] }}</span>
                    </td>
                </tr>
                <tr>
                    <th>{{ __('report.total_sell_discount') }}:</th>
                    <td>
                        (-) <span class="display_currency" data-currency_symbol="true">{{ $data['total_sell_discount'] }}</span>
                    </td>
                </tr>
                <tr>
                    <th>{{ __('report.revenue_from_shipping') }}:</th>
                    <td>
                        (+) <span class="display_currency" data-currency_symbol="true">{{ $data['total_sell_shipping_charge'] }}</span>
                    </td>
                </tr>
                <tr>
                    <th>{{ __('report.revenue_from_vat') }}:</th>
                    <td>
                        (+) <span class="display_currency" data-currency_symbol="true">{{ $data['total_sell_tax'] }}</span>
                    </td>
                </tr>
                <tr>
                    <th>{{ __('report.revenue_from_stock_adjustment') }}:</th>
                    <td>
                        (+) <span class="display_currency" data-currency_symbol="true">{{ $data['total_recovered'] }}</span>
                    </td>
                </tr>
                <tr>
                    <th>{{ __('lang_v1.total_sell_return') }}:</th>
                    <td>
                        (-) <span class="display_currency" data-currency_symbol="true">{{ $data['total_sell_return'] }}</span>
                    </td>
                </tr>
            </tbody>

            <tfoot>
                <tr class="bg-gray">
                    <th>{{ __('report.total_revenue') }}:</th>
                    <td>
                        <span class="display_currency" data-currency_symbol="true">{{ $data['total_revenue'] }}</span>
                    </td>
                </tr>
            </tfoot>
        </table>
    @endcomponent
</div>

<div class="col-xs-6">
    @component('components.widget')
        <table class="table table-striped">
            <thead>
            <tr class="bg-green">
                <th colspan="2">@lang('report.expense')</th>
            </tr>
            </thead>

            <tbody>
            <tr>
                <th>{{ __('report.purchase_expense') }}:</th>
                <td>
                    (+) <span class="display_currency" data-currency_symbol="true">{{$data['total_purchase']}}</span>
                </td>
            </tr>
            <tr>
                <th>{{ __('report.expense_for_stock_transfer') }}:</th>
                <td>
                    (+) <span class="display_currency" data-currency_symbol="true">{{ $data['total_transfer_shipping_charges'] }}</span>
                </td>
            </tr>
            <tr>
                <th>{{ __('report.expense_for_customer') }}:</th>
                <td>
                    (+) <span class="display_currency" data-currency_symbol="true">{{ $data['expense_for_customer'] }}</span>
                </td>
            </tr>
            {{--<tr>
                <th>{{ __('report.other_expense') }}:</th>
                <td>
                    (+) <span class="display_currency" data-currency_symbol="true">{{ $data['other_expense'] }}</span>
                </td>
            </tr>--}}
            </tbody>

            <tfoot>
            <tr class="bg-gray">
                <th>{{ __('report.total_expense') }}:</th>
                <td>
                    <span class="display_currency" data-currency_symbol="true">{{ $data['total_expense'] }}</span>
                </td>
            </tr>
            </tfoot>
        </table>
    @endcomponent
</div>
<br>
<div class="col-xs-12">
    @component('components.widget')
        <h3 class="text-muted mb-0">
            {{ __('report.total_profit') }}:
            <span class="display_currency" data-currency_symbol="true">{{ $data['total_profit'] }}</span>
        </h3>
        <small class="help-block">
            (@lang('report.total_revenue') - @lang('report.total_expense'))
        </small>
    @endcomponent
</div>
