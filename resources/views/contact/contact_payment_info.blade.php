@if( $contact->type == 'supplier' || $contact->type == 'both')
    <strong>@lang('report.total_purchase')</strong>
    <p class="text-muted">
    <span class="display_currency" data-currency_symbol="true">
    {{ $contact->total_purchase }}</span>
    </p>
@endif
@if( $contact->type == 'customer' || $contact->type == 'both')
    <strong>@lang('report.total_sell')</strong>
    <p class="text-muted">
    <span class="display_currency" data-currency_symbol="true">
    {{ $contact->total_invoice }}</span>
    </p>
    <strong>@lang('contact.total_sale_paid')</strong>
    <p class="text-muted">
    <span class="display_currency" data-currency_symbol="true">
    {{ $contact->invoice_sale_received + $contact->receipt_amount }}</span>
    </p>
    <strong>@lang('contact.total_sale_due')</strong>
    <p class="text-muted">
    <span class="display_currency" data-currency_symbol="true">
        {{ $contact->total_invoice - $contact->invoice_received + $contact->sell_return_paid - $contact->total_sell_return + $contact->opening_balance - $contact->total_purchase - $contact->reduce_debt  }}
    </span>
    </p>
    <strong>@lang('lang_v1.total_sell_return')</strong>
    <p class="text-muted">
        <span class="display_currency" data-currency_symbol="true">{{ $contact->total_sell_return }}</span>
    </p>
    @if($opening_balance)
        <strong>@lang('lang_v1.opening_balance')</strong>
        <p class="text-muted">
            <span class="display_currency" data-currency_symbol="true">{{ @number_format($opening_balance->final_total) }}</span>
        </p>
        <strong>@lang('contact.opening_balance_time')</strong>
        <p class="text-muted">
            {{ @format_datetime($opening_balance->created_at) }}
        </p>
    @endif
@endif