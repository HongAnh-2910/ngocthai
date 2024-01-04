<?php

namespace App\Http\Controllers;

use App\Account;
use App\Brands;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Contact;
use App\CustomerGroup;
use App\InvoiceScheme;
use App\Media;
use App\PlateStock;
use App\Product;
use App\SellingPriceGroup;
use App\TaxRate;
use App\Transaction;
use App\TransactionExpense;
use App\TransactionPayment;
use App\TransactionPlateLine;
use App\TransactionPlateLinesReturn;
use App\TransactionReceipt;
use App\TransactionSellLine;
use App\TransactionSellLinesChange;
use App\TransactionShip;
use App\TypesOfService;
use App\Unit;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Variation;
use App\Warehouse;
use App\Warranty;
use App\MainHelpers;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class SellController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $contactUtil;
    protected $businessUtil;
    protected $transactionUtil;
    protected $productUtil;


    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ContactUtil $contactUtil, BusinessUtil $businessUtil, TransactionUtil $transactionUtil, ModuleUtil $moduleUtil, ProductUtil $productUtil)
    {
        $this->contactUtil = $contactUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->productUtil = $productUtil;

        $this->dummyPaymentLine = ['method' => '', 'amount' => 0, 'note' => '', 'card_transaction_number' => '', 'card_number' => '', 'card_type' => '', 'card_holder_name' => '', 'card_month' => '', 'card_year' => '', 'card_security' => '', 'cheque_number' => '', 'bank_account_number' => '',
            'is_return' => 0, 'transaction_no' => '', 'approval_status' => 'unapproved'];

        $this->shipping_status_colors = [
            'not_shipped' => 'bg-yellow',
            'shipped' => 'bg-green',
        ];

        $this->export_status_colors = [
            'none' => 'bg-red',
            'pending' => 'bg-yellow',
            'approved' => 'bg-green',
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('sell.create') && !auth()->user()->can('direct_sell.access') && !auth()->user()->can('view_own_sell_only') && !auth()->user()->can('stock.view_deliver_orders')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $is_woocommerce = $this->moduleUtil->isModuleInstalled('Woocommerce');
        $is_tables_enabled = $this->transactionUtil->isModuleEnabled('tables');
        $is_service_staff_enabled = $this->transactionUtil->isModuleEnabled('service_staff');
        $is_types_service_enabled = $this->moduleUtil->isModuleEnabled('types_of_service');

        if (request()->ajax()) {
            $payment_types = $this->transactionUtil->payment_types(null, true);
            $with = [];
            $shipping_statuses = $this->transactionUtil->shipping_statuses();
            $sells = $this->transactionUtil->getListSells($business_id);

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $sells->whereIn('transactions.location_id', $permitted_locations);
            }

            //Add condition for created_by,used in sales representative sales report
            if (request()->has('created_by')) {
                $created_by = request()->get('created_by');
                if (!empty($created_by)) {
                    $sells->where('transactions.created_by', $created_by);
                }
            }

            if (!auth()->user()->can('direct_sell.access') && auth()->user()->can('view_own_sell_only')) {
                $sells->where('transactions.created_by', request()->session()->get('user.id'));
            }

            if (!empty(request()->input('payment_status')) && request()->input('payment_status') != 'overdue') {
                $sells->where('transactions.payment_status', request()->input('payment_status'));
            } elseif (request()->input('payment_status') == 'overdue') {
                $sells->whereIn('transactions.payment_status', ['due', 'partial'])
                    ->whereNotNull('transactions.pay_term_number')
                    ->whereNotNull('transactions.pay_term_type')
                    ->whereRaw("IF(transactions.pay_term_type='days', DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number DAY) < CURDATE(), DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number MONTH) < CURDATE())");
            }

            //Add condition for location,used in sales representative expense report
            if (request()->has('location_id')) {
                $location_id = request()->get('location_id');
                if (!empty($location_id)) {
                    $sells->where('transactions.location_id', $location_id);
                }
            }

            if (!empty(request()->input('rewards_only')) && request()->input('rewards_only') == true) {
                $sells->where(function ($q) {
                    $q->whereNotNull('transactions.rp_earned')
                        ->orWhere('transactions.rp_redeemed', '>', 0);
                });
            }

            if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $sells->where('contacts.id', $customer_id);
            }

            if (!empty(request()->address)) {
                $address = request()->address;
                $sells->where('transactions.shipping_address', 'LIKE', '%'. $address .'%');
            }

            if (!empty(request()->phone)) {
                $phone = request()->phone;
                $sells->where('transactions.phone_contact', 'LIKE', '%'. $phone .'%');
            }

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $sells->whereDate('transactions.transaction_date', '>=', $start)
                    ->whereDate('transactions.transaction_date', '<=', $end);
            }

            //Check is_direct sell
            if (request()->has('is_direct_sale')) {
                $is_direct_sale = request()->is_direct_sale;
                if ($is_direct_sale == 0) {
                    $sells->where('transactions.is_direct_sale', 0);
                    $sells->whereNull('transactions.sub_type');
                }
            }

            //Add condition for commission_agent,used in sales representative sales with commission report
            if (request()->has('commission_agent')) {
                $commission_agent = request()->get('commission_agent');
                if (!empty($commission_agent)) {
                    $sells->where('transactions.commission_agent', $commission_agent);
                }
            }

            if ($is_woocommerce) {
                $sells->addSelect('transactions.woocommerce_order_id');
                if (request()->only_woocommerce_sells) {
                    $sells->whereNotNull('transactions.woocommerce_order_id');
                }
            }

            if (request()->only_subscriptions) {
                $sells->where(function ($q) {
                    $q->whereNotNull('transactions.recur_parent_id')
                        ->orWhere('transactions.is_recurring', 1);
                });
            }

            if (!empty(request()->list_for) && request()->list_for == 'service_staff_report') {
                $sells->whereNotNull('transactions.res_waiter_id');
            }

            if (!empty(request()->res_waiter_id)) {
                $sells->where('transactions.res_waiter_id', request()->res_waiter_id);
            }

            if (!empty(request()->input('sub_type'))) {
                $sells->where('transactions.sub_type', request()->input('sub_type'));
            }

            /*if (!empty(request()->input('created_by'))) {
                $sells->where('transactions.res_waiter_id', request()->input('created_by'));
            }*/

            if (!empty(request()->input('sales_cmsn_agnt'))) {
                $sells->where('transactions.commission_agent', request()->input('sales_cmsn_agnt'));
            }

            if (!empty(request()->input('service_staffs'))) {
                $transactionIds = TransactionShip::query()->where('ship_id', request()->input('service_staffs'))->pluck('transaction_id')->toArray();
                $sells->whereIn('transactions.id', $transactionIds);
            }
            $only_shipments = request()->only_shipments == 'true' ? true : false;
            if ($only_shipments && auth()->user()->can('shipping.update')) {
                $sells->whereNotNull('transactions.shipping_status');
            }

            if (!empty(request()->input('shipping_status'))) {
                $sells->where('transactions.shipping_status', request()->input('shipping_status'));
            }

            $sells->groupBy('transactions.id');

            if (!empty(request()->suspended)) {
                $transaction_sub_type = request()->get('transaction_sub_type');
                if (!empty($transaction_sub_type)) {
                    $sells->where('transactions.sub_type', $transaction_sub_type);
                } else {
                    $sells->where('transactions.sub_type', null);
                }

                $with = ['sell_lines'];

                if ($is_tables_enabled) {
                    $with[] = 'table';
                }

                if ($is_service_staff_enabled) {
                    $with[] = 'service_staff';
                }

                $sales = $sells->where('transactions.is_suspend', 1)
                    ->with($with)
                    ->addSelect('transactions.is_suspend', 'transactions.res_table_id', 'transactions.res_waiter_id', 'transactions.additional_notes')
                    ->get();

                return view('sale_pos.partials.suspended_sales_modal')->with(compact('sales', 'is_tables_enabled', 'is_service_staff_enabled', 'transaction_sub_type'));
            }

            $with[] = 'payment_lines';
            if (!empty($with)) {
                $sells->with($with);
            }

            //$business_details = $this->businessUtil->getDetails($business_id);
            if ($this->businessUtil->isModuleEnabled('subscription')) {
                $sells->addSelect('transactions.is_recurring', 'transactions.recur_parent_id');
            }

            $datatable = Datatables::of($sells)
                ->addColumn(
                    'action',
                    function ($row) use ($only_shipments) {
                        //Check if closed end of day
                        $current_date = date('Y-m-d');
                        $transaction_date = date('Y-m-d', strtotime($row->transaction_date));
                        if ($this->moduleUtil->isClosedEndOfDay() && $transaction_date <= $current_date) {
                            $is_closed = true;
                        }else{
                            $is_closed = false;
                        }

                        $html = '<div class="btn-group">
                                    <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                                        data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                        </span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-left" role="menu">' ;

                        if (auth()->user()->can("sell.view") || auth()->user()->can("direct_sell.access") || auth()->user()->can("view_own_sell_only") || auth()->user()->can('stock.view_deliver_orders')) {
                            $html .= '<li><a href="#" data-href="' . action("SellController@show", [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-eye" aria-hidden="true"></i> ' . __("messages.view") . '</a></li>';
                        }

                        if ($only_shipments) {
                            if(auth()->user()->can("shipping.update") && $row->shipping_status != "shipped"  && !$is_closed){
                                $shipping_text = $row->shipping_status == 'not_shipped' ? __("lang_v1.edit_shipping") : __("lang_v1.shipping");
                                $html .= '<li><a href="#" data-href="' . action('SellController@editShipping', [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-truck" aria-hidden="true"></i>' . $shipping_text . '</a></li>';
                            }

                            $html .= '<li><a href="#" class="print-invoice" data-href="' . route('sell.printShippingInvoice', [$row->id]) . '"><i class="fas fa-print" aria-hidden="true"></i> ' . __("lang_v1.print_shipping") . '</a></li>';
                            $html .= '<li><a href="#" class="print-invoice" data-href="' . route('sell.printShippingInvoiceWithoutHeader', [$row->id]) . '"><i class="fas fa-print" aria-hidden="true"></i> ' . __("lang_v1.print_shipping_without_header") . '</a></li>';
                        }
                        if (!$only_shipments && $row->export_status == 'none' && $row->status != 'cancel') {
                            if ($row->is_direct_sale == 0) {
                                if (auth()->user()->can("sell.update") && !$is_closed) {
                                    $html .= '<li><a class="sell_update" target="_blank" href="' . action('SellPosController@edit', [$row->id]) . '"><i class="fas fa-edit"></i> ' . __("messages.edit") . '</a></li>';
                                }
                            } else {
                                if (auth()->user()->can("direct_sell.access") && !$is_closed) {
                                    $html .= '<li><a class="sell_update" href="' . action('SellController@edit', [$row->id]) . '"><i class="fas fa-edit"></i> ' . __("messages.edit") . '</a></li>';
                                }
                            }

                            if (auth()->user()->can("sell.cancel") && !$is_closed) {
                                $html .= '<li><a class="cancel_sell" data-href="' . action('SellPosController@cancel', [$row->id]) . '"><i class="fa fa-power-off"></i> ' . __("messages.cancel") . '</a></li>';
                            }
//                            if (auth()->user()->can("direct_sell.delete") || auth()->user()->can("sell.delete")) {
//                                $html .= '<li><a href="' . action('SellPosController@destroy', [$row->id]) . '" class="delete-sale"><i class="fas fa-trash"></i> ' . __("messages.delete") . '</a></li>';
//                            }
                        }

                        if (!$only_shipments) {
                            if ($row->export_status == 'approved' && auth()->user()->can("sell.create") && $row->cod_approved != 'approved' && !$is_closed) {
                                $html .= '<li><a href="#" data-href="' . action("SellController@editCodBySeller", [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-money-bill-alt"></i> ' . __("lang_v1.update_cod") . '</a></li>';
                            }

                            if (auth()->user()->can("sell.view") || auth()->user()->can("direct_sell.access") || auth()->user()->can('stock.view_deliver_orders')) {
                                $html .= '<li><a href="#" class="print-invoice" data-href="' . route('sell.printInvoice', [$row->id]) . '"><i class="fas fa-print" aria-hidden="true"></i> ' . __("messages.print_sell_order") . '</a></li>';
                            }

                            if (auth()->user()->can("sell.create") || auth()->user()->can("sell.create_return_bill")) {
                                if($row->export_status == 'approved') {
                                    $sell_return = TransactionPlateLinesReturn::where('transaction_id', $row->id)->first();
                                    if (!$sell_return) {
                                        $html .= '<li class="divider"></li>';
                                        $html .= '<li><a href="' . action('SellReturnController@add', [$row->id]) . '"><i class="fas fa-undo"></i> ' . __("lang_v1.sell_return") . '</a></li>';
                                    }
//                                $html .= '<li><a href="' . action('SellController@duplicateSell', [$row->id]) . '"><i class="fas fa-copy"></i> ' . __("lang_v1.duplicate_sell") . '</a></li>';
                                }
                            }
                        }

                        $html .= '</ul></div>';

                        return $html;
                    }
                )
                ->removeColumn('id')
                ->editColumn('shipping_status', function ($row) use ($shipping_statuses) {
                    $status_color = !empty($this->shipping_status_colors[$row->shipping_status]) ? $this->shipping_status_colors[$row->shipping_status] : 'bg-gray';
                    $status = !empty($row->shipping_status) ? '<a href="#" class="btn-modal" data-href="' . action('SellController@editShipping', [$row->id]) . '" data-container=".view_modal"><span class="label ' . $status_color .'">' . $shipping_statuses[$row->shipping_status] . '</span></a>' : '';

                    return $status;
                })
                ->editColumn('name', function ($row) {
                    return '<a href="' . action("ContactController@show", [$row->customer_id]) . '" target="_blank">' . $row->name . '</a>';
                })
                ->editColumn('final_total', function ($row) {
                    $final_total = $row->final_total;

                    if ($row->status == 'cancel') {
                        $final_total = 0;
                    }

                    $html = '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="'. $final_total .'">'. $final_total .'</span>';

                    return $html;
                })
                ->editColumn('total_paid', function ($row) {
                    return '<span class="display_currency total-paid" data-currency_symbol="true" data-orig-value="' . $row->total_paid . '">' . $row->total_paid . '</span>';
                })
                ->editColumn(
                    'deposit',
                    function ($row) {
                        if($row->deposit > 0){
                            $approval_statuses = $this->productUtil->approvalStatuses();
                            $approval_status_colors = $this->productUtil->approvalStatusColors();
                            $link_title = $approval_statuses[$row->deposit_approved];
                            $link_class = $approval_status_colors[$row->deposit_approved];
                            $icon_class = $row->deposit_approved == 'approved' ? 'fas fa-check' : 'fas fa-times';

                            if(in_array($row->deposit_approved, ['pending', 'unapproved'])){
                                $html = '
                                    <button type="button" class="approve_payment btn btn-xs '. $link_class .'" data-toggle="tooltip" title="'. $link_title .'" data-href="' . action('TransactionPaymentController@editDeposit', [$row->deposit_id]) . '">
                                        <i class="'.$icon_class.'"></i> <span class="display_currency total_deposit" data-currency_symbol="true" data-orig-value="' . $row->deposit . '">' . $row->deposit . '</span>
                                    </button>';
                            }else{
                                $html = '<i class="'.$icon_class.'"></i> <span class="display_currency total_deposit" data-currency_symbol="true" data-orig-value="'. $row->deposit .'">'. $row->deposit .'</span>';
                            };
                        }else{
                            $html = '';
                        }

                        return $html;
                    }
                )
                ->editColumn(
                    'cod',
                    function ($row) {
                        if($row->cod > 0){
                            $approval_statuses = $this->productUtil->approvalStatuses();
                            $approval_status_colors = $this->productUtil->approvalStatusColors();
                            $link_title = $approval_statuses[$row->cod_approved];
                            $link_class = $approval_status_colors[$row->cod_approved];
                            $icon_class = $row->cod_approved == 'approved' ? 'fas fa-check' : 'fas fa-times';

                            if(in_array($row->cod_approved, ['pending', 'unapproved'])){
                                $html = '
                                    <button type="button" class="approve_payment btn btn-xs '. $link_class .'" data-toggle="tooltip" title="'. $link_title .'" data-href="' . action('TransactionPaymentController@editCod', [$row->cod_id]) . '">
                                        <i class="'.$icon_class.'"></i> <span class="display_currency total_cod" data-currency_symbol="true" data-orig-value="' . $row->cod .'">' . $row->cod . '</span>
                                    </button>';
                            }else{
                                $html = '<i class="'.$icon_class.'"></i> <span class="display_currency total_cod" data-currency_symbol="true" data-orig-value="' . $row->cod . '">' . $row->cod . '</span>';
                            };
                        }else{
                            $html = '';
                        }

                        return $html;
                    }
                )
                ->addColumn('total_remaining', function ($row) {
                    $total_remaining = $row->final_total - $row->total_paid;
                    $approval_statuses = $this->productUtil->approvalStatuses();
                    $approval_status_colors = $this->productUtil->approvalStatusColors();
                    $link_title = $approval_statuses[$row->total_remaining_approved];
                    $link_class = $approval_status_colors[$row->total_remaining_approved];
                    $icon_class = $row->total_remaining_approved == 'approved' ? 'fas fa-check' : 'fas fa-times';

                    if(in_array($row->total_remaining_approved, ['pending', 'unapproved']) && $row->status != "cancel"){
                        $html = '
                            <button type="button" class="add_remaining_payment btn btn-xs '. $link_class .'" data-toggle="tooltip" title="'. $link_title .'" href="' . action('TransactionPaymentController@addRemaining', [$row->id]) . '">
                                <i class="'.$icon_class.'"></i> <span class="display_currency payment_due" data-currency_symbol="true" data-orig-value="'. $total_remaining .'">'. $this->transactionUtil->num_f($total_remaining) .'</span>
                            </button>';
                    }else{
                        $html = '<i class="'.$icon_class.'"></i> <span class="display_currency payment_due" data-currency_symbol="true" data-orig-value="'. $total_remaining .'">'. $this->transactionUtil->num_f($total_remaining) .'</span>';
                    }

                    if ($row->status == "cancel") {
                        $html = '<span class="display_currency payment_due" data-currency_symbol="true" data-orig-value="0">0</span>';
                    }

                    return $html;
                })
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn(
                    'payment_status',
                    function ($row) {
                        $payment_status = Transaction::getPaymentStatus($row);
                        $total_remaining = $row->total_final - $row->total_paid;
                        if($total_remaining >= 0){
                            $payment_approved = 0;
                        }else{
                            $payment_approved = $row->payment_approved;
                        }
                        return (string) view('sell.partials.payment_status', ['payment_status' => $payment_status, 'payment_approved' => $payment_approved, 'id' => $row->id]);
                    }
                )
                ->editColumn('invoice_no', function ($row) {
                    $invoice_no = $row->invoice_no;
                    if (!empty($row->woocommerce_order_id)) {
                        $invoice_no .= ' <i class="fab fa-wordpress text-primary no-print" title="' . __('lang_v1.synced_from_woocommerce') . '"></i>';
                    }
                    if (!empty($row->return_exists)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-red label-round no-print" title="' . __('lang_v1.some_qty_returned_from_sell') .'"><i class="fas fa-undo"></i></small>';
                    }
                    if (!empty($row->is_recurring)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-red label-round no-print" title="' . __('lang_v1.subscribed_invoice') .'"><i class="fas fa-recycle"></i></small>';
                    }

                    if (!empty($row->recur_parent_id)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-info label-round no-print" title="' . __('lang_v1.subscription_invoice') .'"><i class="fas fa-recycle"></i></small>';
                    }
                    if (!empty($row->status == 'cancel')) {
                        $invoice_no .= ' &nbsp;<small class="label bg-yellow label-round no-print" title="' . __('lang_v1.sell_cancel_message') .'"><i class="fas fa-power-off"></i></small>';
                    }

                    return $invoice_no;
                })
                /*->editColumn('shipping_status', function ($row) use ($shipping_statuses) {
                    $status_color = !empty($this->shipping_status_colors[$row->shipping_status]) ? $this->shipping_status_colors[$row->shipping_status] : 'bg-gray';
                    $status = !empty($row->shipping_status) ? '<a href="#" class="btn-modal" data-href="' . action('SellController@editShipping', [$row->id]) . '" data-container=".view_modal"><span class="label ' . $status_color .'">' . $shipping_statuses[$row->shipping_status] . '</span></a>' : '';

                    return $status;
                })*/
                ->editColumn('shipper', function ($row){
                    $arr = array_unique(explode(',', $row->shipper));
                    return implode(', ', $arr);
                })
                ->filterColumn('shipper', function ($query, $keyword) {
                    $query->whereRaw("CONCAT(ss.first_name,' ',ss.last_name) like ?", ["%{$keyword}%"]);
                })
                ->filterColumn('total_paid', function ($query, $keyword) {
                    $query->whereRaw("(SELECT SUM(IF(TP.is_return = 1,-1*TP.amount,TP.amount)) FROM transaction_payments AS TP WHERE
                        TP.transaction_id=transactions.id AND TP.approval_status <> 'reject') like ?", ["%{$keyword}%"]);
                })
                ->filterColumn('deposit', function ($query, $keyword) {
                    $query->whereRaw("(SELECT IF(TP.is_return = 1,-1*TP.amount,TP.amount) FROM transaction_payments AS TP WHERE
                        TP.transaction_id=transactions.id AND TP.type = 'deposit' AND TP.approval_status <> 'reject' ORDER BY TP.id LIMIT 1) like ?", ["%{$keyword}%"]);
                })
                ->filterColumn('cod', function ($query, $keyword) {
                    $query->whereRaw("(SELECT IF(TP.is_return = 1,-1*TP.amount,TP.amount) FROM transaction_payments AS TP WHERE
                        TP.transaction_id=transactions.id AND TP.type = 'cod' AND TP.approval_status <> 'reject' ORDER BY TP.id LIMIT 1) like ?", ["%{$keyword}%"]);
                })
                ->filterColumn('total_remaining', function ($query, $keyword) {
                    $query->whereRaw("(transactions.final_total - (SELECT SUM(IF(TP.is_return = 1,-1*TP.amount,TP.amount)) FROM transaction_payments AS TP WHERE
                        TP.transaction_id=transactions.id AND TP.approval_status <> 'reject')) like ?", ["%{$keyword}%"]);
                })
                ->orderColumn('total_remaining', function ($query, $order) {
                    $query->orderByRaw('(transactions.final_total - total_paid) '.$order);
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("sell.view") || auth()->user()->can("view_own_sell_only") || auth()->user()->can('stock.view_deliver_orders')) {
                            return  action('SellController@show', [$row->id]) ;
                        } else {
                            return '';
                        }
                    }]);

            $rawColumns = ['final_total', 'cod', 'deposit', 'action', 'total_paid', 'total_remaining', 'payment_status', 'invoice_no', 'discount_amount', 'tax_amount', 'total_before_tax', 'shipping_status', 'types_of_service_name', 'payment_methods', 'return_due', 'name'];

            return $datatable->rawColumns($rawColumns)
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);
        $sales_representative = User::forDropdown($business_id, false, false, true);

        //Commission agent filter
        $is_cmsn_agent_enabled = request()->session()->get('business.sales_cmsn_agnt');
        $commission_agents = [];
        if (!empty($is_cmsn_agent_enabled)) {
            $commission_agents = User::forDropdown($business_id, false, true, true);
        }

        //Service staff filter
        $service_staffs = null;
        if ($this->productUtil->isModuleEnabled('service_staff')) {
            $service_staffs = $this->productUtil->serviceStaffDropdown($business_id);
        }

        $users = Contact::customersDropdown($business_id);

        $payment_line = $this->dummyPaymentLine;
        $payment_types = $this->transactionUtil->payment_types(null, true);

        return view('sell.index')
            ->with(compact('business_locations',
                'customers',
                'is_woocommerce',
                'sales_representative',
                'is_cmsn_agent_enabled',
                'commission_agents',
                'service_staffs',
                'is_tables_enabled',
                'is_service_staff_enabled',
                'is_types_service_enabled',
                'users',
                'payment_line',
                'payment_types'
            ));
    }

    public function getPlateStock(Request $request)
    {
        /*if (!auth()->user()->can('direct_sell.access') && !auth()->user()->can('report.input_output_inventory')) {
            abort(403, 'Unauthorized action.');
        }*/

        //Return the details in ajax call
        if ($request->ajax()) {
            $business_id = $request->session()->get('user.business_id');

            $query = PlateStock::leftjoin('products as p', 'plate_stocks.product_id', '=', 'p.id')
                ->leftjoin('variations', 'plate_stocks.variation_id', '=', 'variations.id')
                ->leftjoin('products', 'variations.product_id', '=', 'products.id')
                ->leftjoin('business_locations as l', 'plate_stocks.location_id', '=', 'l.id')
                ->leftjoin('units', 'products.unit_id', '=', 'units.id')
                ->where('l.is_active', BusinessLocation::ACTIVE)
                ->where('p.business_id', $business_id)
                ->where('plate_stocks.qty_available', '>', 0)
                ->where('plate_stocks.width', '>', 0)
                ->where('plate_stocks.height', '>', 0)
                ->whereIn('p.type', ['single', 'variable']);

//            $layout = request()->get('layout');

            //Filter by location
            $location_id = request()->get('location_id', null);
            $permitted_locations = auth()->user()->permitted_locations();

            if (!empty($location_id)) {
                if ($permitted_locations == 'all' || in_array($location_id, $permitted_locations)) {
                    $query->where('plate_stocks.location_id', $location_id);
                }else{
                    $query->whereNull('plate_stocks.location_id');
                }
            } else {
                if ($permitted_locations != 'all') {
                    $query->whereIn('plate_stocks.location_id', $permitted_locations);
                }
            }

            //Filter by warehouse
            $warehouse_id = request()->get('warehouse_id', null);
            $permitted_warehouses = auth()->user()->getPermittedWarehouses();

            if (!empty($warehouse_id)) {
                if ($permitted_warehouses == 'all' || in_array($warehouse_id, $permitted_warehouses)) {
                    $query->where('plate_stocks.warehouse_id', $warehouse_id);
                }else{
                    $query->whereNull('plate_stocks.warehouse_id');
                }
            } else {
                if ($permitted_warehouses != 'all') {
                    $query->whereIn('plate_stocks.warehouse_id', $permitted_warehouses);
                }
            }

            if (!empty($request->input('category_id'))) {
                $query->where('p.category_id', $request->input('category_id'));
            }

            if (!empty($request->input('variation_id'))) {
                $query->where('variations.id', $request->input('variation_id'));
            }
            if (!empty($request->input('width'))) {
                $query->whereRaw("CAST(plate_stocks.width AS DECIMAL(10,3)) >= ".floatval($request->input('width')));
            }
            if (!empty($request->input('height'))) {
                $query->whereRaw("CAST(plate_stocks.height AS DECIMAL(10,3)) >= ".floatval($request->input('height')));
            }

            $expect_stock_where_query = 'plate_stock_drafts.variation_id = plate_stocks.variation_id AND plate_stock_drafts.width = plate_stocks.width AND plate_stock_drafts.height = plate_stocks.height AND plate_stock_drafts.is_origin = plate_stocks.is_origin';

            $products = $query->select(
                'units.type as unit_type',
                DB::raw('IF(units.type="area", "'. __('unit.roll') .'", IF(units.type="meter", "'. __('unit.plate') .'", units.actual_name)) AS unit_name'),
                'plate_stocks.location_id',
                DB::raw("SUM(plate_stocks.qty_available) as stock"),
                DB::raw("IF((SELECT COUNT(1) FROM plate_stock_drafts WHERE ". $expect_stock_where_query .") > 0, SUM(plate_stocks.qty_available) + (SELECT SUM(plate_stock_drafts.qty_available) FROM plate_stock_drafts WHERE ". $expect_stock_where_query ."), SUM(plate_stocks.qty_available)) as expect_stock"),
                'variations.sub_sku as sku',
                DB::raw('IF(p.type = "variable", CONCAT(p.name, " - ", variations.name, " (", variations.sub_sku, ")"), CONCAT(p.name, " (", variations.sub_sku, ")")) as product'),
                'p.name',
                'variations.id as variation_id',
                'p.id as product_id',
                'plate_stocks.width',
                'plate_stocks.height',
                'plate_stocks.is_origin')
                ->groupBy('variations.id')
                ->groupBy('plate_stocks.width')
                ->groupBy('plate_stocks.height')
                ->groupBy('plate_stocks.is_origin')
                ->orderBy('plate_stocks.width', 'ASC');

            $datatable =  Datatables::of($products)
                ->addColumn('action',
                    '<button type="button" class="btn btn-xs btn-info view_detail"><i class="fa fa-eye"></i></button>')
                ->editColumn('width', function ($row) {
                    if(in_array($row->unit_type, ['area', 'meter'])){
                        $html = $row->width.' m';
                    }else{
                        $html = '';
                    }
                    return $html;
                })
                ->editColumn('height', function ($row) {
                    if($row->unit_type == 'area'){
                        $html = $row->height.' m';
                    }else{
                        $html = '';
                    }
                    return $html;
                })
                ->editColumn('product', function ($row) {
                    $name = $row->product;
                    if ($row->type == 'variable') {
                        $name .= ' - ' . $row->product_variation . '-' . $row->variation_name;
                    }
                    return $name;
                })
                ->editColumn('stock', function ($row) {
                    $html = number_format($row->stock).' '.$row->unit_name;
                    return $html;
                })
                ->editColumn('expect_stock', function ($row) {
                    $style = $row->expect_stock != $row->stock ? 'style="color: orangered"' : '';
                    $html = '<span '. $style .'>'. number_format($row->expect_stock).' '.$row->unit_name. '</span>';
                    return $html;
                })
                ->editColumn('is_origin', function ($row) {
                    if($row->is_origin){
                        $html = __('sale.origin_plate');
                    }else{
                        $html = '';
                    }
                    return $html;
                })
                ->addColumn('warehouses', function ($row) {
                    $html = '';
                    $warehouses = PlateStock::leftjoin('warehouses', 'plate_stocks.warehouse_id', '=', 'warehouses.id')
                        ->leftjoin('products as p', 'plate_stocks.product_id', '=', 'p.id')
                        ->where('plate_stocks.location_id', $row->location_id)
                        ->where('plate_stocks.variation_id', $row->variation_id)
                        ->where('plate_stocks.width', $row->width)
                        ->where('height', $row->height)
                        ->where('is_origin', $row->is_origin)
                        ->where('plate_stocks.qty_available', '>', 0)
                        ->whereIn('p.type', ['single', 'variable'])
                        ->select('warehouses.name')
                        ->groupBy('plate_stocks.warehouse_id')
                        ->get();

                    foreach($warehouses as $key => $warehouse){
                        if($key != 0){
                            $html .= ', ';
                        }
                        $html .= $warehouse->name;
                    }

                    return $html;
                })
                ->removeColumn('unit')
                ->removeColumn('id');

            $raw_columns  = ['stock', 'action', 'expect_stock'];

            return $datatable->rawColumns($raw_columns)->make(true);
        }
    }

    public function getPlateStockDetail(Request $request, $variation_id){
        /*if (!auth()->user()->can('direct_sell.access') && !auth()->user()->can('report.input_output_inventory')) {
            abort(403, 'Unauthorized action.');
        }*/

        $business_id = $request->session()->get('user.business_id');
        $is_origin = $request->input('is_origin');
        if(!empty($is_origin)){
            $is_origin = true;
        }else{
            $is_origin = false;
        }

        $query = PlateStock::query()
            ->leftjoin('products as p', 'plate_stocks.product_id', '=', 'p.id')
            ->leftjoin('units', 'p.unit_id', '=', 'units.id')
            ->leftjoin('variations', 'plate_stocks.variation_id', '=', 'variations.id')
            ->leftjoin('business_locations as l', 'plate_stocks.location_id', '=', 'l.id')
            ->leftjoin('warehouses', 'plate_stocks.warehouse_id', '=', 'warehouses.id')
            ->where('p.business_id', $business_id)
            ->where('l.is_active', BusinessLocation::ACTIVE)
            ->where('plate_stocks.variation_id', $variation_id)
            ->where('plate_stocks.qty_available', '>', 0)
            ->where('plate_stocks.width', '>', 0)
            ->where('plate_stocks.height', '>', 0)
            ->where('plate_stocks.is_origin', $is_origin)
            ->whereIn('p.type', ['single', 'variable']);

        //Filter by location
        $location_id = request()->get('location_id', null);
        $permitted_locations = auth()->user()->permitted_locations();

        if (!empty($location_id)) {
            if ($permitted_locations == 'all' || in_array($location_id, $permitted_locations)) {
                $query->where('plate_stocks.location_id', $location_id);
            }else{
                $query->whereNull('plate_stocks.location_id');
            }
        } else {
            if ($permitted_locations != 'all') {
                $query->whereIn('plate_stocks.location_id', $permitted_locations);
            }
        }

        //Filter by warehouse
        $warehouse_id = request()->get('warehouse_id');
        $permitted_warehouses = auth()->user()->getPermittedWarehouses();

        if (!empty($warehouse_id)) {
            if ($permitted_warehouses == 'all' || in_array($warehouse_id, $permitted_warehouses)) {
                $query->where('plate_stocks.warehouse_id', $warehouse_id);
            }else{
                $query->whereNull('plate_stocks.warehouse_id');
            }
        } else {
            if ($permitted_warehouses != 'all') {
                $query->whereIn('plate_stocks.warehouse_id', $permitted_warehouses);
            }
        }

        if (!empty($request->input('category_id'))) {
            $query->where('p.category_id', $request->input('category_id'));
        }
        if (!empty($request->input('variation_id'))) {
            $query->where('variations.id', $request->input('variation_id'));
        }
        if (!empty($request->input('width'))) {
            $query->whereRaw("CAST(plate_stocks.width AS DECIMAL(10,3)) = ".floatval($request->input('width')));
        }
        if (!empty($request->input('height'))) {
            $query->whereRaw("CAST(plate_stocks.height AS DECIMAL(10,3)) = ".floatval($request->input('height')));
        }
        if (!empty($request->input('category_id'))) {
            $query->where('p.category_id', $request->input('category_id'));
        }

        $expect_stock_where_query = 'plate_stock_drafts.variation_id = plate_stocks.variation_id AND plate_stock_drafts.width = plate_stocks.width AND plate_stock_drafts.height = plate_stocks.height AND plate_stock_drafts.is_origin = plate_stocks.is_origin AND plate_stock_drafts.location_id = plate_stocks.location_id AND plate_stock_drafts.warehouse_id = plate_stocks.warehouse_id';

        $plate_stocks = $query->select(
            'units.type as unit_type',
            'plate_stocks.id',
            'l.name as location',
            'warehouses.name as warehouse',
            DB::raw('SUM(plate_stocks.qty_available) as stock'),
            DB::raw("IF((SELECT COUNT(1) FROM plate_stock_drafts WHERE ". $expect_stock_where_query .") > 0, SUM(plate_stocks.qty_available) + (SELECT SUM(plate_stock_drafts.qty_available) FROM plate_stock_drafts WHERE ". $expect_stock_where_query ."), SUM(plate_stocks.qty_available)) as expect_stock"),
            DB::raw('IF(units.type="area", "'. __('unit.roll') .'", IF(units.type="meter", "'. __('unit.plate') .'", units.actual_name)) AS unit_name')
        )
            ->groupBy('plate_stocks.id')
            ->get();

        $layout = $request->input('layout');

        if($layout == 'stock_deliver'){
            $view = 'stock_deliver.partials.plate_stock_detail';
        }elseif($layout == 'stock_transfer'){
            $view = 'stock_transfer.partials.plate_stock_detail';
        }elseif($layout == 'stock_adjustment'){
            $view = 'stock_adjustment.partials.plate_stock_detail';
        }elseif($layout == 'stock_report'){
            $view = 'report.partials.plate_stock_detail';
        }else{
            $view = 'sale_pos.partials.plate_stock_detail';
        }

        return view($view)
            ->with(compact('plate_stocks'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('direct_sell.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not, then check for users quota
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        } elseif (!$this->moduleUtil->isQuotaAvailable('invoices', $business_id)) {
            return $this->moduleUtil->quotaExpiredResponse('invoices', $business_id, action('SellController@index'));
        }

        $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);

        $business_details = $this->businessUtil->getDetails($business_id);
        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);

        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];

        $default_location = null;
        if (count($business_locations) == 1) {
            foreach ($business_locations as $id => $name) {
                $default_location = BusinessLocation::findOrFail($id);
            }
        }

        $commsn_agnt_setting = $business_details->sales_cmsn_agnt;
        $commission_agent = [];
        if ($commsn_agnt_setting == 'user') {
            $commission_agent = User::forDropdown($business_id);
        } elseif ($commsn_agnt_setting == 'cmsn_agnt') {
            $commission_agent = User::saleCommissionAgentsDropdown($business_id);
        }

        $types = [];
        if (auth()->user()->can('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create')) {
            $types['customer'] = __('report.customer');
        }
        if (auth()->user()->can('supplier.create') && auth()->user()->can('customer.create')) {
            $types['both'] = __('lang_v1.both_supplier_customer');
        }
        $customer_groups = CustomerGroup::forDropdown($business_id);

        $payment_line = $this->dummyPaymentLine;
        $payment_types = $this->transactionUtil->payment_types(null, true);

        $cod_line = $this->dummyPaymentLine;

        //Selling Price Group Dropdown
        $price_groups = SellingPriceGroup::forDropdown($business_id);

        $default_datetime = $this->businessUtil->format_date('now', true);

        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

        $invoice_schemes = InvoiceScheme::forDropdown($business_id);
        $default_invoice_schemes = InvoiceScheme::getDefault($business_id);
        $shipping_statuses = $this->transactionUtil->shipping_statuses();

        //Types of service
        $types_of_service = [];
        if ($this->moduleUtil->isModuleEnabled('types_of_service')) {
            $types_of_service = TypesOfService::forDropdown($business_id);
        }

        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }

        $default_unit = Unit::where('type', 'area')->where('is_default', true)->first();
        $default_unit_id = $default_unit->id;

        $categories = Category::forDropdown($business_id, 'product');

        $products = Variation::leftjoin('products', 'variations.product_id', '=', 'products.id')
            ->select([
                DB::raw('IF(products.type = "variable", CONCAT(products.name, " - ", variations.name, " (", variations.sub_sku, ")"), CONCAT(products.name, " (", variations.sub_sku, ")")) as product'),
                'variations.id'
            ])->pluck('product', 'variations.id')
            ->toArray();

        $permitted_warehouses = auth()->user()->getPermittedWarehouses();
        if ($permitted_warehouses != 'all') {
            $warehouses = Warehouse::forDropdown($business_id, false, [], $permitted_warehouses);
        }else{
            $warehouses = Warehouse::forDropdown($business_id);
        }

        $selling_price_groups = SellingPriceGroup::forDropdown($business_id, true);

        //Set transaction date
        if ($this->moduleUtil->isClosedEndOfDay()) {
            $transaction_date = date('Y-m-d', strtotime('now +1 days'));
            $transaction_date .= ' 00:00';
        }else{
            $transaction_date = date('Y-m-d H:i:s');
        }

        return view('sell.create')
            ->with(compact(
                'business_details',
                'taxes',
                'walk_in_customer',
                'business_locations',
                'bl_attributes',
                'default_location',
                'commission_agent',
                'types',
                'customer_groups',
                'payment_line',
                'payment_types',
                'cod_line',
                'price_groups',
                'default_datetime',
                'pos_settings',
                'invoice_schemes',
                'default_invoice_schemes',
                'types_of_service',
                'accounts',
                'shipping_statuses',
                'default_unit_id',
                'warehouses',
                'categories',
                'products',
                'selling_price_groups',
                'transaction_date'
            ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('sell.receipt_expense')  && !auth()->user()->can('direct_sell.access') && !auth()->user()->can('view_own_sell_only') && !auth()->user()->can('stock.view_deliver_orders')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $taxes = TaxRate::where('business_id', $business_id)
            ->pluck('name', 'id');
        $query = Transaction::where('business_id', $business_id)
            ->where('id', $id)
            ->with([
                'contact',
                'media',
                'sell_lines.plate_lines',
                'sell_lines.plate_lines.selected_plate_stock',
                'sell_lines.plate_lines.selected_plate_stock.product',
                'sell_lines.plate_lines.selected_plate_stock.variation',
                'sell_lines.plate_lines.selected_plate_stock.warehouse',
                'sell_lines.product',
                'sell_lines.product.unit',
                'sell_lines.product.brand',
                'sell_lines.variations',
                'sell_lines.variations.product_variation',
                'payment_lines',
                'sell_lines.modifiers',
                'sell_lines.lot_details',
                'tax',
                'sell_lines.sub_unit',
                'table',
                'service_staff',
                'sell_lines.service_staff',
                'types_of_service',
                'sell_lines.warranties',
                'sell_lines' => function ($q) {
                    $q->whereNull('parent_sell_line_id');
                }
            ]);

        if (!auth()->user()->can('sell.view') && !auth()->user()->can('direct_sell.access') && auth()->user()->can('view_own_sell_only')) {
            $query->where('transactions.created_by', request()->session()->get('user.id'));
        }

        $sell = $query->firstOrFail();
        $exportSell = $sell->toArray()['sell_lines'];
        $dataSell = [];
        foreach ($exportSell as $key => $exSell){
            if(!isset($exSell['flag_changed']) || $exSell['flag_changed'] == '0'){
                $dataSell[] = $exSell;
                continue;
            }

            $tempSell = TransactionSellLinesChange::where('parent_id', $exSell['id'])
                ->with([
                    'product',
                    'product.unit',
                    'variations',
                    'variations.product_variation',
                    'modifiers',
                    'lot_details',
                    'sub_unit',
                    'service_staff',
                    'warranties',
                ])->where(function ($q) {
                    $q->whereNull('parent_sell_line_id');
                })->first();

            if($tempSell == null){
                $dataSell[] = $exSell;
                continue;
            }

            $dataSell[] = $tempSell->toArray();
        }
        $dataSell = json_decode(json_encode($dataSell), false);
        $sell->sell_lines = $dataSell;

        foreach ($sell->sell_lines as $key => $value) {
            if (!empty($value->sub_unit_id)) {
                $formated_sell_line = $this->transactionUtil->recalculateSellLineTotals($business_id, $sell->sell_lines[$key]);
//                $sell->sell_lines[$key] = $formated_sell_line;
            }
        }

        $payment_types = $this->transactionUtil->payment_types(null, true);

        $order_taxes = [];
        if (!empty($sell->tax)) {
            if ($sell->tax->is_tax_group) {
                $order_taxes = $this->transactionUtil->sumGroupTaxDetails($this->transactionUtil->groupTaxDetails($sell->tax, $sell->tax_amount));
            } else {
                $order_taxes[$sell->tax->name] = $sell->tax_amount;
            }
        }

        $business_details = $this->businessUtil->getDetails($business_id);
        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);
        $shipping_statuses = $this->transactionUtil->shipping_statuses();
        $shipping_status_colors = $this->shipping_status_colors;
        $common_settings = session()->get('business.common_settings');
        $is_warranty_enabled = !empty($common_settings['enable_product_warranty']) ? true : false;
        $approval_statuses = $this->productUtil->approvalStatuses();
        $shippers = TransactionShip::query()->where('transaction_id', $sell->id)
            ->leftJoin('users', 'users.id', '=', 'transaction_ships.ship_id')
            ->select([ DB::raw("GROUP_CONCAT(users.first_name,' ', users.last_name) AS shipper"),])
            ->groupBy('transaction_id')
            ->first();

        return view('sale_pos.show')
            ->with(compact(
                'taxes',
                'sell',
                'payment_types',
                'order_taxes',
                'pos_settings',
                'shipping_statuses',
                'shipping_status_colors',
                'is_warranty_enabled',
                'approval_statuses',
                'shippers'
            ));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('direct_sell.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $transaction = Transaction::where('business_id', $business_id)
            ->with(['price_group', 'types_of_service', 'media'])
            ->where('type', 'sell')
            ->findorfail($id);

        //Check if the transaction can be edited or not.
        $edit_days = request()->session()->get('business.transaction_edit_days');
        if ($transaction->status != 'draft' && !$this->transactionUtil->canBeEdited($id, $edit_days)) {
            return back()
                ->with('status', ['success' => 0,
                    'msg' => __('messages.transaction_edit_not_allowed', ['days' => $edit_days])]);
        }

        //Check if closed end of day
        $current_date = date('Y-m-d');
        $transaction_date = date('Y-m-d', strtotime($transaction->transaction_date));
        if ($transaction->status != 'draft' && $this->moduleUtil->isClosedEndOfDay() && $transaction_date <= $current_date) {
            $output = ['success' => 0,
                'msg' => __('messages.can_not_update_after_closed_app')
            ];

            return redirect('sells')->with('status', $output);
        }

        //Check if return exist then not allowed
        if ($this->transactionUtil->isReturnExist($id)) {
            return back()->with('status', ['success' => 0,
                'msg' => __('lang_v1.return_exist')]);
        }

        //Check export status
        if(in_array($transaction->export_status, ['pending', 'approved'])){
            $output = ['success' => 0,
                'msg' => __('sale.sell_have_been_export')
            ];
            return redirect('sells')->with('status', $output);
        }

        $business_details = $this->businessUtil->getDetails($business_id);
        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);

        $location_id = $transaction->location_id;
        $location_printer_type = BusinessLocation::find($location_id)->receipt_printer_type;

        $sell_details = TransactionSellLine::
        join(
            'products AS p',
            'transaction_sell_lines.product_id',
            '=',
            'p.id'
        )
            ->join(
                'variations AS variations',
                'transaction_sell_lines.variation_id',
                '=',
                'variations.id'
            )
            ->join(
                'product_variations AS pv',
                'variations.product_variation_id',
                '=',
                'pv.id'
            )
            ->leftjoin('variation_location_details AS vld', function ($join) use ($location_id) {
                $join->on('variations.id', '=', 'vld.variation_id')
                    ->where('vld.location_id', '=', $location_id);
            })
            ->leftjoin('units', 'transaction_sell_lines.sub_unit_id', '=', 'units.id')
            ->where('transaction_sell_lines.transaction_id', $id)
            ->with(['warranties'])
            ->select(
                DB::raw("IF(pv.is_dummy = 0, CONCAT(p.name, ' (', pv.name, ':',variations.name, ')'), p.name) AS product_name"),
                'p.id as product_id',
                'p.sub_unit_ids',
                'p.weight',
                'p.enable_stock',
                'p.name as product_actual_name',
                'p.type as product_type',
                'pv.name as product_variation_name',
                'pv.is_dummy as is_dummy',
                'variations.name as variation_name',
                'variations.sub_sku',
                'p.barcode_type',
                'p.enable_sr_no',
                'variations.id as variation_id',
                'units.actual_name as unit',
                'units.type as unit_type',
                'units.base_unit_id',
                'units.base_unit_multiplier',
                'units.allow_decimal as unit_allow_decimal',
                'transaction_sell_lines.tax_id as tax_id',
                'transaction_sell_lines.item_tax as item_tax',
                'transaction_sell_lines.unit_price as default_sell_price',
                'transaction_sell_lines.unit_price_before_discount',
                'transaction_sell_lines.unit_price_inc_tax as sell_price_inc_tax',
                'transaction_sell_lines.id as transaction_sell_lines_id',
                'transaction_sell_lines.id',
                'transaction_sell_lines.quantity as quantity_ordered',
                'transaction_sell_lines.sell_line_note as sell_line_note',
                'transaction_sell_lines.parent_sell_line_id',
                'transaction_sell_lines.lot_no_line_id',
                'transaction_sell_lines.line_discount_type',
                'transaction_sell_lines.line_discount_amount',
                'transaction_sell_lines.res_service_staff_id',
                'units.id as unit_id',
                DB::raw('(SELECT u2.is_default FROM units as u2 WHERE u2.id=transaction_sell_lines.sub_unit_id) as is_default_unit'),
                'transaction_sell_lines.sub_unit_id',
                'transaction_sell_lines.quantity',
                'transaction_sell_lines.quantity_line',
                'transaction_sell_lines.width',
                'transaction_sell_lines.height',
                'transaction_sell_lines.lot_number',
                DB::raw('vld.qty_available + transaction_sell_lines.quantity AS qty_available')
            )
            ->get();

        $default_unit = Unit::where('type', 'area')->where('is_default', true)->first();
        $default_unit_id = $default_unit->id;

        $weight_default_unit = Unit::where('type', 'weight')
            ->where('is_default', 1)
            ->first();

        $weight_default_unit_id = null;
        if($weight_default_unit){
            $weight_default_unit_id = $weight_default_unit->id;
        }

        if (!empty($sell_details)) {
            foreach ($sell_details as $key => $value) {
                $unit_id = $value->unit_id;
                if($unit_id == $weight_default_unit_id){
                    $unit_id = $default_unit_id;
                }

                $value->sub_units = $this->productUtil->getSubUnits($business_id, $unit_id, false, $value->product_id);

                if ($transaction->status != 'final') {
                    $actual_qty_avlbl = $value->qty_available - $value->quantity_ordered;
                    $sell_details[$key]->qty_available = $actual_qty_avlbl;
                    $value->qty_available = $actual_qty_avlbl;
                }

                $sell_details[$key]->formatted_qty_available = $this->transactionUtil->num_f($value->qty_available);

                $lot_numbers = [];
                if (request()->session()->get('business.enable_lot_number') == 1) {
                    $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($value->variation_id, $business_id, $location_id);
                    foreach ($lot_number_obj as $lot_number) {
                        //If lot number is selected added ordered quantity to lot quantity available
                        if ($value->lot_no_line_id == $lot_number->purchase_line_id) {
                            $lot_number->qty_available += $value->quantity_ordered;
                        }

                        $lot_number->qty_formated = $this->transactionUtil->num_f($lot_number->qty_available);
                        $lot_numbers[] = $lot_number;
                    }
                }
                $sell_details[$key]->lot_numbers = $lot_numbers;

                if (!empty($value->sub_unit_id)) {
                    $value = $this->productUtil->changeSellLineUnit($business_id, $value);
                    $sell_details[$key] = $value;
                }

                $sell_details[$key]->formatted_qty_available = $this->transactionUtil->num_f($value->qty_available);
            }

        }

        $commsn_agnt_setting = $business_details->sales_cmsn_agnt;
        $commission_agent = [];
        if ($commsn_agnt_setting == 'user') {
            $commission_agent = User::forDropdown($business_id);
        } elseif ($commsn_agnt_setting == 'cmsn_agnt') {
            $commission_agent = User::saleCommissionAgentsDropdown($business_id);
        }

        $types = [];
        if (auth()->user()->can('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create')) {
            $types['customer'] = __('report.customer');
        }
        if (auth()->user()->can('supplier.create') && auth()->user()->can('customer.create')) {
            $types['both'] = __('lang_v1.both_supplier_customer');
        }
        $customer_groups = CustomerGroup::forDropdown($business_id);

        $transaction->transaction_date = $this->transactionUtil->format_date($transaction->transaction_date, true);

        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

        $waiters = null;
        if ($this->productUtil->isModuleEnabled('service_staff') && !empty($pos_settings['inline_service_staff'])) {
            $waiters = $this->productUtil->serviceStaffDropdown($business_id);
        }

        $invoice_schemes = [];
        $default_invoice_schemes = null;

        if ($transaction->status == 'draft') {
            $invoice_schemes = InvoiceScheme::forDropdown($business_id);
            $default_invoice_schemes = InvoiceScheme::getDefault($business_id);
        }

        $redeem_details = [];
        if (request()->session()->get('business.enable_rp') == 1) {
            $redeem_details = $this->transactionUtil->getRewardRedeemDetails($business_id, $transaction->contact_id);

            $redeem_details['points'] += $transaction->rp_redeemed;
            $redeem_details['points'] -= $transaction->rp_earned;
        }

        $edit_discount = auth()->user()->can('edit_product_discount_from_sale_screen');
        $edit_price = auth()->user()->can('edit_product_price_from_sale_screen');

        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }

        $shipping_statuses = $this->transactionUtil->shipping_statuses();

        $common_settings = session()->get('business.common_settings');
        $is_warranty_enabled = !empty($common_settings['enable_product_warranty']) ? true : false;
        $warranties = $is_warranty_enabled ? Warranty::forDropdown($business_id) : [];

        $categories = Category::forDropdown($business_id, 'product');
        $products = Product::select('name', 'id')->get()->pluck('name', 'id')->toArray();

        $permitted_warehouses = auth()->user()->getPermittedWarehouses();
        if ($permitted_warehouses != 'all') {
            $warehouses = Warehouse::forDropdown($business_id, false, [], $permitted_warehouses);
        }else{
            $warehouses = Warehouse::forDropdown($business_id);
        }

        //Get deposit
        $deposit = TransactionPayment::where('transaction_id', $transaction->id)
            ->where('type', 'deposit')
            ->first();

        if($deposit){
            $deposit = $deposit->toArray();
        }else{
            $deposit = $this->dummyPaymentLine;
        }

        //Get cod
        $cod = TransactionPayment::where('transaction_id', $transaction->id)
            ->where('type', 'cod')
            ->first();

        if($cod){
            $cod = $cod->toArray();
        }else{
            $cod = $this->dummyPaymentLine;
        }

        $selling_price_groups = SellingPriceGroup::forDropdown($business_id, true);

        return view('sell.edit')
            ->with(compact('business_details', 'taxes', 'sell_details', 'transaction', 'commission_agent', 'types', 'customer_groups', 'pos_settings', 'waiters', 'invoice_schemes', 'default_invoice_schemes', 'redeem_details', 'edit_discount', 'edit_price', 'accounts', 'shipping_statuses', 'warranties', 'default_unit_id', 'categories', 'products', 'warehouses', 'deposit', 'cod', 'selling_price_groups'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // public function update(Request $request, $id)
    // {
    //     //
    // }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // public function destroy($id)
    // {
    //     //
    // }

    /**
     * Display a listing sell drafts.
     *
     * @return \Illuminate\Http\Response
     */
    public function getDrafts()
    {
        if (!auth()->user()->can('list_drafts')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);

        $sales_representative = User::forDropdown($business_id, false, false, true);


        return view('sale_pos.draft')
            ->with(compact('business_locations', 'customers', 'sales_representative'));
    }

    /**
     * Display a listing sell quotations.
     *
     * @return \Illuminate\Http\Response
     */
    public function getQuotations()
    {
        if (!auth()->user()->can('list_quotations')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);

        $sales_representative = User::forDropdown($business_id, false, false, true);

        return view('sale_pos.quotations')
            ->with(compact('business_locations', 'customers', 'sales_representative'));
    }

    /**
     * Send the datatable response for draft or quotations.
     *
     * @return \Illuminate\Http\Response
     */
    public function getDraftDatables()
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $is_quotation = request()->only('is_quotation', 0);

            $sells = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->join(
                    'business_locations AS bl',
                    'transactions.location_id',
                    '=',
                    'bl.id'
                )
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'draft')
                ->where('is_quotation', $is_quotation)
                ->select(
                    'transactions.id',
                    'transaction_date',
                    'invoice_no',
                    'contacts.id as customer_id',
                    'contacts.name',
                    'contacts.contact_id',
                    'bl.name as business_location',
                    'is_direct_sale'
                );

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $sells->whereIn('transactions.location_id', $permitted_locations);
            }

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $sells->whereDate('transaction_date', '>=', $start)
                    ->whereDate('transaction_date', '<=', $end);
            }

            if (request()->has('location_id')) {
                $location_id = request()->get('location_id');
                if (!empty($location_id)) {
                    $sells->where('transactions.location_id', $location_id);
                }
            }

            if (request()->has('created_by')) {
                $created_by = request()->get('created_by');
                if (!empty($created_by)) {
                    $sells->where('transactions.created_by', $created_by);
                }
            }

            if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $sells->where('contacts.id', $customer_id);
            }

            $sells->groupBy('transactions.id');

            return Datatables::of($sells)
                ->addColumn(
                    'action',
                    '<a href="#" data-href="{{action(\'SellController@show\', [$id])}}" class="btn btn-xs btn-success btn-modal" data-container=".view_modal"><i class="fas fa-eye" aria-hidden="true"></i> @lang("messages.view")</a>
                    &nbsp;
                    @if($is_direct_sale == 1)
                        <a href="{{action(\'SellController@edit\', [$id])}}" class="btn btn-xs btn-primary"><i class="fas fa-edit"></i>  @lang("messages.edit")</a>
                    @else
                    <a href="{{action(\'SellPosController@edit\', [$id])}}" class="btn btn-xs btn-primary"><i class="fas fa-edit"></i>  @lang("messages.edit")</a>
                    @endif

                    &nbsp;
                    <a href="#" class="print-invoice btn btn-xs btn-info" data-href="{{route(\'sell.printInvoice\', [$id])}}"><i class="fas fa-print" aria-hidden="true"></i> @lang("messages.print_sell_order")</a>

                    &nbsp; <a href="{{action(\'SellPosController@destroy\', [$id])}}" class="btn btn-xs btn-danger delete-sale"><i class="fas fa-trash"></i>  @lang("messages.delete")</a>
                    '
                )
                ->removeColumn('id')
                ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                ->editColumn('name', function ($row) {
                    return '<a href="' . action("ContactController@show", [$row->customer_id]) . '" target="_blank">' . $row->name . '</a>';
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("sell.view")) {
                            return  action('SellController@show', [$row->id]) ;
                        } else {
                            return '';
                        }
                    }])
                ->rawColumns(['action', 'invoice_no', 'transaction_date', 'name'])
                ->make(true);
        }
    }

    /**
     * Creates copy of the requested sale.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function duplicateSell($id)
    {
        if (!auth()->user()->can('sell.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $user_id = request()->session()->get('user.id');

            $transaction = Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->findorfail($id);
            $duplicate_transaction_data = [];
            foreach ($transaction->toArray() as $key => $value) {
                if (!in_array($key, ['id', 'created_at', 'updated_at'])) {
                    $duplicate_transaction_data[$key] = $value;
                }
            }
            $duplicate_transaction_data['status'] = 'draft';
            $duplicate_transaction_data['payment_status'] = null;
            $duplicate_transaction_data['transaction_date'] =  \Carbon::now();
            $duplicate_transaction_data['created_by'] = $user_id;
            $duplicate_transaction_data['invoice_token'] = null;

            DB::beginTransaction();
            $duplicate_transaction_data['invoice_no'] = $this->transactionUtil->getInvoiceNumber($business_id, 'draft', $duplicate_transaction_data['location_id']);

            if (!$duplicate_transaction_data['invoice_no']){
                DB::rollBack();
                \Log::emergency('Error: Duplicate invoice_no when duplicate sell');
                $output = [
                    'success' => 0,
                    'msg' => trans("messages.duplicate_invoice_no_error"),
                ];

                return redirect()
                    ->action('SellController@index')
                    ->with('status', $output);
            }

            //Create duplicate transaction
            $duplicate_transaction = Transaction::create($duplicate_transaction_data);

            //Create duplicate transaction sell lines
            $duplicate_sell_lines_data = [];

            foreach ($transaction->sell_lines as $sell_line) {
                $new_sell_line = [];
                foreach ($sell_line->toArray() as $key => $value) {
                    if (!in_array($key, ['id', 'transaction_id', 'created_at', 'updated_at', 'lot_no_line_id'])) {
                        $new_sell_line[$key] = $value;
                    }
                }

                $duplicate_sell_lines_data[] = $new_sell_line;
            }

            $duplicate_transaction->sell_lines()->createMany($duplicate_sell_lines_data);

            DB::commit();

            $output = ['success' => 0,
                'msg' => trans("lang_v1.duplicate_sell_created_successfully")
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                'msg' => trans("messages.something_went_wrong")
            ];
        }

        if (!empty($duplicate_transaction)) {
            if ($duplicate_transaction->is_direct_sale == 1) {
                return redirect()->action('SellController@edit', [$duplicate_transaction->id])->with(['status', $output]);
            } else {
                return redirect()->action('SellPosController@edit', [$duplicate_transaction->id])->with(['status', $output]);
            }
        } else {
            abort(404, 'Not Found.');
        }
    }

    /**
     * Shows modal to edit shipping details.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function editShipping($id)
    {
        if (!auth()->user()->can('shipping.update') || !auth()->user()->can('stock.view_deliver_orders')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $service_staffs = $this->productUtil->serviceStaffDropdown($business_id);

        $transaction = Transaction::with('contact')->where('business_id', $business_id)
            ->findorfail($id);
        $shippers = TransactionShip::query()->where('transaction_id', $id)->pluck('ship_id')->toArray();
        $shipping_statuses = $this->transactionUtil->shipping_statuses();

        return view('sell.partials.edit_shipping')
            ->with(compact('transaction', 'shipping_statuses', 'service_staffs', 'shippers'));
    }

    /**
     * Update shipping.
     *
     * @param  Request $request, int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateShipping(Request $request, $id)
    {
        if (!auth()->user()->can('shipping.update') || !auth()->user()->can('stock.view_deliver_orders')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only([
                'shipping_details', 'shipping_address',
                'shipping_status', 'delivered_to', 'phone_contact'
            ]);

            $business_id = $request->session()->get('user.business_id');

            DB::beginTransaction();
            $transaction = Transaction::query()->where('business_id', $business_id)
                ->where('id', $id)->first();

            if (empty($transaction)) {
                DB::commit();
                return [
                    'success' => 0,
                    'msg' => "Không tồn tại transaction"
                ];;
            }

            $transaction->update($input);

            $dataSave = [];
            TransactionShip::query()->where('transaction_id', $transaction->id)->delete();
            if (!empty($request->input('res_waiter_id'))) {
                foreach (array_filter($request->input('res_waiter_id')) as $shipId){
                    $dataSave[] = [
                        'transaction_id' => $transaction->id,
                        'ship_id'        => $shipId,
                        'created_at'     => date('Y-m-d H:i:s'),
                        'updated_at'     => date('Y-m-d H:i:s'),
                    ];
                }

                if (!empty($dataSave)) {
                    TransactionShip::query()->insert($dataSave);
                }
            }

            $output = ['success' => 1,
                'msg' => trans("lang_v1.updated_success")
            ];
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                'msg' => trans("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    /**
     * Display list of shipments.
     *
     * @return \Illuminate\Http\Response
     */
    public function shipments()
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('stock.view_deliver_orders')) {
            abort(403, 'Unauthorized action.');
        }

        $shipping_statuses = $this->transactionUtil->shipping_statuses();

        $business_id = request()->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);

        $sales_representative = User::forDropdown($business_id, false, false, true);

        $is_service_staff_enabled = $this->transactionUtil->isModuleEnabled('service_staff');

        //Service staff filter
        $service_staffs = null;
        if ($this->productUtil->isModuleEnabled('service_staff')) {
            $service_staffs = $this->productUtil->serviceStaffDropdown($business_id);
        }

        return view('sell.shipments')
            ->with(compact('business_locations', 'customers', 'sales_representative', 'is_service_staff_enabled', 'service_staffs', 'shipping_statuses'));
    }

    //Stock To Deliver
    public function stockDeliverIndex(Request $request){
        if (!auth()->user()->can('stock.view_deliver_orders')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $is_woocommerce = $this->moduleUtil->isModuleInstalled('Woocommerce');
        $is_tables_enabled = $this->transactionUtil->isModuleEnabled('tables');
        $is_service_staff_enabled = $this->transactionUtil->isModuleEnabled('service_staff');
        $is_types_service_enabled = $this->moduleUtil->isModuleEnabled('types_of_service');
        $export_statuses = $this->transactionUtil->export_statuses();
        $shipping_statuses = $this->transactionUtil->shipping_statuses();

        if (request()->ajax()) {
            $with = [];
            $sells = $this->transactionUtil->getListSells($business_id);
            $sells->where('transactions.is_service_order', 0)
                ->leftJoin('transaction_plate_lines as tpl', 'transactions.id', '=', 'tpl.transaction_id');

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $sells->whereIn('transactions.location_id', $permitted_locations);
            }

            //Add condition for created_by,used in sales representative sales report
            if (request()->has('created_by')) {
                $created_by = request()->get('created_by');
                if (!empty($created_by)) {
                    $sells->where('transactions.created_by', $created_by);
                }
            }

            if (!auth()->user()->can('direct_sell.access') && auth()->user()->can('view_own_sell_only')) {
                $sells->where('transactions.created_by', request()->session()->get('user.id'));
            }

            if (!empty(request()->input('payment_status')) && request()->input('payment_status') != 'overdue') {
                $sells->where('transactions.payment_status', request()->input('payment_status'));
            } elseif (request()->input('payment_status') == 'overdue') {
                $sells->whereIn('transactions.payment_status', ['due', 'partial'])
                    ->whereNotNull('transactions.pay_term_number')
                    ->whereNotNull('transactions.pay_term_type')
                    ->whereRaw("IF(transactions.pay_term_type='days', DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number DAY) < CURDATE(), DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number MONTH) < CURDATE())");
            }

            //Add condition for location,used in sales representative expense report
            if (request()->has('location_id')) {
                $location_id = request()->get('location_id');
                if (!empty($location_id)) {
                    $sells->where('transactions.location_id', $location_id);
                }
            }

            if (!empty(request()->input('rewards_only')) && request()->input('rewards_only') == true) {
                $sells->where(function ($q) {
                    $q->whereNotNull('transactions.rp_earned')
                        ->orWhere('transactions.rp_redeemed', '>', 0);
                });
            }

            if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $sells->where('contacts.id', $customer_id);
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $sells->whereDate('transactions.transaction_date', '>=', $start)
                    ->whereDate('transactions.transaction_date', '<=', $end);
            }

            //Check is_direct sell
            if (request()->has('is_direct_sale')) {
                $is_direct_sale = request()->is_direct_sale;
                if ($is_direct_sale == 0) {
                    $sells->where('transactions.is_direct_sale', 0);
                    $sells->whereNull('transactions.sub_type');
                }
            }

            //Add condition for commission_agent,used in sales representative sales with commission report
            if (request()->has('commission_agent')) {
                $commission_agent = request()->get('commission_agent');
                if (!empty($commission_agent)) {
                    $sells->where('transactions.commission_agent', $commission_agent);
                }
            }

            if ($is_woocommerce) {
                $sells->addSelect('transactions.woocommerce_order_id');
                if (request()->only_woocommerce_sells) {
                    $sells->whereNotNull('transactions.woocommerce_order_id');
                }
            }

            if (request()->only_subscriptions) {
                $sells->where(function ($q) {
                    $q->whereNotNull('transactions.recur_parent_id')
                        ->orWhere('transactions.is_recurring', 1);
                });
            }

            if (!empty(request()->list_for) && request()->list_for == 'service_staff_report') {
                $sells->whereNotNull('transactions.res_waiter_id');
            }

            if (!empty(request()->res_waiter_id)) {
                $sells->where('transactions.res_waiter_id', request()->res_waiter_id);
            }

            if (!empty(request()->input('sub_type'))) {
                $sells->where('transactions.sub_type', request()->input('sub_type'));
            }

            if (!empty(request()->input('created_by'))) {
                $sells->where('transactions.created_by', request()->input('created_by'));
            }

            if (!empty(request()->input('sales_cmsn_agnt'))) {
                $sells->where('transactions.commission_agent', request()->input('sales_cmsn_agnt'));
            }

            if (!empty(request()->input('service_staffs'))) {
                $transactionIds = TransactionShip::query()->where('ship_id', request()->input('service_staffs'))->pluck('transaction_id')->toArray();
                $sells->whereIn('transactions.id', $transactionIds);
            }
            $only_shipments = request()->only_shipments == 'true' ? true : false;
            if ($only_shipments && auth()->user()->can('shipping.update')) {
                $sells->whereNotNull('transactions.shipping_status');
            }

            if (!empty(request()->input('shipping_status'))) {
                $sells->where('transactions.shipping_status', request()->input('shipping_status'));
            }

            if (!empty(request()->input('export_status'))) {
                $sells->where('transactions.export_status', request()->input('export_status'));
            }

            if (!empty(request()->input('category_id'))) {
                $sells->where(function ($query){
                    $product_ids = Product::where('category_id', request()->input('category_id'))->pluck('id')->toArray();
                    $query->whereIn('tsl.product_id', $product_ids)
                        ->orWhereIn('tpl.product_id', $product_ids);
                });
            }

            if (!empty(request()->input('variation_id'))) {
                $sells->where(function ($query){
                    $product_id = request()->input('variation_id');
                    $query->where('tsl.product_id', $product_id)
                        ->OrWhere('tpl.product_id', $product_id);
                });
            }

            $sells->groupBy('transactions.id');

            if (!empty(request()->suspended)) {
                $transaction_sub_type = request()->get('transaction_sub_type');
                if (!empty($transaction_sub_type)) {
                    $sells->where('transactions.sub_type', $transaction_sub_type);
                } else {
                    $sells->where('transactions.sub_type', null);
                }

                $with = ['sell_lines'];

                if ($is_tables_enabled) {
                    $with[] = 'table';
                }

                if ($is_service_staff_enabled) {
                    $with[] = 'service_staff';
                }

                $sales = $sells->where('transactions.is_suspend', 1)
                    ->with($with)
                    ->addSelect('transactions.is_suspend', 'transactions.res_table_id', 'transactions.res_waiter_id', 'transactions.additional_notes')
                    ->get();

                return view('sale_pos.partials.suspended_sales_modal')->with(compact('sales', 'is_tables_enabled', 'is_service_staff_enabled', 'transaction_sub_type'));
            }

            $with[] = 'payment_lines';
            if (!empty($with)) {
                $sells->with($with);
            }

            //$business_details = $this->businessUtil->getDetails($business_id);
            if ($this->businessUtil->isModuleEnabled('subscription')) {
                $sells->addSelect('transactions.is_recurring', 'transactions.recur_parent_id');
            }

            $datatable = Datatables::of($sells)
                ->addColumn(
                    'action',
                    function ($row) use ($only_shipments) {
                        $html = '<div class="btn-group">
                                    <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                                        data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                        </span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-left" role="menu">' ;
                        if($row->status != 'cancel'){
                            if($row->export_status == 'none' && auth()->user()->can('stock.to_deliver')){
                                $html .= '<li><a href="' . action('SellController@createStockDeliver', [$row->id]) . '"><i class="fa fa-shopping-cart"></i> ' . __("lang_v1.stock.to_deliver") . '</a></li>';
                            }

                            if(auth()->user()->can("shipping.update") && $row->shipping_status != "shipped"){
                                $shipping_text = $row->shipping_status == 'not_shipped' ? __("lang_v1.edit_shipping") : __("lang_v1.shipping");
                                $html .= '<li><a href="#" data-href="' . action('SellController@editShipping', [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-truck" aria-hidden="true"></i>' . $shipping_text . '</a></li>';
                            }
                        }
                        if (auth()->user()->can("sell.view") || auth()->user()->can('stock.view_deliver_orders')) {
                            $html .= '<li><a href="#" data-href="' . action("SellController@showStockDeliver", [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-eye" aria-hidden="true"></i> ' . ($row->export_status == 'pending' ? __("messages.view_and_confirm_export") : __("messages.view")) . '</a></li>';
                        }
                        if ($row->export_status == 'pending') {
                            $html .= '<li><a href="' . action("SellController@editStockDeliver", [$row->id]) . '"><i class="fas fa-edit" aria-hidden="true"></i> ' . __("messages.edit_export") . '</a></li>';
                        }
                        if (in_array($row->export_status, ['pending', 'approved']) && (auth()->user()->can("sell.view") || auth()->user()->can("direct_sell.access") || auth()->user()->can('stock.view_deliver_orders'))) {
                            if($row->plates_sort_order){
                                $html .= '<li><a href="#" class="print-invoice" data-href="' . route('sell.printDeliverInvoice', [$row->id]) . '"><i class="fas fa-print" aria-hidden="true"></i> ' . __("messages.print_sell_deliver") . ' (Mẫu mới)</a></li>';
                            }
                            $html .= '<li><a href="#" class="print-invoice" data-href="' . route('sell.printDeliverInvoiceOldTemplate', [$row->id]) . '"><i class="fas fa-print" aria-hidden="true"></i> ' . __("messages.print_sell_deliver") . ' (Mẫu cũ)</a></li>';
                        }
                        $html .= '</ul></div>';

                        return $html;
                    }
                )
                ->removeColumn('id')
                ->editColumn('name', function ($row) {
                    return '<a href="' . action("ContactController@show", [$row->customer_id]) . '" target="_blank">' . $row->name . '</a>';
                })
                ->editColumn('final_total', function ($row) {
                    return '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="' . $row->final_total . '">' . $row->final_total . '</span>';
                })
                ->editColumn(
                    'tax_amount',
                    '<span class="display_currency total-tax" data-currency_symbol="true" data-orig-value="{{$tax_amount}}">{{$tax_amount}}</span>'
                )
                ->editColumn('total_paid', function ($row) {
                    return '<span class="display_currency total-paid" data-currency_symbol="true" data-orig-value="' . $row->total_paid . '">' . $row->total_paid . '</span>';
                })
                ->editColumn('cod', function ($row) {
                    return '<span class="display_currency total_cod" data-currency_symbol="true" data-orig-value="' . $row->cod . '">' . $row->cod . '</span>';
                })
                ->editColumn('deposit', function ($row) {
                    return '<span class="display_currency total_deposit" data-currency_symbol="true" data-orig-value="' . $row->deposit . '">' . $row->deposit . '</span>';
                })
                ->editColumn('total_before_tax', function ($row) {
                    return '<span class="display_currency total_before_tax" data-currency_symbol="true" data-orig-value="' . $row->total_before_tax . '">' . $row->total_before_tax . '</span>';
                })
                ->editColumn(
                    'discount_amount',
                    function ($row) {
                        $discount = !empty($row->discount_amount) ? $row->discount_amount : 0;

                        if (!empty($discount) && $row->discount_type == 'percentage') {
                            $discount = $row->total_before_tax * ($discount / 100);
                        }

                        return '<span class="display_currency total-discount" data-currency_symbol="true" data-orig-value="' . $discount . '">' . $discount . '</span>';
                    }
                )
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
//                ->editColumn(
//                    'payment_status',
//                    function ($row) {
//                        $payment_status = Transaction::getPaymentStatus($row);
//                        return (string) view('stock_deliver.partials.payment_status', ['payment_status' => $payment_status, 'id' => $row->id]);
//                    }
//                )
                ->editColumn(
                    'types_of_service_name',
                    '<span class="service-type-label" data-orig-value="{{$types_of_service_name}}" data-status-name="{{$types_of_service_name}}">{{$types_of_service_name}}</span>'
                )
                ->addColumn('total_remaining', function ($row) {
                    $total_remaining = $row->final_total - $row->total_paid_inc_unapprove;
                    $total_remaining_html = '<span class="display_currency payment_due" data-currency_symbol="true" data-orig-value="' . $total_remaining . '">' . $total_remaining . '</span>';
                    return $total_remaining_html;
                })
                ->addColumn('return_due', function ($row) {
                    $return_due_html = '';
                    if (!empty($row->return_exists)) {
                        $return_due = $row->amount_return - $row->return_paid;
                        $return_due_html .= '<a href="' . action("TransactionPaymentController@show", [$row->return_transaction_id]) . '" class="view_purchase_return_payment_modal"><span class="display_currency sell_return_due" data-currency_symbol="true" data-orig-value="' . $return_due . '">' . $return_due . '</span></a>';
                    }

                    return $return_due_html;
                })
                ->editColumn('invoice_no', function ($row) {
                    $invoice_no = $row->invoice_no;
                    if (!empty($row->woocommerce_order_id)) {
                        $invoice_no .= ' <i class="fab fa-wordpress text-primary no-print" title="' . __('lang_v1.synced_from_woocommerce') . '"></i>';
                    }
                    if (!empty($row->return_exists)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-red label-round no-print" title="' . __('lang_v1.some_qty_returned_from_sell') .'"><i class="fas fa-undo"></i></small>';
                    }
                    if (!empty($row->is_recurring)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-red label-round no-print" title="' . __('lang_v1.subscribed_invoice') .'"><i class="fas fa-recycle"></i></small>';
                    }
                    if (!empty($row->recur_parent_id)) {
                                $invoice_no .= ' &nbsp;<small class="label bg-info label-round no-print" title="' . __('lang_v1.subscription_invoice') .'"><i class="fas fa-recycle"></i></small>';
                    }
                    if (!empty($row->status == 'cancel')) {
                        $invoice_no .= ' &nbsp;<small class="label bg-yellow label-round no-print" title="' . __('lang_v1.sell_cancel_message') .'"><i class="fas fa-power-off"></i></small>';
                    }

                    return $invoice_no;
                })
                ->editColumn('shipping_status', function ($row) use ($shipping_statuses) {
                    $status_color = !empty($this->shipping_status_colors[$row->shipping_status]) ? $this->shipping_status_colors[$row->shipping_status] : 'bg-gray';
                    $status = !empty($row->shipping_status) ? '<a href="#" class="btn-modal" data-href="' . action('SellController@editShipping', [$row->id]) . '" data-container=".view_modal"><span class="label ' . $status_color .'">' . $shipping_statuses[$row->shipping_status] . '</span></a>' : '';

                    return $status;
                })
                ->editColumn('shipping_address', function ($row){
                    return $row->shipping_address;
                })
                ->editColumn('products', function ($row){
                    if ($row->type == 'expense') {
                        $html = $row->expense_note;
                    } elseif ($row->type == 'receipt') {
                        $html = $row->receipt_note;
                    } elseif ($row->type == 'purchase') {
                        $html = '';
                        foreach ($row->purchase_lines as $purchase_line) {
                            $product_name = $purchase_line->variations->product->name;
                            $variation = $purchase_line->variations->name;
                            $quantity = number_format($purchase_line->quantity_line);
                            $unit_price = $purchase_line->purchase_price;

                            if($purchase_line->product->unit->type == 'area'){
                                $unit = number_format($purchase_line->height, 2) . 'm x ' . number_format($purchase_line->width, 2) . 'm x ';
                            }elseif($purchase_line->product->unit->type == 'meter'){
                                $unit = number_format($purchase_line->width, 2) . 'm x ';
                            }else{
                                $unit = '';
                            }

                            if ($purchase_line->variations->product->type == 'single') {
                                $html .= $product_name . ' ('. $unit . $quantity .')' . '<br>';
                            } else {
                                $html .= $product_name . ' ('. $unit . $quantity .')' . ' ('. $variation .')' . '<br>';
                            }
                        }
                    } else {
                        $html = '';
                        foreach ($row->sell_lines as $sell_line) {
                            $product_name = $sell_line->variations->product->name;
                            $variation = $sell_line->variations->name;
                            $quantity = number_format($sell_line->quantity_line);
                            $unit_price = $sell_line->unit_price;

                            if($sell_line->product->unit->type == 'area'){
                                $unit = number_format($sell_line->height, 2) . 'm x ' . number_format($sell_line->width, 2) . 'm x ';
                            }elseif($sell_line->product->unit->type == 'meter'){
                                $unit = number_format($sell_line->width, 2) . 'm x ';
                            }else{
                                $unit = '';
                            }

                            if ($sell_line->variations->product->type == 'single') {
                                $html .= $product_name . ' ('. $unit . $quantity .')' . '<br>';
                            } else {
                                $html .= $product_name . ' ('. $unit . $quantity . ' ('. $variation .')' . '<br>';
                            }
                        }

                        if ($row->type == 'sell_return') {
                            $sell_return = Transaction::where('id', $row->id)->first();
                            $trans_return = Transaction::where('id', $sell_return->return_parent_id)->first();
                            $plates_return = TransactionPlateLinesReturn::where('transaction_id', $trans_return->id)->get();

                            foreach ($plates_return as $plate_return) {
                                $variation_return = Variation::with('product')->where('id', $plate_return->variation_id)->first();
                                if ($variation_return) {
                                    $product_name = $variation_return->product->name;
                                    $variation = $variation_return->name;
                                    $unit = number_format($plate_return->height, 2) . 'm x ' . number_format($plate_return->width, 2) . 'm x ';
                                    $quantity = number_format($plate_return->quantity);
                                    $unit_price = $plate_return->unit_price;
                                    $sub_unit = $variation_return->product->unit_id;

                                    $unit_type = Unit::find($sub_unit);
                                    if($unit_type->type == 'area'){
                                        $unit = number_format($plate_return->height, 2) . 'm x ' . number_format($plate_return->width, 2) . 'm x ';
                                    }elseif($unit_type->type == 'meter'){
                                        $unit = number_format($plate_return->width, 2) . 'm x ';
                                    }else{
                                        $unit = '';
                                    }

                                    if ($variation_return->product->type == 'single') {
                                        $html .= $product_name . ' ('. $unit . $quantity .')' . '<br>';
                                    } else {
                                        $html .= $product_name . ' ('. $unit . $quantity .')' . ' ('. $variation .')' . '<br>';
                                    }
                                }
                            }
                        }
                    }

                    return $html;
                })
                ->editColumn('export_status', function ($row) use ($export_statuses){
                    if ($row->export_status === 'none'){
                        return '<a href="'. action('SellController@createStockDeliver', [$row->id]) .'" class="label ' . $this->export_status_colors[$row->export_status] .'">' . $export_statuses[$row->export_status] . '</a>';
                    }else{
                        return '<a href="#" data-href="'. action('SellController@showStockDeliver', [$row->id]) .'" data-container=".view_modal" class="btn-modal label ' . $this->export_status_colors[$row->export_status] .'">' . $export_statuses[$row->export_status] . '</a>';
                    }
                })
                ->editColumn('shipper', function ($row){
                    $arr = array_unique(explode(',', $row->shipper));
                    return implode(', ', $arr);
                })
                ->filterColumn('shipper', function ($query, $keyword) {
                    $query->whereRaw("CONCAT(ss.first_name,' ',ss.last_name) like ?", ["%{$keyword}%"]);
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        return  action('SellController@showStockDeliver', [$row->id]) ;
                        /*if (auth()->user()->can("sell.view") || auth()->user()->can("view_own_sell_only")) {
                            return  action('SellController@showStockDeliver', [$row->id]) ;
                        } else {
                            return '';
                        }*/
                    }]);

            $rawColumns = ['shipper_id', 'final_total', 'cod', 'deposit', 'action', 'total_paid', 'total_remaining',
                'payment_status', 'invoice_no', 'discount_amount', 'tax_amount', 'total_before_tax', 'shipping_status',
                'types_of_service_name', 'payment_methods', 'return_due', 'name', 'products', 'export_status'];

            return $datatable->rawColumns($rawColumns)
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);
        $sales_representative = User::forDropdown($business_id, false, false, true);

        //Commission agent filter
        $is_cmsn_agent_enabled = request()->session()->get('business.sales_cmsn_agnt');
        $commission_agents = [];
        if (!empty($is_cmsn_agent_enabled)) {
            $commission_agents = User::forDropdown($business_id, false, true, true);
        }

        //Service staff filter
        $service_staffs = null;
        if ($this->productUtil->isModuleEnabled('service_staff')) {
            $service_staffs = $this->productUtil->serviceStaffDropdown($business_id);
        }

        $categories = Category::forDropdown($business_id, 'product');
        $products = Variation::leftjoin('products', 'variations.product_id', '=', 'products.id')
            ->select([
                DB::raw('IF(products.type = "variable", CONCAT(products.name, " - ", variations.name, " (", variations.sub_sku, ")"), CONCAT(products.name, " (", variations.sub_sku, ")")) as product'),
                'variations.id'
            ])->pluck('product', 'variations.id')
            ->toArray();

        return view('stock_deliver.index')
            ->with(compact('business_locations', 'customers', 'is_woocommerce', 'sales_representative', 'is_cmsn_agent_enabled', 'commission_agents', 'service_staffs', 'is_tables_enabled', 'is_service_staff_enabled', 'is_types_service_enabled', 'categories', 'products', 'export_statuses', 'shipping_statuses'));
    }

    public function sellsOfCashier()
    {
        if (!auth()->user()->can('sell.receipt_expense')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $is_woocommerce = $this->moduleUtil->isModuleInstalled('Woocommerce');
        $is_tables_enabled = $this->transactionUtil->isModuleEnabled('tables');
        $is_service_staff_enabled = $this->transactionUtil->isModuleEnabled('service_staff');
        $is_types_service_enabled = $this->moduleUtil->isModuleEnabled('types_of_service');
        $payment_types = $this->transactionUtil->payment_types(null, true);

        if (request()->ajax()) {
            $with = [];
            $shipping_statuses = $this->transactionUtil->shipping_statuses();
            $sells = $this->transactionUtil->getListSells($business_id);

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $sells->whereIn('transactions.location_id', $permitted_locations);
            }

            //Add condition for created_by,used in sales representative sales report
            if (request()->has('created_by')) {
                $created_by = request()->get('created_by');
                if (!empty($created_by)) {
                    $sells->where('transactions.created_by', $created_by);
                }
            }

            if (!auth()->user()->can('direct_sell.access') && auth()->user()->can('view_own_sell_only')) {
                $sells->where('transactions.created_by', request()->session()->get('user.id'));
            }

            if (!empty(request()->input('payment_status')) && request()->input('payment_status') != 'overdue') {
                $sells->where('transactions.payment_status', request()->input('payment_status'));
            } elseif (request()->input('payment_status') == 'overdue') {
                $sells->whereIn('transactions.payment_status', ['due', 'partial'])
                    ->whereNotNull('transactions.pay_term_number')
                    ->whereNotNull('transactions.pay_term_type')
                    ->whereRaw("IF(transactions.pay_term_type='days', DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number DAY) < CURDATE(), DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number MONTH) < CURDATE())");
            }

            //Add condition for location,used in sales representative expense report
            if (request()->has('location_id')) {
                $location_id = request()->get('location_id');
                if (!empty($location_id)) {
                    $sells->where('transactions.location_id', $location_id);
                }
            }

            if (!empty(request()->input('rewards_only')) && request()->input('rewards_only') == true) {
                $sells->where(function ($q) {
                    $q->whereNotNull('transactions.rp_earned')
                        ->orWhere('transactions.rp_redeemed', '>', 0);
                });
            }

            if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $sells->where('contacts.id', $customer_id);
            }

            if (!empty(request()->address)) {
                $address = request()->address;
                $sells->where('transactions.shipping_address', 'LIKE', '%'. $address .'%');
            }

            if (!empty(request()->phone)) {
                $phone = request()->phone;
                $sells->where('transactions.phone_contact', 'LIKE', '%'. $phone .'%');
            }

            if (!empty(request()->payment_method)) {
                $payment_method = request()->payment_method;
                $sells->leftJoin(
                    'transaction_payments',
                    'transactions.id',
                    '=',
                    'transaction_payments.transaction_id'
                )
                    ->where('transaction_payments.method', $payment_method);
            }

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $sells->whereDate('transactions.transaction_date', '>=', $start)
                    ->whereDate('transactions.transaction_date', '<=', $end);
            }

            //Check is_direct sell
            if (request()->has('is_direct_sale')) {
                $is_direct_sale = request()->is_direct_sale;
                if ($is_direct_sale == 0) {
                    $sells->where('transactions.is_direct_sale', 0);
                    $sells->whereNull('transactions.sub_type');
                }
            }

            //Add condition for commission_agent,used in sales representative sales with commission report
            if (request()->has('commission_agent')) {
                $commission_agent = request()->get('commission_agent');
                if (!empty($commission_agent)) {
                    $sells->where('transactions.commission_agent', $commission_agent);
                }
            }

            if ($is_woocommerce) {
                $sells->addSelect('transactions.woocommerce_order_id');
                if (request()->only_woocommerce_sells) {
                    $sells->whereNotNull('transactions.woocommerce_order_id');
                }
            }

            if (request()->only_subscriptions) {
                $sells->where(function ($q) {
                    $q->whereNotNull('transactions.recur_parent_id')
                        ->orWhere('transactions.is_recurring', 1);
                });
            }

            if (!empty(request()->list_for) && request()->list_for == 'service_staff_report') {
                $sells->whereNotNull('transactions.res_waiter_id');
            }

            if (!empty(request()->res_waiter_id)) {
                $sells->where('transactions.res_waiter_id', request()->res_waiter_id);
            }

            if (!empty(request()->input('sub_type'))) {
                $sells->where('transactions.sub_type', request()->input('sub_type'));
            }

            if (!empty(request()->input('created_by'))) {
                $sells->where('transactions.created_by', request()->input('created_by'));
            }

            if (!empty(request()->input('sales_cmsn_agnt'))) {
                $sells->where('transactions.commission_agent', request()->input('sales_cmsn_agnt'));
            }

            if (!empty(request()->input('service_staffs'))) {
                $transactionIds = TransactionShip::query()->where('ship_id', request()->input('service_staffs'))->pluck('transaction_id')->toArray();
                $sells->whereIn('transactions.id', $transactionIds);
            }
            $only_shipments = request()->only_shipments == 'true' ? true : false;
            if ($only_shipments && auth()->user()->can('shipping.update')) {
                $sells->whereNotNull('transactions.shipping_status');
            }

            if (!empty(request()->input('shipping_status'))) {
                $sells->where('transactions.shipping_status', request()->input('shipping_status'));
            }

            $sells->groupBy('transactions.id');

            if (!empty(request()->suspended)) {
                $transaction_sub_type = request()->get('transaction_sub_type');
                if (!empty($transaction_sub_type)) {
                    $sells->where('transactions.sub_type', $transaction_sub_type);
                } else {
                    $sells->where('transactions.sub_type', null);
                }

                $with = ['sell_lines'];

                if ($is_tables_enabled) {
                    $with[] = 'table';
                }

                if ($is_service_staff_enabled) {
                    $with[] = 'service_staff';
                }

                $sales = $sells->where('transactions.is_suspend', 1)
                    ->with($with)
                    ->addSelect('transactions.is_suspend', 'transactions.res_table_id', 'transactions.res_waiter_id', 'transactions.additional_notes')
                    ->get();

                return view('sale_pos.partials.suspended_sales_modal')->with(compact('sales', 'is_tables_enabled', 'is_service_staff_enabled', 'transaction_sub_type'));
            }

            $with[] = 'payment_lines';
            if (!empty($with)) {
                $sells->with($with);
            }

            //$business_details = $this->businessUtil->getDetails($business_id);
            if ($this->businessUtil->isModuleEnabled('subscription')) {
                $sells->addSelect('transactions.is_recurring', 'transactions.recur_parent_id');
            }

            $datatable = Datatables::of($sells)
                ->addColumn(
                    'action',
                    function ($row) use ($only_shipments) {
                        $canNotUpdate = $this->transactionUtil->canNotUpdate($row);
                        $can_not_create_payment = $canNotUpdate['can_not_create_payment'];
                        $can_not_approval_payment = $canNotUpdate['can_not_approval_payment'];

                        $html = '<div class="btn-group">
                                    <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                                        data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                        </span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-left" role="menu">' ;

                        if (!$only_shipments && $row->status != 'cancel') {
                            if (!$can_not_create_payment && $row->payment_status != "paid" && (auth()->user()->can("sell.create") || auth()->user()->can("sell.payments"))) {
                                $html .= '<li><a href="' . action('TransactionPaymentController@addPayment', [$row->id]) . '" class="add_payment_modal"><i class="fas fa-money-bill-alt"></i> ' . __("purchase.add_payment") . '</a></li>';
                            }

                            $html .= '<li><a href="' . action('TransactionPaymentController@show', [$row->id]) . '" class="view_payment_modal"><i class="fas fa-money-bill-alt"></i> ' . __("purchase.view_payments") . '</a></li>';
                            if ($row->export_status == 'approved') {
                                $html .= '<li><a href="#" data-href="' . action("SellController@createDebitPaper", [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-plus"></i> ' . __("lang_v1.add_debit_paper") . '</a></li>';
                            }

                            if (auth()->user()->can('sell.confirm_bank_transfer_method') && !$can_not_approval_payment) {
                                //Get paid on
                                if ($this->moduleUtil->isClosedEndOfDay()) {
                                    $paid_on = date('Y-m-d', strtotime('now +1 days'));
                                }else{
                                    $paid_on = date('Y-m-d');
                                }

                                $payment = TransactionPayment::where('type', 'normal')
                                    ->where('transaction_id', $row->id)
                                    ->whereDate('paid_on', $paid_on)
                                    ->first();

                                if ($payment && ($payment->method == 'cash' || ($payment->method == 'bank_transfer' && $payment->approval_status == 'pending'))) {
                                    $html .= '<li><a class="btn-cancel-remaining" data-transaction_id="'. $row->id .'"><i class="fa fa-power-off"></i> ' . __("lang_v1.cancel_remaining") . '</a></li>';
                                }
                            }

                            $html .= '<li class="divider"></li>';
                        }

                        if (auth()->user()->can("sell.view") || auth()->user()->can("direct_sell.access") || auth()->user()->can("view_own_sell_only") || auth()->user()->can("sell.receipt_expense")) {
                            $html .= '<li><a href="#" data-href="' . action("SellController@show", [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-eye" aria-hidden="true"></i> ' . __("messages.view") . '</a></li>';
                        }
//                        if (auth()->user()->can("sell.view") || auth()->user()->can("direct_sell.access")) {
//                            $html .= '<li><a href="#" class="print-invoice" data-href="' . route('sell.printInvoice', [$row->id]) . '"><i class="fas fa-print" aria-hidden="true"></i> ' . __("messages.print_sell_order") . '</a></li>';
//                        }
//                        if (!$only_shipments) {
//                            if (auth()->user()->can("sell.create")) {
//                                $html .= '<li><a href="' . action('SellPosController@showInvoiceUrl', [$row->id]) . '" class="view_invoice_url"><i class="fas fa-eye"></i> ' . __("lang_v1.view_invoice_url") . '</a></li>';
//                            }
//
//                            $html .= '<li><a href="#" data-href="' . action('NotificationController@getTemplate', ["transaction_id" => $row->id,"template_for" => "new_sale"]) . '" class="btn-modal" data-container=".view_modal"><i class="fa fa-envelope" aria-hidden="true"></i>' . __("lang_v1.new_sale_notification") . '</a></li>';
//                        }

                        $html .= '</ul></div>';

                        return $html;
                    }
                )
                ->removeColumn('id')
                /*
                ->addColumn('unit_prices', function($row){
                    $html = '';
                    $sell_lines = $row->sell_lines;
                    $unit_prices = [];

                    foreach ($sell_lines as $sell_line){
                        if(!isset($unit_prices[$sell_line->variation_id])){
                            $unit_prices[$sell_line->variation_id] = $sell_line->unit_price_inc_tax;
                        }
                    }

                    foreach ($unit_prices as $unit_price){
                        if(!empty($html)){
                            $html .= '<br/> ';
                        }
                        $html .= $this->transactionUtil->num_f($unit_price, true);
                    }

                    return $html;
                })*/
                ->editColumn('name', function ($row) {
                    return '<a href="' . action("ContactController@show", [$row->customer_id]) . '" target="_blank">' . $row->name . '</a>';
                })
                ->editColumn(
                    'final_total', function ($row) {
                    return '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="' . $row->final_total . '">' . $row->final_total . '</span>';
                })
                ->editColumn('total_paid', function ($row) {
                    return '<span class="display_currency total-paid" data-currency_symbol="true" data-orig-value="' . $row->total_paid . '">' . $row->total_paid . '</span>';
                })
                ->editColumn(
                    'deposit',
                    function ($row) {
                        if($row->deposit > 0){
                            $canNotUpdate = $this->transactionUtil->canNotUpdate($row);
                            $can_not_create_payment = $canNotUpdate['can_not_create_payment'];

                            $approval_statuses = $this->productUtil->approvalStatuses();
                            $approval_status_colors = $this->productUtil->approvalStatusColors();
                            $link_title = $approval_statuses[$row->deposit_approved];
                            $link_class = $approval_status_colors[$row->deposit_approved];
                            $icon_class = $row->deposit_approved == 'approved' ? 'fas fa-check' : 'fas fa-times';

                            if(in_array($row->deposit_approved, ['pending', 'unapproved'])){
                                if (!$can_not_create_payment) {
                                    $html = '
                                    <button type="button" class="approve_payment btn btn-xs '. $link_class .'" data-toggle="tooltip" title="'. $link_title .'" data-href="' . action('TransactionPaymentController@editDeposit', [$row->deposit_id]) . '">
                                        <i class="'.$icon_class.'"></i> <span class="display_currency total_deposit" data-currency_symbol="true" data-orig-value="' . $row->deposit . '">' . $row->deposit . '</span>
                                    </button>';
                                }else{
                                    $html = '
                                    <button type="button" class="payment_closed btn btn-xs '. $link_class .'" data-toggle="tooltip" title="'. $link_title .'">
                                        <i class="'.$icon_class.'"></i> <span class="display_currency total_deposit" data-currency_symbol="true" data-orig-value="' . $row->deposit . '">' . $row->deposit . '</span>
                                    </button>';
                                }
                            }else{
                                $html = '<i class="'.$icon_class.'"></i> <span class="display_currency total_deposit" data-currency_symbol="true" data-orig-value="'. $row->deposit .'">' . $row->deposit . '</span>';
                            };
                        }else{
                            $html = '';
                        }

                        return $html;
                    }
                )
                ->editColumn(
                    'cod',
                    function ($row) {
                        if($row->cod > 0){
                            $canNotUpdate = $this->transactionUtil->canNotUpdate($row);
                            $can_not_create_payment = $canNotUpdate['can_not_create_payment'];

                            $approval_statuses = $this->productUtil->approvalStatuses();
                            $approval_status_colors = $this->productUtil->approvalStatusColors();
                            $link_title = $approval_statuses[$row->cod_approved];
                            $link_class = $approval_status_colors[$row->cod_approved];
                            $icon_class = $row->cod_approved == 'approved' ? 'fas fa-check' : 'fas fa-times';

                            if(in_array($row->cod_approved, ['pending', 'unapproved'])){
                                if (!$can_not_create_payment) {
                                    $html = '
                                    <button type="button" class="approve_payment btn btn-xs '. $link_class .'" data-toggle="tooltip" title="'. $link_title .'" data-href="' . action('TransactionPaymentController@editCod', [$row->cod_id]) . '">
                                        <i class="'.$icon_class.'"></i> <span class="display_currency total_cod" data-currency_symbol="true" data-orig-value="' . $row->cod . '">' . $row->cod . '</span>
                                    </button>';
                                }else{
                                    $html = '
                                    <button type="button" class="payment_closed btn btn-xs '. $link_class .'" data-toggle="tooltip" title="'. $link_title .'">
                                        <i class="'.$icon_class.'"></i> <span class="display_currency total_cod" data-currency_symbol="true" data-orig-value="' . $row->cod . '">' . $row->cod . '</span>
                                    </button>';
                                }
                            }else{
                                $html = '<i class="'.$icon_class.'"></i> <span class="display_currency total_cod" data-currency_symbol="true" data-orig-value="'. $row->cod .'">' . $row->cod . '</span>';
                            };
                        }else{
                            $html = '';
                        }

                        return $html;
                    }
                )
                ->editColumn('is_confirm_debit_paper', function ($row) {
                    $html = '';
                    if ($row->is_confirm_debit_paper) {
                        $html .= '<span class="label bg-green">'. __('lang_v1.confirmed') .'</span>';
                    }

                    return $html;
                })
                ->addColumn('mass_action', function ($row) {
                    return  '<input type="checkbox" class="row-select" value="' . $row->id .'">' ;
                })
                ->addColumn('total_remaining', function ($row) {
                    $canNotUpdate = $this->transactionUtil->canNotUpdate($row);
                    $can_not_create_payment = $canNotUpdate['can_not_create_payment'];

                    $total_remaining = $row->final_total - $row->total_paid;
                    $approval_statuses = $this->productUtil->approvalStatuses();
                    $approval_status_colors = $this->productUtil->approvalStatusColors();
                    $link_title = $approval_statuses[$row->total_remaining_approved];
                    $link_class = $approval_status_colors[$row->total_remaining_approved];
                    $icon_class = $row->total_remaining_approved == 'approved' ? 'fas fa-check' : 'fas fa-times';

                    if(in_array($row->total_remaining_approved, ['pending', 'unapproved']) && $row->status != "cancel"){
                        if (!$can_not_create_payment) {
                            $html = '
                            <button type="button" class="add_remaining_payment btn btn-xs '. $link_class .'" data-toggle="tooltip" title="'. $link_title .'" href="' . action('TransactionPaymentController@addRemaining', [$row->id]) . '">
                                <i class="'.$icon_class.'"></i> <span class="display_currency payment_due" data-currency_symbol="true" data-orig-value="'. $total_remaining .'">'. $this->transactionUtil->num_f($total_remaining) .'</span>
                            </button>';
                        }else{
                            $html = '
                            <button type="button" class="payment_closed btn btn-xs '. $link_class .'" data-toggle="tooltip" title="'. $link_title .'">
                                <i class="'.$icon_class.'"></i> <span class="display_currency payment_due" data-currency_symbol="true" data-orig-value="'. $total_remaining .'">'. $this->transactionUtil->num_f($total_remaining) .'</span>
                            </button>';}
                    }else{
                        $html = '<i class="'.$icon_class.'"></i> <span class="display_currency payment_due" data-currency_symbol="true" data-orig-value="'. $total_remaining .'">'. $this->transactionUtil->num_f($total_remaining) .'</span>';
                    };
                    return $html;
                })
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn(
                    'payment_status',
                    function ($row) {
                        $payment_status = Transaction::getPaymentStatus($row);
                        $total_remaining = $row->total_final - $row->total_paid;
                        if($total_remaining >= 0){
                            $payment_approved = 0;
                        }else{
                            $payment_approved = $row->payment_approved;
                        }
                        return (string) view('sell.partials.payment_status', ['payment_status' => $payment_status, 'payment_approved' => $payment_approved, 'id' => $row->id]);
                    }
                )
                /*->addColumn('return_due', function ($row) {
                    $return_due_html = '';
                    if (!empty($row->return_exists)) {
                        $return_due = $row->amount_return - $row->return_paid;
                        $return_due_html .= '<a href="' . action("TransactionPaymentController@show", [$row->return_transaction_id]) . '" class="view_purchase_return_payment_modal"><span class="display_currency sell_return_due" data-currency_symbol="true" data-orig-value="' . $return_due . '">' . $return_due . '</span></a>';
                    }

                    return $return_due_html;
                })*/
                ->editColumn('invoice_no', function ($row) {
                    $invoice_no = $row->invoice_no;
                    if (!empty($row->woocommerce_order_id)) {
                        $invoice_no .= ' <i class="fab fa-wordpress text-primary no-print" title="' . __('lang_v1.synced_from_woocommerce') . '"></i>';
                    }
                    if (!empty($row->return_exists)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-red label-round no-print" title="' . __('lang_v1.some_qty_returned_from_sell') .'"><i class="fas fa-undo"></i></small>';
                    }
                    if (!empty($row->is_recurring)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-red label-round no-print" title="' . __('lang_v1.subscribed_invoice') .'"><i class="fas fa-recycle"></i></small>';
                    }
                    if (!empty($row->recur_parent_id)) {
                        $invoice_no .= ' &nbsp;<small class="label bg-info label-round no-print" title="' . __('lang_v1.subscription_invoice') .'"><i class="fas fa-recycle"></i></small>';
                    }
                    if (!empty($row->status == 'cancel')) {
                        $invoice_no .= ' &nbsp;<small class="label bg-yellow label-round no-print" title="' . __('lang_v1.sell_cancel_message') .'"><i class="fas fa-power-off"></i></small>';
                    }

                    return $invoice_no;
                })
                /*->editColumn('shipping_status', function ($row) use ($shipping_statuses) {
                    $status_color = !empty($this->shipping_status_colors[$row->shipping_status]) ? $this->shipping_status_colors[$row->shipping_status] : 'bg-gray';
                    $status = !empty($row->shipping_status) ? '<a href="#" class="btn-modal" data-href="' . action('SellController@editShipping', [$row->id]) . '" data-container=".view_modal"><span class="label ' . $status_color .'">' . $shipping_statuses[$row->shipping_status] . '</span></a>' : '';

                    return $status;
                })*/
                ->editColumn('shipper', function ($row){
                    $arr = array_unique(explode(',', $row->shipper));
                    return implode(', ', $arr);
                })
                ->filterColumn('shipper', function ($query, $keyword) {
                    $query->whereRaw("CONCAT(ss.first_name,' ',ss.last_name) like ?", ["%{$keyword}%"]);
                })
                ->filterColumn('total_paid', function ($query, $keyword) {
                    $query->whereRaw("(SELECT SUM(IF(TP.is_return = 1,-1*TP.amount,TP.amount)) FROM transaction_payments AS TP WHERE
                        TP.transaction_id=transactions.id AND TP.approval_status <> 'reject') like ?", ["%{$keyword}%"]);
                })
                ->filterColumn('deposit', function ($query, $keyword) {
                    $query->whereRaw("(SELECT IF(TP.is_return = 1,-1*TP.amount,TP.amount) FROM transaction_payments AS TP WHERE
                        TP.transaction_id=transactions.id AND TP.type = 'deposit' AND TP.approval_status <> 'reject' ORDER BY TP.id LIMIT 1) like ?", ["%{$keyword}%"]);
                })
                ->filterColumn('cod', function ($query, $keyword) {
                    $query->whereRaw("(SELECT IF(TP.is_return = 1,-1*TP.amount,TP.amount) FROM transaction_payments AS TP WHERE
                        TP.transaction_id=transactions.id AND TP.type = 'cod' AND TP.approval_status <> 'reject' ORDER BY TP.id LIMIT 1) like ?", ["%{$keyword}%"]);
                })
                ->filterColumn('total_remaining', function ($query, $keyword) {
                    $query->whereRaw("(transactions.final_total - (SELECT SUM(IF(TP.is_return = 1,-1*TP.amount,TP.amount)) FROM transaction_payments AS TP WHERE
                        TP.transaction_id=transactions.id AND TP.approval_status <> 'reject')) like ?", ["%{$keyword}%"]);
                })
                ->orderColumn('total_remaining', function ($query, $order) {
                    $query->orderByRaw('(transactions.final_total - total_paid) '.$order);
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        return  action('SellController@show', [$row->id]) ;
                        /*if (auth()->user()->can("sell.view") || auth()->user()->can("view_own_sell_only")) {
                            return  action('SellController@show', [$row->id]) ;
                        } else {
                            return '';
                        }*/
                    }]);

            $rawColumns = ['final_total', 'cod', 'deposit', 'action', 'total_paid', 'total_remaining', 'payment_status', 'invoice_no', 'discount_amount', 'tax_amount', 'total_before_tax', 'shipping_status', 'types_of_service_name', 'payment_methods', 'return_due', 'mass_action', 'name', 'is_confirm_debit_paper'];

            return $datatable->rawColumns($rawColumns)
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);
        $sales_representative = User::forDropdown($business_id, false, false, true);

        //Commission agent filter
        $is_cmsn_agent_enabled = request()->session()->get('business.sales_cmsn_agnt');
        $commission_agents = [];
        if (!empty($is_cmsn_agent_enabled)) {
            $commission_agents = User::forDropdown($business_id, false, true, true);
        }

        //Service staff filter
        $service_staffs = null;
        if ($this->productUtil->isModuleEnabled('service_staff')) {
            $service_staffs = $this->productUtil->serviceStaffDropdown($business_id);
        }

        $users = Contact::customersDropdown($business_id);

        $payment_line = $this->dummyPaymentLine;
        $cod_line = $this->dummyPaymentLine;
        $today = Carbon::today()->toDateString();
        $total_payment_on_day = $this->transactionUtil->calculateTotalMoneyOnDay($today);

        $total_money_cod = TransactionPayment::whereDate('paid_on', '=', Carbon::today()->toDateString())
            ->where('type', 'cod')
            ->where('approval_status', 'approved')
            ->select('amount', 'method', 'bank_account_number')
            ->get()
            ->toArray();

        $note_receipt = TransactionReceipt::whereNotNull('note')->get()->pluck('note', 'note')->toArray();
        $note_expense = TransactionExpense::whereNotNull('note')->get()->pluck('note', 'note')->toArray();

        $receipt_customers = Contact::customersDropdown($business_id, false);

        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false, true, 'bank');
        }

        return view('sell_of_cashier.index')
            ->with(compact('business_locations',
                'customers',
                'is_woocommerce',
                'sales_representative',
                'is_cmsn_agent_enabled',
                'commission_agents',
                'service_staffs',
                'is_tables_enabled',
                'is_service_staff_enabled',
                'is_types_service_enabled',
                'users',
                'payment_line',
                'payment_types',
                'cod_line',
                'total_money_cod',
                'note_receipt',
                'note_expense',
                'receipt_customers',
                'total_payment_on_day',
                'accounts'
            ));
    }

    public function receiptOfCashier() {
        if (!auth()->user()->can('sell.receipt_expense')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $query = TransactionReceipt::join('transactions as t', 'transaction_receipts.transaction_id', '=', 't.id')
                ->join('transaction_payments as tp', 't.id', '=', 'tp.transaction_id')
                ->leftjoin('contacts as ct', 'transaction_receipts.contact_id', '=', 'ct.id')
                ->where('t.type', 'receipt')
                ->where('tp.approval_status', 'approved')
                ->select(
                    't.ref_no',
                    'tp.amount as total_money',
                    'tp.approval_status as approved',
                    'transaction_receipts.id',
                    'transaction_receipts.transaction_id',
                    'transaction_receipts.type',
                    'ct.id AS customer_id',
                    'ct.name',
                    'ct.contact_id',
                    'transaction_receipts.note',
                    'tp.method',
                    'tp.bank_account_number',
                    'tp.paid_on'
                )
                ->orderBy('tp.paid_on', 'desc');

            if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $query->where('tp.payment_for', $customer_id);
            }
            if (!empty(request()->note)) {
                $note = request()->note;
                $query->where('transaction_receipts.note', 'LIKE', $note);
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $query
                    ->whereDate('tp.paid_on', '>=', $start)
                    ->whereDate('tp.paid_on', '<=', $end);
            } else {
                $query->whereDate('tp.paid_on', '=', Carbon::today()->toDateString());
            }

            if (!empty(request()->type)) {
                $type = request()->type;
                $query->where('transaction_receipts.type', $type);
            }
            if (!empty(request()->payment_method)) {
                $payment_method = request()->payment_method;
                $query->where('tp.method', $payment_method);
            }

            $receipt_types = TransactionReceipt::$TYPES;
            $payment_types = $this->transactionUtil->payment_types();

            $receipt = DataTables::of($query)
                ->addColumn('action', function ($row) {
                    $html = '';
                    if (Carbon::parse($row->paid_on)->toDateString() == Carbon::today()->toDateString()) {
                        if (auth()->user()->can('sell.receipt_expense')) {
                            $html .= '<button data-href="'. action('ExpenseController@editReceiptRow', [$row->id]) .'"  class="btn btn-xs btn-primary edit_receipt_button"><i class="glyphicon glyphicon-edit"></i> '. __('messages.edit') .'</button>
                                  <button data-href="'. action('ExpenseController@deleteReceiptRow', [$row->id]) .'" class="btn btn-xs btn-danger delete_receipt_button"><i class="glyphicon glyphicon-trash"></i> '. __('messages.delete') .'</button> ';
                        }
                    }
                    if ($row->type == 'deposit'){
                        $html .= '<a href="#" class="print-invoice btn btn-xs btn-info " data-href="' . route('receipts.printReceiptInvoice', [$row->transaction_id]) . '"><i class="fas fa-print" aria-hidden="true"></i> ' . __("messages.print") . '</a>';
                    }
                    return $html;
                })
                ->editColumn('type', function($row) use ($receipt_types) {
                    return $receipt_types[$row->type];
                })
                ->editColumn('contact_id', function ($row) {
                    return !empty($row->contact_id) ? $row->contact_id : '--';
                })
                ->editColumn('name', function ($row) {
                    return !empty($row->name) ? '<a href="' . action("ContactController@show", [$row->customer_id]) . '" target="_blank">' . $row->name . '</a>' : '--';
                })
                ->editColumn('note', function ($row) {
                    return !empty($row->note) ? $row->note : '--';
                })
                ->editColumn('total_money', function ($row) {
                    $html = '<span class="display_currency" data-currency_symbol="true">' . $row->total_money . '</span>';
                    return $html;
                })
                ->editColumn('method', function ($row) use ($payment_types) {
                    return $payment_types[$row->method];
                })
                ->editColumn('bank_account_number', function ($row) {
                    return !empty($row->bank_account_number) ? $row->bank_account_number : '--';
                })
//                ->editColumn(
//                    'total_receipt',
//                    '<span class="display_currency final-total-receipt" data-currency_symbol="true" data-orig-value="{{$total_receipt}}">{{$total_receipt}}</span>'
//                )
                ->removeColumn('id');

            return $receipt->rawColumns(['total_money', 'action', 'name'])
                ->make(true);
        }

        return view('sell_of_cashier.index');
    }

    public function expenseOfCashier() {
        if (!auth()->user()->can('sell.receipt_expense')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $query = TransactionExpense::join('transactions as t', 'transaction_expenses.transaction_id', '=', 't.id')
                ->join('transaction_payments as tp', 't.id', '=', 'tp.transaction_id')
                ->leftjoin('contacts as ct', 'transaction_expenses.contact_id', '=', 'ct.id')
                ->where('t.type', 'expense')
                ->where('tp.approval_status', 'approved')
                ->select(
                    'tp.amount as total_money',
                    'transaction_expenses.id',
                    'transaction_expenses.transaction_id',
                    'transaction_expenses.type',
                    'ct.id AS customer_id',
                    'ct.name',
                    'ct.contact_id',
                    'transaction_expenses.note',
                    'tp.method',
                    'tp.bank_account_number',
                    'tp.paid_on',
                    't.ref_no'
                )
                ->orderBy('tp.paid_on', 'desc');

            if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $query->where('tp.payment_for', $customer_id);
            }
            if (!empty(request()->note)) {
                $note = request()->note;
                $query->where('transaction_expenses.note', 'LIKE', $note);
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $query
                    ->whereDate('tp.paid_on', '>=', $start)
                    ->whereDate('tp.paid_on', '<=', $end);
            } else {
                $query->whereDate('tp.paid_on', '=', Carbon::today()->toDateString());
            }
            if (!empty(request()->type)) {
                $type = request()->type;
                $query->where('transaction_expenses.type', $type);
            }
            if (!empty(request()->payment_method)) {
                $payment_method = request()->payment_method;
                $query->where('tp.method', $payment_method);
            }

            $receipt = DataTables::of($query)
                ->addColumn('action', function ($row) {
                    $html = '';
                    if (Carbon::parse($row->paid_on)->toDateString() == Carbon::today()->toDateString()) {
                        if (auth()->user()->can('sell.receipt_expense')) {
                            $html .= '<button data-href="'. action('ExpenseController@editExpenseRow', [$row->id]) .'"  class="btn btn-xs btn-primary edit_expense_button"><i class="glyphicon glyphicon-edit" style="margin-right: 5px;"></i>'. __('messages.edit') .'</button>
                                      <button data-href="'. action('ExpenseController@deleteExpenseRow', [$row->id]) .'" class="btn btn-xs btn-danger delete_expense_button"><i class="glyphicon glyphicon-trash" style="margin-right: 5px;"></i>'. __('messages.delete') .'</button>';
                            return $html;
                        }
                    }
                    return $html;
                })
                ->editColumn('type', function($row) {
                    return $row->type == 'return_customer' ? 'Trả lại cho khách' : 'Chi phí';
                })
                ->editColumn('contact_id', function ($row) {
                    return !empty($row->contact_id) ? $row->contact_id : '--';
                })
                ->editColumn('name', function ($row) {
                    return !empty($row->name) ? '<a href="' . action("ContactController@show", [$row->customer_id]) . '" target="_blank">' . $row->name . '</a>' : '--';
                })
                ->editColumn('note', function ($row) {
                    return !empty($row->note) ? $row->note : '--';
                })
                ->editColumn('total_money', function ($row) {
                    $html = '<span class="display_currency" data-currency_symbol="true">' . ($row->total_money * -1) . '</span>';
                    return $html;
                })
                ->editColumn('method', function ($row) {
                    return $row->method == 'cash' ? 'Tiền mặt' : 'Chuyển khoản';
                })
                ->editColumn('bank_account_number', function ($row) {
                    return !empty($row->bank_account_number) ? $row->bank_account_number : '--';
                })
                ->removeColumn('id');

            return $receipt->rawColumns(['total_money', 'action', 'name'])
                ->make(true);
        }

        return view('sell_of_cashier.index');
    }

    public function createStockDeliver($id)
    {
        if (!auth()->user()->can('stock.to_deliver')) {
            abort(403, 'Unauthorized action.');
        }

        //Check if return exist then not allowed
        if ($this->transactionUtil->isReturnExist($id)) {
            return back()->with('status', ['success' => 0,
                'msg' => __('lang_v1.return_exist')]);
        }

        $business_id = request()->session()->get('user.business_id');

        $business_details = $this->businessUtil->getDetails($business_id);
        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);

        $transaction = Transaction::where('business_id', $business_id)
            ->with(['price_group', 'types_of_service'])
            ->where('type', 'sell')
            ->select([
                '*',
                DB::raw('(SELECT SUM(IF(TC.is_return = 1,-1*TC.amount,TC.amount)) FROM transaction_cod AS TC WHERE
                        TC.transaction_id=transactions.id) as cod'),
            ])
            ->findorfail($id);

        if ($transaction->export_status == 'approved') {
            return back()
                ->with('status', ['success' => 0,
                    'msg' => __('messages.sell_order_delivered')]);
        }

        $location_id = $transaction->location_id;
