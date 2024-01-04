<div class="modal-dialog" role="document" id="edit_shipping_modal">
	{!! Form::open(['url' => action('SellController@updateShipping', [$transaction->id]), 'method' => 'put', 'id' => 'edit_shipping_form' ]) !!}
	<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title">@lang('lang_v1.edit_shipping') - {{$transaction->invoice_no}}</h4>
		</div>
		<div class="modal-body">
			<div class="row">
				@if(!empty($service_staffs))
					<div class="col-md-12">
						<div class="form-group">
							{!! Form::label('res_waiter_id[]', __('restaurant.service_staff') . ':') !!}
							{!! Form::select('res_waiter_id[]', $service_staffs, !empty($shippers) ? $shippers : [], ['class' => 'form-control select2', 'multiple', 'style' => 'width:100%', 'placeholder' => __('messages.no'), $transaction->shipping_status == 'shipped' ? 'disabled' : '']); !!}
						</div>
					</div>
				@endif

				<div class="col-md-12">
					<div class="form-group">
						{!! Form::label('delivered_to', __('lang_v1.delivered_to') . ':' ) !!}
						{!! Form::text('delivered_to', !empty($transaction->delivered_to) ? $transaction->delivered_to : '', ['class' => 'form-control','placeholder' => __('lang_v1.delivered_to'), $transaction->shipping_status == 'shipped' ? 'disabled' : '']); !!}
					</div>
				</div>

				<div class="col-md-12">
					<div class="form-group">
						{!! Form::label('phone_contact', __('lang_v1.phone_contact') . ':' ) !!}
						{!! Form::text('phone_contact', !empty($transaction->phone_contact) ? $transaction->phone_contact : '', ['class' => 'form-control','placeholder' => __('lang_v1.phone_contact'), $transaction->shipping_status == 'shipped' ? 'disabled' : '']); !!}
					</div>
				</div>

				{{--<div class="col-md-12">
					{!! Form::label('phone_contact', __( 'lang_v1.phone_contact' ) . ':') !!}
					{!! Form::text('phone_contact', !empty($transaction->phone_contact) ? $transaction->phone_contact : '', ['class' => 'form-control', 'id' => 'phone_contact', 'placeholder' => __('lang_v1.phone_contact')]); !!}
				</div>--}}

			    <div class="col-md-6">
			        <div class="form-group">
			            {!! Form::label('shipping_address', __('lang_v1.shipping_address') . ':' ) !!}
						{!! Form::textarea('shipping_address',!empty($transaction->shipping_address) ? $transaction->shipping_address : '', ['class' => 'form-control','placeholder' => __('lang_v1.shipping_address') ,'rows' => '4', $transaction->shipping_status == 'shipped' ? 'disabled' : '']); !!}
			        </div>
			    </div>

				<div class="col-md-6">
					<div class="form-group">
						{!! Form::label('shipping_details', __('sale.shipping_details') . ':' ) !!}
						{!! Form::textarea('shipping_details', !empty($transaction->shipping_details) ? $transaction->shipping_details : '', ['class' => 'form-control','placeholder' => __('sale.shipping_details') ,'rows' => '4', $transaction->shipping_status == 'shipped' ? 'disabled' : '']); !!}
					</div>
				</div>

			    <div class="col-md-12">
			        <div class="form-group">
			            {!! Form::label('shipping_status', __('lang_v1.shipping_status') . ':' ) !!}
			            {!! Form::select('shipping_status',$shipping_statuses, !empty($transaction->shipping_status) ? $transaction->shipping_status : null, ['class' => 'form-control', $transaction->shipping_status == 'shipped' ? 'disabled' : '']); !!}
			        </div>
			    </div>
			</div>
		</div>
		<div class="modal-footer">
			@if($transaction->shipping_status != 'shipped')
				<button type="submit" class="btn btn-primary">@lang('messages.update')</button>
				<button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.cancel')</button>
			@else
				<button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
			@endif
		</div>
		{!! Form::close() !!}
	</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
