<a class="payment-status-label" data-orig-value="{{$payment_status}}" data-status-name="{{__('lang_v1.' . $payment_status)}}">
    <span class="label @payment_status($payment_status)">
     <i class="{{ isset($payment_approved) && $payment_approved ? 'fas fa-check' : 'fas fa-times' }}"></i> {{__('lang_v1.' . $payment_status)}}
    </span>
</a>