//        $location_printer_type = BusinessLocation::find($location_id)->receipt_printer_type;

        $sell_details = $this->transactionUtil->getQueryStockDeliver($id, $transaction, $location_id, $business_id);

        $commsn_agnt_setting = $business_details->sales_cmsn_agnt;
        $commission_agent = [];
        if ($commsn_agnt_setting == 'user') {
            $commission_agent = User::forDropdown($business_id);
        } elseif ($commsn_agnt_setting == 'cmsn_agnt') {
            $commission_agent = User::saleCommissionAgentsDropdown($business_id);
        }

        $types = [];
        if (auth()->user()->can('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create')) {
            $types['customer'] = __('report.customer');
        }
        if (auth()->user()->can('supplier.create') && auth()->user()->can('customer.create')) {
            $types['both'] = __('lang_v1.both_supplier_customer');
        }
        $customer_groups = CustomerGroup::forDropdown($business_id);

        $transaction->transaction_date = $this->transactionUtil->format_date($transaction->transaction_date, true);

        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

        $waiters = null;
        if ($this->productUtil->isModuleEnabled('service_staff') && !empty($pos_settings['inline_service_staff'])) {
            $waiters = $this->productUtil->serviceStaffDropdown($business_id);
        }

        $invoice_schemes = [];
        $default_invoice_schemes = null;

        if ($transaction->status == 'draft') {
            $invoice_schemes = InvoiceScheme::forDropdown($business_id);
            $default_invoice_schemes = InvoiceScheme::getDefault($business_id);
        }

        $redeem_details = [];
        if (request()->session()->get('business.enable_rp') == 1) {
            $redeem_details = $this->transactionUtil->getRewardRedeemDetails($business_id, $transaction->contact_id);

            $redeem_details['points'] += $transaction->rp_redeemed;
            $redeem_details['points'] -= $transaction->rp_earned;
        }

        $edit_discount = auth()->user()->can('edit_product_discount_from_sale_screen');
        $edit_price = auth()->user()->can('edit_product_price_from_sale_screen');

        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }

        $shipping_statuses = $this->transactionUtil->shipping_statuses();

        $common_settings = session()->get('business.common_settings');
        $is_warranty_enabled = !empty($common_settings['enable_product_warranty']) ? true : false;
        $warranties = $is_warranty_enabled ? Warranty::forDropdown($business_id) : [];

        $default_unit = Unit::where('type', 'area')->where('is_default', true)->first();
        $default_unit_id = $default_unit->id;

        $categories = Category::forDropdown($business_id, 'product');
        $products = Variation::leftjoin('products', 'variations.product_id', '=', 'products.id')
            ->select([
                DB::raw('IF(products.type = "variable", CONCAT(products.name, " - ", variations.name, " (", variations.sub_sku, ")"), CONCAT(products.name, " (", variations.sub_sku, ")")) as product'),
                'variations.id'
            ])->pluck('product', 'variations.id')
            ->toArray();

        return view('stock_deliver.create')
            ->with(compact('business_details', 'taxes', 'sell_details', 'transaction', 'commission_agent', 'types', 'customer_groups', 'pos_settings', 'waiters', 'invoice_schemes', 'default_invoice_schemes', 'redeem_details', 'edit_discount', 'edit_price', 'accounts', 'shipping_statuses', 'warranties', 'default_unit_id', 'categories', 'products'));
    }

    public function editStockDeliver($id)
    {
        if (!auth()->user()->can('stock.to_deliver')) {
            abort(403, 'Unauthorized action.');
        }

        //Check if the transaction can be edited or not.
        $edit_days = request()->session()->get('business.transaction_edit_days');
        if (!$this->transactionUtil->canBeEdited($id, $edit_days)) {
            return back()
                ->with('status', ['success' => 0,
                    'msg' => __('messages.transaction_edit_not_allowed', ['days' => $edit_days])]);
        }

        //Check if return exist then not allowed
        if ($this->transactionUtil->isReturnExist($id)) {
            return back()->with('status', ['success' => 0,
                'msg' => __('lang_v1.return_exist')]);
        }

        $business_id = request()->session()->get('user.business_id');

        $business_details = $this->businessUtil->getDetails($business_id);
        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);

        $transaction = Transaction::where('business_id', $business_id)
            ->with(['price_group', 'types_of_service'])
            ->where('type', 'sell')
            ->select([
                '*',
                DB::raw('(SELECT SUM(IF(TC.is_return = 1,-1*TC.amount,TC.amount)) FROM transaction_cod AS TC WHERE
                        TC.transaction_id=transactions.id) as cod'),
            ])
            ->findorfail($id);

        if ($transaction->export_status == 'approved') {
            return back()
                ->with('status', ['success' => 0,
                    'msg' => __('messages.sell_order_delivered')]);
        }

        $location_id = $transaction->location_id;
