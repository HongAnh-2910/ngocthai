<div class="modal-dialog" role="document" id="show_shipping_modal">
	<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title">@lang('lang_v1.show_shipping') - {{$transaction->invoice_no}}</h4>
		</div>
		<div class="modal-body">
			<div class="row">
				<div class="col-md-12">
					<div class="form-group">
						{!! Form::label('shipper_id', __('lang_v1.shipper') . ':' ) !!}
						<div class="form-control">
							{{ $shipper ? $shipper->user_full_name : '' }}
						</div>
					</div>
				</div>

			    <div class="col-md-6">
			        <div class="form-group">
			            {!! Form::label('shipping_address', __('lang_v1.shipping_address') . ':*' ) !!}
						<div class="form-control">
							{{ !empty($transaction->contact->shipping_address) ? $transaction->contact->shipping_address : '' }}
						</div>
			        </div>
			    </div>

				<div class="col-md-6">
					<div class="form-group">
						{!! Form::label('shipping_details', __('sale.shipping_details') . ':' ) !!}
						<div class="form-control">
							{{ !empty($transaction->shipping_details) ? $transaction->shipping_details : '' }}
						</div>
					</div>
				</div>

			    <div class="col-md-12">
			        <div class="form-group">
			            {!! Form::label('shipping_status', __('lang_v1.shipping_status') . ':' ) !!}
						<div class="form-control">
							{{ !empty($transaction->shipping_status) ? __('lang_v1.'.$transaction->shipping_status) : null }}
						</div>
{{--			            {!! Form::select('shipping_status',$shipping_statuses, !empty($transaction->shipping_status) ? $transaction->shipping_status : null, ['class' => 'form-control','placeholder' => __('messages.no'), 'disabled']); !!}--}}
			        </div>
			    </div>
			</div>
		</div>
		<div class="modal-footer">
		    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
		</div>
	</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
