<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('sell_list_filter_customer_id',  __('contact.customer') . ':') !!}
        {!! Form::select('sell_list_filter_customer_id', $customers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
    </div>
</div>

<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('created_by',  __('contact.created_by') . ':') !!}
        {!! Form::select('created_by', $users, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
    </div>
</div>

<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('status',  __('sale.confirm_status') . ':') !!}
        {!! Form::select('status', $reduce_debt_statuses, null, ['class' => 'form-control select2', 'id' => 'status', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
    </div>
</div>

<div class="col-md-3">
    <div class="form-group">
        {!! Form::label('sell_list_filter_date_range', __('report.date_range') . ':') !!}
        {!! Form::text('sell_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
    </div>
</div>

{!! Form::hidden('sell_list_filter_start_date', '', ['id' => 'sell_list_filter_start_date']) !!}
{!! Form::hidden('sell_list_filter_end_date', '', ['id' => 'sell_list_filter_end_date']) !!}