//        $location_printer_type = BusinessLocation::find($location_id)->receipt_printer_type;

        $sell_details = $this->transactionUtil->getQueryStockDeliver($id, $transaction, $location_id, $business_id);

        $commsn_agnt_setting = $business_details->sales_cmsn_agnt;
        $commission_agent = [];
        if ($commsn_agnt_setting == 'user') {
            $commission_agent = User::forDropdown($business_id);
        } elseif ($commsn_agnt_setting == 'cmsn_agnt') {
            $commission_agent = User::saleCommissionAgentsDropdown($business_id);
        }

        $types = [];
        if (auth()->user()->can('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create')) {
            $types['customer'] = __('report.customer');
        }
        if (auth()->user()->can('supplier.create') && auth()->user()->can('customer.create')) {
            $types['both'] = __('lang_v1.both_supplier_customer');
        }
        $customer_groups = CustomerGroup::forDropdown($business_id);

        $transaction->transaction_date = $this->transactionUtil->format_date($transaction->transaction_date, true);

        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

        $waiters = null;
        if ($this->productUtil->isModuleEnabled('service_staff') && !empty($pos_settings['inline_service_staff'])) {
            $waiters = $this->productUtil->serviceStaffDropdown($business_id);
        }

        $invoice_schemes = [];
        $default_invoice_schemes = null;

        if ($transaction->status == 'draft') {
            $invoice_schemes = InvoiceScheme::forDropdown($business_id);
            $default_invoice_schemes = InvoiceScheme::getDefault($business_id);
        }

        $redeem_details = [];
        if (request()->session()->get('business.enable_rp') == 1) {
            $redeem_details = $this->transactionUtil->getRewardRedeemDetails($business_id, $transaction->contact_id);

            $redeem_details['points'] += $transaction->rp_redeemed;
            $redeem_details['points'] -= $transaction->rp_earned;
        }

        $edit_discount = auth()->user()->can('edit_product_discount_from_sale_screen');
        $edit_price = auth()->user()->can('edit_product_price_from_sale_screen');

        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }

        $shipping_statuses = $this->transactionUtil->shipping_statuses();

        $common_settings = session()->get('business.common_settings');
        $is_warranty_enabled = !empty($common_settings['enable_product_warranty']) ? true : false;
        $warranties = $is_warranty_enabled ? Warranty::forDropdown($business_id) : [];

        $default_unit = Unit::where('type', 'area')->where('is_default', true)->first();
        $default_unit_id = $default_unit->id;

        $categories = Category::forDropdown($business_id, 'product');
        $products = Variation::leftjoin('products', 'variations.product_id', '=', 'products.id')
            ->select([
                DB::raw('IF(products.type = "variable", CONCAT(products.name, " - ", variations.name, " (", variations.sub_sku, ")"), CONCAT(products.name, " (", variations.sub_sku, ")")) as product'),
                'variations.id'
            ])->pluck('product', 'variations.id')
            ->toArray();

        $selected_remaining_widths = $transaction->selected_remaining_widths ? json_decode($transaction->selected_remaining_widths, true) : [];

        return view('stock_deliver.edit')
            ->with(compact('business_details', 'taxes', 'sell_details', 'transaction', 'commission_agent', 'types', 'customer_groups', 'pos_settings', 'waiters', 'invoice_schemes', 'default_invoice_schemes', 'redeem_details', 'edit_discount', 'edit_price', 'accounts', 'shipping_statuses', 'warranties', 'default_unit_id', 'categories', 'products', 'selected_remaining_widths'));
    }

    public function getSellEntryRow(Request $request)
    {
        if (!$request->ajax()) {
            return [
                'success' => false,
                'message' => __('messages.permission_denied')
            ];
        }

        $plate_stock_id = $request->input('plate_stock_id');
        $plate_stock = PlateStock::with(['product', 'variation', 'warehouse'])
            ->find($plate_stock_id);

        if(!$plate_stock){
            return [
                'success' => false,
                'message' => __('sale.plate_not_found')
            ];
        }

        $width = (float) $request->input('width');
        $height = (float) $request->input('height');
        $quantity = $this->productUtil->num_uf(($request->input('quantity')));
        $row_index = $request->input('row_index');
        $transaction_sell_line_id = $request->input('transaction_sell_line_id');
        $total_deliver_quantity = $request->input('total_deliver_quantity');
        $total_selected_quantity = $request->input('total_selected_quantity');
        $sell_line = TransactionSellLine::find($transaction_sell_line_id);
        $selected_width = $plate_stock->width;
        $row_id = 'row_'. time() .rand(100, 999);
        $row_insert_after_id = $request->input('row_insert_after_id');
        $is_cut = $request->input('is_cut') != '' ? intval($request->input('is_cut')) : 1;

        $output = [
            'success' => false,
            'message' => __('sale.plate_invalid'),
        ];

        if(in_array($sell_line->sub_unit->type, ['area', 'meter'])) {
            if($sell_line->sub_unit->type == 'area'){
                if(!$width || !$height){
                    return [
                        'success' => false,
                        'message' => __('sale.not_is_plate')
                    ];
                }
            }elseif($sell_line->sub_unit->type == 'meter'){
                if(!$width){
                    return [
                        'success' => false,
                        'message' => __('sale.not_is_plate')
                    ];
                }else{
                    $height = 1;
                }
            }

            $selected_remaining_widths_json = $request->input('remaining_widths');
            $current_remaining_widths_text = $request->input('current_remaining_widths_text');
            $quantity_available = $plate_stock->qty_available - $total_selected_quantity;
            $choose_plate_result = $this->choosePlate($plate_stock, $width, $height, $quantity, $selected_width, $total_selected_quantity, $total_deliver_quantity, $current_remaining_widths_text, $selected_remaining_widths_json, $quantity_available, 1, 'auto', $is_cut, 1);

            if($choose_plate_result['success']){
                $quantity_before_cut = $choose_plate_result['data']['quantity_before_cut'];
                $quantity_after_cut = $choose_plate_result['data']['quantity_after_cut'];
                $plates_if_not_cut = $choose_plate_result['data']['plates_if_not_cut'];
                $plates_for_print = $choose_plate_result['data']['plates_for_print'];
                $remaining_widths = $choose_plate_result['data']['remaining_widths'];
                $remaining_widths_json = $choose_plate_result['data']['remaining_widths_json'];
                $enabled_not_cut = $choose_plate_result['data']['enabled_not_cut'];
                $new_remaining_widths_text = $choose_plate_result['data']['new_remaining_widths_text'];
                $selected_width = $choose_plate_result['data']['selected_width'];
                $remaining_widths_if_cut_json = $choose_plate_result['data']['remaining_widths_if_cut_json'];
                $remaining_widths_if_not_cut_json = $choose_plate_result['data']['remaining_widths_if_not_cut_json'];
                $all_remaining_widths = $choose_plate_result['data']['all_remaining_widths'];
                $max_quantity_cut_from_one = $choose_plate_result['data']['max_quantity_cut_from_one'];
                $plate_quantity_need_to_cut_from_one_roll = 1;
                $auto_cut = 1;

                //Manually choose plate
                $sell_entry_row_html = view('stock_deliver.partials.sell_by_area_entry_row')
                    ->with(compact(
                        'plate_stock',
                        'width',
                        'transaction_sell_line_id',
                        'quantity_before_cut',
                        'quantity_after_cut',
                        'remaining_widths',
                        'remaining_widths_json',
                        'plates_if_not_cut',
                        'plates_for_print',
                        'remaining_widths_if_cut_json',
                        'remaining_widths_if_not_cut_json',
                        'enabled_not_cut',
                        'selected_width',
                        'row_index',
                        'is_cut',
                        'row_id'
                    ))->render();

                $view = view('stock_deliver.partials.choose_plate_manually')
                    ->with(compact(
                        'plate_stock',
                        'width',
                        'quantity',
                        'selected_width',
                        'total_selected_quantity',
                        'total_deliver_quantity',
                        'quantity_before_cut',
                        'quantity_after_cut',
                        'remaining_widths_if_cut_json',
                        'remaining_widths_if_not_cut_json',
                        'remaining_widths',
                        'row_index',
                        'row_id',
                        'transaction_sell_line_id',
                        'remaining_widths_json',
                        'new_remaining_widths_text',
                        'row_insert_after_id',
                        'all_remaining_widths',
                        'selected_remaining_widths_json',
                        'current_remaining_widths_text',
                        'enabled_not_cut',
                        'plates_if_not_cut',
                        'plates_for_print',
                        'max_quantity_cut_from_one',
                        'plate_quantity_need_to_cut_from_one_roll',
                        'auto_cut',
                        'is_cut',
                        'sell_entry_row_html'
                    ))->render();

                $output = [
                    'success' => true,
                    'data' => $view,
                ];
            }else{
                $output = $choose_plate_result;
            }
        }elseif($sell_line->sub_unit->type == 'pcs'){
            if($plate_stock->variation_id != $sell_line->variation_id){
                return [
                    'success' => false,
                    'message' => __('sale.product_not_same'),
                ];
            }

            $quantity_available = $plate_stock->qty_available - $total_selected_quantity;
            if($quantity_available <= 0){
                return [
                    'success' => false,
                    'message' => __('sale.product_out_of_stock'),
                ];
            }

            $quantity_need_to_get = $quantity - $total_deliver_quantity;
            if($quantity_available >= $quantity_need_to_get){
                $selected_quantity = $quantity_need_to_get;
            }else{
                $selected_quantity = $quantity_available;
            }

            $view =  view('stock_deliver.partials.sell_by_pcs_entry_row')
                ->with(compact(
                    'plate_stock',
                    'row_index',
                    'selected_quantity',
                    'transaction_sell_line_id',
                    'row_id'
                ))->render();

            $output = [
                'success' => true,
                'data' => $view,
            ];
        }

        return $output;
    }

    public function choosePlate($plate_stock, $width, $height, $quantity, $selected_width, $total_selected_quantity, $total_deliver_quantity, $current_remaining_widths_text, $selected_remaining_widths_json, $quantity_available, $auto_cut = 1, $get_plate_type = 'auto', $is_cut = 1, $plate_quantity_need_to_cut_from_one_roll = 1){
        if(bccomp($plate_stock->width, $width, 3) == -1 || bccomp($plate_stock->height, $height, 3) == -1){
            return [
                'success' => false,
                'message' => __('sale.plate_not_valid')
            ];
        }

        $business_id = request()->session()->get('user.business_id');
        $business_details = $this->businessUtil->getDetails($business_id);
        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);
        $area_allow_cut = !empty($pos_settings['area_allow_cut']) ? $pos_settings['area_allow_cut'] : 0;

        $enabled_not_cut = true;
        $quantity_need_to_cut = $quantity - $total_deliver_quantity;
        $quantity_before_cut = 0;
        $quantity_after_cut = 0;
        $plates_if_not_cut = [];
        $plates_for_print = [
            'cut' => [],
            'not_cut' => [],
        ];
        $remaining_widths = [];
        $selected_remaining_widths = [];
        $max_quantity_cut_from_one = 1;

        if(!empty($selected_remaining_widths_json)){
            $selected_remaining_widths = json_decode($selected_remaining_widths_json, 1);
        }

        //Get all remaining plates
        $all_remaining_widths = [];

        if(in_array($get_plate_type, ['auto', 'remaining_plate']) && !empty($selected_remaining_widths)){
            foreach($selected_remaining_widths as $selected_remaining_width){
                if(bccomp($selected_remaining_width['width'], $width, 3) == 1 || bccomp($selected_remaining_width['width'], $width, 3) == 0){
                    $results = $this->calculatePlateCut($width, $height, $selected_remaining_width['width'], $plate_stock->height, $selected_remaining_width['quantity'], $quantity_need_to_cut, $quantity_before_cut, $quantity_after_cut, $plates_if_not_cut, $area_allow_cut, $enabled_not_cut, $remaining_widths, $auto_cut, $plates_for_print, $plate_stock, $is_cut, $plate_quantity_need_to_cut_from_one_roll);

                    if(!empty($results)){
                        $all_remaining_widths[] = $selected_remaining_width;
                    }
                }
            }

            if($get_plate_type == 'auto'){
                //If is auto get mode
                foreach($selected_remaining_widths as $selected_remaining_width){
                    if(bccomp($selected_remaining_width['width'], $width, 3) == 1 || bccomp($selected_remaining_width['width'], $width, 3) == 0){
                        $results = $this->calculatePlateCut($width, $height, $selected_remaining_width['width'], $plate_stock->height, $selected_remaining_width['quantity'], $quantity_need_to_cut, $quantity_before_cut, $quantity_after_cut, $plates_if_not_cut, $area_allow_cut, $enabled_not_cut, $remaining_widths, $auto_cut, $plates_for_print, $plate_stock, $is_cut, $plate_quantity_need_to_cut_from_one_roll);

                        if(!empty($results)){
                            $quantity_before_cut = $results['quantity_before_cut'];
                            $quantity_after_cut = $results['quantity_after_cut'];
                            $plates_if_not_cut = $results['plates_if_not_cut'];
                            $plates_for_print = $results['plates_for_print'];
                            $remaining_widths = $results['remaining_widths'];
                            $enabled_not_cut = $results['enabled_not_cut'];
                            $selected_width = $selected_remaining_width['width'];
                            $max_quantity_cut_from_one = $results['max_quantity_cut_from_one'];
                            break;
                        }
                    }
                }
            }else{
                //If is remaining plate get mode
                $results = $this->calculatePlateCut($width, $height, $selected_width, $plate_stock->height, $quantity_available, $quantity_need_to_cut, $quantity_before_cut, $quantity_after_cut, $plates_if_not_cut, $area_allow_cut, $enabled_not_cut, $remaining_widths, $auto_cut, $plates_for_print, $plate_stock, $is_cut, $plate_quantity_need_to_cut_from_one_roll);

                if(!empty($results)){
                    $quantity_before_cut = $results['quantity_before_cut'];
                    $quantity_after_cut = $results['quantity_after_cut'];
                    $plates_if_not_cut = $results['plates_if_not_cut'];
                    $plates_for_print = $results['plates_for_print'];
                    $remaining_widths = $results['remaining_widths'];
                    $enabled_not_cut = $results['enabled_not_cut'];
                    $max_quantity_cut_from_one = $results['max_quantity_cut_from_one'];
                }
            }
        }

        if(in_array($get_plate_type, ['auto', 'new_plate']) && $quantity_after_cut < $quantity && $quantity_before_cut == 0){
            //Origin plate
            $results = $this->calculatePlateCut($width, $height, $plate_stock->width, $plate_stock->height, $quantity_available, $quantity_need_to_cut, $quantity_before_cut, $quantity_after_cut, $plates_if_not_cut, $area_allow_cut, $enabled_not_cut, $remaining_widths, $auto_cut, $plates_for_print, $plate_stock, $is_cut, $plate_quantity_need_to_cut_from_one_roll);

            if(!empty($results)){
                $quantity_before_cut = $results['quantity_before_cut'];
                $quantity_after_cut = $results['quantity_after_cut'];
                $plates_if_not_cut = $results['plates_if_not_cut'];
                $plates_for_print = $results['plates_for_print'];
                $remaining_widths = $results['remaining_widths'];
                $enabled_not_cut = $results['enabled_not_cut'];
                $max_quantity_cut_from_one = $results['max_quantity_cut_from_one'];
            }
        }

        if($quantity_before_cut == 0){
            return [
                'success' => false,
                'message' => __('sale.plate_out_of_stock')
            ];
        }

        $new_plates_for_print = [
            'cut' => [],
            'not_cut' => [],
        ];

        foreach ($plates_for_print['cut'] as $plate){
            $new_plates_for_print['cut'][] = $plate;
        }

        foreach ($plates_for_print['not_cut'] as $plate){
            $new_plates_for_print['not_cut'][] = $plate;
        }
        $plates_if_not_cut = json_encode($plates_if_not_cut);
        $plates_for_print = json_encode($new_plates_for_print);
        $remaining_widths_if_cut = [];
        $remaining_widths_if_not_cut = [];

        foreach($remaining_widths as $remaining_width){
            //Cut
            if($remaining_width['cut'] != 0){
                $remaining_width_if_cut_is_exist = false;

                foreach($remaining_widths_if_cut as $key => $remaining_width_if_cut){
                    if($remaining_width_if_cut['width'] == $remaining_width['cut']){
                        $remaining_widths_if_cut[$key]['quantity'] += 1;
                        $remaining_width_if_cut_is_exist = true;
                    }
                }

                if(!$remaining_width_if_cut_is_exist){
                    $remaining_widths_if_cut[] = [
                        'width' => $remaining_width['cut'],
                        'quantity' => 1,
                    ];
                }
            }

            //Not cut
            if($remaining_width['not_cut'] != 0){
                $remaining_width_if_not_cut_is_exist = false;

                foreach($remaining_widths_if_not_cut as $key => $remaining_width_if_not_cut){
                    if($remaining_width_if_not_cut['width'] == $remaining_width['not_cut']){
                        $remaining_widths_if_not_cut[$key]['quantity'] += 1;
                        $remaining_width_if_not_cut_is_exist = true;
                    }
                }

                if(!$remaining_width_if_not_cut_is_exist){
                    $remaining_widths_if_not_cut[] = [
                        'width' => $remaining_width['not_cut'],
                        'quantity' => 1,
                    ];
                }
            }
        }

        $remaining_widths_if_cut_json = json_encode($remaining_widths_if_cut);
        $remaining_widths_if_not_cut_json = json_encode($remaining_widths_if_not_cut);

        //Get remaining widths if choose same plate
        $current_remaining_widths = json_decode($current_remaining_widths_text, 1);
        if(!$current_remaining_widths){
            $current_remaining_widths = [];
        }

        $old_remaining_widths = $current_remaining_widths;

        foreach($current_remaining_widths as $key => $current_remaining_width){
            if($current_remaining_width['width'] == $selected_width){
                $quantity_after_change = $old_remaining_widths[$key]['quantity'] - $quantity_before_cut;

                if($quantity_after_change <= 0){
                    unset($old_remaining_widths[$key]);
                }else{
                    $old_remaining_widths[$key]['quantity'] = $quantity_after_change;
                }
            }
        }

        if ($is_cut){
            $merge_remaining_widths = array_merge($old_remaining_widths, $remaining_widths_if_cut);
        }else{
            $merge_remaining_widths = array_merge($old_remaining_widths, $remaining_widths_if_not_cut);
        }
