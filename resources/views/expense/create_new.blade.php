@extends('layouts.app')
@section('title', __('expense.add_expense'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('expense.add_expense')</h1>
</section>

<!-- Main content -->
<section class="content">
	{!! Form::open(['url' => action('ExpenseController@store'), 'method' => 'post', 'id' => 'add_expense_form', 'files' => true ]) !!}
	<div class="box box-primary">
		<div class="box-body">
			<div class="row">

				@if(count($business_locations) == 1)
					@php
						$default_location = current(array_keys($business_locations->toArray()))
					@endphp
				@else
					@php $default_location = null; @endphp
				@endif
				<div class="col-sm-4">
					<div class="form-group">
						{!! Form::label('location_id', __('purchase.business_location').':*') !!}
						{!! Form::select('location_id', $business_locations, $default_location, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
					</div>
				</div>

{{--				<div class="col-sm-3">--}}
{{--					<div class="form-group">--}}
{{--						{!! Form::label('ref_no', __('purchase.ref_no').':') !!}--}}
{{--						{!! Form::text('ref_no', null, ['class' => 'form-control']); !!}--}}
{{--					</div>--}}
{{--				</div>--}}
				<div class="col-sm-4">
					<div class="form-group">
						{!! Form::label('transaction_date', __('messages.date') . ':*') !!}
						<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-calendar"></i>
							</span>
							{!! Form::text('transaction_date', @format_datetime('now'), ['class' => 'form-control', 'readonly', 'required', 'id' => 'expense_transaction_date']); !!}
						</div>
					</div>
				</div>
				<div class="col-sm-4 hide">
					<div class="form-group">
						{!! Form::label('expense_for', __('expense.expense_for').':') !!} @show_tooltip(__('tooltip.expense_for'))
						{!! Form::select('expense_for', $users, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]); !!}
					</div>
				</div>
				<div class="col-sm-4 hidden">
                    <div class="form-group">
                        {!! Form::label('document', __('purchase.attach_document') . ':') !!}
                        {!! Form::file('document', ['id' => 'upload_document', 'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types')))]); !!}
                        <p class="help-block">@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
                        @includeIf('components.document_help_text')</p>
                    </div>
                </div>
				<div class="col-sm-4 hidden">
					<div class="form-group">
						{!! Form::label('additional_notes', __('expense.expense_note') . ':') !!}
								{!! Form::textarea('additional_notes', null, ['class' => 'form-control', 'rows' => 3]); !!}
					</div>
				</div>
				<div class="clearfix"></div>
				<div class="col-md-4 hidden">
			    	<div class="form-group">
			            {!! Form::label('tax_id', __('product.applicable_tax') . ':' ) !!}
			            <div class="input-group">
			                <span class="input-group-addon">
			                    <i class="fa fa-info"></i>
			                </span>
			                {!! Form::select('tax_id', $taxes['tax_rates'], null, ['class' => 'form-control'], $taxes['attributes']); !!}

							<input type="hidden" name="tax_calculation_amount" id="tax_calculation_amount"
							value="0">
			            </div>
			        </div>
			    </div>
{{--			    <div class="col-sm-4 hidden">--}}
{{--					<div class="form-group">--}}
{{--						{!! Form::label('final_total', __('sale.total_amount') . ':*') !!}--}}
{{--						{!! Form::text('final_total', null, ['class' => 'form-control input_number', 'placeholder' => __('sale.total_amount'), 'required']); !!}--}}
{{--					</div>--}}
{{--				</div>--}}
			</div>
		</div>
	</div> <!--box end-->
	@component('components.widget', ['class' => 'box-primary'])
		<div class="row">
			<table class="table bg-white" id="add_expense_table">
				<thead>
				<tr class="bg-green">
					<th>#</th>
					<th>@lang('expense.expense_category')</th>
					<th>@lang('expense.customer')</th>
					<th>@lang('expense.package')</th>
					<th>@lang('expense.content')</th>
					<th>@lang('expense.total_money')</th>
					{{--                        <th>@lang('award.currency')</th>--}}
					<th>
						<i class="fa fa-plus-circle add_row" title="Add" style="cursor:pointer;color: white"></i>
					</th>
				</tr>
				</thead>
				<tbody>
				@foreach( $array as $key => $value)
					<tr>
						<td>
							{{--                            {{$loop->index}}--}}
						</td>
						<td>
								<div class="form-group">
									{!! Form::select('expenses[' . $key . '][type]', \App\TransactionExpense::$TYPES, null, ['class' => 'form-control expense_row', 'required', 'placeholder' => __('messages.please_select')]); !!}
								</div>
						</td>

						<td>
							<div class="form-group">
								{!! Form::select('expenses[' . $key . '][customer_id]', $users, null, ['class' => 'form-control customer_row', 'placeholder' => __('messages.please_select')]); !!}
							</div>
						</td>
						<td>
							<div class="form-group">
								{!! Form::select('expenses[' . $key . '][package_id]', $packages, null, ['class' => 'form-control package_row', 'placeholder' => __('messages.please_select')]); !!}
							</div>
						</td>
						<td>
							<div class="form-group">
								{!! Form::text('expenses[' . $key . '][description]',
                                        '',
                                        [
                                            'class' => 'form-control',
                                            'id'    => 'quantity_' . $key
                                        ]);
                                !!}
							</div>
						</td>
						<td>
							<div class="form-group">
								{!! Form::text('expenses[' . $key . '][final_total]',
                                        '',
                                        [
                                            'class' => 'form-control input_number',
                                            'required'
                                        ]);
                                !!}
							</div>
						</td>
						<td>
						</td>
					</tr>
				@endforeach

				</tbody>
			</table>
		</div>

		<input type="hidden" id="row_count_expense" value="0">
	@endcomponent
	@component('components.widget', ['class' => 'box-primary', 'id' => "payment_rows_div", 'title' => __('purchase.add_payment')])
	<div class="payment_row">
		@include('sale_pos.partials.payment_row_form', ['row_index' => 0])
{{--		<hr>--}}
{{--		<div class="row">--}}
{{--			<div class="col-sm-12">--}}
{{--				<div class="pull-right">--}}
{{--					<strong>@lang('purchase.payment_due'):</strong>--}}
{{--					<span id="payment_due">{{@num_format(0)}}</span>--}}
{{--				</div>--}}
{{--			</div>--}}
{{--		</div>--}}
	</div>
	@endcomponent
	<div class="col-sm-12">
		<button type="submit" class="btn btn-primary pull-right">@lang('messages.save')</button>
	</div>
{!! Form::close() !!}
</section>
@endsection
@section('javascript')
<script type="text/javascript">
	$(document).on('change', 'input#final_total, input.payment-amount', function() {
		calculateExpensePaymentDue();
	});

	function calculateExpensePaymentDue() {
		var final_total = __read_number($('input#final_total'));
		var payment_amount = __read_number($('input.payment-amount'));
		var payment_due = final_total - payment_amount;
		$('#payment_due').text(__currency_trans_from_en(payment_due, true, false));
	}

	$(document).ready(function () {
		function checkChangeContact(){
			$('.customer_row').each(function () {
				let $this = $(this)
				$(this).change(function () {
					$.ajax({
						method: 'POST',
						url: '/expenses/get-packages',
						dataType: 'html',
						data: { contact_id: $this.val() },
						success: function(result) {
							let key = JSON.parse(result)
							let row = $this.closest('tr').find('.package_row')
							row.html('')

							if (key.length <= 0) {
								row.append('<option value=0>' + 'Lựa chọn' + '</option>');
							}

							for(let i = 0; i < key.length; i++){
								row.append('<option value=' + key[i].id + '>' + key[i].invoice_no + '</option>');
							}
						}
					})
				})
			})
		}

		$('.add_row').click(function () {
			let row_count_expense = parseInt($('#row_count_expense').val())
			$.ajax({
				method: 'POST',
				url: '/expenses/add_expense_row',
				dataType: 'html',
				data: { index: row_count_expense },
				success: function(result) {
					$(result)
							.find('.expense_row')
							.each(function() {

								let row = $(this).closest('tr');

								if ($(result).find('.expense_row').length) {
									$('#row_count_expense').val(
											$(result).find('.expense_row').length + row_count_expense
									);
								}

								$('#add_expense_table tbody').append(row);
								checkChangeContact()
							});
				},
			});
		})

		checkChangeContact()

		$('form#add_expense_form')
				.submit(function(e) {
					e.preventDefault();
				})
				.validate({
					rules: {
						type: {
							required: true
						},
						final_total: {
							required: true
						}
					},
					submitHandler: function(form) {
						var data = $(form).serialize();
						$(form)
								.find('button[type="submit"]')
								.attr('disabled', true);
						$.ajax({
							method: 'POST',
							url: $(form).attr('action'),
							dataType: 'json',
							data: data,
							success: function(result) {
								if (result.success === 1){
									window.location = '/expenses'
									setTimeout(toastr.success(result.msg), 2000);
								} else {
									toastr.error(result.msg);
								}
							},
						});
					},
				});

		$(document).on('click', '.remove_row', function() {
			$(this)
					.closest('tr')
					.remove();
		});
	})
</script>
@endsection
