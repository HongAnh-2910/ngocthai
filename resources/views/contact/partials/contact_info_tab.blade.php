<span id="view_contact_page"></span>
<div class="row">
    <div class="col-md-6">
        @include('contact.contact_basic_info')
        @include('contact.contact_tax_info')
    </div>
    <div class="col-md-6 mt-56">
        @include('contact.contact_payment_info')
    </div>

    {{--@if( $contact->type == 'supplier' || $contact->type == 'both')
        <div class="clearfix"></div>
        <div class="col-sm-12">
            @if(($contact->total_purchase - $contact->purchase_paid) > 0)
                <a href="{{action('TransactionPaymentController@getPayContactDue', [$contact->id])}}?type=purchase" class="pay_purchase_due btn btn-primary btn-sm pull-right"><i class="fas fa-money-bill-alt" aria-hidden="true"></i> @lang("contact.pay_due_amount")</a>
            @endif
        </div>
    @endif--}}
</div>