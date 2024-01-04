<div class="modal-header">
    <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title" id="modalTitle"> @lang('target.target_details')
    </h4>
</div>
<div class="modal-body">
{{--  <div class="row">--}}
{{--    <div class="col-sm-12">--}}
{{--      <p class="pull-right"><b>@lang('messages.date'):</b> {{ @format_date($target->transaction_date) }}</p>--}}
{{--    </div>--}}
{{--  </div>--}}
  <br>
  <div class="row">
    <div class="col-sm-12 col-xs-12">
      <div class="table-responsive">
        <table class="table bg-gray">
          @if (!$target->target_category_lines->isEmpty())
          <thead>
            <tr class="bg-green">
              <th>#</th>
              <th>@lang('product.category')</th>
              <th>@lang('target.purchase_quantity')</th>
              <th>@lang('target.note')</th>
            </tr>
          </thead>
            @php
              $total_before_tax = 0.00;
            @endphp
            @foreach($target->target_category_lines as $key => $target_category_line)
              <tr>
                <td>{{$key + 1}}</td>
                <td> {{empty($categories[$target_category_line->category_id]) ? 'None' : $categories[$target_category_line->category_id]}} </td>
                <td> {{@size_format($target_category_line->quantity)}}</td>
                <td> {{$target->note}} </td>
              </tr>
            @endforeach
          @elseif(!$target->target_sale_lines->isEmpty())
            <thead>
            <tr class="bg-green">
              <th>#</th>
              <th>@lang('product.category')</th>
              <th>@lang('target.purchase_quantity')</th>
              <th>@lang('target.note')</th>
            </tr>
            </thead>
            @php
              $total_before_tax = 0.00;
            @endphp

            @foreach($target->target_sale_lines as $key => $target_sale_line)
              <tr>
                <td>{{$key + 1}}</td>
                <td> {{empty($variations[$target_sale_line->variation_id]) ? 'None' : $variations[$target_sale_line->variation_id]}} </td>
                <td>
                  @if($target_sale_line->product->unit->type == 'pcs')
                    {{ number_format($target_sale_line->quantity) }}
                    {{ $target_sale_line->product->unit->actual_name }}
                  @else
                    {{@number_format($target_sale_line->quantity, 2)}}
                    m<sup>2</sup>
                  @endif
                </td>
                <td> {{$target->note}} </td>
              </tr>
            @endforeach
          @else
            <thead>
            <tr class="bg-green">
              <th>#</th>
              @if($target->type == 'amount')
                <th>@lang('target.amount')</th>
              @elseif($target->type == 'profit')
                <th>@lang('target.profit')</th>
              @endif
              <th>@lang('target.note')</th>
            </tr>
            </thead>
            @php
              $total_before_tax = 0.00;
            @endphp
            <tr>
              <td>1</td>
              <td> {{!empty(number_format($target->amount)) ? number_format($target->amount) : number_format($target->profit)}} đ</td>
              <td> {{$target->note}} </td>
            </tr>
          @endif
        </table>
      </div>
    </div>
  </div>
  <br>
  {{-- Barcode --}}
</div>
