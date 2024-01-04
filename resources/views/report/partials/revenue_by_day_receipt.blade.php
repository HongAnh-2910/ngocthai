<div class="revenue_by_day_receipt">
    <table class="revenue_by_day_table">
        <tbody>
        <tr class="print_bold">
            <td colspan="7" class="text-center">BÁO CÁO NGÀY {{ @format_date($date) }}</td>
        </tr>
        <tr>
            <td colspan="7"></td>
        </tr>
        <tr class="print_bold">
            <td>DOANH THU THÁNG</td>
            <td colspan="6">{{ @num_format($total['total_revenue_by_month']) }}</td>
        </tr>
        <tr class="print_bold">
            <td>DOANH THU NGÀY</td>
            <td colspan="6">{{ @num_format($total['total_revenue']) }}</td>
        </tr>
{{--        <tr class="print_bold">--}}
{{--            <td>TỔNG CÔNG NỢ</td>--}}
{{--            <td colspan="6">{{ @num_format($total['total_debt']) }}</td>--}}
{{--        </tr>--}}
        <tr class="print_bold">
            <td>DƯ NỢ</td>
            <td colspan="6">{{ @num_format($total['due']) }}</td>
        </tr>
        <tr class="print_bold">
            <td>DƯ CÓ</td>
            <td colspan="6">{{ @num_format($total['payment']) }}</td>
        </tr>
        <tr class="print_bold">
            <td>TIỀN MẶT</td>
            <td>{{ @num_format($total['total_money_payment_cash']) }}</td>
            <td colspan="2">Nộp TK:</td>
            <td colspan="3">Còn TM:</td>
        </tr>
        <tr class="print_bold">
            <td>KHÁCH CK</td>
            <td colspan="6">{{ @num_format($total['total_money_payment_bank']) }}</td>
        </tr>
        <tr>
            <td colspan="7"></td>
        </tr>
        <tr class="print_bold">
            @php
                $expense_title_class = ($num_expense == 0) ? 'print_border_bottom' : '';
                $receipt_title_class = ($num_receipt == 0) ? 'print_border_bottom' : '';
            @endphp
            <td class="print_border_left print_border_top {{ $expense_title_class }}">CHI PHÍ</td>
            <td class="print_border_left print_border_top {{ $expense_title_class }}">{{ @num_format($total_expense) }}</td>
            <td class="print_border_left print_border_top print_border_right {{ $expense_title_class }}"></td>
            <td></td>
            <td class="print_border_left print_border_top {{ $receipt_title_class }}">THU NỢ</td>
            <td class="print_border_left print_border_top {{ $receipt_title_class }}">{{ @num_format($total_receipt) }}</td>
            <td class="print_border_left print_border_top print_border_right {{ $receipt_title_class }}"></td>
            <td></td>
        </tr>

        @foreach($receipt_and_expense_rows as $key => $receipt_and_expense_row)
            <tr>
                @if($key <= $num_expense - 1)
                    @php
                        $content_class = ($key == $num_expense - 1) ? 'print_border_bottom' : '';
                    @endphp
                    <td class="print_border_left print_border_top {{ $content_class }}">{{ $receipt_and_expense_row['expense']['note'] }}</td>
                    <td class="print_border_left print_border_top {{ $content_class }}">{{ @num_format($receipt_and_expense_row['expense']['amount']) }}</td>
                    <td class="print_border_left print_border_top print_border_right {{ $content_class }}">{{ $receipt_and_expense_row['expense']['method'] }}</td>
                @else
                    <td colspan="3"></td>
                @endif
                <td></td>
                @if($key <= $num_receipt - 1)
                    @php
                        $content_class = ($key == $num_receipt - 1) ? 'print_border_bottom' : '';
                    @endphp
                    <td class="print_border_left print_border_top {{ $content_class }}">{{ $receipt_and_expense_row['receipt']['note'] }}</td>
                    <td class="print_border_left print_border_top {{ $content_class }}">{{ @num_format($receipt_and_expense_row['receipt']['amount']) }}</td>
                    <td class="print_border_left print_border_top print_border_right {{ $content_class }}">{{ $receipt_and_expense_row['receipt']['method'] }}</td>
                @else
                    <td colspan="3"></td>
                @endif
                <td></td>
            </tr>
        @endforeach

        <tr>
            <td colspan="7" class="print_border_bottom"></td>
        </tr>

        <tr class="print_bold">
            <td class="print_border_left">NỢ</td>
            <td>{{ @num_format($total['total_debt']) }}</td>
            <td colspan="5" class="print_border_right"></td>
        </tr>

        @foreach($debt_rows as $key => $debt_row)
            <tr>
                @if($key <= $num_debt_column_1 - 1)
                    <td class="print_border_left">{{ $debt_row['column_1']['name'] }}</td>
                    <td>{{ @num_format($debt_row['column_1']['debt']) }}</td>
                @else
                    <td colspan="2" class="print_border_left"></td>
                @endif
                <td colspan="2"></td>
                @if($key <= $num_debt_column_2 - 1)
                    <td>{{ $debt_row['column_2']['name'] }}</td>
                    <td>{{ @num_format($debt_row['column_2']['debt']) }}</td>
                @else
                    <td colspan="2"></td>
                @endif
                <td class="print_border_right"></td>
            </tr>
        @endforeach
        <tr>
            <td colspan="7" class="print_border_top"></td>
        </tr>
        <tr class="print_bold">
            <td class="print_border_left print_border_top">Số lượng HĐ phát hành <br>(từ số <i>{{ $sell_invoice_from }}</i> đến số <i>{{ $sell_invoice_to }}</i>)</td>
            <td class="print_border_left print_border_top print_border_right">{{ $total_sell }}</td>
            <td colspan="5"></td>
        </tr>
        <tr class="print_bold">
            <td class="print_border_left print_border_top print_border_bottom">Số lượng HĐ hủy</td>
            <td class="print_border_left print_border_top  print_border_right print_border_bottom ">{{ $total_cancel_sell }}</td>
            <td colspan="5"></td>
        </tr>
        <tr>
            <td colspan="7" style="height: 40px"></td>
        </tr>
        <tr class="print_bold text-center">
            <td colspan="2">Thu ngân</td>
            <td colspan="2">Kiểm sát viên</td>
            <td colspan="3">Ký chốt sổ</td>
        </tr>
        <tr style="height: 40px">
            <td colspan="7"></td>
        </tr>
        @if($user_closed_end_of_day)
            <tr class="print_italic text-center">
                <td colspan="4"></td>
                <td colspan="3">Ký bởi: {{ $user_closed_end_of_day['full_name'] }}</td>
            </tr>
            <tr class="print_italic text-center">
                <td colspan="4"></td>
                <td colspan="3">Ký ngày: {{ @format_datetime($user_closed_end_of_day['closed_at']) }}</td>
            </tr>
        @endif
        </tbody>
    </table>
</div>