//        $merge_remaining_widths = array_merge($old_remaining_widths, $remaining_widths_if_cut);
        $check_remaining_widths = [];

        foreach($merge_remaining_widths as $merge_remaining_width){
            if(isset($check_remaining_widths[strval($merge_remaining_width['width'])])){
                $check_remaining_widths[strval($merge_remaining_width['width'])] += $merge_remaining_width['quantity'];
            }else{
                $check_remaining_widths[strval($merge_remaining_width['width'])] = $merge_remaining_width['quantity'];
            }
        }

        $new_remaining_widths = [];
        foreach ($check_remaining_widths as $width => $quantity){
            $new_remaining_widths[] = [
                'width' => floatval($width),
                'quantity' => $quantity,
            ];
        }

        $new_remaining_widths_text = json_encode($new_remaining_widths);
        $remaining_widths_json = json_encode($remaining_widths);

        return [
            'success' => true,
            'data' => [
                'quantity_before_cut' => $quantity_before_cut,
                'quantity_after_cut' => $quantity_after_cut,
                'plates_if_not_cut' => $plates_if_not_cut,
                'plates_for_print' => $plates_for_print,
                'enabled_not_cut' => $enabled_not_cut,
                'selected_width' => $selected_width,
                'remaining_widths' => $remaining_widths,
                'remaining_widths_json' => $remaining_widths_json,
                'new_remaining_widths_text' => $new_remaining_widths_text,
                'remaining_widths_if_cut_json' => $remaining_widths_if_cut_json,
                'remaining_widths_if_not_cut_json' => $remaining_widths_if_not_cut_json,
                'all_remaining_widths' => $all_remaining_widths,
                'max_quantity_cut_from_one' => $max_quantity_cut_from_one,
                'plate_quantity_need_to_cut_from_one_roll' => $plate_quantity_need_to_cut_from_one_roll,
            ]
        ];
    }

    private function calculatePlateCut($order_width, $order_height, $deliver_width, $deliver_height, $quantity_available, $quantity_need_to_cut, $quantity_before_cut, $quantity_after_cut, $plates_if_not_cut, $area_allow_cut, $enabled_not_cut, $remaining_widths = [], $auto_cut = 1, $plates_for_print, $plate_stock, $is_cut = 1, $plate_quantity_need_to_cut_from_one_roll = 1)
    {
        $success = false;
        $max_quantity_cut_from_one = intval(bcdiv($deliver_width, $order_width));

        if($quantity_available > 0){
            if($auto_cut){
                $quantity_cut_from_one = intval(bcdiv($deliver_width, $order_width));
            }else{
                $quantity_cut_from_one = $plate_quantity_need_to_cut_from_one_roll;
            }

            while ($quantity_before_cut < $quantity_available){
                $success = true;
                $quantity_before_cut++;

                for($i=1; $i<=$quantity_cut_from_one; $i++){
                    $quantity_after_cut++;

                    $remaining_widths[$quantity_before_cut - 1]['cut'] = round($deliver_width - $i * $order_width, 3);
                    $remaining_area_width = $remaining_widths[$quantity_before_cut - 1]['cut'] * $deliver_height;

                    $remaining_height = $deliver_height - $order_height;
                    $remaining_area_height = $i * $order_width * $remaining_height;

                    $remaining_area = $remaining_area_width + $remaining_area_height;

                    if($remaining_area <= $area_allow_cut){
                        $remaining_widths[$quantity_before_cut - 1]['not_cut'] = 0;
                    }else{
                        $remaining_widths[$quantity_before_cut - 1]['not_cut'] = $remaining_widths[$quantity_before_cut - 1]['cut'];
                    }

                    $remaining_width = $remaining_widths[$quantity_before_cut - 1];

                    if($i == $quantity_cut_from_one || $quantity_after_cut == $quantity_need_to_cut){
                        if($remaining_area <= $area_allow_cut){
                            $width_if_not_cut = $deliver_width - ($i - 1) * $order_width;
                        }else{
                            $width_if_not_cut = $order_width;
                        }
                    }else{
                        $width_if_not_cut = $order_width;
                    }

                    $is_exist = false;

                    foreach($plates_if_not_cut as $key => $plate){
                        if($plate['width'] == $width_if_not_cut){
                            $plates_if_not_cut[$key]['quantity'] += 1;
                            $is_exist = true;
                        }
                    }

                    if(!$is_exist){
                        $plates_if_not_cut[] = [
                            'width' => $width_if_not_cut,
                            'quantity' => 1,
                            'enabled_not_cut' => $enabled_not_cut,
                        ];
                    }

                    // Get plates for print
                    if($i == $quantity_cut_from_one || $quantity_after_cut == $quantity_need_to_cut){
                        $cut_key = strval($remaining_width['cut']);
                        $plates_for_print['cut'][$cut_key] = $this->getPlatesForPrint('cut', $cut_key, $plates_for_print, $remaining_width, $i, $deliver_width, $order_width, $plate_stock->width);

                        $not_cut_key = strval($remaining_width['not_cut']);
                        $plates_for_print['not_cut'][$not_cut_key] = $this->getPlatesForPrint('not_cut', $not_cut_key, $plates_for_print, $remaining_width, $i, $deliver_width, $width_if_not_cut, $plate_stock->width);
                    }

                    if($quantity_after_cut == $quantity_need_to_cut){
                        break 2;
                    }
                }
            }
        }

        foreach ($remaining_widths as $remaining_width){
            if($remaining_width['not_cut'] == $remaining_width['cut']){
                $enabled_not_cut = false;
            }
        }

        if($success){
            $results = [
                'quantity_before_cut' => $quantity_before_cut,
                'quantity_after_cut' => $quantity_after_cut,
                'plates_if_not_cut' => $plates_if_not_cut,
                'plates_for_print' => $plates_for_print,
                'remaining_widths' => $remaining_widths,
                'enabled_not_cut' => $enabled_not_cut,
                'max_quantity_cut_from_one' => $max_quantity_cut_from_one,
            ];
        }else{
            $results = [];
        }

        return $results;
    }

    function getPlatesForPrint($type, $key, $plates_for_print, $remaining_width, $i, $selected_width, $deliver_width, $origin_width)
    {
        $plate = [];

        if(isset($plates_for_print[$type][$key])){
            $plate = $plates_for_print[$type][$key];
            $plate['selected_quantity'] += 1;
            $plate['deliver_quantity'] += $i;
            $plate['remaining_quantity'] += 1;
        }else{
            $selected_width = floatval($selected_width);
            $origin_width = floatval($origin_width);

            if($selected_width == $origin_width){
                $is_origin = 1;
            }else{
                $is_origin = 0;
            }

            $plate = [
                'selected_width' => $selected_width,
                'selected_quantity' => 1,

                'deliver_width' => $deliver_width,
                'deliver_quantity' => $i,

                'remaining_width' => $remaining_width[$type],
                'remaining_quantity' => 1,

                'is_origin' => $is_origin,
            ];
        }

        return $plate;
    }

    public function changePlateManually(Request $request)
    {
        $plate_stock_id = intval($request->input('plate_stock_id'));
        $plate_stock = PlateStock::with(['product', 'variation', 'warehouse'])
            ->find($plate_stock_id);
        $width = floatval($request->input('order_width'));
        $row_index = $request->input('row_index');
        $row_id = $request->input('row_id');
        $transaction_sell_line_id = $request->input('transaction_sell_line_id');
        $quantity_before_cut = 0;
        $quantity_after_cut = 0;
        $quantity_need_to_cut = $request->input('quantity_after_cut');
        $remaining_widths_json = $request->input('remaining_widths');
        $remaining_widths = json_decode($remaining_widths_json, true);
        $plates_if_not_cut = $request->input('plates_if_not_cut');
        $plates_for_print = $request->input('plates_for_print');
        $remaining_widths_if_cut_json = $request->input('remaining_widths_if_cut');
        $remaining_widths_if_not_cut_json = $request->input('remaining_widths_if_not_cut');
        $enabled_not_cut = $request->input('enabled_not_cut');
        $selected_width = floatval($request->input('deliver_width'));
        $new_remaining_widths_text = $request->input('new_remaining_widths_text');
        $row_insert_after_id = $request->input('row_insert_after_id');
        $current_remaining_widths_text = $request->input('current_remaining_widths_text');
        $selected_remaining_widths_json = $request->input('current_remaining_widths_text');
        $auto_cut = $request->input('auto_cut');
        $get_from_remaining_plate = $request->input('get_from_remaining_plate');
        if(!$get_from_remaining_plate){
            $get_plate_type = 'new_plate';
        }else{
            $get_plate_type = 'remaining_plate';
        }
        $total_selected_quantity = $request->input('selected_quantity');
        $total_deliver_quantity = $request->input('total_deliver_quantity');
        $order_quantity = intval($request->input('order_quantity'));
        if($quantity_need_to_cut > $order_quantity - $total_deliver_quantity){
            $quantity_need_to_cut = $order_quantity - $total_deliver_quantity;
        }
        $quantity_available = $request->input('quantity_available');
        $is_cut = $request->input('is_cut') != '' ? intval($request->input('is_cut')) : 1;
        $plate_quantity_need_to_cut_from_one_roll = intval($request->input('plate_quantity_need_to_cut_from_one_roll'));
        $split_step = $request->input('split_step');

        $choose_plate_result = $this->choosePlate($plate_stock, $width, $plate_stock->height, $quantity_need_to_cut, $selected_width, $total_selected_quantity, $quantity_after_cut, $current_remaining_widths_text, $selected_remaining_widths_json, $quantity_available, $auto_cut, $get_plate_type, $is_cut, $plate_quantity_need_to_cut_from_one_roll);

        if($choose_plate_result['success']){
            if($choose_plate_result['data']['enabled_not_cut'] && $split_step == 'first'){
                $quantity_before_cut_input = intval($request->input('quantity_before_cut'));

                if($quantity_need_to_cut > $quantity_before_cut_input && $choose_plate_result['data']['max_quantity_cut_from_one'] > 1){
                    $split_step = 'first';
                    $auto_cut = 0;
                    $plate_quantity_need_to_cut_from_one_roll = $choose_plate_result['data']['max_quantity_cut_from_one'] - 1;
                    $current_quantity_need_to_cut = $quantity_before_cut_input * $plate_quantity_need_to_cut_from_one_roll;
                    $choose_plate_result = $this->choosePlate($plate_stock, $width, $plate_stock->height, $current_quantity_need_to_cut, $selected_width, $total_selected_quantity, $quantity_after_cut, $current_remaining_widths_text, $selected_remaining_widths_json, $quantity_available, 0, $get_plate_type, 1, $plate_quantity_need_to_cut_from_one_roll);
                }
            }

            $quantity_before_cut = $choose_plate_result['data']['quantity_before_cut'];
            $quantity_after_cut = $choose_plate_result['data']['quantity_after_cut'];
            $plates_if_not_cut = $choose_plate_result['data']['plates_if_not_cut'];
            $plates_for_print = $choose_plate_result['data']['plates_for_print'];
            $enabled_not_cut = $choose_plate_result['data']['enabled_not_cut'];
            $selected_width = $choose_plate_result['data']['selected_width'];
            $remaining_widths = $choose_plate_result['data']['remaining_widths'];
            $new_remaining_widths_text = $choose_plate_result['data']['new_remaining_widths_text'];
            $remaining_widths_if_cut_json = $choose_plate_result['data']['remaining_widths_if_cut_json'];
            $remaining_widths_if_not_cut_json = $choose_plate_result['data']['remaining_widths_if_not_cut_json'];
            $remaining_widths_json = json_encode($remaining_widths);
            $max_quantity_cut_from_one = $choose_plate_result['data']['max_quantity_cut_from_one'];
            $plate_quantity_need_to_cut_from_one_roll = $choose_plate_result['data']['plate_quantity_need_to_cut_from_one_roll'];

            $sell_entry_row_html = view('stock_deliver.partials.sell_by_area_entry_row')
                ->with(compact(
                    'plate_stock',
                    'width',
                    'transaction_sell_line_id',
                    'quantity_before_cut',
                    'quantity_after_cut',
                    'remaining_widths',
                    'remaining_widths_json',
                    'plates_if_not_cut',
                    'plates_for_print',
                    'remaining_widths_if_cut_json',
                    'remaining_widths_if_not_cut_json',
                    'enabled_not_cut',
                    'selected_width',
                    'row_index',
                    'is_cut',
                    'row_id'
                ))->render();

            $output = [
                'success' => true,
                'data' => [
                    'quantity_before_cut' => $quantity_before_cut,
                    'quantity_after_cut' => $quantity_after_cut,
                    'remaining_widths' => $remaining_widths,
                    'remaining_widths_if_cut_json' => $remaining_widths_if_cut_json,
                    'remaining_widths_if_not_cut_json' => $remaining_widths_if_not_cut_json,
                    'remaining_widths_json' => $remaining_widths_json,
                    'new_remaining_widths_text' => $new_remaining_widths_text,
                    'plates_if_not_cut' => $plates_if_not_cut,
                    'plates_for_print' => $plates_for_print,
                    'enabled_not_cut' => $enabled_not_cut,
                    'selected_width' => $selected_width,
                    'max_quantity_cut_from_one' => $max_quantity_cut_from_one,
                    'plate_quantity_need_to_cut_from_one_roll' => $plate_quantity_need_to_cut_from_one_roll,
                    'auto_cut' => $auto_cut,
                    'split_step' => $split_step,
                    'is_cut',
                    'sell_entry_row_html' => $sell_entry_row_html,
                ],
            ];
        }else{
            $output = $choose_plate_result;
        }

        return $output;
    }

    /**
     * Deletes a media file from storage and database.
     *
     * @param  int  $media_id
     * @return json
     */
    public function deleteMedia($media_id)
    {
        if (!auth()->user()->can('product.update') && !auth()->user()->can('sell.payments') && !auth()->user()->can('sell.receipt_expense')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');

                Media::deleteMedia($business_id, $media_id);

                $output = ['success' => true,
                    'msg' => __("lang_v1.file_deleted_successfully")
                ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output = ['success' => false,
                    'msg' => __("messages.something_went_wrong")
                ];
            }

            return $output;
        }
    }

    public function addVAT($id){
        $transaction = Transaction::query()->find($id);
        return view('sell.partials.add_vat')->with(compact('id', 'transaction'));
    }

    public function storeVAT(Request $request){
        $dataUpdate = [];
        $transaction = Transaction::query()->find($request->transaction_id);
        DB::beginTransaction();
        if (empty($transaction)) {
            return redirect('sells')->with('status', ['success' => 0, 'msg' => __('lang_v1.update_fail')]);
        }

        $dataUpdate = array_merge($dataUpdate, ['vat_money' => empty($request->vat) ? 0 : $this->productUtil->num_uf($request->vat)]);
        $dataUpdate = array_merge($dataUpdate, ['shipping_charges' => empty($request->transfer_fee) ? 0 : $this->productUtil->num_uf($request->transfer_fee)]);

        if (!empty($dataUpdate)) {
            $vat                = empty($dataUpdate['vat_money']) ? 0 : $dataUpdate['vat_money'];
            $transferFee        = empty($dataUpdate['shipping_charges']) ? 0 : $dataUpdate['shipping_charges'];
            $subVat             = empty($vat) ? 0 : $transaction->vat_money;
            $subShippingCharge  = empty($transferFee) ? 0 : $transaction->shipping_charges;
            $finalTotal         = $transaction->final_total - $transaction->vat_money - $transaction->shipping_charges + $vat + $transferFee;

            $dataUpdate = array_merge($dataUpdate, ['final_total' => $finalTotal]);
            $transaction->update($dataUpdate);
            DB::commit();

            return redirect('sells')->with('status', [
                'success' => 1,
                'msg'    => __('lang_v1.update_success')
            ]);
        }

        DB::rollback();

        return redirect('sells')->with('status', [
            'success' => 0,
            'msg'    => __('lang_v1.update_fail')
        ]);
    }

    public function editCodBySeller($id) {
//        if (!auth()->user()->can('sell.create') && !auth()->user()->can('direct_sell.access') && !auth()->user()->can('view_own_sell_only')) {
        if (!auth()->user()->can('sell.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $sell = Transaction::where('business_id', $business_id)
            ->where('id', $id)
            ->select(
                '*',
                DB::raw('(SELECT TP.amount FROM transaction_payments as TP 
                    WHERE transactions.id = TP.transaction_id AND TP.type = "cod"
                    AND TP.approval_status <> "reject" ORDER BY TP.id LIMIT 1) as cod'
                ),
                DB::raw('(SELECT TP.note FROM transaction_payments as TP 
                    WHERE transactions.id = TP.transaction_id AND TP.type = "cod"
                    AND TP.approval_status <> "reject" ORDER BY TP.id LIMIT 1) as cod_note'
                ),
                DB::raw('(SELECT TP.id FROM transaction_payments as TP 
                    WHERE transactions.id = TP.transaction_id AND TP.type = "cod"
                    AND TP.approval_status <> "reject" ORDER BY TP.id LIMIT 1) as payment_id'
                )
            )
            ->firstOrFail();

        $shipping_statuses = $this->transactionUtil->shipping_statuses();
        $shipping_status_colors = $this->shipping_status_colors;

        return view('sale_pos.update_cod')
            ->with(compact(
                'sell',
                'shipping_statuses',
                'shipping_status_colors'
            ));
    }

    /**
     * Prints invoice for sell
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function printSubInvoice(Request $request, $transaction_id)
    {
        if (request()->ajax()) {
            try {
                $output = ['success' => 0,
                    'msg' => trans("messages.something_went_wrong")
                ];

                $business_id = $request->session()->get('user.business_id');

                $transaction = Transaction::where('business_id', $business_id)
                    ->where('id', $transaction_id)
                    ->with(['location'])
                    ->first();

                if (empty($transaction)) {
                    return $output;
                }

                $printer_type = 'browser';
                if (!empty(request()->input('check_location')) && request()->input('check_location') == true) {
                    $printer_type = $transaction->location->receipt_printer_type;
                }

                $is_package_slip = !empty($request->input('package_slip')) ? true : false;

                $receipt = $this->receiptContent($business_id, $transaction->location_id, $transaction_id, $printer_type, $is_package_slip, false);

                if (!empty($receipt)) {
                    $output = ['success' => 1, 'receipt' => $receipt];
                }
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output = ['success' => 0,
                    'msg' => trans("messages.something_went_wrong")
                ];
            }

            return $output;
        }
    }

    /**
     * Returns the content for the receipt
     *
     * @param  int  $business_id
     * @param  int  $location_id
     * @param  int  $transaction_id
     * @param string $printer_type = null
     *
     * @return array
     */
    private function receiptContent(
        $business_id,
        $location_id,
        $transaction_id,
        $printer_type = null,
        $is_package_slip = false,
        $from_pos_screen = true
    ) {
        $output = ['is_enabled' => false,
            'print_type' => 'browser',
            'html_content' => null,
            'printer_config' => [],
            'data' => []
        ];


        $business_details = $this->businessUtil->getDetails($business_id);
        $location_details = BusinessLocation::find($location_id);

        if ($from_pos_screen && $location_details->print_receipt_on_invoice != 1) {
            return $output;
        }
        //Check if printing of invoice is enabled or not.
        //If enabled, get print type.
        $output['is_enabled'] = true;

        $invoice_layout = $this->businessUtil->invoiceLayout($business_id, $location_id, $location_details->invoice_layout_id);

        //Check if printer setting is provided.
        $receipt_printer_type = is_null($printer_type) ? $location_details->receipt_printer_type : $printer_type;

        $receipt_details = $this->transactionUtil->getReceiptDetails($transaction_id, $location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type, true);

        $currency_details = [
            'symbol' => $business_details->currency_symbol,
            'thousand_separator' => $business_details->thousand_separator,
            'decimal_separator' => $business_details->decimal_separator,
        ];
        $receipt_details->currency = $currency_details;

        if ($is_package_slip) {
            $output['html_content'] = view('sale_pos.receipts.packing_slip', compact('receipt_details'))->render();
            return $output;
        }
        //If print type browser - return the content, printer - return printer config data, and invoice format config
        if ($receipt_printer_type == 'printer') {
            $output['print_type'] = 'printer';
            $output['printer_config'] = $this->businessUtil->printerConfig($business_id, $location_details->printer_id);
            $output['data'] = $receipt_details;
        } else {
            $layout = 'sale_pos.receipts.sub_classic';

            $output['html_content'] = view($layout, compact('receipt_details'))->render();
        }

        return $output;
    }

    public function createDebitPaper($id) {
//        if (!auth()->user()->can('sell.create') && !auth()->user()->can('direct_sell.access') && !auth()->user()->can('view_own_sell_only')) {
        if (!auth()->user()->can('sell.payments') && !auth()->user()->can('sell.receipt_expense')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $sell = Transaction::where('business_id', $business_id)
            ->with('media')
            ->where('id', $id)
            ->select(
                '*',
                DB::raw('(SELECT TP.amount FROM transaction_payments as TP 
                    WHERE transactions.id = TP.transaction_id AND TP.type = "cod"
                    AND TP.approval_status <> "reject" ORDER BY TP.id LIMIT 1) as cod'
                ),
                DB::raw('(SELECT TP.note FROM transaction_payments as TP 
                    WHERE transactions.id = TP.transaction_id AND TP.type = "cod"
                    AND TP.approval_status <> "reject" ORDER BY TP.id LIMIT 1) as cod_note'
                ),
                DB::raw('(SELECT TP.id FROM transaction_payments as TP 
                    WHERE transactions.id = TP.transaction_id AND TP.type = "cod"
                    AND TP.approval_status <> "reject" ORDER BY TP.id LIMIT 1) as payment_id'
                )
            )
            ->firstOrFail();

        $shipping_statuses = $this->transactionUtil->shipping_statuses();
        $shipping_status_colors = $this->shipping_status_colors;

        return view('sell_of_cashier.create_debit_paper')
            ->with(compact(
                'sell',
                'shipping_statuses',
                'shipping_status_colors'
            ));
    }

    public function showDebitPaper($id) {
        if (!auth()->user()->can('sell.payments') && !auth()->user()->can('sell.receipt_expense')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $sell = Transaction::where('business_id', $business_id)
            ->with('media')
            ->where('id', $id)
            ->firstOrFail();

        $view = view('sell_of_cashier.show_debit_paper')
            ->with(compact('sell'))->render();

        return $view;
    }


    public function showStockDeliver($id) {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('stock.view_deliver_orders')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $query = Transaction::where('business_id', $business_id)
            ->where('id', $id)
            ->with([
                'contact',
                'media',
                'sell_lines.plate_lines',
                'sell_lines.plate_lines.selected_plate_stock',
                'sell_lines.plate_lines.selected_plate_stock.product',
                'sell_lines.plate_lines.selected_plate_stock.variation',
                'sell_lines.plate_lines.selected_plate_stock.warehouse',
                'sell_lines.product',
                'sell_lines.product.unit',
                'sell_lines.product.brand',
                'sell_lines.variations',
                'sell_lines.variations.product_variation',
                'payment_lines',
                'sell_lines.modifiers',
                'sell_lines.lot_details',
                'tax',
                'sell_lines.sub_unit',
                'table',
                'service_staff',
                'sell_lines.service_staff',
                'types_of_service',
                'sell_lines.warranties',
                'sell_lines' => function ($q) {
                    $q->whereNull('parent_sell_line_id');
                }
            ]);

        if (!auth()->user()->can('sell.view') && !auth()->user()->can('direct_sell.access') && auth()->user()->can('view_own_sell_only')) {
            $query->where('transactions.created_by', request()->session()->get('user.id'));
        }

        $sell = $query->firstOrFail();
        $exportSell = $sell->toArray()['sell_lines'];
        $dataSell = [];
        foreach ($exportSell as $key => $exSell){
            if(!isset($exSell['flag_changed']) || $exSell['flag_changed'] == '0'){
                $dataSell[] = $exSell;
                continue;
            }

            $tempSell = TransactionSellLinesChange::where('parent_id', $exSell['id'])
                ->with([
                    'product',
                    'product.unit',
                    'variations',
                    'variations.product_variation',
                    'modifiers',
                    'lot_details',
                    'sub_unit',
                    'service_staff',
                    'warranties',
                ])->where(function ($q) {
                    $q->whereNull('parent_sell_line_id');
                })->first();

            if($tempSell == null){
                $dataSell[] = $exSell;
                continue;
            }

            $dataSell[] = $tempSell->toArray();
        }
        $dataSell = json_decode(json_encode($dataSell), false);
        $sell->sell_lines = $dataSell;

        foreach ($sell->sell_lines as $key => $value) {
            if (!empty($value->sub_unit_id)) {
                $formated_sell_line = $this->transactionUtil->recalculateSellLineTotals($business_id, $sell->sell_lines[$key]);
//                $sell->sell_lines[$key] = $formated_sell_line;
            }
        }

        $payment_types = $this->transactionUtil->payment_types(null, true);

        $order_taxes = [];
        if (!empty($sell->tax)) {
            if ($sell->tax->is_tax_group) {
                $order_taxes = $this->transactionUtil->sumGroupTaxDetails($this->transactionUtil->groupTaxDetails($sell->tax, $sell->tax_amount));
            } else {
                $order_taxes[$sell->tax->name] = $sell->tax_amount;
            }
        }

        $business_details = $this->businessUtil->getDetails($business_id);
        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);
        $shipping_statuses = $this->transactionUtil->shipping_statuses();
        $shipping_status_colors = $this->shipping_status_colors;
        $common_settings = session()->get('business.common_settings');
        $is_warranty_enabled = !empty($common_settings['enable_product_warranty']) ? true : false;
        $approval_statuses = $this->productUtil->approvalStatuses();
        $sell_details = $this->transactionUtil->getQueryStockDeliver($id, $sell, $sell->location_id, $business_id);
        $shippers = TransactionShip::query()->where('transaction_id', $sell->id)
            ->leftJoin('users', 'users.id', '=', 'transaction_ships.ship_id')
            ->select([ DB::raw("GROUP_CONCAT(users.first_name,' ', users.last_name) AS shipper"),])
            ->groupBy('transaction_id')
            ->first();

        return view('stock_deliver.show')
            ->with(compact(
                'sell',
                'sell_details',
                'payment_types',
                'order_taxes',
                'pos_settings',
                'shipping_statuses',
                'shipping_status_colors',
                'is_warranty_enabled',
                'approval_statuses',
                'shippers'
            ));
    }
}
