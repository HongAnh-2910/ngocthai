<div class="pos-tab-content">
    <div class="row">
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('max_weight_dont_need_confirm', __('stock_adjustment.max_weight_dont_need_confirm') . ':*') !!}
                {!! Form::number('max_weight_dont_need_confirm', $business->max_weight_dont_need_confirm, ['class' => 'form-control','required']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('max_pcs_dont_need_confirm', __('stock_adjustment.max_pcs_dont_need_confirm') . ':*') !!}
                {!! Form::number('max_pcs_dont_need_confirm', $business->max_pcs_dont_need_confirm, ['class' => 'form-control','required']); !!}
            </div>
        </div>
    </div>
</div>