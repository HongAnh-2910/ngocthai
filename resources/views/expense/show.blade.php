<div class="modal-dialog modal-lg" role="document">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span
                aria-hidden="true">&times;</span></button>
      <h4 class="modal-title" id="modalTitle"> @lang('expense.expense_details') (<b>@lang('sale.invoice_no')
          :</b> {{ $transaction->ref_no }})
      </h4>
    </div>
    <div class="modal-body">
    <div class="row">
      <table class="table bg-gray" id="add_expense_table">
        <thead>
        <tr class="bg-green">
          <th>#</th>
          <th>@lang('expense.expense_category')</th>
          <th>@lang('expense.customer')</th>
          <th>@lang('expense.package_code')</th>
          <th>@lang('expense.content')</th>
          <th>@lang('expense.total_money')</th>
        </tr>
        </thead>
        <tbody>
        @foreach( $transaction->expenses as $key => $expense)
          <tr>
            <td>
              {{--                            {{$loop->index}}--}}
            </td>
            <td>
              {{ \App\TransactionExpense::$TYPES[$expense->type] }}
            </td>

            <td>
              {{ empty($users[$expense->contact_id]) ? 'None' : $users[$expense->contact_id] }}
            </td>
            <td>
              {{ empty($listRefTrans[$expense->ref_transaction_id]) ? 'None' : $listRefTrans[$expense->ref_transaction_id] }}
            </td>
            <td>
              {{ $expense->note }}
            </td>
            <td>
              {{ number_format($expense->total_money) }}
            </td>
          </tr>
        @endforeach

        </tbody>
      </table>
    </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default no-print"
              data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>
  </div>
</div><!-- /.modal-dialog -->