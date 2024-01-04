@if(empty($only) || in_array('sell_list_filter_location_id', $only))
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('sell_list_filter_location_id',  __('purchase.business_location') . ':') !!}

        {!! Form::select('sell_list_filter_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
    </div>
</div>
@endif
@if(empty($only) || in_array('sell_list_filter_customer_id', $only))
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('sell_list_filter_customer_id',  __('contact.customer') . ':') !!}
        {!! Form::select('sell_list_filter_customer_id', $customers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
    </div>
</div>
@endif
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('sell_list_filter_address',  __('sale.address') . ':') !!}
        {!! Form::text('sell_list_filter_address', null, ['class' => 'form-control', 'style' => 'width:100%', 'placeholder' => __('sale.address')]); !!}
    </div>
</div>
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('sell_list_filter_phone',  __('lang_v1.mobile_number') . ':') !!}
        {!! Form::text('sell_list_filter_phone', null, ['class' => 'form-control', 'style' => 'width:100%', 'placeholder' => __('lang_v1.mobile_number')]); !!}
    </div>
</div>
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('sell_list_filter_payment_method',  __('lang_v1.payment_method') . ':') !!}
        {!! Form::select('sell_list_filter_payment_method', $payment_types, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
    </div>
</div>
@if(empty($only) || in_array('sell_list_filter_payment_status', $only))
    @if(isset(request()->segments()[1]) && request()->segments()[1] != 'transfer-report')
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sell_list_filter_payment_status',  __('purchase.payment_status') . ':') !!}
                {!! Form::select('sell_list_filter_payment_status', ['paid' => __('lang_v1.paid'), 'due' => __('lang_v1.due'), 'partial' => __('lang_v1.partial'), 'overdue' => __('lang_v1.overdue')], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
    @endif
@endif
{{--@if(empty($only) || in_array('sell_list_filter_date_range', $only))
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('sell_list_filter_date_range', __('report.date_range') . ':') !!}
        {!! Form::text('sell_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
    </div>
</div>
@endif--}}
@if((empty($only) || in_array('created_by', $only)) && !empty($sales_representative) && !request()->segments()[0] == 'reporting-date')
<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('created_by',  __('report.user') . ':') !!}
        {!! Form::select('created_by', $sales_representative, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
    </div>
</div>
@endif
@if(empty($only) || in_array('sales_cmsn_agnt', $only))
@if(!empty($is_cmsn_agent_enabled))
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('sales_cmsn_agnt',  __('lang_v1.sales_commission_agent') . ':') !!}
            {!! Form::select('sales_cmsn_agnt', $commission_agents, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
        </div>
    </div>
@endif
@endif
@if(empty($only) || in_array('service_staffs', $only))
@if(!empty($service_staffs))
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('service_staffs', __('restaurant.service_staff') . ':') !!}
            {!! Form::select('service_staffs', $service_staffs, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
        </div>
    </div>
@endif
@endif
@if(empty($only) || in_array('shipping_statuses', $only))
    @if(isset(request()->segments()[1]) && request()->segments()[1] == 'transfer-report')
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('shipping_status', __('lang_v1.shipping_status') . ':') !!}
                {!! Form::select('shipping_status', $shipping_statuses, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
    @endif
@endif
{!! Form::hidden('sell_list_filter_start_date', '', ['id' => 'sell_list_filter_start_date']) !!}
{!! Form::hidden('sell_list_filter_end_date', '', ['id' => 'sell_list_filter_end_date']) !!}
