<h3 class="profile-username">
    <i class="fas fa-fw fa-user-tie"></i>
    {{ $contact->name }}
    <small>
        @if($contact->type == 'both')
            {{__('role.customer')}} & {{__('role.supplier')}}
        @elseif(($contact->type != 'lead'))
            {{__('role.'.$contact->type)}}
        @endif
    </small>
</h3><br>
<strong><i class="fa fa-fw fa-map-marker margin-r-5"></i> @lang('business.address')</strong>
<p class="text-muted">
    {{ implode(", ", array_filter([$contact->landmark, $contact->city, $contact->state, $contact->country])) }}
</p>
@if($contact->supplier_business_name)
    <strong><i class="fa fa-fw fa-briefcase margin-r-5"></i>
    @lang('business.business_name')</strong>
    <p class="text-muted">
        {{ $contact->supplier_business_name }}
    </p>
@endif

<strong><i class="fa fa-mobile fa-fw margin-r-5"></i> @lang('contact.mobile')</strong>
<p class="text-muted">
    {{ $contact->mobile }}
</p>
@if($contact->landline)
    <strong><i class="fa fa-phone fa-fw margin-r-5"></i> @lang('contact.landline')</strong>
    <p class="text-muted">
        {{ $contact->landline }}
    </p>
@endif
@if($contact->alternate_number)
    <strong><i class="fa fa-phone fa-fw margin-r-5"></i> @lang('contact.alternate_contact_number')</strong>
    <p class="text-muted">
        {{ $contact->alternate_number }}
    </p>
@endif
@if($contact->type == 'customer')
    <strong><i class="fas fa-shipping-fast fa-fw margin-r-5"></i> @lang('lang_v1.search_address')</strong>
    <p class="text-muted">
        {{ $contact->shipping_address }}
    </p>
@endif