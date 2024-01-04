<?php
/* LICENSE: This source file belongs to The Web Fosters. The customer
 * is provided a licence to use it.
 * Permission is hereby granted, to any person obtaining the licence of this
 * software and associated documentation files (the "Software"), to use the
 * Software for personal or business purpose ONLY. The Software cannot be
 * copied, published, distribute, sublicense, and/or sell copies of the
 * Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. THE AUTHOR CAN FIX
 * ISSUES ON INTIMATION. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
 * BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH
 * THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author     The Web Fosters <thewebfosters@gmail.com>
 * @owner      The Web Fosters <thewebfosters@gmail.com>
 * @copyright  2018 The Web Fosters
 * @license    As attached in zip file.
 */
namespace App\Http\Controllers;

use App\Account;
use App\AccountTransaction;
use App\Brands;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Contact;
use App\CustomerGroup;
use App\Events\TransactionPaymentAdded;
use App\Media;
use App\PlateStock;
use App\PlateStockDraft;
use App\Product;
use App\RemainingPlateLine;
use App\SellingPriceGroup;
use App\TaxRate;
use App\Transaction;
use App\TransactionPayment;
use App\TransactionPlateLine;
use App\TransactionPlateLinesReturn;
use App\TransactionSellLine;
use App\TypesOfService;
use App\Unit;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\CashRegisterUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Variation;
use App\Warehouse;
use App\Warranty;
use Box\Spout\Writer\Style\StyleBuilder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use Rap2hpoutre\FastExcel\FastExcel;

class SellPosController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $contactUtil;
    protected $productUtil;
    protected $businessUtil;
    protected $transactionUtil;
    protected $cashRegisterUtil;
    protected $moduleUtil;
    protected $notificationUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(
        ContactUtil $contactUtil,
        ProductUtil $productUtil,
        BusinessUtil $businessUtil,
        TransactionUtil $transactionUtil,
        CashRegisterUtil $cashRegisterUtil,
        ModuleUtil $moduleUtil,
        NotificationUtil $notificationUtil
    ) {
        $this->contactUtil = $contactUtil;
        $this->productUtil = $productUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->cashRegisterUtil = $cashRegisterUtil;
        $this->moduleUtil = $moduleUtil;
        $this->notificationUtil = $notificationUtil;

        $this->dummyPaymentLine = ['method' => 'cash', 'amount' => 0, 'note' => '', 'card_transaction_number' => '', 'card_number' => '', 'card_type' => '', 'card_holder_name' => '', 'card_month' => '', 'card_year' => '', 'card_security' => '', 'cheque_number' => '', 'bank_account_number' => '',
            'is_return' => 0, 'transaction_no' => ''];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('sell.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $with = [];
            $sells = $this->transactionUtil->getListSells($business_id);

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $sells->whereIn('transactions.location_id', $permitted_locations);
            }

            //Add condition for created_by,used in sales representative sales report

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
            /*if (request()->has('location_id')) {
                $location_id = request()->get('location_id');
                if (!empty($location_id)) {
                    $sells->where('transactions.location_id', $location_id);
                }
            }*/
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $sells->whereDate('transactions.transaction_date', '>=', $start)
                    ->whereDate('transactions.transaction_date', '<=', $end);
            }

            $sells->groupBy('transactions.id');

            $with[] = 'payment_lines';
            if (!empty($with)) {
                $sells->with($with);
            }

            $datatable = Datatables::of($sells)
                ->removeColumn('id')
                ->editColumn(
                    'final_total',
                    '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="{{$final_total}}">{{$final_total}}</span>'
                )
                ->editColumn(
                    'total_paid',
                    '<span class="display_currency total-paid" data-currency_symbol="true" data-orig-value="{{$total_paid}}">{{$total_paid}}</span>'
                )
                ->editColumn(
                    'cod',
                    '<span class="display_currency total_cod" data-currency_symbol="true" data-orig-value="{{$cod}}">{{$cod}}</span>'
                )
                ->editColumn(
                    'deposit',
                    '<span class="display_currency total_deposit" data-currency_symbol="true" data-orig-value="{{$deposit}}">{{$deposit}}</span>'
                )
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn(
                    'payment_status',
                    function ($row) {
                        $payment_status = Transaction::getPaymentStatus($row);
                        if($row->total_paid == 0){
                            $payment_approved = 0;
                        }else{
                            $payment_approved = $row->payment_approved;
                        }
                        return (string) view('sell.partials.payment_status', ['payment_status' => $payment_status, 'payment_approved' => $payment_approved, 'id' => $row->id]);
                    }
                )
                ->addColumn('total_remaining', function ($row) {
                    $total_remaining =  $row->final_total - $row->total_paid;
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

                    return $invoice_no;
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("sell.view") || auth()->user()->can("view_own_sell_only")) {
                            return  action('SellController@show', [$row->id]) ;
                        } else {
                            return '';
                        }
                    }]);

            $rawColumns = ['final_total', 'cod', 'deposit', 'total_paid', 'total_remaining', 'payment_status', 'invoice_no', 'return_due'];

            return $datatable->rawColumns($rawColumns)
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);

        return view('sale_pos.index')
            ->with(compact('business_locations', 'customers', 'is_woocommerce', 'sales_representative', 'is_cmsn_agent_enabled', 'commission_agents', 'service_staffs', 'is_tables_enabled', 'is_service_staff_enabled', 'is_types_service_enabled'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(auth()->user()->can('superadmin') || auth()->user()->can('sell.create') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && auth()->user()->can('repair.create')))) {
            abort(403, 'Unauthorized action.');
        }

        //Check if subscribed or not, then check for users quota
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action('HomeController@index'));
        } elseif (!$this->moduleUtil->isQuotaAvailable('invoices', $business_id)) {
            return $this->moduleUtil->quotaExpiredResponse('invoices', $business_id, action('SellPosController@index'));
        }

        //like:repair
        $sub_type = request()->get('sub_type');

        //Check if there is a open register, if no then redirect to Create Register screen.
        if ($this->cashRegisterUtil->countOpenedRegister() == 0) {
            return redirect()->action('CashRegisterController@create', ['sub_type' => $sub_type]);
        }

        $register_details = $this->cashRegisterUtil->getCurrentCashRegister(auth()->user()->id);

        $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);

        $business_details = $this->businessUtil->getDetails($business_id);
        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);

        $payment_lines[] = $this->dummyPaymentLine;

        $default_location = BusinessLocation::findOrFail($register_details->location_id);

        $payment_types = $this->productUtil->payment_types($default_location);

        //Shortcuts
        $shortcuts = json_decode($business_details->keyboard_shortcuts, true);
        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

        $commsn_agnt_setting = $business_details->sales_cmsn_agnt;
        $commission_agent = [];
        if ($commsn_agnt_setting == 'user') {
            $commission_agent = User::forDropdown($business_id, false);
        } elseif ($commsn_agnt_setting == 'cmsn_agnt') {
            $commission_agent = User::saleCommissionAgentsDropdown($business_id, false);
        }

        //If brands, category are enabled then send else false.
        $categories = (request()->session()->get('business.enable_category') == 1) ? Category::catAndSubCategories($business_id) : false;
        $brands = (request()->session()->get('business.enable_brand') == 1) ? Brands::where('business_id', $business_id)
            ->pluck('name', 'id')
            ->prepend(__('lang_v1.all_brands'), 'all') : false;

        $change_return = $this->dummyPaymentLine;

        $types = Contact::getContactTypes();
        $customer_groups = CustomerGroup::forDropdown($business_id);

        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false, true);
        }

        //Selling Price Group Dropdown
        $price_groups = SellingPriceGroup::forDropdown($business_id);

        $default_price_group_id = !empty($default_location->selling_price_group_id) && array_key_exists($default_location->selling_price_group_id, $price_groups) ? $default_location->selling_price_group_id : null;

        //Types of service
        $types_of_service = [];
        if ($this->moduleUtil->isModuleEnabled('types_of_service')) {
            $types_of_service = TypesOfService::forDropdown($business_id);
        }

        $shipping_statuses = $this->transactionUtil->shipping_statuses();

        $default_datetime = $this->businessUtil->format_date('now', true);

        $featured_products = $default_location->getFeaturedProducts();

        //pos screen view from module
        $pos_module_data = $this->moduleUtil->getModuleData('get_pos_screen_view', $sub_type);

        return view('sale_pos.create')
            ->with(compact(
                'business_details',
                'taxes',
                'payment_types',
                'walk_in_customer',
                'payment_lines',
                'default_location',
                'shortcuts',
                'commission_agent',
                'categories',
                'brands',
                'pos_settings',
                'change_return',
                'types',
                'customer_groups',
                'accounts',
                'price_groups',
                'types_of_service',
                'default_price_group_id',
                'shipping_statuses',
                'default_datetime',
                'featured_products',
                'sub_type',
                'pos_module_data'
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
        if (!auth()->user()->can('sell.create') && !auth()->user()->can('direct_sell.access')) {
            abort(403, 'Unauthorized action.');
        }

        $is_direct_sale = false;
        if (!empty($request->input('is_direct_sale'))) {
            $is_direct_sale = true;
        }

        //Check if there is a open register, if no then redirect to Create Register screen.
        if (!$is_direct_sale && $this->cashRegisterUtil->countOpenedRegister() == 0) {
            return redirect()->action('CashRegisterController@create');
        }

        try {
            $input = $request->except('_token');

            //Check Customer credit limit
            $is_credit_limit_exeeded = $this->transactionUtil->isCustomerCreditLimitExeeded($input);

            if ($is_credit_limit_exeeded !== false) {
                $credit_limit_amount = $this->transactionUtil->num_f($is_credit_limit_exeeded, true);
                $output = ['success' => 0,
                    'msg' => __('lang_v1.cutomer_credit_limit_exeeded', ['credit_limit' => $credit_limit_amount])
                ];

                if (!$is_direct_sale) {
                    return $output;
                } else {
                    return redirect()
                        ->action('SellController@index')
                        ->with('status', $output);
                }
            }

            $input['is_quotation'] = 0;
            //status is send as quotation from Add sales screen.
            if ($input['status'] == 'quotation') {
                $input['status'] = 'draft';
                $input['is_quotation'] = 1;
            }

            if (!empty($input['products'])) {
                $business_id = $request->session()->get('user.business_id');

                //Check if subscribed or not, then check for users quota
                if (!$this->moduleUtil->isSubscribed($business_id)) {
                    return $this->moduleUtil->expiredResponse();
                } elseif (!$this->moduleUtil->isQuotaAvailable('invoices', $business_id)) {
                    return $this->moduleUtil->quotaExpiredResponse('invoices', $business_id, action('SellPosController@index'));
                }

                $user_id = $request->session()->get('user.id');

                $discount = ['discount_type' => $input['discount_type'],
                    'discount_amount' => $input['discount_amount']
                ];
                $invoice_total = $this->productUtil->calculateInvoiceTotal($input['products'], 0, $discount);

                DB::beginTransaction();

                if (empty($request->input('transaction_date'))) {
                    $input['transaction_date'] =  \Carbon::now();
                } else {
                    $input['transaction_date'] = $this->productUtil->uf_date($request->input('transaction_date'), true);
                }
                if ($is_direct_sale) {
                    $input['is_direct_sale'] = 1;
                }

                //Set commission agent
                $input['commission_agent'] = !empty($request->input('commission_agent')) ? $request->input('commission_agent') : null;
                $commsn_agnt_setting = $request->session()->get('business.sales_cmsn_agnt');
                if ($commsn_agnt_setting == 'logged_in_user') {
                    $input['commission_agent'] = $user_id;
                }

                if (isset($input['exchange_rate']) && $this->transactionUtil->num_uf($input['exchange_rate']) == 0) {
                    $input['exchange_rate'] = 1;
                }

                //Customer group details
                $contact_id = $request->get('contact_id', null);
                $cg = $this->contactUtil->getCustomerGroup($business_id, $contact_id);
                $input['customer_group_id'] = (empty($cg) || empty($cg->id)) ? null : $cg->id;

                //set selling price group id
                $price_group_id = $request->has('price_group') ? $request->input('price_group') : null;

                //If default price group for the location exists
                $price_group_id = $price_group_id == 0 && $request->has('default_price_group') ? $request->input('default_price_group') : $price_group_id;

                $input['is_suspend'] = isset($input['is_suspend']) && 1 == $input['is_suspend']  ? 1 : 0;
                if ($input['is_suspend']) {
                    $input['sale_note'] = !empty($input['additional_notes']) ? $input['additional_notes'] : null;
                }

                //Generate reference number
                if (!empty($input['is_recurring'])) {
                    //Update reference count
                    $ref_count = $this->transactionUtil->setAndGetReferenceCount('subscription');
                    $input['subscription_no'] = $this->transactionUtil->generateReferenceNumber('subscription', $ref_count);
                }

                if ($is_direct_sale) {
                    $input['invoice_scheme_id'] = $request->input('invoice_scheme_id');
                }

                //Types of service
                if ($this->moduleUtil->isModuleEnabled('types_of_service')) {
                    $input['types_of_service_id'] = $request->input('types_of_service_id');
                    $price_group_id = !empty($request->input('types_of_service_price_group')) ? $request->input('types_of_service_price_group') : $price_group_id;
                    $input['packing_charge'] = !empty($request->input('packing_charge')) ?
                        $this->transactionUtil->num_uf($request->input('packing_charge')) : 0;
                    $input['packing_charge_type'] = $request->input('packing_charge_type');
                    $input['service_custom_field_1'] = !empty($request->input('service_custom_field_1')) ?
                        $request->input('service_custom_field_1') : null;
                    $input['service_custom_field_2'] = !empty($request->input('service_custom_field_2')) ?
                        $request->input('service_custom_field_2') : null;
                    $input['service_custom_field_3'] = !empty($request->input('service_custom_field_3')) ?
                        $request->input('service_custom_field_3') : null;
                    $input['service_custom_field_4'] = !empty($request->input('service_custom_field_4')) ?
                        $request->input('service_custom_field_4') : null;
                }

                $input['selling_price_group_id'] = $price_group_id;
                if($contact_id){
                    $contact = Contact::find($contact_id);
                    if($contact->selling_price_group_id != -1 && !$contact->is_default){
                        $input['selling_price_group_id'] = $contact->selling_price_group_id;
                    }
                }

                $transaction = $this->transactionUtil->createSellTransaction($business_id, $input, $invoice_total, $user_id);

                if (!$transaction){
                    DB::rollBack();
                    \Log::emergency('Error: Duplicate invoice_no when create sell');
                    $output = [
                        'success' => 0,
                        'msg' => trans("messages.duplicate_invoice_no_error"),
                    ];

                    return redirect()
                        ->action('SellController@index')
                        ->with('status', $output);
                }

                $this->transactionUtil->createOrUpdateSellLines($transaction, $input['products'], $input['location_id']);

                //Update final total
                $final_total = $this->transactionUtil->getSellFinalTotal($transaction);
                $transaction->final_total = $final_total;

                if (!$is_direct_sale) {
                    //Add change return
                    $change_return = $this->dummyPaymentLine;
                    $change_return['amount'] = $input['change_return'];
                    $change_return['is_return'] = 1;
                }

                $is_credit_sale = isset($input['is_credit_sale']) && $input['is_credit_sale'] == 1 ? true : false;

                //Create payments
                $payments = [];

                if (!$transaction->is_suspend && !empty($input['payment']) && !$is_credit_sale) {
                    $payments[] = $input['payment'][0];
                }

                if (!$transaction->is_suspend && !empty($input['cod']) && !$is_credit_sale) {
                    $payments[] = $input['cod'][0];
                }

                if(!empty($payments)){
                    $this->transactionUtil->createOrUpdatePaymentLines($transaction, $payments);
                }

                $update_transaction = false;
                if ($this->transactionUtil->isModuleEnabled('tables')) {
                    $transaction->res_table_id = request()->get('res_table_id');
                    $update_transaction = true;
                }
                if ($this->transactionUtil->isModuleEnabled('service_staff')) {
                    $transaction->res_waiter_id = request()->get('res_waiter_id');
                    $update_transaction = true;
                }
                if ($update_transaction) {
                    $transaction->save();
                }

                //Check for final and do some processing.
                if ($input['status'] == 'final') {
                    //update product stock
                    foreach ($input['products'] as $product) {
//                        $decrease_qty = $this->productUtil
//                                    ->num_uf($product['quantity']);
//                        if (!empty($product['base_unit_multiplier'])) {
//                            $decrease_qty = $decrease_qty * $product['base_unit_multiplier'];
//                        }


                        //this comment is delete count list in db

//                        $decrease_qty = $this->productUtil->num_uf($product['area']);
//                        if ($product['enable_stock']) {
//                            $this->productUtil->decreaseProductQuantity(
//                                $product['product_id'],
//                                $product['variation_id'],
//                                $input['location_id'],
//                                $decrease_qty
//                            );
//                        }

                        if ($product['product_type'] == 'combo') {
                            //Decrease quantity of combo as well.
                            $this->productUtil
                                ->decreaseProductQuantityCombo(
                                    $product['combo'],
                                    $input['location_id']
                                );
                        }
                    }

                    //Add payments to Cash Register
                    if (!$is_direct_sale && !$transaction->is_suspend && !empty($input['payment']) && !$is_credit_sale) {
                        $this->cashRegisterUtil->addSellPayments($transaction, $input['payment']);
                    }

                    //Update payment status
                    $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

                    if ($request->session()->get('business.enable_rp') == 1) {
                        $redeemed = !empty($input['rp_redeemed']) ? $input['rp_redeemed'] : 0;
                        $this->transactionUtil->updateCustomerRewardPoints($contact_id, $transaction->rp_earned, 0, $redeemed);
                    }

//                    //Allocate the quantity from purchase and add mapping of
//                    //purchase & sell lines in
//                    //transaction_sell_lines_purchase_lines table
//                    $business_details = $this->businessUtil->getDetails($business_id);
//                    $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);
//
//                    $business = ['id' => $business_id,
//                                    'accounting_method' => $request->session()->get('business.accounting_method'),
//                                    'location_id' => $input['location_id'],
//                                    'pos_settings' => $pos_settings
//                                ];
//                    $this->transactionUtil->mapPurchaseSell($business, $transaction->sell_lines, 'purchase');

                    //Auto send notification
                    $this->notificationUtil->autoSendNotification($business_id, 'new_sale', $transaction, $transaction->contact);
                }

                //Set Module fields
                if (!empty($input['has_module_data'])) {
                    $this->moduleUtil->getModuleData('after_sale_saved', ['transaction' => $transaction, 'input' => $input]);
                }

                Media::uploadMedia($business_id, $transaction, $request, 'documents');

                DB::commit();

                Log::info('Create sell: '. json_encode([
                    'user' => auth()->user(),
                    'transaction' => $transaction,
                    'input' => $input,
                ]));

                if ($request->input('is_save_and_print') == 1) {
                    $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id);
                    $output = ['success' => 1, 'receipt' => json_encode($receipt) ];
                    return redirect()
                        ->action('SellController@index')
                        ->with('status', $output);
                }

                $msg = '';
                $receipt = '';
                if ($input['status'] == 'draft' && $input['is_quotation'] == 0) {
                    $msg = trans("sale.draft_added");
                } elseif ($input['status'] == 'draft' && $input['is_quotation'] == 1) {
                    $msg = trans("lang_v1.quotation_added");
                    if (!$is_direct_sale) {
                        $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id);
                    } else {
                        $receipt = '';
                    }
                } elseif ($input['status'] == 'final') {
                    $msg = trans("sale.pos_sale_added");
                    if (!$is_direct_sale) {
                        $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id);
                    } else {
                        $receipt = '';
                    }
                }
                $output = ['success' => 1, 'msg' => $msg, 'receipt' => $receipt ];
            } else {
                $output = ['success' => 0,
                    'msg' => trans("messages.something_went_wrong")
                ];
            }

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            $msg = trans("messages.something_went_wrong");

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }

            $output = ['success' => 0,
                'msg' => $msg
            ];
        }

        if (!$is_direct_sale) {
            return $output;
        } else {
            if ($input['status'] == 'draft') {
                if (isset($input['is_quotation']) && $input['is_quotation'] == 1) {
                    return redirect()
                        ->action('SellController@getQuotations')
                        ->with('status', $output);
                } else {
                    return redirect()
                        ->action('SellController@getDrafts')
                        ->with('status', $output);
                }
            } else {
                if (!empty($input['sub_type']) && $input['sub_type'] == 'repair') {
                    $redirect_url = $input['print_label'] == 1 ? action('\Modules\Repair\Http\Controllers\RepairController@printLabel', [$transaction->id]) : action('\Modules\Repair\Http\Controllers\RepairController@index');
                    return redirect($redirect_url)
                        ->with('status', $output);
                }
                return redirect()
                    ->action('SellController@index')
                    ->with('status', $output);
            }
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

        $receipt_details = $this->transactionUtil->getReceiptDetails($transaction_id, $location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type);

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
            $layout = !empty($receipt_details->design) ? 'sale_pos.receipts.' . $receipt_details->design : 'sale_pos.receipts.classic';

            $output['html_content'] = view($layout, compact('receipt_details'))->render();
        }

        return $output;
    }

    private function deliverReceiptContent(
        $business_id,
        $location_id,
        $transaction_id,
        $printer_type = null,
        $is_package_slip = false,
        $from_pos_screen = true,
        $print_new_template = true
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

        $receipt_details = $this->transactionUtil->getDeliverReceiptDetails($transaction_id, $location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type, $print_new_template);

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
            $layout = 'sale_pos.receipts.deliver';
            $output['html_content'] = view($layout, compact('receipt_details'))->render();
        }

        return $output;
    }

    private function shippingReceiptContent(
        $business_id,
        $location_id,
        $transaction_id,
        $printer_type = null,
        $is_package_slip = false,
        $from_pos_screen = true,
        $without_header = false
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

        $receipt_details = $this->transactionUtil->getShippingReceiptDetails($transaction_id, $location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type);

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
            $layout = $without_header ? 'sale_pos.receipts.shipping_without_header' : 'sale_pos.receipts.shipping';
            $output['html_content'] = view($layout, compact('receipt_details'))->render();
        }

        return $output;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(auth()->user()->can('superadmin') || auth()->user()->can('sell.update') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && auth()->user()->can('repair.update')))) {
            abort(403, 'Unauthorized action.');
        }

        //Check if the transaction can be edited or not.
        $edit_days = request()->session()->get('business.transaction_edit_days');
        if (!$this->transactionUtil->canBeEdited($id, $edit_days)) {
            return back()
                ->with('status', ['success' => 0,
                    'msg' => __('messages.transaction_edit_not_allowed', ['days' => $edit_days])]);
        }

        //Check if there is a open register, if no then redirect to Create Register screen.
        if ($this->cashRegisterUtil->countOpenedRegister() == 0) {
            return redirect()->action('CashRegisterController@create');
        }

        //Check if return exist then not allowed
        if ($this->transactionUtil->isReturnExist($id)) {
            return back()->with('status', ['success' => 0,
                'msg' => __('lang_v1.return_exist')]);
        }

        $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);

        $business_details = $this->businessUtil->getDetails($business_id);

        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);

        $transaction = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->with(['price_group', 'types_of_service'])
            ->findorfail($id);

        $location_id = $transaction->location_id;
        $business_location = BusinessLocation::find($location_id);
        $payment_types = $this->productUtil->payment_types($business_location);
        $location_printer_type = $business_location->receipt_printer_type;

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
            ->leftjoin('units', 'units.id', '=', 'p.unit_id')
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
                'units.short_name as unit',
                'units.type as unit_type',
                'units.allow_decimal as unit_allow_decimal',
                'transaction_sell_lines.tax_id as tax_id',
                'transaction_sell_lines.item_tax as item_tax',
                'transaction_sell_lines.unit_price as default_sell_price',
                'transaction_sell_lines.unit_price_before_discount as unit_price_before_discount',
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
                'transaction_sell_lines.sub_unit_id',
                'transaction_sell_lines.quantity',
                'transaction_sell_lines.quantity_line',
                DB::raw('vld.qty_available + transaction_sell_lines.quantity AS qty_available')
            )
            ->get();

        if (!empty($sell_details)) {
            foreach ($sell_details as $key => $value) {
                $value->sub_units = $this->productUtil->getSubUnits($business_id, $value->unit_id, false, $value->product_id);
                //If modifier or combo sell line then unset
                if (!empty($sell_details[$key]->parent_sell_line_id)) {
                    unset($sell_details[$key]);
                } else {
                    if ($transaction->status != 'final') {
                        $actual_qty_avlbl = $value->qty_available - $value->quantity_ordered;
                        $sell_details[$key]->qty_available = $actual_qty_avlbl;
                        $value->qty_available = $actual_qty_avlbl;
                    }

                    $sell_details[$key]->formatted_qty_available = $this->productUtil->num_f($value->qty_available, false, null, true);

                    //Add available lot numbers for dropdown to sell lines
                    $lot_numbers = [];
                    if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                        $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($value->variation_id, $business_id, $location_id);
                        foreach ($lot_number_obj as $lot_number) {
                            //If lot number is selected added ordered quantity to lot quantity available
                            if ($value->lot_no_line_id == $lot_number->purchase_line_id) {
                                $lot_number->qty_available += $value->quantity_ordered;
                            }

                            $lot_number->qty_formated = $this->productUtil->num_f($lot_number->qty_available);
                            $lot_numbers[] = $lot_number;
                        }
                    }
                    $sell_details[$key]->lot_numbers = $lot_numbers;

                    if (!empty($value->sub_unit_id)) {
                        $value = $this->productUtil->changeSellLineUnit($business_id, $value);
                        $sell_details[$key] = $value;
                    }

                    $sell_details[$key]->formatted_qty_available = $this->productUtil->num_f($value->qty_available, false, null, true);

                    if ($this->transactionUtil->isModuleEnabled('modifiers')) {
                        //Add modifier details to sel line details
                        $sell_line_modifiers = TransactionSellLine::where('parent_sell_line_id', $sell_details[$key]->transaction_sell_lines_id)
                            ->where('children_type', 'modifier')
                            ->get();
                        $modifiers_ids = [];
                        if (count($sell_line_modifiers) > 0) {
                            $sell_details[$key]->modifiers = $sell_line_modifiers;
                            foreach ($sell_line_modifiers as $sell_line_modifier) {
                                $modifiers_ids[] = $sell_line_modifier->variation_id;
                            }
                        }
                        $sell_details[$key]->modifiers_ids = $modifiers_ids;

                        //add product modifier sets for edit
                        $this_product = Product::find($sell_details[$key]->product_id);
                        if (count($this_product->modifier_sets) > 0) {
                            $sell_details[$key]->product_ms = $this_product->modifier_sets;
                        }
                    }

                    //Get details of combo items
                    if ($sell_details[$key]->product_type == 'combo') {
                        $sell_line_combos = TransactionSellLine::where('parent_sell_line_id', $sell_details[$key]->transaction_sell_lines_id)
                            ->where('children_type', 'combo')
                            ->get()
                            ->toArray();
                        if (!empty($sell_line_combos)) {
                            $sell_details[$key]->combo_products = $sell_line_combos;
                        }

                        //calculate quantity available if combo product
                        $combo_variations = [];
                        foreach ($sell_line_combos as $combo_line) {
                            $combo_variations[] = [
                                'variation_id' => $combo_line['variation_id'],
                                'quantity' => $combo_line['quantity'] / $sell_details[$key]->quantity_ordered,
                                'unit_id' => null
                            ];
                        }
                        $sell_details[$key]->qty_available =
                            $this->productUtil->calculateComboQuantity($location_id, $combo_variations);

                        if ($transaction->status == 'final') {
                            $sell_details[$key]->qty_available = $sell_details[$key]->qty_available + $sell_details[$key]->quantity_ordered;
                        }

                        $sell_details[$key]->formatted_qty_available = $this->productUtil->num_f($sell_details[$key]->qty_available, false, null, true);
                    }
                }
            }
        }

        $payment_lines = $this->transactionUtil->getPaymentDetails($id);
        //If no payment lines found then add dummy payment line.
        if (empty($payment_lines)) {
            $payment_lines[] = $this->dummyPaymentLine;
        }

        $shortcuts = json_decode($business_details->keyboard_shortcuts, true);
        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

        $commsn_agnt_setting = $business_details->sales_cmsn_agnt;
        $commission_agent = [];
        if ($commsn_agnt_setting == 'user') {
            $commission_agent = User::forDropdown($business_id, false);
        } elseif ($commsn_agnt_setting == 'cmsn_agnt') {
            $commission_agent = User::saleCommissionAgentsDropdown($business_id, false);
        }

        //If brands, category are enabled then send else false.
        $categories = (request()->session()->get('business.enable_category') == 1) ? Category::catAndSubCategories($business_id) : false;
        $brands = (request()->session()->get('business.enable_brand') == 1) ? Brands::where('business_id', $business_id)
            ->pluck('name', 'id')
            ->prepend(__('lang_v1.all_brands'), 'all') : false;

        $change_return = $this->dummyPaymentLine;

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

        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false, true);
        }

        $waiters = [];
        if ($this->productUtil->isModuleEnabled('service_staff') && !empty($pos_settings['inline_service_staff'])) {
            $waiters_enabled = true;
            $waiters = $this->productUtil->serviceStaffDropdown($business_id);
        }
        $redeem_details = [];
        if (request()->session()->get('business.enable_rp') == 1) {
            $redeem_details = $this->transactionUtil->getRewardRedeemDetails($business_id, $transaction->contact_id);

            $redeem_details['points'] += $transaction->rp_redeemed;
            $redeem_details['points'] -= $transaction->rp_earned;
        }

        $edit_discount = auth()->user()->can('edit_product_discount_from_pos_screen');
        $edit_price = auth()->user()->can('edit_product_price_from_pos_screen');
        $shipping_statuses = $this->transactionUtil->shipping_statuses();

        $warranties = $this->__getwarranties();
        $sub_type = request()->get('sub_type');

        //pos screen view from module
        $pos_module_data = $this->moduleUtil->getModuleData('get_pos_screen_view', $sub_type);

        return view('sale_pos.edit')
            ->with(compact('business_details', 'taxes', 'payment_types', 'walk_in_customer', 'sell_details', 'transaction', 'payment_lines', 'location_printer_type', 'shortcuts', 'commission_agent', 'categories', 'pos_settings', 'change_return', 'types', 'customer_groups', 'brands', 'accounts', 'waiters', 'redeem_details', 'edit_price', 'edit_discount', 'shipping_statuses', 'warranties', 'sub_type', 'pos_module_data'));
    }

    /**
     * Update the specified resource in storage.
     * TODO: Add edit log.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('sell.update') && !auth()->user()->can('direct_sell.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $transaction_deliver = Transaction::find($id);
            if ($transaction_deliver->is_deliver) {
                $output = ['success' => 0,
                    'msg' => __('lang_v1.bill_delivered')
                ];

                return redirect()
                    ->action('SellController@index')
                    ->with('status', $output);
            }

            $input = $request->except('_token');

            //Check if change sell status from draft to final
            if ($input['status'] == 'draft' && $transaction_deliver->status == 'final'){
                $output = ['success' => 0,
                    'msg' => __('messages.can_not_change_sell_status')
                ];
                return redirect('sells')->with('status', $output);
            }

            //Check if closed end of day
            $current_date = date('Y-m-d');
            $transaction_date = date('Y-m-d', strtotime($this->productUtil->uf_date($request->input('transaction_date'), true)));
            if ($input['status'] != 'draft' && $this->moduleUtil->isClosedEndOfDay() && $transaction_date <= $current_date) {
                $output = ['success' => 0,
                    'msg' => __('messages.can_not_update_after_closed_app')
                ];

                return redirect('sells')->with('status', $output);
            }

            //status is send as quotation from edit sales screen.
            $input['is_quotation'] = 0;
            if ($input['status'] == 'quotation') {
                $input['status'] = 'draft';
                $input['is_quotation'] = 1;
            }

            $is_direct_sale = false;
            if (!empty($input['products'])) {
                //Get transaction value before updating.
                $transaction_before = Transaction::find($id);
                $status_before =  $transaction_before->status;
                $rp_earned_before = $transaction_before->rp_earned;
                $rp_redeemed_before = $transaction_before->rp_redeemed;

                // correct
                if ($transaction_before->is_direct_sale == 1) {
                    $is_direct_sale = true;
                }

                //Check Customer credit limit
                $is_credit_limit_exeeded = $this->transactionUtil->isCustomerCreditLimitExeeded($input, $id);

                if ($is_credit_limit_exeeded !== false) {
                    $credit_limit_amount = $this->transactionUtil->num_f($is_credit_limit_exeeded, true);
                    $output = ['success' => 0,
                        'msg' => __('lang_v1.cutomer_credit_limit_exeeded', ['credit_limit' => $credit_limit_amount])
                    ];
                    if (!$is_direct_sale) {
                        return $output;
                    } else {
                        return redirect()
                            ->action('SellController@index')
                            ->with('status', $output);
                    }
                }

                //Check if there is a open register, if no then redirect to Create Register screen.
                if (!$is_direct_sale && $this->cashRegisterUtil->countOpenedRegister() == 0) {
                    return redirect()->action('CashRegisterController@create');
                }

                $business_id = $request->session()->get('user.business_id');
                $user_id = $request->session()->get('user.id');
                $commsn_agnt_setting = $request->session()->get('business.sales_cmsn_agnt');

                $discount = ['discount_type' => $input['discount_type'],
                    'discount_amount' => $input['discount_amount']
                ];

                $invoice_total = $this->productUtil->calculateInvoiceTotal($input['products'], 0, $discount);

                if (!empty($request->input('transaction_date'))) {
                    $input['transaction_date'] = $this->productUtil->uf_date($request->input('transaction_date'), true);
                }

                $input['commission_agent'] = !empty($request->input('commission_agent')) ? $request->input('commission_agent') : null;
                if ($commsn_agnt_setting == 'logged_in_user') {
                    $input['commission_agent'] = $user_id;
                }

                if (isset($input['exchange_rate']) && $this->transactionUtil->num_uf($input['exchange_rate']) == 0) {
                    $input['exchange_rate'] = 1;
                }

                //Customer group details
                $contact_id = $request->get('contact_id', null);
                $cg = $this->contactUtil->getCustomerGroup($business_id, $contact_id);
                $input['customer_group_id'] = (empty($cg) || empty($cg->id)) ? null : $cg->id;

                //set selling price group id
                $price_group_id = $request->has('price_group') ? $request->input('price_group') : null;

                $input['is_suspend'] = isset($input['is_suspend']) && 1 == $input['is_suspend']  ? 1 : 0;
                if ($input['is_suspend']) {
                    $input['sale_note'] = !empty($input['additional_notes']) ? $input['additional_notes'] : null;
                }

                if ($is_direct_sale && $status_before == 'draft') {
                    $input['invoice_scheme_id'] = $request->input('invoice_scheme_id');
                }

                //Types of service
                if ($this->moduleUtil->isModuleEnabled('types_of_service')) {
                    $input['types_of_service_id'] = $request->input('types_of_service_id');
                    $price_group_id = !empty($request->input('types_of_service_price_group')) ? $request->input('types_of_service_price_group') : $price_group_id;
                    $input['packing_charge'] = !empty($request->input('packing_charge')) ?
                        $this->transactionUtil->num_uf($request->input('packing_charge')) : 0;
                    $input['packing_charge_type'] = $request->input('packing_charge_type');
                    $input['service_custom_field_1'] = !empty($request->input('service_custom_field_1')) ?
                        $request->input('service_custom_field_1') : null;
                    $input['service_custom_field_2'] = !empty($request->input('service_custom_field_2')) ?
                        $request->input('service_custom_field_2') : null;
                    $input['service_custom_field_3'] = !empty($request->input('service_custom_field_3')) ?
                        $request->input('service_custom_field_3') : null;
                    $input['service_custom_field_4'] = !empty($request->input('service_custom_field_4')) ?
                        $request->input('service_custom_field_4') : null;
                }

                $input['selling_price_group_id'] = $price_group_id;

                //Begin transaction
                DB::beginTransaction();

                $transaction = $this->transactionUtil->updateSellTransaction($id, $business_id, $input, $invoice_total, $user_id);

                if (!$transaction){
                    DB::rollBack();
                    \Log::emergency('Error: Duplicate invoice_no when update sell');
                    $output = [
                        'success' => 0,
                        'msg' => __("messages.duplicate_invoice_no_error"),
                    ];

                    return redirect()
                        ->action('SellController@index')
                        ->with('status', $output);
                }

                //Update Sell lines
                $deleted_lines = $this->transactionUtil->createOrUpdateSellLines($transaction, $input['products'], $input['location_id'], true, $status_before);

                //Update final total
                $final_total = $this->transactionUtil->getSellFinalTotal($transaction);
                $transaction->final_total = $final_total;

                //Update update lines
                $is_credit_sale = isset($input['is_credit_sale']) && $input['is_credit_sale'] == 1 ? true : false;

                if (!$is_direct_sale && !$transaction->is_suspend && !$is_credit_sale) {
                    //Add change return
                    $change_return = $this->dummyPaymentLine;
                    $change_return['amount'] = $input['change_return'];
                    $change_return['is_return'] = 1;
                    if (!empty($input['change_return_id'])) {
                        $change_return['id'] = $input['change_return_id'];
                    }
                    $input['payment'][] = $change_return;

                    $this->transactionUtil->createOrUpdatePaymentLines($transaction, $input['payment']);

                    //Update cash register
                    $this->cashRegisterUtil->updateSellPayments($status_before, $transaction, $input['payment']);
                }

                if ($status_before == 'draft') {
                    if ($transaction->status == 'final') {
                        $transaction_payments_draft = TransactionPayment::where('transaction_id', $transaction->id)->get();
                        foreach ($transaction_payments_draft as $value) {
                            $notificationUtil = new NotificationUtil();
                            $notificationUtil->transactionPaymentNotification($value);
                        }
                    }
                }

                if ($request->session()->get('business.enable_rp') == 1) {
                    $this->transactionUtil->updateCustomerRewardPoints($contact_id, $transaction->rp_earned, $rp_earned_before, $transaction->rp_redeemed, $rp_redeemed_before);
                }

                //Update payments
                $payments = [];

                //Add deposit to array
                if (!$transaction->is_suspend && !empty($input['payment']) && !$is_credit_sale) {
                    if ($this->transactionUtil->num_uf($input['payment'][0]['amount']) == 0){
                        TransactionPayment::where('transaction_id', $id)->where('type', 'deposit')->delete();
                    }else{
                        $payments[] = $input['payment'][0];
                    }
                }

                //Add COD to array
                if (!$transaction->is_suspend && !empty($input['cod']) && !$is_credit_sale) {
                    if ($this->transactionUtil->num_uf($input['cod'][0]['amount']) == 0){
                        TransactionPayment::where('transaction_id', $id)->where('type', 'cod')->delete();
                    }else{
                        $payments[] = $input['cod'][0];
                    }
                }

                //Add normal payment to array
                $normal_payments = TransactionPayment::where('transaction_id', $id)
                    ->where('type', 'normal')
                    ->get()
                    ->toArray();
                $payments = array_merge($payments, $normal_payments);

                //Update payments
                if(!empty($payments)){
                    $this->transactionUtil->createOrUpdatePaymentLines($transaction, $payments);
                }

                //Update payment status
                $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

                //Update product stock
                $this->productUtil->adjustProductStockForInvoice($status_before, $transaction, $input);

                //Allocate the quantity from purchase and add mapping of
                //purchase & sell lines in
                //transaction_sell_lines_purchase_lines table
                $business_details = $this->businessUtil->getDetails($business_id);
                $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

                $business = ['id' => $business_id,
                    'accounting_method' => $request->session()->get('business.accounting_method'),
                    'location_id' => $input['location_id'],
                    'pos_settings' => $pos_settings
                ];
                $this->transactionUtil->adjustMappingPurchaseSell($status_before, $transaction, $business, $deleted_lines);

                if ($this->transactionUtil->isModuleEnabled('tables')) {
                    $transaction->res_table_id = request()->get('res_table_id');
                    $transaction->save();
                }
                if ($this->transactionUtil->isModuleEnabled('service_staff')) {
                    $transaction->res_waiter_id = request()->get('res_waiter_id');
                    $transaction->save();
                }
                $log_properties = [];
                if (isset($input['repair_completed_on'])) {
                    $completed_on = !empty($input['repair_completed_on']) ? $this->transactionUtil->uf_date($input['repair_completed_on'], true) : null;
                    if ($transaction->repair_completed_on != $completed_on) {
                        $log_properties['completed_on_from'] = $transaction->repair_completed_on;
                        $log_properties['completed_on_to'] = $completed_on;
                    }
                }

                //Set Module fields
                if (!empty($input['has_module_data'])) {
                    $this->moduleUtil->getModuleData('after_sale_saved', ['transaction' => $transaction, 'input' => $input]);
                }

                if (!empty($input['update_note'])) {
                    $log_properties['update_note'] = $input['update_note'];
                }

                Media::uploadMedia($business_id, $transaction, $request, 'documents');

                activity()
                    ->performedOn($transaction)
                    ->withProperties($log_properties)
                    ->log('edited');

                DB::commit();

                if ($status_before == 'draft' && $input['status'] == 'final') {
                    Log::info('Change sell status from draft to final: '. json_encode([
                        'user' => auth()->user(),
                        'transaction_id' => $id,
                        'old_transaction' => $transaction_before,
                        'new_transaction' => $transaction,
                        'input' => $input,
                    ]));
                }

                if ($request->input('is_save_and_print') == 1) {
                    $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id);
                    $output = ['success' => 1, 'receipt' => json_encode($receipt) ];
                    return redirect()
                        ->action('SellController@index')
                        ->with('status', $output);

//                    $url = $this->transactionUtil->getInvoiceUrl($id, $business_id);
//                    return redirect()->to($url . '?print_on_load=true');
                }

                $msg = '';
                $receipt = '';

                if ($input['status'] == 'draft' && $input['is_quotation'] == 0) {
                    $msg = trans("sale.draft_updated");
                } elseif ($input['status'] == 'draft' && $input['is_quotation'] == 1) {
                    $msg = trans("lang_v1.quotation_updated");
                    if (!$is_direct_sale) {
                        $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id);
                    } else {
                        $receipt = '';
                    }
                } elseif ($input['status'] == 'final') {
                    $msg = trans("sale.pos_sale_updated");
                    if (!$is_direct_sale) {
                        $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id);
                    } else {
                        $receipt = '';
                    }
                }

                $output = ['success' => 1, 'msg' => $msg, 'receipt' => $receipt ];
            } else {
                $output = ['success' => 0,
                    'msg' => trans("messages.something_went_wrong")
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        if (!$is_direct_sale) {
            return $output;
        } else {
            if ($input['status'] == 'draft') {
                if (isset($input['is_quotation']) && $input['is_quotation'] == 1) {
                    return redirect()
                        ->action('SellController@getQuotations')
                        ->with('status', $output);
                } else {
                    return redirect()
                        ->action('SellController@getDrafts')
                        ->with('status', $output);
                }
            } else {
                if (!empty($transaction->sub_type) && $transaction->sub_type == 'repair') {
                    return redirect()
                        ->action('\Modules\Repair\Http\Controllers\RepairController@index')
                        ->with('status', $output);
                }

                return redirect()
                    ->action('SellController@index')
                    ->with('status', $output);
            }
        }
    }

    public function storeStockDeliver(Request $request, $id)
    {
        if (!auth()->user()->can('stock.to_deliver')) {
            abort(403, 'Unauthorized action.');
        }

        $locked = Transaction::where('id', $id)
            ->where('locked_for_ex_warehouse', 1)
            ->first();

        if($locked){
            $output = ['success' => 0,
                'msg' => __('lang_v1.locked_for_ex_warehouse')
            ];

            return redirect()
                ->action('SellController@stockDeliverIndex')
                ->with('status', $output);
        }else{
            Transaction::where('id', $id)
                ->update(['locked_for_ex_warehouse' => 1]);
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $input = $request->except('_token');
            $input_log = [
                'transaction_id' => $id,
                'action' => 'create',
                'status' => 'pending',
                'input' => $input,
            ];
            $input_log_json = json_encode($input_log);
            Log::info('Deliver request: '. $input_log_json);

            $transactionPlateLine = TransactionPlateLine::query()->where('transaction_id', $id)->first();

            if (!empty($transactionPlateLine)) {
                $output = ['success' => 0,
                    'msg' => __('lang_v1.export_exists')
                ];

                return $this->unlockExWarehouseAndRedirect($id, $output);
//                return redirect()
//                    ->action('SellController@stockDeliverIndex')
//                    ->with('status', $output);
            }

            $transaction = Transaction::find( $id);
            if ($transaction->is_deliver) {
                $output = ['success' => 0,
                    'msg' => __('lang_v1.invoice_really_delivered')
                ];

                return $this->unlockExWarehouseAndRedirect($id, $output);
//                return redirect()
//                    ->action('SellController@stockDeliverIndex')
//                    ->with('status', $output);
            }

            if(!empty($input['products'])){
                DB::beginTransaction();

                //TODO: Update deliver status
                $transaction->export_status = 'pending';
                $transaction->selected_remaining_widths = isset($input['all_selected_remaining_widths']) ? $input['all_selected_remaining_widths'] : null;
                $transaction->plates_sort_order = isset($input['plates_sort_order']) ? $input['plates_sort_order'] : null;
                $transaction->save();
                $plate_lines = [];
                $remaining_plate_lines = [];
                $sort_order_remaining_plates = [];

                $selected_remaining_widths_json = $request->input('selected_remaining_widths');
                $selected_remaining_widths = [];
                if($selected_remaining_widths_json){
                    foreach($selected_remaining_widths_json as $plate_stock_id => $selected_remaining_width_json){
                        $plate_stock = PlateStock::with('product')->find($plate_stock_id);

                        if(in_array($plate_stock->product->unit->type, ['area', 'meter'])){
                            $selected_remaining_width = json_decode($selected_remaining_width_json, 1);
                            if($selected_remaining_width === null){
                                $selected_remaining_width = [];
                            }

                            $selected_remaining_widths[$plate_stock_id]['remaining_widths'] = $selected_remaining_width;
                            $selected_remaining_widths[$plate_stock_id]['is_same_plate'] = false;
                            $selected_remaining_widths[$plate_stock_id]['selected_width'] = 0;
                            $selected_remaining_widths[$plate_stock_id]['selected_quantity'] = 0;
                        }
                    }
                }

                if(!empty($selected_remaining_widths)){
                    foreach ($input['products'] as $product){
                        $transaction_sell_line = TransactionSellLine::find($product['transaction_sell_line_id']);

                        foreach ($product['plate_stock'] as $plate_stock_input) {
                            if(in_array($plate_stock_input['unit_type'], ['area', 'meter'])){
                                $selected_plate_stock_id = $plate_stock_input['selected_plate_stock_id'];
                                $selected_plate_stock = PlateStock::find($selected_plate_stock_id);
                                $plates = [];

                                if(bccomp($plate_stock_input['selected_width'], $selected_plate_stock->width, 3) == -1){
                                    $selected_remaining_widths[$selected_plate_stock_id]['is_same_plate'] = true;
                                }

                                $plate_stock_input['is_cut'] = (boolean) $plate_stock_input['is_cut'];
                                if (!$plate_stock_input['is_cut']) {
                                    //If not cut
                                    $plates = json_decode($plate_stock_input['plates_if_not_cut'], 1);
                                } else {
                                    //If cut
                                    $plates[0] = [
                                        'width' => $transaction_sell_line->width,
                                        'quantity' => $plate_stock_input['quantity'],
                                        'enabled_not_cut' => false,
                                    ];
                                }

                                foreach ($plates as $plate) {
                                    $selected_remaining_widths[$selected_plate_stock_id]['selected_width'] += $plate['width'];
                                }

                                if (bccomp($plate_stock_input['selected_width'], $selected_plate_stock->width, 3) == 0) {
                                    $selected_remaining_widths[$selected_plate_stock_id]['selected_quantity'] += intval($plate_stock_input['selected_quantity']);
                                }
                            }
                        }
                    }
                }

                $transaction->select_remain_width = json_encode($selected_remaining_widths);
                $transaction->save();

//                $test_results = [
//                    'plates' => [],
//                    'remaining_plates' => [],
//                    'plate_stocks' => [],
//                    'remaining_plate_stocks' => [],
//                ];
                foreach ($input['products'] as $product){
                    $transaction_sell_line = TransactionSellLine::find($product['transaction_sell_line_id']);

                    foreach($product['plate_stock'] as $plate_stock_input){
                        //Get selected product stock
                        $selected_plate_stock_id = $plate_stock_input['selected_plate_stock_id'];
                        $old_plate_stock = PlateStock::find($selected_plate_stock_id);

                        if(in_array($transaction_sell_line->sub_unit->type, ['area', 'meter'])){
                            $remaining_plates_json = isset($plate_stock_input['remaining_plates']) ? $plate_stock_input['remaining_plates'] : '';
                            $remaining_plates = !empty($remaining_plates_json) ? json_decode($remaining_plates_json, true) : [];

                            $remaining_widths_json = isset($plate_stock_input['remaining_widths']) ? $plate_stock_input['remaining_widths'] : '';
                            $remaining_widths_if_cut_json = isset($plate_stock_input['remaining_widths_if_cut']) ? $plate_stock_input['remaining_widths_if_cut'] : '';
                            $remaining_widths_if_not_cut_json = isset($plate_stock_input['remaining_widths_if_not_cut']) ? $plate_stock_input['remaining_widths_if_not_cut'] : '';
                            $plates_if_not_cut_json = isset($plate_stock_input['plates_if_not_cut']) ? $plate_stock_input['plates_if_not_cut'] : '';
                            $plates = [];

                            $plate_stock_input['is_cut'] = (boolean) $plate_stock_input['is_cut'];
                            if (!$plate_stock_input['is_cut']) {
                                //If not cut
                                $is_cut = false;
                                $plates = json_decode($plate_stock_input['plates_if_not_cut'], 1);
                            }else{
                                //If cut
                                $is_cut = true;
                                $plates[0] = [
                                    'width' => $transaction_sell_line->width,
                                    'quantity' => $plate_stock_input['quantity'],
                                    'enabled_not_cut' => false,
                                ];
                            }

                            $plate_line = null;
                            foreach ($plates as $plate){
                                $plate_line = TransactionPlateLine::create([
                                    'transaction_id' => $id,
                                    'transaction_sell_line_id' => $plate_stock_input['transaction_sell_line_id'],
                                    'selected_plate_stock_id' => $selected_plate_stock_id,
                                    'selected_width' => $plate_stock_input['selected_width'],
                                    'selected_height' => $old_plate_stock->height,
                                    'selected_quantity' => $plate_stock_input['selected_quantity'],
                                    'width' => $plate['width'],
                                    'height' => $old_plate_stock->height,
                                    'quantity' => $plate['quantity'],
                                    'total_quantity' => $old_plate_stock->height * $plate['width'] * $plate['quantity'],
                                    'product_id' => $old_plate_stock->product_id,
                                    'variation_id' => $old_plate_stock->variation_id,
                                    'is_cut' => (!$is_cut && $plate['enabled_not_cut']) ? false : true,
                                    'is_cut_from_same_plate' => $selected_remaining_widths[$selected_plate_stock_id]['is_same_plate'] ? 1 : 0,
                                    'remaining_plates' => $remaining_plates_json,
                                    'remaining_widths' => $remaining_widths_json,
                                    'remaining_widths_if_cut' => $remaining_widths_if_cut_json,
                                    'remaining_widths_if_not_cut' => $remaining_widths_if_not_cut_json,
                                    'plates_if_not_cut' => $plates_if_not_cut_json,
                                    'enabled_not_cut' => $plate_stock_input['enabled_not_cut'],
                                    'row_id' => $plate_stock_input['row_id'],
                                    'row_index' => $plate_stock_input['row_index'],
                                    'plates_for_print' => $plate_stock_input['plates_for_print'],
                                ]);

                                foreach ($remaining_plates as $remaining_plate){
                                    $remaining_plate_line = RemainingPlateLine::create([
                                        'transaction_id' => $id,
                                        'transaction_sell_line_id' => $plate_stock_input['transaction_sell_line_id'],
                                        'transaction_plate_line_id' => $plate_line->id,
                                        'plate_stock_id' => null,
                                        'width' => $remaining_plate['width'],
                                        'height' => $old_plate_stock->height,
                                        'quantity' => $remaining_plate['quantity'],
                                        'total_quantity' => $remaining_plate['width'] * $old_plate_stock->height * $remaining_plate['quantity'],
                                        'product_id' => $old_plate_stock->product_id,
                                        'variation_id' => $old_plate_stock->variation_id,
                                        'warehouse_id' => $old_plate_stock->warehouse_id,
                                        'order_number' => $remaining_plate['order_number'],
                                        'row_id' => $remaining_plate['next_id'],
                                        'row_next_id' => $remaining_plate['next_id'],
                                        'row_prev_id' => $remaining_plate['prev_id'],
                                    ]);

                                    $remaining_plate_lines[] = $remaining_plate_line;
                                    $sort_order_remaining_plates[$remaining_plate['id']] = $remaining_plate_line->id;
                                }

                                $plate_lines[] = $plate_line;
//                                $test_results['plates'][] = $plate_line;
                            }
                        } elseif ($transaction_sell_line->sub_unit->type == 'pcs'){
                            $plate_lines[] = TransactionPlateLine::create([
                                'transaction_id' => $id,
                                'transaction_sell_line_id' => $plate_stock_input['transaction_sell_line_id'],
                                'selected_plate_stock_id' => $selected_plate_stock_id,
                                'selected_quantity' => $plate_stock_input['selected_quantity'],
                                'width' => 1,
                                'height' => 1,
                                'total_quantity' => $plate_stock_input['selected_quantity'],
                                'product_id' => $old_plate_stock->product_id,
                                'variation_id' => $old_plate_stock->variation_id,
                                'quantity' => $plate_stock_input['selected_quantity'],
                                'is_cut' => false,
                                'row_id' => $plate_stock_input['row_id'],
                                'row_index' => $plate_stock_input['row_index'],
                            ]);

                            //TODO: Update product quantity available
                            $qty_available = $old_plate_stock->qty_available - $plate_stock_input['selected_quantity'];
                            if($qty_available < 0){
                                DB::rollBack();
                                $output = [
                                    'success' => 0,
                                    'msg' => __('sale.stock_invalid')
                                ];
                                return $this->unlockExWarehouseAndRedirect($id, $output);
//                                return redirect()
//                                    ->action('SellController@stockDeliverIndex')
//                                    ->with('status', $output);
                            }

                            //Update plate stock drafts
                            $plate_stock_draft = PlateStockDraft::where('location_id', $old_plate_stock->location_id)
                                ->where('variation_id', $old_plate_stock->variation_id)
                                ->where('width', $old_plate_stock->width)
                                ->where('height', $old_plate_stock->height)
                                ->where('warehouse_id', $old_plate_stock->warehouse_id)
                                ->where('is_origin', $old_plate_stock->is_origin)
                                ->first();
                            $draft_action = $plate_stock_draft ? 'update' : 'create';

                            if($plate_stock_draft){
                                $plate_stock_draft->qty_available -= $plate_stock_input['selected_quantity'];
                                $plate_stock_draft->save();
                            }else{
                                $plate_stock_draft = PlateStockDraft::create([
                                    'location_id' => $old_plate_stock->location_id,
                                    'product_id' => $old_plate_stock->product_id,
                                    'variation_id' => $old_plate_stock->variation_id,
                                    'width' => $old_plate_stock->width,
                                    'height' => $old_plate_stock->height,
                                    'warehouse_id' => $old_plate_stock->warehouse_id,
                                    'qty_available' => $plate_stock_input['selected_quantity'] * -1,
                                    'is_origin' => $old_plate_stock->is_origin,
                                ]);
                            }

                            Log::info(json_encode([
                                'action' => "plate_stock_draft.{$plate_stock_draft->id}.{$draft_action}",
                                'quantity' => $plate_stock_input['selected_quantity'] * -1,
                                'plate_stock_draft' => $plate_stock_draft->toArray(),
                                'line' => sprintf("File %s - Line %d", __FILE__, __LINE__),
                                'input' => $input_log,
                            ]));
//                            $test_results['plate_stocks'][] = $plate_stock_draft;
                        }
                    }
                }

                //Update next_id & prev_id for remaining plate lines
                foreach ($remaining_plate_lines as $remaining_plate_line){
                    $is_update = false;

                    if (!empty($remaining_plate_line->row_next_id) && isset($sort_order_remaining_plates[$remaining_plate_line->row_next_id])){
                        $remaining_plate_line->next_id = $sort_order_remaining_plates[$remaining_plate_line->row_next_id];
                        $is_update = true;
                    }

                    if (!empty($remaining_plate_line->row_prev_id) && isset($sort_order_remaining_plates[$remaining_plate_line->row_prev_id])){
                        $remaining_plate_line->prev_id = $sort_order_remaining_plates[$remaining_plate_line->row_prev_id];
                        $is_update = true;
                    }

                    if ($is_update){
                        $remaining_plate_line->save();
                    }
                }

                //Update draft stock for plate
                foreach ($selected_remaining_widths as $selected_plate_stock_id => $selected_remaining_width){
                    //TODO: Update plate quantity available
                    $old_plate_stock = PlateStock::with('product.unit')->find($selected_plate_stock_id);
                    $qty_available = $old_plate_stock->qty_available - $selected_remaining_width['selected_quantity'];
                    if($qty_available < 0){
                        DB::rollBack();
                        $output = [
                            'success' => 0,
                            'msg' => __('sale.stock_invalid')
                        ];
                        return $this->unlockExWarehouseAndRedirect($id, $output);
//                        return redirect()
//                            ->action('SellController@stockDeliverIndex')
//                            ->with('status', $output);
                    }

                    //Update plate stock drafts
                    $plate_stock_draft = PlateStockDraft::where('location_id', $old_plate_stock->location_id)
                        ->where('variation_id', $old_plate_stock->variation_id)
                        ->where('width', $old_plate_stock->width)
                        ->where('height', $old_plate_stock->height)
                        ->where('warehouse_id', $old_plate_stock->warehouse_id)
                        ->where('is_origin', $old_plate_stock->is_origin)
                        ->first();
                    $draft_action = $plate_stock_draft ? 'update' : 'create';

                    if($plate_stock_draft){
                        $plate_stock_draft->qty_available -= $selected_remaining_width['selected_quantity'];
                        $plate_stock_draft->save();
                    }else{
                        $plate_stock_draft = PlateStockDraft::create([
                            'location_id' => $old_plate_stock->location_id,
                            'product_id' => $old_plate_stock->product_id,
                            'variation_id' => $old_plate_stock->variation_id,
                            'width' => $old_plate_stock->width,
                            'height' => $old_plate_stock->height,
                            'warehouse_id' => $old_plate_stock->warehouse_id,
                            'qty_available' => $selected_remaining_width['selected_quantity'] * -1,
                            'is_origin' => $old_plate_stock->is_origin,
                        ]);
                    }

                    Log::info(json_encode([
                        'action' => "plate_stock_draft.{$plate_stock_draft->id}.{$draft_action}",
                        'quantity' => $selected_remaining_width['selected_quantity'] * -1,
                        'plate_stock_draft' => $plate_stock_draft->toArray(),
                        'line' => sprintf("File %s - Line %d", __FILE__, __LINE__),
                        'input' => $input_log,
                    ]));
//                    $test_results['plate_stocks'][] = $plate_stock_draft;

                    //TODO: Create remaining plate after cut
                    foreach($selected_remaining_widths[$selected_plate_stock_id]['remaining_widths'] as $remaining){
                        $remaining_width = floatval($remaining['width']);
                        $remaining_quantity = intval($remaining['quantity']);

                        if($remaining_quantity > 0){
                            $remaining_plate_stock_draft = PlateStockDraft::where('location_id', $old_plate_stock->location_id)
                                ->where('variation_id', $old_plate_stock->variation_id)
                                ->where('width', $remaining_width)
                                ->where('height', $old_plate_stock->height)
                                ->where('warehouse_id', $old_plate_stock->warehouse_id)
                                ->where('is_origin', $old_plate_stock->is_origin)
                                ->first();
                            $draft_action = $remaining_plate_stock_draft ? 'update' : 'create';

                            if($remaining_plate_stock_draft){
                                $remaining_plate_stock_draft->qty_available += $remaining_quantity;
                                $remaining_plate_stock_draft->save();
                            }else{
                                $remaining_plate_stock_draft = PlateStockDraft::create([
                                    'location_id' => $old_plate_stock->location_id,
                                    'product_id' => $old_plate_stock->product_id,
                                    'variation_id' => $old_plate_stock->variation_id,
                                    'width' => $remaining_width,
                                    'height' => $old_plate_stock->height,
                                    'warehouse_id' => $old_plate_stock->warehouse_id,
                                    'qty_available' => $remaining_quantity,
                                    'is_origin' => $old_plate_stock->is_origin,
                                ]);
                            }

                            Log::info(json_encode([
                                'action' => "plate_stock_draft.{$remaining_plate_stock_draft->id}.{$draft_action}",
                                'quantity' => $remaining_quantity,
                                'plate_stock_draft' => $remaining_plate_stock_draft->toArray(),
                                'line' => sprintf("File %s - Line %d", __FILE__, __LINE__),
                                'input' => $input_log,
                            ]));
//                            $test_results['remaining_plate_stocks'][] = $remaining_plate_stock_draft;
                        }
                    }
                }

                DB::commit();

                if ($request->input('is_save_and_print') == 1) {
                    $receipt = $this->deliverReceiptContent($business_id, $transaction->location_id, $transaction->id);
                    $output = ['success' => 1, 'receipt' => json_encode($receipt) ];
                    return $this->unlockExWarehouseAndRedirect($id, $output);
//                    return redirect()
//                        ->action('SellController@stockDeliverIndex')
//                        ->with('status', $output);
                }

                $output = [
                    'success' => 1,
                    'msg' => __('sale.stock_deliver_success')
                ];
            }else{
                $output = [
                    'success' => 0,
                    'msg' => __('sale.not_selected_plates_to_be_cut')
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $this->unlockExWarehouseAndRedirect($id, $output);
//        return redirect()
//            ->action('SellController@stockDeliverIndex')
//            ->with('status', $output);
    }

    public function updateStockDeliver(Request $request, $id)
    {
        if (!auth()->user()->can('stock.to_deliver')) {
            abort(403, 'Unauthorized action.');
        }

        $locked = Transaction::where('id', $id)
            ->where('locked_for_ex_warehouse', 1)
            ->first();

        if($locked){
            $output = ['success' => 0,
                'msg' => __('lang_v1.locked_for_update_ex_warehouse')
            ];

            return redirect()
                ->action('SellController@stockDeliverIndex')
                ->with('status', $output);
        }else{
            Transaction::where('id', $id)
                ->update(['locked_for_ex_warehouse' => 1]);
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $input = $request->except('_token');
            $input_log = [
                'transaction_id' => $id,
                'action' => 'update',
                'status' => 'pending',
                'input' => $input,
            ];
            Log::info('Deliver request: '. json_encode($input_log));

            $transaction = Transaction::find( $id);
            if ($transaction->is_deliver) {
                $output = ['success' => 0,
                    'msg' => __('lang_v1.invoice_really_delivered')
                ];
                return $this->unlockExWarehouseAndRedirect($id, $output);
//                return redirect()
//                    ->action('SellController@stockDeliverIndex')
//                    ->with('status', $output);
            }

            if(!empty($input['products'])){
                DB::beginTransaction();

                //TODO: Delete old data
                foreach ($request->input('products') as $product){
                    $product['transaction_sell_line_id'] = intval($product['transaction_sell_line_id']);
                    $plate_lines = TransactionPlateLine::where('transaction_sell_line_id', $product['transaction_sell_line_id'])
                        ->get();

                    foreach ($plate_lines as $plate_line){
                        $selected_plate_stock_id = $plate_line->selected_plate_stock_id;
                        //Get selected product stock
                        $old_plate_stock = PlateStock::find($selected_plate_stock_id);

                        if($product['unit_type'] == 'pcs'){
                            //Update plate stock drafts
                            $plate_stock_draft = PlateStockDraft::where('location_id', $old_plate_stock->location_id)
                                ->where('variation_id', $old_plate_stock->variation_id)
                                ->where('width', $old_plate_stock->width)
                                ->where('height', $old_plate_stock->height)
                                ->where('warehouse_id', $old_plate_stock->warehouse_id)
                                ->where('is_origin', $old_plate_stock->is_origin)
                                ->first();
                            $draft_action = $plate_stock_draft ? 'update' : 'create';

                            if($plate_stock_draft){
                                $plate_stock_draft->qty_available += $plate_line->selected_quantity;
                                $plate_stock_draft->save();
                            }else{
                                $plate_stock_draft = PlateStockDraft::create([
                                    'location_id' => $old_plate_stock->location_id,
                                    'product_id' => $old_plate_stock->product_id,
                                    'variation_id' => $old_plate_stock->variation_id,
                                    'width' => $old_plate_stock->width,
                                    'height' => $old_plate_stock->height,
                                    'warehouse_id' => $old_plate_stock->warehouse_id,
                                    'qty_available' => $plate_line->selected_quantity,
                                    'is_origin' => $old_plate_stock->is_origin,
                                ]);
                            }

                            Log::info(json_encode([
                                'action' => "plate_stock_draft.{$plate_stock_draft->id}.{$draft_action}",
                                'quantity' => $plate_line->selected_quantity,
                                'plate_stock_draft' => $plate_stock_draft->toArray(),
                                'line' => sprintf("File %s - Line %d", __FILE__, __LINE__),
                                'input' => $input_log,
                            ]));
                        }
                    }
                }

                //Update stock for plate
                $selected_remaining_widths = json_decode($transaction->select_remain_width, 1);

                foreach ($selected_remaining_widths as $selected_plate_stock_id => $selected_remaining_width){
                    //TODO: Update plate quantity available
                    $old_plate_stock = PlateStock::with('product.unit')->find($selected_plate_stock_id);
                    if (!empty($old_plate_stock)) {
                        //Update plate stock drafts
                        $plate_stock_draft = PlateStockDraft::where('location_id', $old_plate_stock->location_id)
                            ->where('variation_id', $old_plate_stock->variation_id)
                            ->where('width', $old_plate_stock->width)
                            ->where('height', $old_plate_stock->height)
                            ->where('warehouse_id', $old_plate_stock->warehouse_id)
                            ->where('is_origin', $old_plate_stock->is_origin)
                            ->first();
                        $draft_action = $plate_stock_draft ? 'update' : 'create';

                        if($plate_stock_draft){
                            $plate_stock_draft->qty_available += $selected_remaining_width['selected_quantity'];
                            $plate_stock_draft->save();
                        }else{
                            $plate_stock_draft = PlateStockDraft::create([
                                'location_id' => $old_plate_stock->location_id,
                                'product_id' => $old_plate_stock->product_id,
                                'variation_id' => $old_plate_stock->variation_id,
                                'width' => $old_plate_stock->width,
                                'height' => $old_plate_stock->height,
                                'warehouse_id' => $old_plate_stock->warehouse_id,
                                'qty_available' => $selected_remaining_width['selected_quantity'],
                                'is_origin' => $old_plate_stock->is_origin,
                            ]);
                        }

                        Log::info(json_encode([
                            'action' => "plate_stock_draft.{$plate_stock_draft->id}.{$draft_action}",
                            'quantity' => $selected_remaining_width['selected_quantity'],
                            'plate_stock_draft' => $plate_stock_draft->toArray(),
                            'line' => sprintf("File %s - Line %d", __FILE__, __LINE__),
                            'input' => $input_log,
                        ]));
                    }

                    //TODO: Create remaining plate after cut
                    foreach($selected_remaining_widths[$selected_plate_stock_id]['remaining_widths'] as $remaining){
                        $remaining_width = floatval($remaining['width']);
                        $remaining_quantity = intval($remaining['quantity']);

                        if($remaining_quantity > 0){
                            $remaining_plate_stock_draft = PlateStockDraft::where('location_id', $old_plate_stock->location_id)
                                ->where('variation_id', $old_plate_stock->variation_id)
                                ->where('width', $remaining_width)
                                ->where('height', $old_plate_stock->height)
                                ->where('warehouse_id', $old_plate_stock->warehouse_id)
                                ->where('is_origin', $old_plate_stock->is_origin)
                                ->first();
                            $draft_action = $plate_stock_draft ? 'update' : 'create';

                            if($remaining_plate_stock_draft){
                                $remaining_plate_stock_draft->qty_available -= $remaining_quantity;
                                $remaining_plate_stock_draft->save();
                            }else{
                                $remaining_plate_stock_draft = PlateStockDraft::create([
                                    'location_id' => $old_plate_stock->location_id,
                                    'product_id' => $old_plate_stock->product_id,
                                    'variation_id' => $old_plate_stock->variation_id,
                                    'width' => $remaining_width * -1,
                                    'height' => $old_plate_stock->height,
                                    'warehouse_id' => $old_plate_stock->warehouse_id,
                                    'qty_available' => $remaining_quantity * -1,
                                    'is_origin' => $old_plate_stock->is_origin,
                                ]);
                            }

                            Log::info(json_encode([
                                'action' => "plate_stock_draft.{$remaining_plate_stock_draft->id}.{$draft_action}",
                                'quantity' => $remaining_width * -1,
                                'plate_stock_draft' => $remaining_plate_stock_draft->toArray(),
                                'line' => sprintf("File %s - Line %d", __FILE__, __LINE__),
                                'input' => $input_log,
                            ]));
                        }
                    }
                }

                TransactionPlateLine::where('transaction_id', $transaction->id)
                    ->delete();
                RemainingPlateLine::where('transaction_id', $transaction->id)
                    ->delete();

                //TODO: Update transaction
                $transaction->selected_remaining_widths = isset($input['all_selected_remaining_widths']) ? $input['all_selected_remaining_widths'] : null;
                $transaction->plates_sort_order = isset($input['plates_sort_order']) ? $input['plates_sort_order'] : null;
                $transaction->save();
                $plate_lines = [];
                $remaining_plate_lines = [];
                $sort_order_remaining_plates = [];

                $selected_remaining_widths_json = $request->input('selected_remaining_widths');
                $selected_remaining_widths = [];
                if($selected_remaining_widths_json){
                    foreach($selected_remaining_widths_json as $plate_stock_id => $selected_remaining_width_json){
                        $plate_stock = PlateStock::with('product')->find($plate_stock_id);

                        if(in_array($plate_stock->product->unit->type, ['area', 'meter'])){
                            $selected_remaining_width = json_decode($selected_remaining_width_json, 1);
                            if($selected_remaining_width === null){
                                $selected_remaining_width = [];
                            }

                            $selected_remaining_widths[$plate_stock_id]['remaining_widths'] = $selected_remaining_width;
                            $selected_remaining_widths[$plate_stock_id]['is_same_plate'] = false;
                            $selected_remaining_widths[$plate_stock_id]['selected_width'] = 0;
                            $selected_remaining_widths[$plate_stock_id]['selected_quantity'] = 0;
                        }
                    }
                }

                if(!empty($selected_remaining_widths)){
                    foreach ($input['products'] as $product){
                        $transaction_sell_line = TransactionSellLine::find($product['transaction_sell_line_id']);

                        foreach ($product['plate_stock'] as $plate_stock_input) {
                            if(in_array($plate_stock_input['unit_type'], ['area', 'meter'])){
                                $selected_plate_stock_id = $plate_stock_input['selected_plate_stock_id'];
                                $selected_plate_stock = PlateStock::find($selected_plate_stock_id);
                                $plates = [];

                                if(bccomp($plate_stock_input['selected_width'], $selected_plate_stock->width, 3) == -1){
                                    $selected_remaining_widths[$selected_plate_stock_id]['is_same_plate'] = true;
                                }

                                $plate_stock_input['is_cut'] = (boolean) $plate_stock_input['is_cut'];
                                if (!$plate_stock_input['is_cut']) {
                                    //If not cut
                                    $plates = json_decode($plate_stock_input['plates_if_not_cut'], 1);
                                } else {
                                    //If cut
                                    $plates[0] = [
                                        'width' => $transaction_sell_line->width,
                                        'quantity' => $plate_stock_input['quantity'],
                                        'enabled_not_cut' => false,
                                    ];
                                }

                                foreach ($plates as $plate) {
                                    $selected_remaining_widths[$selected_plate_stock_id]['selected_width'] += $plate['width'];
                                }

                                if (bccomp($plate_stock_input['selected_width'], $selected_plate_stock->width, 3) == 0) {
                                    $selected_remaining_widths[$selected_plate_stock_id]['selected_quantity'] += intval($plate_stock_input['selected_quantity']);
                                }
                            }
                        }
                    }
                }

                $transaction->select_remain_width = json_encode($selected_remaining_widths);
                $transaction->save();

//                $test_results = [
//                    'plates' => [],
//                    'remaining_plates' => [],
//                    'plate_stocks' => [],
//                    'remaining_plate_stocks' => [],
//                ];
                foreach ($input['products'] as $product){
                    $transaction_sell_line = TransactionSellLine::find($product['transaction_sell_line_id']);

                    foreach($product['plate_stock'] as $plate_stock_input){
                        //Get selected product stock
                        $selected_plate_stock_id = $plate_stock_input['selected_plate_stock_id'];
                        $old_plate_stock = PlateStock::find($selected_plate_stock_id);

                        if(in_array($transaction_sell_line->sub_unit->type, ['area', 'meter'])){
                            $remaining_plates_json = isset($plate_stock_input['remaining_plates']) ? $plate_stock_input['remaining_plates'] : '';
                            $remaining_plates = !empty($remaining_plates_json) ? json_decode($remaining_plates_json, true) : [];

                            $remaining_widths_json = isset($plate_stock_input['remaining_widths']) ? $plate_stock_input['remaining_widths'] : '';
                            $remaining_widths_if_cut_json = isset($plate_stock_input['remaining_widths_if_cut']) ? $plate_stock_input['remaining_widths_if_cut'] : '';
                            $remaining_widths_if_not_cut_json = isset($plate_stock_input['remaining_widths_if_not_cut']) ? $plate_stock_input['remaining_widths_if_not_cut'] : '';
                            $plates_if_not_cut_json = isset($plate_stock_input['plates_if_not_cut']) ? $plate_stock_input['plates_if_not_cut'] : '';
                            $plates = [];

                            $plate_stock_input['is_cut'] = (boolean) $plate_stock_input['is_cut'];
                            if (!$plate_stock_input['is_cut']) {
                                //If not cut
                                $is_cut = false;
                                $plates = json_decode($plate_stock_input['plates_if_not_cut'], 1);
                            }else{
                                //If cut
                                $is_cut = true;
                                $plates[0] = [
                                    'width' => $transaction_sell_line->width,
                                    'quantity' => $plate_stock_input['quantity'],
                                    'enabled_not_cut' => false,
                                ];
                            }

                            $plate_line = null;
                            foreach ($plates as $plate){
                                $plate_line = TransactionPlateLine::create([
                                    'transaction_id' => $id,
                                    'transaction_sell_line_id' => $plate_stock_input['transaction_sell_line_id'],
                                    'selected_plate_stock_id' => $selected_plate_stock_id,
                                    'selected_width' => $plate_stock_input['selected_width'],
                                    'selected_height' => $old_plate_stock->height,
                                    'selected_quantity' => $plate_stock_input['selected_quantity'],
                                    'width' => $plate['width'],
                                    'height' => $old_plate_stock->height,
                                    'quantity' => $plate['quantity'],
                                    'total_quantity' => $old_plate_stock->height * $plate['width'] * $plate['quantity'],
                                    'product_id' => $old_plate_stock->product_id,
                                    'variation_id' => $old_plate_stock->variation_id,
                                    'is_cut' => (!$is_cut && $plate['enabled_not_cut']) ? false : true,
                                    'is_cut_from_same_plate' => $selected_remaining_widths[$selected_plate_stock_id]['is_same_plate'] ? 1 : 0,
                                    'remaining_plates' => $remaining_plates_json,
                                    'remaining_widths' => $remaining_widths_json,
                                    'remaining_widths_if_cut' => $remaining_widths_if_cut_json,
                                    'remaining_widths_if_not_cut' => $remaining_widths_if_not_cut_json,
                                    'plates_if_not_cut' => $plates_if_not_cut_json,
                                    'enabled_not_cut' => $plate_stock_input['enabled_not_cut'],
                                    'row_id' => $plate_stock_input['row_id'],
                                    'row_index' => $plate_stock_input['row_index'],
                                        'plates_for_print' => $plate_stock_input['plates_for_print'],
                                ]);

                                foreach ($remaining_plates as $remaining_plate){
                                    $remaining_plate_line = RemainingPlateLine::create([
                                        'transaction_id' => $id,
                                        'transaction_sell_line_id' => $plate_stock_input['transaction_sell_line_id'],
                                        'transaction_plate_line_id' => $plate_line->id,
                                        'plate_stock_id' => null,
                                        'width' => $remaining_plate['width'],
                                        'height' => $old_plate_stock->height,
                                        'quantity' => $remaining_plate['quantity'],
                                        'total_quantity' => $remaining_plate['width'] * $old_plate_stock->height * $remaining_plate['quantity'],
                                        'product_id' => $old_plate_stock->product_id,
                                        'variation_id' => $old_plate_stock->variation_id,
                                        'warehouse_id' => $old_plate_stock->warehouse_id,
                                        'order_number' => $remaining_plate['order_number'],
                                        'row_id' => $remaining_plate['next_id'],
                                        'row_next_id' => $remaining_plate['next_id'],
                                        'row_prev_id' => $remaining_plate['prev_id'],
                                    ]);

                                    $remaining_plate_lines[] = $remaining_plate_line;
                                    $sort_order_remaining_plates[$remaining_plate['id']] = $remaining_plate_line->id;
                                }

                                $plate_lines[] = $plate_line;
//                                $test_results['plates'][] = $plate_line;
                            }
                        } elseif ($transaction_sell_line->sub_unit->type == 'pcs'){
                            $plate_lines[] = TransactionPlateLine::create([
                                'transaction_id' => $id,
                                'transaction_sell_line_id' => $plate_stock_input['transaction_sell_line_id'],
                                'selected_plate_stock_id' => $selected_plate_stock_id,
                                'selected_quantity' => $plate_stock_input['selected_quantity'],
                                'width' => 1,
                                'height' => 1,
                                'total_quantity' => $plate_stock_input['selected_quantity'],
                                'product_id' => $old_plate_stock->product_id,
                                'variation_id' => $old_plate_stock->variation_id,
                                'quantity' => $plate_stock_input['selected_quantity'],
                                'is_cut' => false,
                                'row_id' => $plate_stock_input['row_id'],
                                'row_index' => $plate_stock_input['row_index'],
                            ]);

                            //TODO: Update product quantity available
                            $qty_available = $old_plate_stock->qty_available - $plate_stock_input['selected_quantity'];
                            if($qty_available < 0){
                                DB::rollBack();
                                $output = [
                                    'success' => 0,
                                    'msg' => __('sale.stock_invalid')
                                ];
                                return $this->unlockExWarehouseAndRedirect($id, $output);
//                                return redirect()
//                                    ->action('SellController@stockDeliverIndex')
//                                    ->with('status', $output);
                            }

                            //Update plate stock drafts
                            $plate_stock_draft = PlateStockDraft::where('location_id', $old_plate_stock->location_id)
                                ->where('variation_id', $old_plate_stock->variation_id)
                                ->where('width', $old_plate_stock->width)
                                ->where('height', $old_plate_stock->height)
                                ->where('warehouse_id', $old_plate_stock->warehouse_id)
                                ->where('is_origin', $old_plate_stock->is_origin)
                                ->first();
                            $draft_action = $plate_stock_draft ? 'update' : 'create';

                            if($plate_stock_draft){
                                $plate_stock_draft->qty_available -= $plate_stock_input['selected_quantity'];
                                $plate_stock_draft->save();
                            }else{
                                $plate_stock_draft = PlateStockDraft::create([
                                    'location_id' => $old_plate_stock->location_id,
                                    'product_id' => $old_plate_stock->product_id,
                                    'variation_id' => $old_plate_stock->variation_id,
                                    'width' => $old_plate_stock->width,
                                    'height' => $old_plate_stock->height,
                                    'warehouse_id' => $old_plate_stock->warehouse_id,
                                    'qty_available' => $plate_stock_input['selected_quantity'] * -1,
                                    'is_origin' => $old_plate_stock->is_origin,
                                ]);
                            }

                            Log::info(json_encode([
                                'action' => "plate_stock_draft.{$plate_stock_draft->id}.{$draft_action}",
                                'quantity' => $plate_stock_input['selected_quantity'] * -1,
                                'plate_stock_draft' => $plate_stock_draft->toArray(),
                                'line' => sprintf("File %s - Line %d", __FILE__, __LINE__),
                                'input' => $input_log,
                            ]));
//                            $test_results['plate_stocks'][] = $plate_stock_draft;
                        }
                    }
                }

                //Update next_id & prev_id for remaining plate lines
                foreach ($remaining_plate_lines as $remaining_plate_line){
                    $is_update = false;

                    if (!empty($remaining_plate_line->row_next_id) && isset($sort_order_remaining_plates[$remaining_plate_line->row_next_id])){
                        $remaining_plate_line->next_id = $sort_order_remaining_plates[$remaining_plate_line->row_next_id];
                        $is_update = true;
                    }

                    if (!empty($remaining_plate_line->row_prev_id) && isset($sort_order_remaining_plates[$remaining_plate_line->row_prev_id])){
                        $remaining_plate_line->prev_id = $sort_order_remaining_plates[$remaining_plate_line->row_prev_id];
                        $is_update = true;
                    }

                    if ($is_update){
                        $remaining_plate_line->save();
                    }
                }

                //Update draft stock for plate
                foreach ($selected_remaining_widths as $selected_plate_stock_id => $selected_remaining_width){
                    //TODO: Update plate quantity available
                    $old_plate_stock = PlateStock::with('product.unit')->find($selected_plate_stock_id);
                    $qty_available = $old_plate_stock->qty_available - $selected_remaining_width['selected_quantity'];
                    if($qty_available < 0){
                        DB::rollBack();
                        $output = [
                            'success' => 0,
                            'msg' => __('sale.stock_invalid')
                        ];
                        return $this->unlockExWarehouseAndRedirect($id, $output);
//                        return redirect()
//                            ->action('SellController@stockDeliverIndex')
//                            ->with('status', $output);
                    }

                    //Update plate stock drafts
                    $plate_stock_draft = PlateStockDraft::where('location_id', $old_plate_stock->location_id)
                        ->where('variation_id', $old_plate_stock->variation_id)
                        ->where('width', $old_plate_stock->width)
                        ->where('height', $old_plate_stock->height)
                        ->where('warehouse_id', $old_plate_stock->warehouse_id)
                        ->where('is_origin', $old_plate_stock->is_origin)
                        ->first();
                    $draft_action = $plate_stock_draft ? 'update' : 'create';

                    if($plate_stock_draft){
                        $plate_stock_draft->qty_available -= $selected_remaining_width['selected_quantity'];
                        $plate_stock_draft->save();
                    }else{
                        $plate_stock_draft = PlateStockDraft::create([
                            'location_id' => $old_plate_stock->location_id,
                            'product_id' => $old_plate_stock->product_id,
                            'variation_id' => $old_plate_stock->variation_id,
                            'width' => $old_plate_stock->width,
                            'height' => $old_plate_stock->height,
                            'warehouse_id' => $old_plate_stock->warehouse_id,
                            'qty_available' => $selected_remaining_width['selected_quantity'] * -1,
                            'is_origin' => $old_plate_stock->is_origin,
                        ]);
                    }

                    Log::info(json_encode([
                        'action' => "plate_stock_draft.{$plate_stock_draft->id}.{$draft_action}",
                        'quantity' => $selected_remaining_width['selected_quantity'] * -1,
                        'plate_stock_draft' => $plate_stock_draft->toArray(),
                        'line' => sprintf("File %s - Line %d", __FILE__, __LINE__),
                        'input' => $input_log,
                    ]));
//                    $test_results['plate_stocks'][] = $plate_stock_draft;

                    //TODO: Create remaining plate after cut
                    foreach($selected_remaining_widths[$selected_plate_stock_id]['remaining_widths'] as $remaining){
                        $remaining_width = floatval($remaining['width']);
                        $remaining_quantity = intval($remaining['quantity']);

                        if($remaining_quantity > 0){
                            $remaining_plate_stock_draft = PlateStockDraft::where('location_id', $old_plate_stock->location_id)
                                ->where('variation_id', $old_plate_stock->variation_id)
                                ->where('width', $remaining_width)
                                ->where('height', $old_plate_stock->height)
                                ->where('warehouse_id', $old_plate_stock->warehouse_id)
                                ->where('is_origin', $old_plate_stock->is_origin)
                                ->first();
                            $draft_action = $plate_stock_draft ? 'update' : 'create';

                            if($remaining_plate_stock_draft){
                                $remaining_plate_stock_draft->qty_available += $remaining_quantity;
                                $remaining_plate_stock_draft->save();
                            }else{
                                $remaining_plate_stock_draft = PlateStockDraft::create([
                                    'location_id' => $old_plate_stock->location_id,
                                    'product_id' => $old_plate_stock->product_id,
                                    'variation_id' => $old_plate_stock->variation_id,
                                    'width' => $remaining_width,
                                    'height' => $old_plate_stock->height,
                                    'warehouse_id' => $old_plate_stock->warehouse_id,
                                    'qty_available' => $remaining_quantity,
                                    'is_origin' => $old_plate_stock->is_origin,
                                ]);
                            }

                            Log::info(json_encode([
                                'action' => "plate_stock_draft.{$remaining_plate_stock_draft->id}.{$draft_action}",
                                'quantity' => $remaining_quantity,
                                'plate_stock_draft' => $remaining_plate_stock_draft->toArray(),
                                'line' => sprintf("File %s - Line %d", __FILE__, __LINE__),
                                'input' => $input_log,
                            ]));
//                            $test_results['remaining_plate_stocks'][] = $remaining_plate_stock_draft;
                        }
                    }
                }

                DB::commit();

                if ($request->input('is_save_and_print') == 1) {
                    $receipt = $this->deliverReceiptContent($business_id, $transaction->location_id, $transaction->id);
                    $output = ['success' => 1, 'receipt' => json_encode($receipt) ];
                    return $this->unlockExWarehouseAndRedirect($id, $output);
//                    return redirect()
//                        ->action('SellController@stockDeliverIndex')
//                        ->with('status', $output);
                }

                $output = [
                    'success' => 1,
                    'msg' => __('sale.stock_deliver_success')
                ];
            }else{
                $output = [
                    'success' => 0,
                    'msg' => __('sale.not_selected_plates_to_be_cut')
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $this->unlockExWarehouseAndRedirect($id, $output);

//        return redirect()
//            ->action('SellController@stockDeliverIndex')
//            ->with('status', $output);
    }

    private function unlockExWarehouseAndRedirect($id, $output){
        Transaction::where('id', $id)
            ->update(['locked_for_ex_warehouse' => 0]);

        return redirect()
            ->action('SellController@stockDeliverIndex')
            ->with('status', $output);
    }

    public function confirmExport(Request $request, $id){
        $transaction = Transaction::find( $id);

        try{
            DB::beginTransaction();
            $transaction->is_deliver = true;
            $transaction->export_status = 'approved';
            $transaction->save();
            $selected_remaining_widths = json_decode($transaction->select_remain_width, 1);
            $input = $request->except('_token');
            $input_log = [
                'transaction_id' => $id,
                'action' => 'approve',
                'status' => 'approved',
                'input' => $input,
            ];
            Log::info('Deliver request: '. json_encode($input_log));

            foreach ($input['products'] as $product){
                $product['transaction_sell_line_id'] = intval($product['transaction_sell_line_id']);
                $plate_lines = TransactionPlateLine::where('transaction_sell_line_id', $product['transaction_sell_line_id'])
                    ->get();

                foreach ($plate_lines as $plate_line){
                    $selected_plate_stock_id = $plate_line->selected_plate_stock_id;
                    //Get selected product stock
                    $old_plate_stock = PlateStock::find($selected_plate_stock_id);
                    Log::info("Before update stock for transaction_id $id: " . json_encode($old_plate_stock));
                    if($product['unit_type'] == 'pcs'){
                        //TODO: Update product quantity available
                        $qty_available = $old_plate_stock->qty_available - $plate_line->selected_quantity;
                        if($qty_available < 0){
                            DB::rollBack();
                            $output = [
                                'success' => 0,
                                'msg' => __('sale.stock_invalid')
                            ];
                            return redirect()
                                ->action('SellController@stockDeliverIndex')
                                ->with('status', $output);
                        }

                        $old_plate_stock->qty_available = $qty_available;
                        $old_plate_stock->save();
                        Log::info("After update stock for transaction_id $id: " . json_encode($old_plate_stock));
                        //Update plate stock drafts
                        $plate_stock_draft = PlateStockDraft::where('location_id', $old_plate_stock->location_id)
                            ->where('variation_id', $old_plate_stock->variation_id)
                            ->where('width', $old_plate_stock->width)
                            ->where('height', $old_plate_stock->height)
                            ->where('warehouse_id', $old_plate_stock->warehouse_id)
                            ->where('is_origin', $old_plate_stock->is_origin)
                            ->first();
                        $draft_action = $plate_stock_draft ? 'update' : 'create';

                        if($plate_stock_draft){
                            $plate_stock_draft->qty_available += $plate_line->selected_quantity;
                            $plate_stock_draft->save();
                        }else{
                            PlateStockDraft::create([
                                'location_id' => $old_plate_stock->location_id,
                                'product_id' => $old_plate_stock->product_id,
                                'variation_id' => $old_plate_stock->variation_id,
                                'width' => $old_plate_stock->width,
                                'height' => $old_plate_stock->height,
                                'warehouse_id' => $old_plate_stock->warehouse_id,
                                'qty_available' => $plate_line->selected_quantity,
                                'is_origin' => $old_plate_stock->is_origin,
                            ]);
                        }

                        Log::info(json_encode([
                            'action' => "plate_stock_draft.{$plate_stock_draft->id}.{$draft_action}",
                            'quantity' => $plate_line->selected_quantity,
                            'plate_stock_draft' => $plate_stock_draft->toArray(),
                            'line' => sprintf("File %s - Line %d", __FILE__, __LINE__),
                            'input' => $input_log,
                        ]));
                    }
                }
            }

            //Update stock for plate
            foreach ($selected_remaining_widths as $selected_plate_stock_id => $selected_remaining_width){
                //TODO: Update plate quantity available
                $old_plate_stock = PlateStock::with('product.unit')->find($selected_plate_stock_id);
                Log::info("Before update stock for transaction_id $id: " . json_encode($old_plate_stock));
                if (!empty($old_plate_stock)) {
                    $qty_available = $old_plate_stock->qty_available - $selected_remaining_width['selected_quantity'];
                    if($qty_available < 0){
                        DB::rollBack();
                        $output = [
                            'success' => 0,
                            'msg' => __('sale.stock_invalid')
                        ];
                        return redirect()
                            ->action('SellController@stockDeliverIndex')
                            ->with('status', $output);
                    }

                    $old_plate_stock->qty_available = $qty_available;
                    $old_plate_stock->save();
                    Log::info("After update stock for transaction_id $id: " . json_encode($old_plate_stock));
                    //Update plate stock drafts
                    $plate_stock_draft = PlateStockDraft::where('location_id', $old_plate_stock->location_id)
                        ->where('variation_id', $old_plate_stock->variation_id)
                        ->where('width', $old_plate_stock->width)
                        ->where('height', $old_plate_stock->height)
                        ->where('warehouse_id', $old_plate_stock->warehouse_id)
                        ->where('is_origin', $old_plate_stock->is_origin)
                        ->first();
                    $draft_action = $plate_stock_draft ? 'update' : 'create';

                    if($plate_stock_draft){
                        $plate_stock_draft->qty_available += $selected_remaining_width['selected_quantity'];
                        $plate_stock_draft->save();
                    }else{
                        PlateStockDraft::create([
                            'location_id' => $old_plate_stock->location_id,
                            'product_id' => $old_plate_stock->product_id,
                            'variation_id' => $old_plate_stock->variation_id,
                            'width' => $old_plate_stock->width,
                            'height' => $old_plate_stock->height,
                            'warehouse_id' => $old_plate_stock->warehouse_id,
                            'qty_available' => $selected_remaining_width['selected_quantity'],
                            'is_origin' => $old_plate_stock->is_origin,
                        ]);
                    }

                    Log::info(json_encode([
                        'action' => "plate_stock_draft.{$plate_stock_draft->id}.{$draft_action}",
                        'quantity' => $selected_remaining_width['selected_quantity'],
                        'plate_stock_draft' => $plate_stock_draft->toArray(),
                        'line' => sprintf("File %s - Line %d", __FILE__, __LINE__),
                        'input' => $input_log,
                    ]));
                }

                //TODO: Create remaining plate after cut
                foreach($selected_remaining_widths[$selected_plate_stock_id]['remaining_widths'] as $remaining){
                    $remaining_width = floatval($remaining['width']);
                    $remaining_quantity = intval($remaining['quantity']);

                    if($remaining_quantity > 0){
                        //Update plate stock
                        $remaining_plate_stock = PlateStock::where('location_id', $old_plate_stock->location_id)
                            ->where('variation_id', $old_plate_stock->variation_id)
                            ->where('width', $remaining_width)
                            ->where('height', $old_plate_stock->height)
                            ->where('warehouse_id', $old_plate_stock->warehouse_id)
                            ->first();

                        if($remaining_plate_stock){
                            $remaining_plate_stock->qty_available += $remaining_quantity;
                            $remaining_plate_stock->save();
                            Log::info("Plate stock remaining transactions_id $id: " . json_encode($remaining_plate_stock));
                        }else{
                            $newPlate = PlateStock::create([
                                'product_id' => $old_plate_stock->product_id,
                                'variation_id' => $old_plate_stock->variation_id,
                                'location_id' => $old_plate_stock->location_id,
                                'width' => $remaining_width,
                                'height' => $old_plate_stock->height,
                                'warehouse_id' => $old_plate_stock->warehouse_id,
                                'qty_available' => $remaining_quantity,
                            ]);
                            Log::info("Plate stock create new transactions_id $id: " . json_encode($newPlate));
                        }

                        //Update plate stock drafts
                        $remaining_plate_stock_draft = PlateStockDraft::where('location_id', $old_plate_stock->location_id)
                            ->where('variation_id', $old_plate_stock->variation_id)
                            ->where('width', $remaining_width)
                            ->where('height', $old_plate_stock->height)
                            ->where('warehouse_id', $old_plate_stock->warehouse_id)
                            ->where('is_origin', $old_plate_stock->is_origin)
                            ->first();
                        $draft_action = $remaining_plate_stock_draft ? 'update' : 'create';

                        if($remaining_plate_stock_draft){
                            $remaining_plate_stock_draft->qty_available -= $remaining_quantity;
                            $remaining_plate_stock_draft->save();
                        }else{
                            $remaining_plate_stock_draft = PlateStockDraft::create([
                                'location_id' => $old_plate_stock->location_id,
                                'product_id' => $old_plate_stock->product_id,
                                'variation_id' => $old_plate_stock->variation_id,
                                'width' => $remaining_width,
                                'height' => $old_plate_stock->height,
                                'warehouse_id' => $old_plate_stock->warehouse_id,
                                'qty_available' => $remaining_quantity * -1,
                                'is_origin' => $old_plate_stock->is_origin,
                            ]);
                        }

                        Log::info(json_encode([
                            'action' => "plate_stock_draft.{$remaining_plate_stock_draft->id}.{$draft_action}",
                            'quantity' => $remaining_quantity * -1,
                            'plate_stock_draft' => $remaining_plate_stock_draft->toArray(),
                            'line' => sprintf("File %s - Line %d", __FILE__, __LINE__),
                            'input' => $input_log,
                        ]));
                    }
                }
            }

            $output = [
                'success' => true,
                'msg'     => __('messages.confirm_export_success')
            ];
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            $output = [
                'success' => false,
                'msg'     => __('messages.something_went_wrong')
            ];
        }

        return redirect('stock-to-deliver')->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('sell.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');
                //Begin transaction
                DB::beginTransaction();

                $output = $this->transactionUtil->deleteSale($business_id, $id);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output['success'] = false;
                $output['msg'] = trans("messages.something_went_wrong");
            }

            return $output;
        }
    }

    public function cancel($id)
    {
        if (!auth()->user()->can('sell.cancel') && !auth()->user()->can('return.cancel')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $transaction = Transaction::find($id);
                if ($transaction->export_status != 'none'){
                    $output = [
                        'success' => false,
                        'msg' => __('sale.can_not_sell_cancel')
                    ];
                    return $output;
                }

                $business_id = request()->session()->get('user.business_id');
                //Begin transaction
                DB::beginTransaction();

                $payments = TransactionPayment::where('transaction_id', $id);
                $payments->update(['approval_status' => 'reject']);
                $payments = $payments->get();

                Transaction::find($id)->update(['status' => 'cancel']);
                if ($transaction->type == 'sell_return') {
                    $sell_return = TransactionPlateLinesReturn::where('transaction_id', $transaction->return_parent_id)
                        ->get()
                        ->toArray();
                    foreach ($sell_return as $value) {
                        $plate_stock = PlateStock::where('location_id', $transaction->location_id)
                            ->where('variation_id', $value['variation_id'])
                            ->whereRaw('width = ' . $value['width'])
                            ->whereRaw('height = ' . $value['height'])
                            ->where('warehouse_id', $value['warehouse_id'])
                            ->first();
                        if ($plate_stock) {
                            $plate_stock->update([
                                'qty_available' => $plate_stock->qty_available - $value['quantity']
                            ]);
                        } else {
                            $plate_stock->update([
                                'qty_available' => $plate_stock->qty_available
                            ]);
                        }
                    }
                }

                foreach ($payments as $payment){
                    $notificationUtil = new NotificationUtil();
                    $notificationUtil->transactionPaymentNotification($payment);
                }

                $output = [
                    'success' => true,
                    'msg' => __('sale.sell_cancel_success')
                ];

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output['success'] = false;
                $output['msg'] = trans("messages.something_went_wrong");
            }

            return $output;
        }
    }

    /**
     * Returns the HTML row for a product in POS
     *
     * @param  int  $variation_id
     * @param  int  $location_id
     * @return \Illuminate\Http\Response
     */
    public function getProductRow($variation_id, $location_id)
    {
        $output = [];

        try {
            $row_count = request()->get('product_row');
            $row_count = $row_count + 1;
            $is_direct_sell = false;
            if (request()->get('is_direct_sell') == 'true') {
                $is_direct_sell = true;
            }

            $business_id = request()->session()->get('user.business_id');

            $business_details = $this->businessUtil->getDetails($business_id);
            $quantity = 1;

            $default_unit = Unit::where('type', 'area')->where('is_default', true)->first();
            $default_unit_id = $default_unit->id;

            //Check for weighing scale barcode
            $weighing_barcode = request()->get('weighing_scale_barcode');
            if ($variation_id == 'null' && !empty($weighing_barcode)) {
                $product_details = $this->__parseWeighingBarcode($weighing_barcode);
                if ($product_details['success']) {
                    $variation_id = $product_details['variation_id'];
                    $quantity = $product_details['qty'];
                } else {
                    $output['success'] = false;
                    $output['msg'] = $product_details['msg'];
                    return $output;
                }
            }

            $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

            $check_qty = !empty($pos_settings['allow_overselling']) ? false : true;
            $product = $this->productUtil->getDetailsFromVariation($variation_id, $business_id, $location_id, $check_qty);
            if (!isset($product->quantity_ordered)) {
                $product->quantity_ordered = $quantity;
            }

            $product->formatted_qty_available = $this->productUtil->num_f($product->qty_available, false, null, true);

            $sub_units = $this->productUtil->getSubUnits($business_id, $product->unit_id, false, $product->product_id);

            //Get customer group and change the price accordingly
            $customer_id = request()->get('customer_id', null);
            $cg = $this->contactUtil->getCustomerGroup($business_id, $customer_id);
            $percent = (empty($cg) || empty($cg->amount)) ? 0 : $cg->amount;
            $product->default_sell_price = $product->default_sell_price + ($percent * $product->default_sell_price / 100);
            $product->default_sell_price_by_plate = $product->default_sell_price_by_plate + ($percent * $product->default_sell_price_by_plate / 100);
            $product->sell_price_inc_tax = $product->sell_price_inc_tax + ($percent * $product->sell_price_inc_tax / 100);

            $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);

            $enabled_modules = $this->transactionUtil->allModulesEnabled();

            //Get lot number dropdown if enabled
            $lot_numbers = [];
            if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($variation_id, $business_id, $location_id, true);
                foreach ($lot_number_obj as $lot_number) {
                    $lot_number->qty_formated = $this->productUtil->num_f($lot_number->qty_available);
                    $lot_numbers[] = $lot_number;
                }
            }
            $product->lot_numbers = $lot_numbers;

            $purchase_line_id = request()->get('purchase_line_id');

            $price_group = request()->input('price_group');
            if (!empty($price_group)) {
                $variation_group_prices = $this->productUtil->getVariationGroupPrice($variation_id, $price_group, $product->tax_id);

                if (!empty($variation_group_prices['price_inc_tax'])) {
                    $product->sell_price_inc_tax = $variation_group_prices['price_inc_tax'];
                    $product->default_sell_price_by_plate = $variation_group_prices['price_by_plate'];
                    $product->default_sell_price = $variation_group_prices['price_exc_tax'];
                }
            }

            $warranties = $this->__getwarranties();

            $output['success'] = true;

            $waiters = [];
            if ($this->productUtil->isModuleEnabled('service_staff') && !empty($pos_settings['inline_service_staff'])) {
                $waiters_enabled = true;
                $waiters = $this->productUtil->serviceStaffDropdown($business_id, $location_id);
            }

            if (request()->get('type') == 'sell-return') {
                $output['html_content'] =  view('sell_return.partials.product_row')->with(compact(
                    'product',
                    'row_count',
                    'tax_dropdown',
                    'enabled_modules',
                    'sub_units'
                ))->render();
            } else {
                $is_cg = !empty($cg->id);
                $is_pg = !empty($price_group);
                $discount = $this->productUtil->getProductDiscount($product, $business_id, $location_id, $is_cg, $is_pg);

                if ($is_direct_sell) {
                    $edit_discount = auth()->user()->can('edit_product_discount_from_sale_screen');
                    $edit_price = auth()->user()->can('edit_product_price_from_sale_screen');
                } else {
                    $edit_discount = auth()->user()->can('edit_product_discount_from_pos_screen');
                    $edit_price = auth()->user()->can('edit_product_price_from_pos_screen');
                }

                $output['html_content'] =  view('sale_pos.product_row')->with(compact(
                    'product',
                    'row_count',
                    'tax_dropdown',
                    'enabled_modules',
                    'pos_settings',
                    'sub_units',
                    'discount',
                    'waiters',
                    'edit_discount',
                    'edit_price',
                    'purchase_line_id',
                    'warranties',
                    'quantity',
                    'default_unit_id'
                ))->render();
            }

            $output['enable_sr_no'] = $product->enable_sr_no;

            if ($this->transactionUtil->isModuleEnabled('modifiers')  && !$is_direct_sell) {
                $this_product = Product::where('business_id', $business_id)
                    ->find($product->product_id);
                if (count($this_product->modifier_sets) > 0) {
                    $product_ms = $this_product->modifier_sets;
                    $output['html_modifier'] =  view('restaurant.product_modifier_set.modifier_for_product')
                        ->with(compact('product_ms', 'row_count'))->render();
                }
            }

        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output['success'] = false;
            $output['msg'] = __('lang_v1.something_went_wrong');
//            $output['msg'] = __('lang_v1.item_out_of_stock');
        }

        return $output;
    }

    public function getProductRowByFilter($variation_id, $location_id)
    {
        $output = [];

        try {
            $row_count = request()->get('product_row');
            $row_count = $row_count + 1;
            $is_direct_sell = false;
            if (request()->get('is_direct_sell') == 'true') {
                $is_direct_sell = true;
            }

            $business_id = request()->session()->get('user.business_id');

            $business_details = $this->businessUtil->getDetails($business_id);
            $quantity = 1;

            $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

            $check_qty = !empty($pos_settings['allow_overselling']) ? false : true;
            $product = $this->productUtil->getDetailsFromVariation($variation_id, $business_id, $location_id, $check_qty);
            if (!isset($product->quantity_ordered)) {
                $product->quantity_ordered = $quantity;
            }

            $product->formatted_qty_available = $this->productUtil->num_f($product->qty_available, false, null, true);

            $sub_units = $this->productUtil->getSubUnits($business_id, $product->unit_id, false, $product->product_id);

            //Get customer group and change the price accordingly
            $customer_id = request()->get('customer_id', null);
            $cg = $this->contactUtil->getCustomerGroup($business_id, $customer_id);
            $percent = (empty($cg) || empty($cg->amount)) ? 0 : $cg->amount;
            $product->default_sell_price = $product->default_sell_price + ($percent * $product->default_sell_price / 100);
            $product->sell_price_inc_tax = $product->sell_price_inc_tax + ($percent * $product->sell_price_inc_tax / 100);

            $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);

            $enabled_modules = $this->transactionUtil->allModulesEnabled();

            //Get lot number dropdown if enabled
            $lot_numbers = [];
            if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($variation_id, $business_id, $location_id, true);
                foreach ($lot_number_obj as $lot_number) {
                    $lot_number->qty_formated = $this->productUtil->num_f($lot_number->qty_available);
                    $lot_numbers[] = $lot_number;
                }
            }
            $product->lot_numbers = $lot_numbers;

//            $purchase_line_id = request()->get('purchase_line_id');

            $price_group = request()->input('price_group');
            if (!empty($price_group)) {
                $variation_group_prices = $this->productUtil->getVariationGroupPrice($variation_id, $price_group, $product->tax_id);

                if (!empty($variation_group_prices['price_inc_tax'])) {
                    $product->sell_price_inc_tax = $variation_group_prices['price_inc_tax'];
                    $product->default_sell_price = $variation_group_prices['price_exc_tax'];
                }
            }

            $warranties = $this->__getwarranties();

            $output['success'] = true;

            $waiters = [];
            if ($this->productUtil->isModuleEnabled('service_staff') && !empty($pos_settings['inline_service_staff'])) {
                $waiters_enabled = true;
                $waiters = $this->productUtil->serviceStaffDropdown($business_id, $location_id);
            }

            if (request()->get('type') == 'sell-return') {
                $output['html_content'] =  view('sell_return.partials.product_row')->with(compact(
                    'product',
                    'row_count',
                    'tax_dropdown',
                    'enabled_modules',
                    'sub_units'
                ))->render();
            } else {
                $is_cg = !empty($cg->id);
                $is_pg = !empty($price_group);
                $discount = $this->productUtil->getProductDiscount($product, $business_id, $location_id, $is_cg, $is_pg);

                if ($is_direct_sell) {
                    $edit_discount = auth()->user()->can('edit_product_discount_from_sale_screen');
                    $edit_price = auth()->user()->can('edit_product_price_from_sale_screen');
                } else {
                    $edit_discount = auth()->user()->can('edit_product_discount_from_pos_screen');
                    $edit_price = auth()->user()->can('edit_product_price_from_pos_screen');
                }

                $width = request()->input('width');
                $height = request()->input('height');

                $output['html_content'] =  view('sale_pos.product_row')->with(compact(
                    'product',
                    'row_count',
                    'tax_dropdown',
                    'enabled_modules',
                    'pos_settings',
                    'sub_units',
                    'discount',
                    'waiters',
                    'edit_discount',
                    'edit_price',
                    'warranties',
                    'width',
                    'height',
                    'quantity'
                ))->render();
            }

            $output['enable_sr_no'] = $product->enable_sr_no;

            if ($this->transactionUtil->isModuleEnabled('modifiers')  && !$is_direct_sell) {
                $this_product = Product::where('business_id', $business_id)
                    ->find($product->product_id);
                if (count($this_product->modifier_sets) > 0) {
                    $product_ms = $this_product->modifier_sets;
                    $output['html_modifier'] =  view('restaurant.product_modifier_set.modifier_for_product')
                        ->with(compact('product_ms', 'row_count'))->render();
                }
            }

        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output['success'] = false;
            $output['msg'] = __('lang_v1.something_went_wrong');
//            $output['msg'] = __('lang_v1.item_out_of_stock');
        }

        return $output;
    }

    /**
     * Returns the HTML row for a payment in POS
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getPaymentRow(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        $row_index = $request->input('row_index');
        $location_id = $request->input('location_id');
        $removable = true;
        $payment_types = $this->productUtil->payment_types($location_id);

        $payment_line = $this->dummyPaymentLine;

        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false, true);
        }

        return view('sale_pos.partials.payment_row')
            ->with(compact('payment_types', 'row_index', 'removable', 'payment_line', 'accounts'));
    }

    /**
     * Returns recent transactions
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getRecentTransactions(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $user_id = $request->session()->get('user.id');
        $transaction_status = $request->get('status');

        $register = $this->cashRegisterUtil->getCurrentCashRegister($user_id);

        $query = Transaction::where('business_id', $business_id)
            ->where('transactions.created_by', $user_id)
            ->where('transactions.type', 'sell')
            ->where('is_direct_sale', 0);

        if ($transaction_status == 'final') {
            if (!empty($register->id)) {
                $query->leftjoin('cash_register_transactions as crt', 'transactions.id', '=', 'crt.transaction_id')
                    ->where('crt.cash_register_id', $register->id);
            }
        }

        if ($transaction_status == 'quotation') {
            $query->where('transactions.status', 'draft')
                ->where('is_quotation', 1);
        } elseif ($transaction_status == 'draft') {
            $query->where('transactions.status', 'draft')
                ->where('is_quotation', 0);
        } else {
            $query->where('transactions.status', $transaction_status);
        }

        $transaction_sub_type = $request->get('transaction_sub_type');
        if (!empty($transaction_sub_type)) {
            $query->where('transactions.sub_type', $transaction_sub_type);
        } else {
            $query->where('transactions.sub_type', null);
        }

        $transactions = $query->orderBy('transactions.created_at', 'desc')
            ->groupBy('transactions.id')
            ->select('transactions.*')
            ->with(['contact'])
            ->limit(10)
            ->get();

        return view('sale_pos.partials.recent_transactions')
            ->with(compact('transactions', 'transaction_sub_type'));
    }

    /**
     * Prints invoice for sell
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function printInvoice(Request $request, $transaction_id)
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

    public function printDeliverInvoiceOldTemplate(Request $request, $transaction_id)
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

                $receipt = $this->deliverReceiptContent($business_id, $transaction->location_id, $transaction_id, $printer_type, $is_package_slip, false, false);

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

    public function printDeliverInvoice(Request $request, $transaction_id)
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

                $receipt = $this->deliverReceiptContent($business_id, $transaction->location_id, $transaction_id, $printer_type, $is_package_slip, false);

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
     * Gives suggetion for product based on category
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getProductSuggestion(Request $request)
    {
        if ($request->ajax()) {
            $category_id = $request->get('category_id');
            $brand_id = $request->get('brand_id');
            $location_id = $request->get('location_id');
            $term = $request->get('term');

            $check_qty = false;
            $business_id = $request->session()->get('user.business_id');

            $products = Variation::join('products as p', 'variations.product_id', '=', 'p.id')
                ->join('product_locations as pl', 'pl.product_id', '=', 'p.id')
                ->leftjoin(
                    'variation_location_details AS VLD',
                    function ($join) use ($location_id) {
                        $join->on('variations.id', '=', 'VLD.variation_id');

                        //Include Location
                        if (!empty($location_id)) {
                            $join->where(function ($query) use ($location_id) {
                                $query->where('VLD.location_id', '=', $location_id);
                                //Check null to show products even if no quantity is available in a location.
                                //TODO: Maybe add a settings to show product not available at a location or not.
                                $query->orWhereNull('VLD.location_id');
                            });
                            ;
                        }
                    }
                )
                ->where('p.business_id', $business_id)
                ->where('p.type', '!=', 'modifier')
                ->where('p.is_inactive', 0)
                ->where('p.not_for_selling', 0)
                //Hide products not available in the selected location
                ->where(function ($q) use ($location_id) {
                    $q->where('pl.location_id', $location_id);
                });

            //Include search
            if (!empty($term)) {
                $products->where(function ($query) use ($term) {
                    $query->where('p.name', 'like', '%' . $term .'%');
                    $query->orWhere('sku', 'like', '%' . $term .'%');
                    $query->orWhere('sub_sku', 'like', '%' . $term .'%');
                });
            }

            //Include check for quantity
            if ($check_qty) {
                $products->where('VLD.qty_available', '>', 0);
            }

            if (!empty($category_id) && ($category_id != 'all')) {
                $products->where(function ($query) use ($category_id) {
                    $query->where('p.category_id', $category_id);
                    $query->orWhere('p.sub_category_id', $category_id);
                });
            }
            if (!empty($brand_id) && ($brand_id != 'all')) {
                $products->where('p.brand_id', $brand_id);
            }

            if (!empty($request->get('is_enabled_stock'))) {
                $is_enabled_stock = 0;
                if ($request->get('is_enabled_stock') == 'product') {
                    $is_enabled_stock = 1;
                }

                $products->where('p.enable_stock', $is_enabled_stock);
            }

            if (!empty($request->get('repair_model_id'))) {
                $products->where('p.repair_model_id', $request->get('repair_model_id'));
            }

            $products = $products->select(
                'p.id as product_id',
                'p.name',
                'p.type',
                'p.enable_stock',
                'p.image as product_image',
                'variations.id',
                'variations.name as variation',
                'VLD.qty_available',
                'variations.default_sell_price as selling_price',
                'variations.sub_sku'
            )
                ->with(['media'])
                ->orderBy('p.name', 'asc')
                ->paginate(50);

            return view('sale_pos.partials.product_list')
                ->with(compact('products'));
        }
    }

    /**
     * Shows invoice url.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showInvoiceUrl($id)
    {
        if (!auth()->user()->can('sell.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $transaction = Transaction::where('business_id', $business_id)
                ->findorfail($id);
            $url = $this->transactionUtil->getInvoiceUrl($id, $business_id);

            return view('sale_pos.partials.invoice_url_modal')
                ->with(compact('transaction', 'url'));
        }
    }

    /**
     * Shows invoice to guest user.
     *
     * @param  string  $token
     * @return \Illuminate\Http\Response
     */
    public function showInvoice($token)
    {
        $transaction = Transaction::where('invoice_token', $token)->with(['business'])->first();

        if (!empty($transaction)) {
            $receipt = $this->receiptContent($transaction->business_id, $transaction->location_id, $transaction->id, 'browser');

            $title = $transaction->business->name . ' | ' . $transaction->invoice_no;
            return view('sale_pos.partials.show_invoice')
                ->with(compact('receipt', 'title'));
        } else {
            die(__("messages.something_went_wrong"));
        }
    }

    /**
     * Display a listing of the recurring invoices.
     *
     * @return \Illuminate\Http\Response
     */
    public function listSubscriptions()
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('direct_sell.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $sells = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->leftJoin('transaction_payments as tp', 'transactions.id', '=', 'tp.transaction_id')
                ->join(
                    'business_locations AS bl',
                    'transactions.location_id',
                    '=',
                    'bl.id'
                )
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->where('transactions.is_recurring', 1)
                ->select(
                    'transactions.id',
                    'transactions.transaction_date',
                    'transactions.is_direct_sale',
                    'transactions.invoice_no',
                    'contacts.name',
                    'transactions.subscription_no',
                    'bl.name as business_location',
                    'transactions.recur_parent_id',
                    'transactions.recur_stopped_on',
                    'transactions.is_recurring',
                    'transactions.recur_interval',
                    'transactions.recur_interval_type',
                    'transactions.recur_repetitions',
                    'transactions.subscription_repeat_on'
                )->with(['subscription_invoices']);



            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $sells->whereIn('transactions.location_id', $permitted_locations);
            }

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $sells->whereDate('transactions.transaction_date', '>=', $start)
                    ->whereDate('transactions.transaction_date', '<=', $end);
            }
            if (!empty(request()->contact_id)) {
                $sells->where('transactions.contact_id', request()->contact_id);
            }
            $datatable = Datatables::of($sells)
                ->addColumn(
                    'action',
                    function ($row) {
                        $html = '' ;

                        if ($row->is_recurring == 1 && auth()->user()->can("sell.update")) {
                            $link_text = !empty($row->recur_stopped_on) ? __('lang_v1.start_subscription') : __('lang_v1.stop_subscription');
                            $link_class = !empty($row->recur_stopped_on) ? 'btn-success' : 'btn-danger';

                            $html .= '<a href="' . action('SellPosController@toggleRecurringInvoices', [$row->id]) . '" class="toggle_recurring_invoice btn btn-xs ' . $link_class . '"><i class="fa fa-power-off"></i> ' . $link_text . '</a>';

                            if ($row->is_direct_sale == 0) {
                                $html .= '<a target="_blank" class="btn btn-xs btn-primary" href="' . action('SellPosController@edit', [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a>';
                            } else {
                                $html .= '<a target="_blank" class="btn btn-xs btn-primary" href="' . action('SellController@edit', [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a>';
                            }

                            if (auth()->user()->can("direct_sell.delete") || auth()->user()->can("sell.delete")) {
                                $html .= '&nbsp;<a href="' . action('SellPosController@destroy', [$row->id]) . '" class="delete-sale btn btn-xs btn-danger"><i class="fas fa-trash"></i> ' . __("messages.delete") . '</a>';
                            }
                        }

                        return $html;
                    }
                )
                ->removeColumn('id')
                ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                ->editColumn('recur_interval', function ($row) {
                    $type = $row->recur_interval == 1 ? Str::singular(__('lang_v1.' . $row->recur_interval_type)) : __('lang_v1.' . $row->recur_interval_type);
                    return $row->recur_interval . $type;
                })
                ->editColumn('recur_repetitions', function ($row) {
                    return !empty($row->recur_repetitions) ? $row->recur_repetitions : '-';
                })
                ->addColumn('subscription_invoices', function ($row) {
                    $invoices = [];
                    if (!empty($row->subscription_invoices)) {
                        $invoices = $row->subscription_invoices->pluck('invoice_no')->toArray();
                    }

                    $html = '';
                    $count = 0;
                    if (!empty($invoices)) {
                        $imploded_invoices = '<span class="label bg-info">' . implode('</span>, <span class="label bg-info">', $invoices) . '</span>';
                        $count = count($invoices);
                        $html .= '<small>' . $imploded_invoices . '</small>';
                    }
                    if ($count > 0) {
                        $html .= '<br><small class="text-muted">' .
                            __('sale.total') . ': ' . $count . '</small>';
                    }

                    return $html;
                })
                ->addColumn('last_generated', function ($row) {
                    if (!empty($row->subscription_invoices)) {
                        $last_generated_date = $row->subscription_invoices->max('created_at');
                    }
                    return !empty($last_generated_date) ? $last_generated_date->diffForHumans() : '';
                })
                ->addColumn('upcoming_invoice', function ($row) {
                    if (empty($row->recur_stopped_on)) {
                        $last_generated = !empty(count($row->subscription_invoices)) ? \Carbon::parse($row->subscription_invoices->max('transaction_date')) : \Carbon::parse($row->transaction_date);
                        $last_generated_string = $last_generated->format('Y-m-d');
                        $last_generated = \Carbon::parse($last_generated_string);

                        if ($row->recur_interval_type == 'days') {
                            $upcoming_invoice = $last_generated->addDays($row->recur_interval);
                        } elseif ($row->recur_interval_type == 'months') {
                            if (!empty($row->subscription_repeat_on)) {
                                $last_generated_string = $last_generated->format('Y-m');
                                $last_generated = \Carbon::parse($last_generated_string . '-' . $row->subscription_repeat_on);
                            }

                            $upcoming_invoice = $last_generated->addMonths($row->recur_interval);
                        } elseif ($row->recur_interval_type == 'years') {
                            $upcoming_invoice = $last_generated->addYears($row->recur_interval);
                        }
                    }
                    return !empty($upcoming_invoice) ? $this->transactionUtil->format_date($upcoming_invoice) : '';
                })
                ->rawColumns(['action', 'subscription_invoices'])
                ->make(true);

            return $datatable;
        }
        return view('sale_pos.subscriptions');
    }

    /**
     * Starts or stops a recurring invoice.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function toggleRecurringInvoices($id)
    {
        if (!auth()->user()->can('sell.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $transaction = Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('is_recurring', 1)
                ->findorfail($id);

            if (empty($transaction->recur_stopped_on)) {
                $transaction->recur_stopped_on = \Carbon::now();
            } else {
                $transaction->recur_stopped_on = null;
            }
            $transaction->save();

            $output = ['success' => 1,
                'msg' => trans("lang_v1.updated_success")
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                'msg' => trans("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    public function getRewardDetails(Request $request)
    {
        if ($request->session()->get('business.enable_rp') != 1) {
            return '';
        }

        $business_id = request()->session()->get('user.business_id');

        $customer_id = $request->input('customer_id');

        $redeem_details = $this->transactionUtil->getRewardRedeemDetails($business_id, $customer_id);

        return json_encode($redeem_details);
    }

    public function placeOrdersApi(Request $request)
    {
        try {
            $api_token = $request->header('API-TOKEN');
            $api_settings = $this->moduleUtil->getApiSettings($api_token);

            $business_id = $api_settings->business_id;
            $location_id = $api_settings->location_id;

            $input = $request->only(['products', 'customer_id', 'addresses']);

            //check if all stocks are available
            $variation_ids = [];
            foreach ($input['products'] as $product_data) {
                $variation_ids[] = $product_data['variation_id'];
            }

            $variations_details = $this->getVariationsDetails($business_id, $location_id, $variation_ids);
            $is_valid = true;
            $error_messages = [];
            $sell_lines = [];
            $final_total = 0;
            foreach ($variations_details as $variation_details) {
                if ($variation_details->product->enable_stock == 1) {
                    if (empty($variation_details->variation_location_details[0]) || $variation_details->variation_location_details[0]->qty_available < $input['products'][$variation_details->id]['quantity']) {
                        $is_valid = false;
                        $error_messages[] = 'Only ' . $variation_details->variation_location_details[0]->qty_available . ' ' . $variation_details->product->unit->short_name . ' of '. $input['products'][$variation_details->id]['product_name'] . ' available';
                    }
                }

                //Create product line array
                $sell_lines[] = [
                    'product_id' => $variation_details->product->id,
                    'unit_price_before_discount' => $variation_details->unit_price_inc_tax,
                    'unit_price' => $variation_details->unit_price_inc_tax,
                    'unit_price_inc_tax' => $variation_details->unit_price_inc_tax,
                    'variation_id' => $variation_details->id,
                    'quantity' => $input['products'][$variation_details->id]['quantity'],
                    'item_tax' => 0,
                    'enable_stock' => $variation_details->product->enable_stock,
                    'tax_id' => null,
                ];

                $final_total += ($input['products'][$variation_details->id]['quantity'] * $variation_details->unit_price_inc_tax);
            }

            if (!$is_valid) {
                return $this->respond([
                    'success' => false,
                    'error_messages' => $error_messages
                ]);
            }

            $business = Business::find($business_id);
            $user_id = $business->owner_id;

            $business_data = [
                'id' => $business_id,
                'accounting_method' => $business->accounting_method,
                'location_id' => $location_id
            ];

            $customer = Contact::where('business_id', $business_id)
                ->whereIn('type', ['customer', 'both'])
                ->find($input['customer_id']);

            $order_data = [
                'business_id' => $business_id,
                'location_id' => $location_id,
                'contact_id' => $input['customer_id'],
                'final_total' => $final_total,
                'created_by' => $user_id,
                'status' => 'final',
                'payment_status' => 'due',
                'additional_notes' => '',
                'transaction_date' => \Carbon::now(),
                'customer_group_id' => $customer->customer_group_id,
                'tax_rate_id' => null,
                'sale_note' => null,
                'commission_agent' => null,
                'order_addresses' => json_encode($input['addresses']),
                'products' => $sell_lines,
                'is_created_from_api' => 1,
                'discount_type' => 'fixed',
                'discount_amount' => 0
            ];

            $invoice_total = [
                'total_before_tax' => $final_total,
                'tax' => 0,
            ];

            DB::beginTransaction();

            $transaction = $this->transactionUtil->createSellTransaction($business_id, $order_data, $invoice_total, $user_id, false);

            //Create sell lines
            $this->transactionUtil->createOrUpdateSellLines($transaction, $order_data['products'], $order_data['location_id'], false, null, [], false);

            //update product stock
            foreach ($order_data['products'] as $product) {
                if ($product['enable_stock']) {
                    $this->productUtil->decreaseProductQuantity(
                        $product['product_id'],
                        $product['variation_id'],
                        $order_data['location_id'],
                        $product['quantity']
                    );
                }
            }

            $this->transactionUtil->mapPurchaseSell($business_data, $transaction->sell_lines, 'purchase');
            //Auto send notification
            $this->notificationUtil->autoSendNotification($business_id, 'new_sale', $transaction, $transaction->contact);

            DB::commit();

            $receipt = $this->receiptContent($business_id, $transaction->location_id, $transaction->id);

            $output = [
                'success' => 1,
                'transaction' => $transaction,
                'receipt' => $receipt
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            $msg = trans("messages.something_went_wrong");

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }

            $output = ['success' => 0,
                'error_messages' => [$msg]
            ];
        }

        return $this->respond($output);
    }

    private function getVariationsDetails($business_id, $location_id, $variation_ids)
    {
        $variation_details = Variation::whereIn('id', $variation_ids)
            ->with([
                'product' => function ($q) use ($business_id) {
                    $q->where('business_id', $business_id);
                },
                'product.unit',
                'variation_location_details' => function ($q) use ($location_id) {
                    $q->where('location_id', $location_id);
                }
            ])->get();

        return $variation_details;
    }

    public function getTypesOfServiceDetails(Request $request)
    {
        $location_id = $request->input('location_id');
        $types_of_service_id = $request->input('types_of_service_id');

        $business_id = $request->session()->get('user.business_id');

        $types_of_service = TypesOfService::where('business_id', $business_id)
            ->where('id', $types_of_service_id)
            ->first();

        $price_group_id = !empty($types_of_service->location_price_group[$location_id])
            ? $types_of_service->location_price_group[$location_id] : '';
        $price_group_name = '';

        if (!empty($price_group_id)) {
            $price_group = SellingPriceGroup::find($price_group_id);
            $price_group_name = $price_group->name;
        }

        $modal_html = view('types_of_service.pos_form_modal')
            ->with(compact('types_of_service'))->render();

        return $this->respond([
            'price_group_id' => $price_group_id,
            'packing_charge' => $types_of_service->packing_charge,
            'packing_charge_type' => $types_of_service->packing_charge_type,
            'modal_html' => $modal_html,
            'price_group_name' => $price_group_name
        ]);
    }

    private function __getwarranties()
    {
        $business_id = session()->get('user.business_id');
        $common_settings = session()->get('business.common_settings');
        $is_warranty_enabled = !empty($common_settings['enable_product_warranty']) ? true : false;
        $warranties = $is_warranty_enabled ? Warranty::forDropdown($business_id) : [];
        return $warranties;
    }

    /**
     * Parse the weighing barcode.
     *
     * @return array
     */
    private function __parseWeighingBarcode($scale_barcode)
    {
        $business_id = session()->get('user.business_id');

        $scale_setting = session()->get('business.weighing_scale_setting');

        $error_msg = trans("messages.something_went_wrong");

        //Check for prefix.
        if ((strlen($scale_setting['label_prefix']) == 0) || Str::startsWith($scale_barcode, $scale_setting['label_prefix'])) {
            $scale_barcode = substr($scale_barcode, strlen($scale_setting['label_prefix']));

            //Get product sku, trim left side 0
            $sku = ltrim(substr($scale_barcode, 0, $scale_setting['product_sku_length']+1), '0');

            //Get quantity integer
            $qty_int = substr($scale_barcode, $scale_setting['product_sku_length']+1, $scale_setting['qty_length']+1);

            //Get quantity decimal
            $qty_decimal = '0.' . substr($scale_barcode, $scale_setting['product_sku_length'] + $scale_setting['qty_length'] + 2, $scale_setting['qty_length_decimal']+1);

            $qty = (float)$qty_int + (float)$qty_decimal;

            //Find the variation id
            $result = $this->productUtil->filterProduct($business_id, $sku, null, false, null, [], ['sub_sku'], false)->first();

            if (!empty($result)) {
                return ['variation_id' => $result->variation_id,
                    'qty' => $qty,
                    'success' => true
                ];
            } else {
                $error_msg = trans("lang_v1.sku_not_match", ['sku' => $sku]);
            }
        } else {
            $error_msg = trans("lang_v1.prefix_did_not_match");
        }

        return [
            'success' => false,
            'msg' => $error_msg
        ];
    }

    public function approveCashier($id) {
        if (!auth()->user()->can('sell.accept_received_money_to_custom')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->user()->business_id;

                $approve = Transaction::where('business_id', $business_id)->findOrFail(request()->id);
                $approve->update([
                    'is_approved_by_cashier' => 1
                ]);

                $output = ['success' => true,
                    'msg' => __("lang_v1.accepted_success")
                ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output = ['success' => false,
                    'msg' => __("messages.something_went_wrong")
                ];
            }

            return $output;
        }
        return [
            'code' => 403,
            'message' => 'Unauthorized action.'
        ];
    }

    public function listTransactionPayments()
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('direct_sell.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $transaction_payments = TransactionPayment::leftJoin('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
                ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->select(
                    'transaction_payments.id',
                    'transactions.invoice_no',
                    'transaction_payments.amount',
                    'transaction_payments.method',
                    'transaction_payments.paid_on',
                    'transaction_payments.approval_status',
                    'transaction_payments.type',
                    'transaction_payments.cashier_confirmed_id',
                    'transaction_payments.admin_confirmed_id',
                    'transactions.id as transaction_id',
                    'transactions.transaction_date as transaction_date',
                    'contacts.id as customer_id',
                    'contacts.name'
                );

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $transaction_payments->whereIn('transactions.location_id', $permitted_locations);
            }

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $transaction_payments->whereDate('transaction_payments.paid_on', '>=', $start)
                    ->whereDate('transaction_payments.paid_on', '<=', $end);
            }

            if (!empty(request()->payment_method)) {
                $transaction_payments->where('transaction_payments.method', request()->payment_method);
            }

            if (!empty(request()->approval_status)) {
                $transaction_payments->where('transaction_payments.approval_status', request()->approval_status);
            }

            $datatable = Datatables::of($transaction_payments->orderBy('approval_status', 'desc'))
                ->addColumn(
                    'action',
                    function ($row) {
                        $html = '<button type="button" class="btn btn-xs btn-info view_payment" data-href="' . action("TransactionPaymentController@viewPayment", [$row->id]) . '">
                            <i class="fas fa-eye"></i> ' . __("purchase.view_payments") . '</button>';
                        return $html;
                    }
                )
                ->editColumn(
                    'amount', function ($row) {
                    $html = '<span class="display_currency final-total" data-currency_symbol="true"';
                    $amount = $row->amount;
                    if ($row->type == 'expense') {
                        $amount *= -1;
                    }

                    $html .= 'data-orig-value="'. $amount .'">'. $amount .'</span>';
                    return $html;
                })
                ->editColumn(
                    'method',
                    function ($row) {
                        return __('lang_v1.'.$row->method);
                    }
                )
                ->editColumn(
                    'type',
                    function ($row) {
                        return __('sale.'.$row->type);
                    }
                )
                ->editColumn(
                    'approval_status',
                    function ($row) {
                        $transaction = Transaction::find($row->transaction_id);
                        $canNotUpdate = $this->transactionUtil->canNotUpdate($transaction);
                        $can_not_approval_payment = $canNotUpdate['can_not_approval_payment'];

                        $approval_statuses = $this->productUtil->approvalStatuses();
                        $approval_status_colors = $this->productUtil->approvalStatusColors();
                        $link_text = $approval_statuses[$row->approval_status];
                        $link_class = $approval_status_colors[$row->approval_status];
                        $icon_class = $row->approval_status == 'approved' ? 'fas fa-check' : 'fas fa-times';
                        if($row->type == 'deposit'){
                            $action_function = 'TransactionPaymentController@editDeposit';
                        }elseif($row->type == 'cod'){
                            $action_function = 'TransactionPaymentController@editCod';
                        }else{
                            $action_function = 'TransactionPaymentController@editNormal';
                        }

                        if(!$can_not_approval_payment && in_array($row->approval_status, ['pending', 'unapproved']) && (($row->method == 'bank_transfer' && auth()->user()->can('sell.confirm_bank_transfer_method')) || ($row->method == 'cash' && auth()->user()->can('sell.accept_received_money_to_custom')) || ($row->method == 'other' && (auth()->user()->can('sell.accept_received_money_to_custom') || auth()->user()->can('sell.confirm_bank_transfer_method'))))){
                            $html = '<button data-href="' . action($action_function, [$row->id]) . '" class="approve_payment btn btn-xs ' . $link_class . '"><i class="'.$icon_class.'"></i> ' . $link_text . '</button>';
                        }else{
                            $html = '<span><i class="'.$icon_class.'"></i> ' . $link_text . '</span>';
                        }

                        return $html;
                    }
                )
                ->editColumn('invoice_no', function ($row) {
                    return '<button type="button" class="btn btn-link btn-modal" data-container=".view_modal" data-href="' . action('SellController@show', [$row->transaction_id]) . '">' . $row->invoice_no . '</button>';
                })
                ->editColumn('name', function ($row) {
                    return '<a href="' . action("ContactController@show", [$row->customer_id]) . '" target="_blank">' . $row->name . '</a>';
                })
                ->addColumn('user_confirm', function ($row) {
                    $user_confirm = $this->transactionUtil->getUserConfirmPayment($row->cashier_confirmed_id, $row->admin_confirmed_id);
                    if($user_confirm['type'] == 'both'){
                        $html = __('sale.cashier_confirm') .': '. $user_confirm['cashier_name'] .'<br>
                            '. __('sale.admin_confirm') .': '. $user_confirm['admin_name'];
                    }elseif($user_confirm['type'] == 'cashier'){
                        $html = $user_confirm['cashier_name'];
                    }else{
                        $html = '';
                    }

                    return $html;
                })
                ->removeColumn('id')
                ->editColumn('paid_on', '{{@format_datetime($paid_on)}}')
                ->rawColumns(['action', 'approval_status', 'amount', 'invoice_no', 'user_confirm', 'name'])
                ->make(true);

            return $datatable;
        }

        $payment_methods = $this->productUtil->payment_types();
        $approval_statuses = $this->productUtil->newApprovalStatuses();

        return view('sell_of_cashier.transaction_payments')
            ->with(compact('payment_methods', 'approval_statuses'));
    }

    public function showTransactionPayment($notification_id)
    {
        if (!auth()->user()->can('sell.receipt_expense')) {
            abort(403, 'Unauthorized action.');
        }

        $notification = auth()->user()->notifications()->where('id', $notification_id)->first();
        $notification_data = $notification->data;

        $payment_types = $this->productUtil->payment_types();
        $approval_statuses = $this->productUtil->approvalStatuses();

        $transaction = Transaction::where('id', $notification_data['transaction_id'])
            ->with(['contact', 'location', 'transaction_for'])
            ->first();

        $payment = TransactionPayment::leftJoin('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->select(
                'transaction_payments.id',
                'transactions.invoice_no',
                'transaction_payments.amount',
                'transaction_payments.method',
                'transaction_payments.paid_on',
                'transaction_payments.approval_status',
                'transaction_payments.type',
                'transaction_payments.payment_ref_no',
                'transactions.id as transaction_id',
                'transaction_payments.cashier_confirmed_id',
                'transaction_payments.admin_confirmed_id',
                'contacts.name'
            )->find($notification_data['id']);

        if(!$payment){
            return view('sell_of_cashier.show_canceled_transaction_payment')
                ->with(compact(
                    'transaction',
                    'notification_data',
                    'payment_types'
                ));
        }

        $approval_status_colors = $this->productUtil->approvalStatusColors();
        $link_text = $approval_statuses[$payment->approval_status];
        $link_class = $approval_status_colors[$payment->approval_status];
        $icon_class = $payment->approval_status == 'approved' ? 'fas fa-check' : 'fas fa-times';
        if($payment->type == 'deposit'){
            $action_function = 'TransactionPaymentController@editDeposit';
        }elseif($payment->type == 'cod'){
            $action_function = 'TransactionPaymentController@editCod';
        }else{
            $action_function = 'TransactionPaymentController@editNormal';
        }

        $canNotUpdate = $this->transactionUtil->canNotUpdate($transaction);
        $can_not_approval_payment = $canNotUpdate['can_not_approval_payment'];

        if(!$can_not_approval_payment && in_array($payment->approval_status, ['pending', 'unapproved']) && (($payment->method == 'bank_transfer' && auth()->user()->can('sell.confirm_bank_transfer_method')) || ($payment->method == 'cash' && auth()->user()->can('sell.accept_received_money_to_custom')) || ($payment->method == 'other' && (auth()->user()->can('sell.accept_received_money_to_custom') || auth()->user()->can('sell.confirm_bank_transfer_method'))))){
            $approval_status_html = '<button data-href="' . action($action_function, [$payment->id]) . '" class="notify_approve_payment btn btn-xs ' . $link_class . '"><i class="'.$icon_class.'"></i> ' . $link_text . '</button>';
        }else{
            $approval_status_html = '<span class="payment_closed btn btn-xs ' . $link_class . '"><i class="'.$icon_class.'"></i> ' . $link_text . '</span>';
        }

        $user_confirm = $this->transactionUtil->getUserConfirmPayment($payment->cashier_confirmed_id, $payment->admin_confirmed_id);

        return view('sell_of_cashier.show_transaction_payment')
            ->with(compact(
                'transaction',
                'payment',
                'approval_status_html',
                'payment_types',
                'user_confirm'
            ));
    }

    /*public function getSellCustomer($contact_id)
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('contacts_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $query = Transaction::where('contact_id', $contact_id)
                ->whereRaw('
                (
                    (
                        (
                            transactions.type = "sell"
                            OR transactions.type = "reduce_debt"
                            OR transactions.type = "sell_return"
                            OR (transactions.type = "receipt" AND (SELECT TP.approval_status FROM transaction_payments as TP WHERE transactions.id = TP.transaction_id AND TP.type = "receipt") = "approved")
                            OR (transactions.type = "expense" AND (SELECT TP.approval_status FROM transaction_payments as TP WHERE transactions.id = TP.transaction_id AND TP.type = "expense") = "approved")
                        )
                        AND transactions.status = "final"
                    )
                    OR (transactions.type = "purchase" AND transactions.status = "received")
                )')
                ->with(
                    'payment_lines',
                    'contact',
                    'sell_lines',
                    'sell_lines.variations.product.unit',
                    'sell_lines.variations.product.sub_unit',
                    'purchase_lines',
                    'purchase_lines.variations.product.unit',
                    'purchase_lines.variations.product.sub_unit',
                    'return_parent'
                )
                ->select(
                    '*',
                    DB::raw('(SELECT TP.note FROM transaction_payments as TP WHERE transactions.id = TP.transaction_id
                        AND TP.type = "receipt") as receipt_note'
                    ),
                    DB::raw('(SELECT TP.note FROM transaction_payments as TP WHERE transactions.id = TP.transaction_id
                        AND TP.type = "expense") as expense_note'
                    )
                );

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $query->whereDate('transaction_date', '>=', $start)
                    ->whereDate('transaction_date', '<=', $end);
            }

            $contact = DataTables::of($query)
                ->editColumn('final_total', function ($row) {
                    if ($row->type == 'receipt' || $row->type == 'purchase') {
                        $html = '--';
                    }elseif ($row->type == 'reduce_debt'){
                        $html = ($row->final_total < 0) ? '<span class="display_currency final_total_footer" data-currency_symbol="true" data-orig-value="'. ($row->final_total * -1) .'">'. ($row->final_total * -1) .'</span>' : '--';
                    }elseif($row->type == 'sell_return'){
                        $amount = 0;
                        foreach ($row->payment_lines as $payment) {
                            $amount += $payment->amount;
                        }
                        if ($amount == 0) {
                            $html = '--';
                        }else{
                            $html = '<span class="display_currency final_total_footer" data-currency_symbol="true" data-orig-value="'. $amount .'">'. $amount .'</span>';
                        }
                    }else{
                        $html = '<span class="display_currency final_total_footer" data-currency_symbol="true" data-orig-value="'. $row->final_total .'">'. $row->final_total .'</span>';
                    }

                    return $html;
                })
                ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                ->editColumn('invoice_no', function ($row) {
                    if(in_array($row->type, ['sell', 'sell_return'])){
                        $invoice_no = '<span>'. $row->invoice_no .'</span>';
                        if ($row->type == 'sell_return') {
                            $sell_return = Transaction::where('id', $row->id)->first();
                            $invoice_return = Transaction::where('id', $sell_return->return_parent_id)->first();
                            $invoice_no .= '&nbsp;&nbsp;<small class="label bg-red label-round no-print"><i class="fas fa-undo"></i></small>&nbsp;<small style="color: rgba(245,54,92,0)">' . $invoice_return->invoice_no .'</small>';
                        }
                    }else{
                        $invoice_no = $row->ref_no;
                    }

                    return $invoice_no;
                })
                ->addColumn('note', function ($row) {
                    $html = '';
                    if($row->type == 'reduce_debt'){
                        $html = __('contact.reduce_debt');
                    }else{
                        if($row->shipping_charges > 0){
                            $html .= __('sale.shipping_charges').': '. number_format(round_int($row->shipping_charges, env('DIGIT', 4))) .'đ';
                        }
                        if($row->tax_amount > 0){
                            if(!empty($html)){
                                $html .= '<br>';
                            }
                            $html .= __('purchase.purchase_tax').': '. number_format(round_int($row->tax_amount, env('DIGIT', 4))) .'đ';
                        }
                    }
                    return $html;
                })
                ->addColumn('product_sku', function ($row) {
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
                                $html .= $product_name . ' ('. $unit . $quantity . ' x ' . number_format($unit_price) .')' . '<br>';
                            } else {
                                $html .= $product_name . ' ('. $unit . $quantity . ' x ' . number_format($unit_price) .')' . ' ('. $variation .')' . '<br>';
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
                                $html .= $product_name . ' ('. $unit . $quantity . ' x ' . number_format($unit_price) .')' . '<br>';
                            } else {
                                $html .= $product_name . ' ('. $unit . $quantity . ' x ' . number_format($unit_price) .')' . ' ('. $variation .')' . '<br>';
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
                                        $html .= $product_name . ' ('. $unit . $quantity . ' x ' . number_format($unit_price) .')' . '<br>';
                                    } else {
                                        $html .= $product_name . ' ('. $unit . $quantity . ' x ' . number_format($unit_price) .')' . ' ('. $variation .')' . '<br>';
                                    }
                                }
                            }
                        }
                    }

                    return $html;
                })
                ->addColumn('total_sell_paid', function ($row) {
                    if ($row->type == 'receipt' || $row->type == 'purchase' || ($row->type == 'sell_return' && $row->status != 'cancel')) {
                        $amount = $row->final_total;
                    }elseif ($row->type == 'reduce_debt'){
                        $amount = ($row->final_total > 0) ? $row->final_total : 0;
                    }else{
                        $payments = $row->payment_lines;
                        $amount = 0;

                        foreach ($payments as $payment) {
                            if ($payment->approval_status == 'approved') {
                                if (($row->type == 'sell' && $row->status == 'final') || ($row->type == 'sell_return' && $row->status != 'cancel')) {
                                    $amount += $payment->amount;
                                }
                            }
                        }
                    }

                    if ($amount > 0) {
                        return '<span class="display_currency total_sell_paid_footer" data-currency_symbol="true" data-orig-value="'. $amount .'">'. $amount .'</span>';
                    } else {
                        return '--';
                    }
                })
                ->addColumn('total_due_customer', function ($row) {
                    if ($row->type == 'receipt' || $row->type == 'purchase') {
                        $html = '--';
                    }elseif ($row->type == 'reduce_debt'){
                        $html = ($row->final_total < 0) ? '<span class="display_currency total_due_customer" data-currency_symbol="true" data-orig-value="'. ($row->final_total * -1) .'">'. ($row->final_total * -1) .'</span>' : '--';
                    } else{
                        $payments = $row->payment_lines;
                        $amount = 0;
                        foreach ($payments as $payment) {
                            if ($row->type == 'sell' && $row->status == 'final') {
                                if ($payment->approval_status == 'approved') {
                                    $amount += $payment->amount;
                                }
                            }
                        }

                        $html = '';
                        $remain = $row->final_total - $amount;
                        if ($remain > 0) {
                            $html .= '<span class="display_currency total_due_customer" data-currency_symbol="true" data-orig-value="'. abs($remain) .'">'. abs($remain) .'</span>';
                        } else {
                            $html .= '--';
                        }

                        if ($row->type == 'sell_return') {
                            foreach ($payments as $payment) {
                                if ($payment->approval_status == 'approved') {
                                    $amount += $payment->amount;
                                }
                            }

                            if ($remain < $amount) {
                                $html = '<span class="display_currency total_due_customer" data-currency_symbol="true" data-orig-value="'. abs($remain - $amount) .'">'. abs($remain - $amount) .'</span>';;
                            } else {
                                $html = '--';
                            }
                        }
                    }

                    return $html;
                })
                ->addColumn('total_due_business', function ($row) {
                    if ($row->type == 'receipt' || $row->type == 'purchase') {
                        $html = '<span class="display_currency total_due_business" data-currency_symbol="true" data-orig-value="'. $row->final_total .'">'. $row->final_total .'</span>';
                    }elseif ($row->type == 'reduce_debt'){
                        $html = ($row->final_total > 0) ? '<span class="display_currency total_due_business" data-currency_symbol="true" data-orig-value="'. $row->final_total .'">'. $row->final_total .'</span>' : '--';
                    }else{
                        $payments = $row->payment_lines;
                        $amount = 0;
                        foreach ($payments as $payment) {
                            if ($payment->approval_status == 'approved') {
                                if ($row->type == 'sell' || $row->type == 'sell_return') {
                                    $amount += $payment->amount;
                                }
                            }
                        }

                        $html = '--';
                        $remain = $row->final_total - $amount;
                        if ($row->type != 'sell_return' && $remain < 0) {
                            $html = '<span class="display_currency total_due_business" data-currency_symbol="true" data-orig-value="'. abs($remain) .'">'. abs($remain) .'</span>';
                        } elseif ($row->type == 'sell_return' && $remain != 0) {
                            $html = '<span class="display_currency total_due_business" data-currency_symbol="true" data-orig-value="'. abs($remain) .'">'. abs($remain) .'</span>';
                        }
                    }

                    return $html;
                });

            return $contact->rawColumns(['invoice_no', 'product_sku', 'final_total', 'total_sell_paid', 'total_due_customer', 'total_due_business', 'note'])
                ->make(true);
        }

        return view('contact.show');
    }*/

    public function exportExcel()
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('sell.create') && !auth()->user()->can('direct_sell.access') && !auth()->user()->can('view_own_sell_only')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            //Set maximum php execution time
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);

            $business_id = request()->session()->get('user.business_id');
            $with = [];
            $sells = $this->transactionUtil->getListSells($business_id);
            $sells = $sells->with('sell_lines.sub_unit', 'sell_lines.product');

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

            if (!empty(request()->input('sell_list_filter_payment_status')) && request()->input('sell_list_filter_payment_status') != 'overdue') {
                $sells->where('transactions.payment_status', request()->input('sell_list_filter_payment_status'));
            } elseif (request()->input('sell_list_filter_payment_status') == 'overdue') {
                $sells->whereIn('transactions.payment_status', ['due', 'partial'])
                    ->whereNotNull('transactions.pay_term_number')
                    ->whereNotNull('transactions.pay_term_type')
                    ->whereRaw("IF(transactions.pay_term_type='days', DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number DAY) < CURDATE(), DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number MONTH) < CURDATE())");
            }

            //Add condition for location,used in sales representative expense report
            if (request()->has('sell_list_filter_location_id')) {
                $location_id = request()->get('sell_list_filter_location_id');
                if (!empty($location_id)) {
                    $sells->where('transactions.location_id', $location_id);
                }
            }

            if (!empty(request()->sell_list_filter_customer_id)) {
                $customer_id = request()->sell_list_filter_customer_id;
                $sells->where('contacts.id', $customer_id);
            }
            if (!empty(request()->sell_list_filter_start_date) && !empty(request()->sell_list_filter_end_date)) {
                $start = request()->sell_list_filter_start_date;
                $end = request()->sell_list_filter_end_date;
                $sells->whereDate('transactions.transaction_date', '>=', $start)
                    ->whereDate('transactions.transaction_date', '<=', $end);
            }

            if (!empty(request()->input('created_by'))) {
                $sells->where('transactions.created_by', request()->input('created_by'));
            }

            if (!empty(request()->input('service_staffs'))) {
                $sells->where('transactions.res_waiter_id', request()->input('service_staffs'));
            }
            $only_shipments = request()->only_shipments == 'true' ? true : false;
            if ($only_shipments && auth()->user()->can('shipping.update')) {
                $sells->whereNotNull('transactions.shipping_status');
            }

            if (!empty(request()->input('shipping_status'))) {
                $sells->where('transactions.shipping_status', request()->input('shipping_status'));
            }

            $sells->groupBy('transactions.id');

            $with[] = 'payment_lines';
            if (!empty($with)) {
                $sells->with($with);
            }

            $sells = $sells->orderByDesc('transactions.created_at')->get();
//            $merge = [];
            $sum_final_total = 0;
            $sum_total_paid = 0;
            $sum_deposit = 0;
            $sum_cod = 0;
            $sum_total_remaining = 0;
            $export = [];

            $column_a = __('messages.date');
            $column_b = __('sale.shipper');
            $column_c = __('sale.invoice_no');
            $column_d = __('sale.customer_name');
            $column_e = __('lang_v1.contact_id');
            $column_f = __('sale.address');
            $column_g = __('sale.product');
            $column_h = __('product.product_sku');
            $column_i = __('sale.order_quantity');
            $column_j = __('lang_v1.unit');
            $column_k = __('sale.unit_price');
            $column_l = __('sale.total_amount');
            $column_m = __('sale.total_paid');
            $column_n = __('lang_v1.deposit');
            $column_o = __('lang_v1.cod');
            $column_p = __('lang_v1.return_money');

            //Gán các dòng dữ liệu
            $row_index = 3;
            foreach($sells as $sell){
                $transaction_date = Carbon::parse($sell->transaction_date)->format('d/m/Y H:i');
                $sell_orders = [];

                foreach ($sell->sell_lines as $sell_line){
                    $is_exist = false;

                    foreach ($sell_orders as $key => $sell_order){
                        if($sell_order['variation_id'] == $sell_line->variation_id && $sell_order['unit_type'] == $sell_line->sub_unit->type){
                            if($sell_order['unit_price'] == $sell_line->unit_price){
                                $sell_orders[$key]['quantity'] += $sell_line->quantity_line * $sell_line->width * $sell_line->height;

                                $is_exist = true;
                            }
                            break;
                        }
                    }

                    $multiplier = $sell_line->sub_unit->base_unit_multiplier;
                    if(!$multiplier){
                        $multiplier = 1;
                    }

                    $area = $sell_line->sub_unit->type == 'weight' ? ($sell_line->quantity_line * $multiplier) / $sell_line->product->weight : $sell_line->width * $sell_line->height * $sell_line->quantity_line;
                    $unit_price_before_discount =  $sell_line->sub_unit->type == 'weight' ? $sell_line->unit_price_before_discount * $area : $sell_line->unit_price_before_discount;
                    $unit_price_after_discount =  $unit_price_before_discount - $sell_line->line_discount_amount;

                    if(!$is_exist){
                        $quantity = $sell_line->quantity_line * $sell_line->width * $sell_line->height;
                        $unit_name = $sell_line->sub_unit->actual_name;

                        if ($sell_line->sub_unit->type == 'pcs' || $sell_line->sub_unit->type == 'weight' || ($sell_line->sub_unit->type == 'area' && $sell_line->width == $sell_line->sub_unit->width)) {
                            $quantity = $sell_line->quantity_line;
                        }

                        $sell_orders[] = [
                            'product_name' => $sell_line->product->name,
                            'sku' => $sell_line->product->sku,
                            'variation_id' => $sell_line->variation_id,
                            'quantity' => $quantity,
                            'unit_price' => $unit_price_after_discount,
                            'unit_type' => $sell_line->sub_unit->type,
                            'unit_name' => $unit_name,
                            'base_unit_id' => $sell_line->sub_unit->base_unit_id,
                        ];
                    }
                }

                if(count($sell_orders) > 1){
                    $sell_order_remaining = array_slice($sell_orders,1);
                }else{
                    $sell_order_remaining = [];
                }

                $invoice_no = $sell->invoice_no;
                if ($sell->status == 'cancel') {
                    $sell->final_total = 0;
                    $sell->total_paid = 0;
                    $invoice_no .= ' ('. __('sale.cancelled') .')';
                }

                $shipper_array = array_unique(explode(',', $sell->shipper));
                $shipper = implode(', ', $shipper_array);

                $export[$row_index][$column_a] = $transaction_date;
                $export[$row_index][$column_b] = $shipper;
                $export[$row_index][$column_c] = $invoice_no;
                $export[$row_index][$column_d] = $sell->name;
                $export[$row_index][$column_e] = $sell->contact_id;
                $export[$row_index][$column_f] = $sell->shipping_address;
                $export[$row_index][$column_g] = !empty($sell_orders) ? $sell_orders[0]['product_name'] : null;
                $export[$row_index][$column_h] = !empty($sell_orders) ? $sell_orders[0]['sku'] : null;
                $export[$row_index][$column_i] = !empty($sell_orders) ? number_format(round($sell_orders[0]['quantity'], 3), 3) : null;
                $export[$row_index][$column_j] = !empty($sell_orders) ? $sell_orders[0]['unit_name'] : null;
                $export[$row_index][$column_k] = !empty($sell_orders) ? number_format($sell_orders[0]['unit_price']) : null;
                $export[$row_index][$column_l] = number_format(round_int($sell->final_total, env('DIGIT', 4)));
                $export[$row_index][$column_m] = number_format(round_int($sell->total_paid, env('DIGIT', 4)));
                $export[$row_index][$column_n] = number_format(round_int($sell->deposit, env('DIGIT', 4)));
                $export[$row_index][$column_o] = number_format(round_int($sell->cod, env('DIGIT', 4)));
                $export[$row_index][$column_p] = number_format(round_int($sell->final_total - $sell->total_paid, env('DIGIT', 4)));

                $sum_final_total += round_int($sell->final_total, env('DIGIT', 4));
                $sum_total_paid += round_int($sell->total_paid, env('DIGIT', 4));
                $sum_deposit += round_int($sell->deposit, env('DIGIT', 4));
                $sum_cod += round_int($sell->cod, env('DIGIT', 4));
                $sum_total_remaining = $sum_final_total - $sum_total_paid;

                $first_sell_line_index = $row_index;
                foreach ($sell_order_remaining as $sell_order){
                    if (!empty($sell_order['base_unit_id'])) {
                        $unit_name_order = Unit::find($sell_order['base_unit_id'])->actual_name;
                    } else {
                        $unit_name_order = $sell_order['unit_name'];
                    }
                    $row_index++;
                    $export[$row_index][$column_a] = null;
                    $export[$row_index][$column_b] = null;
                    $export[$row_index][$column_c] = null;
                    $export[$row_index][$column_d] = null;
                    $export[$row_index][$column_e] = null;
                    $export[$row_index][$column_f] = null;
                    $export[$row_index][$column_g] = $sell_order['product_name'];
                    $export[$row_index][$column_h] = $sell_order['sku'];
                    $export[$row_index][$column_i] = number_format(round($sell_order['quantity'], 3), 3);
                    $export[$row_index][$column_j] = $unit_name_order;
                    $export[$row_index][$column_k] = number_format($sell_order['unit_price']);
                    $export[$row_index][$column_l] = null;
                    $export[$row_index][$column_m] = null;
                    $export[$row_index][$column_n] = null;
                    $export[$row_index][$column_o] = null;
                    $export[$row_index][$column_p] = null;
                }

//                $merge[] = 'A'.($first_sell_line_index).':A'.($row_index);
//                $merge[] = 'B'.($first_sell_line_index).':B'.($row_index);
//                $merge[] = 'C'.($first_sell_line_index).':C'.($row_index);
//                $merge[] = 'D'.($first_sell_line_index).':D'.($row_index);
//                $merge[] = 'E'.($first_sell_line_index).':E'.($row_index);
//                $merge[] = 'F'.($first_sell_line_index).':F'.($row_index);
//                $merge[] = 'L'.($first_sell_line_index).':L'.($row_index);
//                $merge[] = 'M'.($first_sell_line_index).':M'.($row_index);
//                $merge[] = 'N'.($first_sell_line_index).':N'.($row_index);
//                $merge[] = 'O'.($first_sell_line_index).':O'.($row_index);
//                $merge[] = 'P'.($first_sell_line_index).':P'.($row_index);

                $row_index++;
            }

            //Hiển thị tổng cộng
            $row_index++;
            $export[$row_index][$column_a] = __('lang_v1.grand_total');
            $export[$row_index][$column_b] = null;
            $export[$row_index][$column_c] = null;
            $export[$row_index][$column_d] = null;
            $export[$row_index][$column_e] = null;
            $export[$row_index][$column_f] = null;
            $export[$row_index][$column_g] = null;
            $export[$row_index][$column_h] = null;
            $export[$row_index][$column_i] = null;
            $export[$row_index][$column_j] = null;
            $export[$row_index][$column_k] = null;
            $export[$row_index][$column_l] = number_format(round_int($sum_final_total, env('DIGIT', 4)));
            $export[$row_index][$column_m] = number_format(round_int($sum_total_paid, env('DIGIT', 4)));
            $export[$row_index][$column_n] = number_format(round_int($sum_deposit, env('DIGIT', 4)));
            $export[$row_index][$column_o] = number_format(round_int($sum_cod, env('DIGIT', 4)));
            $export[$row_index][$column_p] = number_format(round_int($sum_total_remaining, env('DIGIT', 4)));

            $file_name = 'danh-sach-don-hang';
            if(isset($start) && isset($end)){
                $file_name .= '_'.$start.'_'.$end;
            }
            $file_name .= '.xlsx';

            $export_collect = collect($export);

            //Sheet style
            $header_style = (new StyleBuilder())
                ->setFontName('Arial')
                ->setFontSize(12)
//                ->setCellAlignment(CellAlignment::CENTER)
                ->setFontBold()
                ->setShouldWrapText()
                ->build();

            $rows_style = (new StyleBuilder())
                ->setFontName('Arial')
                ->setFontSize(12)
//                ->setCellAlignment(CellAlignment::LEFT)
                ->setShouldWrapText()
                ->build();

            return (new FastExcel($export_collect))
                ->headerStyle($header_style)
                ->rowsStyle($rows_style)
                ->download($file_name);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                'msg' => $e->getMessage()
            ];
            return redirect(url()->previous())->with('status', $output);
        }
    }

    public function exportExcelCashier()
    {
        /*if (!auth()->user()->can('sell.view') && !auth()->user()->can('sell.create') && !auth()->user()->can('direct_sell.access') && !auth()->user()->can('view_own_sell_only')) {
            abort(403, 'Unauthorized action.');
        }*/

        try {
            //Set maximum php execution time
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);

            $business_id = request()->session()->get('user.business_id');
            $with = [];
            $sells = $this->transactionUtil->getListSells($business_id);
            $sells = $sells->with('sell_lines.sub_unit', 'sell_lines.product');

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

            if (!empty(request()->input('sell_list_filter_payment_status')) && request()->input('sell_list_filter_payment_status') != 'overdue') {
                $sells->where('transactions.payment_status', request()->input('sell_list_filter_payment_status'));
            } elseif (request()->input('sell_list_filter_payment_status') == 'overdue') {
                $sells->whereIn('transactions.payment_status', ['due', 'partial'])
                    ->whereNotNull('transactions.pay_term_number')
                    ->whereNotNull('transactions.pay_term_type')
                    ->whereRaw("IF(transactions.pay_term_type='days', DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number DAY) < CURDATE(), DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number MONTH) < CURDATE())");
            }

            //Add condition for location,used in sales representative expense report
            if (request()->has('sell_list_filter_location_id')) {
                $location_id = request()->get('sell_list_filter_location_id');
                if (!empty($location_id)) {
                    $sells->where('transactions.location_id', $location_id);
                }
            }

            if (!empty(request()->sell_list_filter_customer_id)) {
                $customer_id = request()->sell_list_filter_customer_id;
                $sells->where('contacts.id', $customer_id);
            }

            if (!empty(request()->sell_list_filter_payment_method)) {
                $payment_method = request()->sell_list_filter_payment_method;
                $sells->leftJoin(
                    'transaction_payments',
                    'transactions.id',
                    '=',
                    'transaction_payments.transaction_id'
                )
                    ->where('transaction_payments.method', $payment_method);
            }

            if (!empty(request()->sell_list_filter_start_date) && !empty(request()->sell_list_filter_end_date)) {
                $start = request()->sell_list_filter_start_date;
                $end = request()->sell_list_filter_end_date;
                $sells->whereDate('transactions.transaction_date', '>=', $start)
                    ->whereDate('transactions.transaction_date', '<=', $end);
            }

            if (!empty(request()->input('created_by'))) {
                $sells->where('transactions.created_by', request()->input('created_by'));
            }

            if (!empty(request()->input('service_staffs'))) {
                $sells->where('transactions.res_waiter_id', request()->input('service_staffs'));
            }
            $only_shipments = request()->only_shipments == 'true' ? true : false;
            if ($only_shipments && auth()->user()->can('shipping.update')) {
                $sells->whereNotNull('transactions.shipping_status');
            }

            if (!empty(request()->input('shipping_status'))) {
                $sells->where('transactions.shipping_status', request()->input('shipping_status'));
            }

            $sells->groupBy('transactions.id');

            $with[] = 'payment_lines';
            if (!empty($with)) {
                $sells->with($with);
            }

            $sells = $sells->orderByDesc('transactions.created_at')->get();
//            $merge = [];
            $sum_final_total = 0;
            $sum_total_paid = 0;
            $sum_deposit = 0;
            $sum_cod = 0;
            $sum_total_remaining = 0;
            $export = [];

            $column_a = __('messages.date');
            $column_b = __('sale.shipper');
            $column_c = __('sale.invoice_no');
            $column_d = __('sale.customer_name');
            $column_e = __('lang_v1.contact_id');
            $column_f = __('sale.address');
            $column_g = __('sale.product');
            $column_h = __('product.product_sku');
            $column_i = __('sale.order_quantity');
            $column_j = __('lang_v1.unit');
            $column_k = __('sale.unit_price');
            $column_l = __('sale.total_amount');
            $column_m = __('sale.total_paid');
            $column_n = __('lang_v1.deposit');
            $column_o = __('lang_v1.cod');
            $column_p = __('lang_v1.return_money');

            //Gán các dòng dữ liệu
            $row_index = 3;
            foreach($sells as $sell){
                $transaction_date = Carbon::parse($sell->transaction_date)->format('d/m/Y H:i');
                $sell_orders = [];

                foreach ($sell->sell_lines as $sell_line){
                    $is_exist = false;

                    foreach ($sell_orders as $key => $sell_order){
                        if($sell_order['variation_id'] == $sell_line->variation_id && $sell_order['unit_type'] == $sell_line->sub_unit->type){
                            if($sell_order['unit_price'] == $sell_line->unit_price){
                                $sell_orders[$key]['quantity'] += $sell_line->quantity_line * $sell_line->width * $sell_line->height;

                                $is_exist = true;
                            }
                            break;
                        }
                    }

                    $multiplier = $sell_line->sub_unit->base_unit_multiplier;
                    if(!$multiplier){
                        $multiplier = 1;
                    }

                    $area = $sell_line->sub_unit->type == 'weight' ? ($sell_line->quantity_line * $multiplier) / $sell_line->product->weight : $sell_line->width * $sell_line->height * $sell_line->quantity_line;
                    $unit_price_before_discount =  $sell_line->sub_unit->type == 'weight' ? $sell_line->unit_price_before_discount * $area : $sell_line->unit_price_before_discount;
                    $unit_price_after_discount =  $unit_price_before_discount - $sell_line->line_discount_amount;

                    if(!$is_exist){
                        $quantity = $sell_line->quantity_line * $sell_line->width * $sell_line->height;
                        $unit_name = $sell_line->sub_unit->actual_name;

                        if ($sell_line->sub_unit->type == 'pcs' || $sell_line->sub_unit->type == 'weight' || ($sell_line->sub_unit->type == 'area' && $sell_line->width == $sell_line->sub_unit->width)) {
                            $quantity = $sell_line->quantity_line;
                        }

                        $sell_orders[] = [
                            'product_name' => $sell_line->product->name,
                            'sku' => $sell_line->product->sku,
                            'variation_id' => $sell_line->variation_id,
                            'quantity' => $quantity,
                            'unit_price' => $unit_price_after_discount,
                            'unit_type' => $sell_line->sub_unit->type,
                            'unit_name' => $unit_name,
                            'base_unit_id' => $sell_line->sub_unit->base_unit_id,
                        ];
                    }
                }

                if(count($sell_orders) > 1){
                    $sell_order_remaining = array_slice($sell_orders,1);
                }else{
                    $sell_order_remaining = [];
                }

                $invoice_no = $sell->invoice_no;
                if ($sell->status == 'cancel') {
                    $sell->final_total = 0;
                    $sell->total_paid = 0;
                    $invoice_no .= ' ('. __('sale.cancelled') .')';
                }

                $shipper_array = array_unique(explode(',', $sell->shipper));
                $shipper = implode(', ', $shipper_array);

                $export[$row_index][$column_a] = $transaction_date;
                $export[$row_index][$column_b] = $shipper;
                $export[$row_index][$column_c] = $invoice_no;
                $export[$row_index][$column_d] = $sell->name;
                $export[$row_index][$column_e] = $sell->contact_id;
                $export[$row_index][$column_f] = $sell->shipping_address;
                $export[$row_index][$column_g] = !empty($sell_orders) ? $sell_orders[0]['product_name'] : null;
                $export[$row_index][$column_h] = !empty($sell_orders) ? $sell_orders[0]['sku'] : null;
                $export[$row_index][$column_i] = !empty($sell_orders) ? number_format(round($sell_orders[0]['quantity'], 3), 3, ',', '.') : null;
                $export[$row_index][$column_j] = !empty($sell_orders) ? $sell_orders[0]['unit_name'] : null;
                $export[$row_index][$column_k] = !empty($sell_orders) ? number_format($sell_orders[0]['unit_price'], 0, ',', '.') : null;
                $export[$row_index][$column_l] = number_format(round_int($sell->final_total, env('DIGIT', 4)), 0, ',', '.');
                $export[$row_index][$column_m] = number_format(round_int($sell->total_paid, env('DIGIT', 4)), 0, ',', '.');
                $export[$row_index][$column_n] = number_format(round_int($sell->deposit, env('DIGIT', 4)), 0, ',', '.');
                $export[$row_index][$column_o] = number_format(round_int($sell->cod, env('DIGIT', 4)), 0, ',', '.');
                $export[$row_index][$column_p] = number_format(round_int($sell->final_total - $sell->total_paid, env('DIGIT', 4)), 0, ',', '.');

                $sum_final_total += round_int($sell->final_total, env('DIGIT', 4));
                $sum_total_paid += round_int($sell->total_paid, env('DIGIT', 4));
                $sum_deposit += round_int($sell->deposit, env('DIGIT', 4));
                $sum_cod += round_int($sell->cod, env('DIGIT', 4));
                $sum_total_remaining = $sum_final_total - $sum_total_paid;

                $first_sell_line_index = $row_index;
                foreach ($sell_order_remaining as $sell_order){
                    if (!empty($sell_order['base_unit_id'])) {
                        $unit_name_order = Unit::find($sell_order['base_unit_id'])->actual_name;
                    } else {
                        $unit_name_order = $sell_order['unit_name'];
                    }
                    $row_index++;
                    $export[$row_index][$column_a] = null;
                    $export[$row_index][$column_b] = null;
                    $export[$row_index][$column_c] = null;
                    $export[$row_index][$column_d] = null;
                    $export[$row_index][$column_e] = null;
                    $export[$row_index][$column_f] = null;
                    $export[$row_index][$column_g] = $sell_order['product_name'];
                    $export[$row_index][$column_h] = $sell_order['sku'];
                    $export[$row_index][$column_i] = number_format(round($sell_order['quantity'], 3), 3, ',', '.');
                    $export[$row_index][$column_j] = $unit_name_order;
                    $export[$row_index][$column_k] = number_format($sell_order['unit_price'], 0, ',', '.');
                    $export[$row_index][$column_l] = null;
                    $export[$row_index][$column_m] = null;
                    $export[$row_index][$column_n] = null;
                    $export[$row_index][$column_o] = null;
                    $export[$row_index][$column_p] = null;
                }

//                $merge[] = 'A'.($first_sell_line_index).':A'.($row_index);
//                $merge[] = 'B'.($first_sell_line_index).':B'.($row_index);
//                $merge[] = 'C'.($first_sell_line_index).':C'.($row_index);
//                $merge[] = 'D'.($first_sell_line_index).':D'.($row_index);
//                $merge[] = 'E'.($first_sell_line_index).':E'.($row_index);
//                $merge[] = 'F'.($first_sell_line_index).':F'.($row_index);
//                $merge[] = 'L'.($first_sell_line_index).':L'.($row_index);
//                $merge[] = 'M'.($first_sell_line_index).':M'.($row_index);
//                $merge[] = 'N'.($first_sell_line_index).':N'.($row_index);
//                $merge[] = 'O'.($first_sell_line_index).':O'.($row_index);
//                $merge[] = 'P'.($first_sell_line_index).':P'.($row_index);

                $row_index++;
            }

            //Hiển thị tổng cộng
            $row_index++;
            $export[$row_index][$column_a] = __('lang_v1.grand_total');
            $export[$row_index][$column_b] = null;
            $export[$row_index][$column_c] = null;
            $export[$row_index][$column_d] = null;
            $export[$row_index][$column_e] = null;
            $export[$row_index][$column_f] = null;
            $export[$row_index][$column_g] = null;
            $export[$row_index][$column_h] = null;
            $export[$row_index][$column_i] = null;
            $export[$row_index][$column_j] = null;
            $export[$row_index][$column_k] = null;
            $export[$row_index][$column_l] = number_format(round_int($sum_final_total, env('DIGIT', 4)), 0, ',', '.');
            $export[$row_index][$column_m] = number_format(round_int($sum_total_paid, env('DIGIT', 4)), 0, ',', '.');
            $export[$row_index][$column_n] = number_format(round_int($sum_deposit, env('DIGIT', 4)), 0, ',', '.');
            $export[$row_index][$column_o] = number_format(round_int($sum_cod, env('DIGIT', 4)), 0, ',', '.');
            $export[$row_index][$column_p] = number_format(round_int($sum_total_remaining, env('DIGIT', 4)), 0, ',', '.');

            $file_name = 'danh-sach-don-hang';
            if(isset($start) && isset($end)){
                $file_name .= '_'.$start.'_'.$end;
            }
            $file_name .= '.xlsx';

            $export_collect = collect($export);

            //Sheet style
            $header_style = (new StyleBuilder())
                ->setFontName('Arial')
                ->setFontSize(12)
//                ->setCellAlignment(CellAlignment::CENTER)
                ->setFontBold()
                ->setShouldWrapText()
                ->build();

            $rows_style = (new StyleBuilder())
                ->setFontName('Arial')
                ->setFontSize(12)
//                ->setCellAlignment(CellAlignment::LEFT)
                ->setShouldWrapText()
                ->build();

            return (new FastExcel($export_collect))
                ->headerStyle($header_style)
                ->rowsStyle($rows_style)
                ->download($file_name);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                'msg' => $e->getMessage()
            ];
            return redirect(url()->previous())->with('status', $output);
        }
    }

    public function exportOnDayExcelCashier()
    {
        try {
            //Set maximum php execution time
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);

            $business_id = request()->session()->get('user.business_id');
            $with = [];
            $sells = $this->transactionUtil->getListSells($business_id);
            $sells = $sells->with('sell_lines.sub_unit', 'sell_lines.product');

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $sells->whereIn('transactions.location_id', $permitted_locations);
            }

            if (!empty(request()->sell_list_filter_start_date) && !empty(request()->sell_list_filter_start_date)) {
                $start = request()->sell_list_filter_start_date;
                $end =  request()->sell_list_filter_start_date;
            }else{
                $start  = date('Y-m-d');
                $end    = date('Y-m-d 23:59:59');
            }
            $sells->whereDate('transactions.transaction_date', '>=', $start)
                ->whereDate('transactions.transaction_date', '<=', $end);

            $sells->groupBy('transactions.id');

            $with[] = 'payment_lines';
            if (!empty($with)) {
                $sells->with($with);
            }

            $sells = $sells->orderByDesc('transactions.created_at')->get();
//            $merge = [];
            $sum_final_total = 0;
            $sum_total_paid = 0;
            $sum_deposit = 0;
            $sum_cod = 0;
            $export = [];

            $column_b = __('sale.shipper');
            $column_c = __('sale.invoice_no');
            $column_d = __('sale.customer_name');
            $column_f = __('sale.address');
            $column_l = __('sale.invoice_amount');
            $column_n = __('lang_v1.deposit');
            $column_o = __('lang_v1.cod');
            $column_p = __('lang_v1.return_money');

            //Gán các dòng dữ liệu
            $row_index = 3;
            foreach($sells as $sell){
                $transaction_date = Carbon::parse($sell->transaction_date)->format('d/m/Y H:i');
                $sell_orders = [];
                $paymentStatus = [];
                $getExcel = true;
                foreach ($sell->payment_lines as $payment_line) {
                    $paymentStatus[$payment_line->type] = $payment_line->approval_status;
                    if ($payment_line->approval_status != 'approved') {
                        $getExcel = false;
                    }
                }

                if (($getExcel && $sell->payment_status == 'paid') || !empty($sell->is_confirm_debit_paper)) {
                    continue;
                }

                foreach ($sell->sell_lines as $sell_line){
                    $is_exist = false;

                    foreach ($sell_orders as $key => $sell_order){
                        if($sell_order['variation_id'] == $sell_line->variation_id && $sell_order['unit_type'] == $sell_line->sub_unit->type){
                            if($sell_order['unit_price'] == $sell_line->unit_price){
                                $sell_orders[$key]['quantity'] += $sell_line->quantity_line * $sell_line->width * $sell_line->height;

                                $is_exist = true;
                            }
                            break;
                        }
                    }

                    $multiplier = $sell_line->sub_unit->base_unit_multiplier;
                    if(!$multiplier){
                        $multiplier = 1;
                    }

                    $area = $sell_line->sub_unit->type == 'weight' ? ($sell_line->quantity_line * $multiplier) / $sell_line->product->weight : $sell_line->width * $sell_line->height * $sell_line->quantity_line;
                    $unit_price_before_discount =  $sell_line->sub_unit->type == 'weight' ? $sell_line->unit_price_before_discount * $area : $sell_line->unit_price_before_discount;
                    $unit_price_after_discount =  $unit_price_before_discount - $sell_line->line_discount_amount;

                    if(!$is_exist){
                        $quantity = $sell_line->quantity_line * $sell_line->width * $sell_line->height;
                        $unit_name = $sell_line->sub_unit->actual_name;

                        if ($sell_line->sub_unit->type == 'pcs' || $sell_line->sub_unit->type == 'weight' || ($sell_line->sub_unit->type == 'area' && $sell_line->width == $sell_line->sub_unit->width)) {
                            $quantity = $sell_line->quantity_line;
                        }

                        $sell_orders[] = [
                            'product_name' => $sell_line->product->name,
                            'sku' => $sell_line->product->sku,
                            'variation_id' => $sell_line->variation_id,
                            'quantity' => $quantity,
                            'unit_price' => $unit_price_after_discount,
                            'unit_type' => $sell_line->sub_unit->type,
                            'unit_name' => $unit_name,
                            'base_unit_id' => $sell_line->sub_unit->base_unit_id,
                        ];
                    }
                }

                if(count($sell_orders) > 1){
                    $sell_order_remaining = array_slice($sell_orders,1);
                }else{
                    $sell_order_remaining = [];
                }

                $invoice_no = $sell->invoice_no;
                if ($sell->status == 'cancel') {
                    $sell->final_total = 0;
                    $sell->total_paid = 0;
                    $invoice_no .= ' ('. __('sale.cancelled') .')';
                }

                $remainMoney = round_int($sell->final_total - $sell->total_paid, env('DIGIT', 4));

                $shipper_array = array_unique(explode(',', $sell->shipper));
                $shipper = implode(', ', $shipper_array);

                $export[$row_index][$column_b] = $shipper;
                $export[$row_index][$column_c] = $invoice_no;
                $export[$row_index][$column_d] = $sell->name;
                $export[$row_index][$column_f] = $sell->shipping_address;
                $export[$row_index][$column_l] = number_format(round_int($sell->final_total, env('DIGIT', 4)));
                $export[$row_index][$column_n] = !empty($paymentStatus['deposit']) && $paymentStatus['deposit'] != 'approved' ? number_format(round_int($sell->deposit, env('DIGIT', 4))) : '';
                $export[$row_index][$column_o] = !empty($paymentStatus['cod']) && $paymentStatus['cod'] != 'approved' ? number_format(round_int($sell->cod, env('DIGIT', 4))) : '';
                $export[$row_index][$column_p] = $remainMoney == 0 && !empty($paymentStatus['normal']) && $paymentStatus['normal'] != 'approved' ? number_format(round_int($sell->total_paid, env('DIGIT', 4))) : number_format(round_int($remainMoney, env('DIGIT', 4)));

                $sum_final_total += round_int($sell->final_total, env('DIGIT', 4));
                $sum_total_paid += round_int($sell->total_paid, env('DIGIT', 4));
                $sum_deposit += round_int($sell->deposit, env('DIGIT', 4));
                $sum_cod += round_int($sell->cod, env('DIGIT', 4));

                $row_index++;
            }

            //Hiển thị tổng cộng
            $file_name = 'danh-sach-don-hang';
            if(isset($start) && isset($end)){
                $file_name .= '_' . $start . '_' . date('H:i:s');
            }
            $file_name .= '.xlsx';

            $export_collect = collect($export);

            //Sheet style
            $header_style = (new StyleBuilder())
                ->setFontName('Arial')
                ->setFontSize(12)
//                ->setCellAlignment(CellAlignment::CENTER)
                ->setFontBold()
                ->setShouldWrapText()
                ->build();

            $rows_style = (new StyleBuilder())
                ->setFontName('Arial')
                ->setFontSize(12)
//                ->setCellAlignment(CellAlignment::LEFT)
                ->setShouldWrapText()
                ->build();

            return (new FastExcel($export_collect))
                ->headerStyle($header_style)
                ->rowsStyle($rows_style)
                ->download($file_name);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                'msg' => $e->getMessage()
            ];
            return redirect(url()->previous())->with('status', $output);
        }
    }

    public function exportExcelDeliver()
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('sell.create') && !auth()->user()->can('direct_sell.access') && !auth()->user()->can('view_own_sell_only')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            //Set maximum php execution time
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);

            $business_id = request()->session()->get('user.business_id');
            $with = [];
            $sells = $this->transactionUtil->getListSells($business_id);
            $sells = $sells->with('sell_lines.plate_lines.product.unit', 'sell_lines.plate_lines.product');

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

            if (!empty(request()->input('sell_list_filter_payment_status')) && request()->input('sell_list_filter_payment_status') != 'overdue') {
                $sells->where('transactions.payment_status', request()->input('sell_list_filter_payment_status'));
            } elseif (request()->input('sell_list_filter_payment_status') == 'overdue') {
                $sells->whereIn('transactions.payment_status', ['due', 'partial'])
                    ->whereNotNull('transactions.pay_term_number')
                    ->whereNotNull('transactions.pay_term_type')
                    ->whereRaw("IF(transactions.pay_term_type='days', DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number DAY) < CURDATE(), DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number MONTH) < CURDATE())");
            }

            //Add condition for location,used in sales representative expense report
            if (request()->has('sell_list_filter_location_id')) {
                $location_id = request()->get('sell_list_filter_location_id');
                if (!empty($location_id)) {
                    $sells->where('transactions.location_id', $location_id);
                }
            }

            if (!empty(request()->sell_list_filter_customer_id)) {
                $customer_id = request()->sell_list_filter_customer_id;
                $sells->where('contacts.id', $customer_id);
            }
            if (!empty(request()->sell_list_filter_start_date) && !empty(request()->sell_list_filter_end_date)) {
                $start = request()->sell_list_filter_start_date;
                $end = request()->sell_list_filter_end_date;
                $sells->whereDate('transactions.transaction_date', '>=', $start)
                    ->whereDate('transactions.transaction_date', '<=', $end);
            }

            if (!empty(request()->input('created_by'))) {
                $sells->where('transactions.created_by', request()->input('created_by'));
            }

            if (!empty(request()->input('service_staffs'))) {
                $sells->where('transactions.res_waiter_id', request()->input('service_staffs'));
            }
            $only_shipments = request()->only_shipments == 'true' ? true : false;
            if ($only_shipments && auth()->user()->can('shipping.update')) {
                $sells->whereNotNull('transactions.shipping_status');
            }

            if (!empty(request()->input('shipping_status'))) {
                $sells->where('transactions.shipping_status', request()->input('shipping_status'));
            }

            $sells->groupBy('transactions.id');

            $with[] = 'payment_lines';
            if (!empty($with)) {
                $sells->with($with);
            }

            $sells = $sells->orderByDesc('transactions.created_at')->get();
//            $merge = [];
            $sum_final_total = 0;
            $sum_total_paid = 0;
            $sum_deposit = 0;
            $sum_cod = 0;
            $export = [];

            $column_a = __('messages.date');
            $column_b = __('sale.shipper');
            $column_c = __('sale.invoice_no');
            $column_d = __('sale.customer_name');
            $column_e = __('lang_v1.contact_id');
            $column_f = __('sale.address');
            $column_g = __('sale.deliver_status');
            $column_h = __('lang_v1.shipping_status');
            $column_i = __('sale.product');
            $column_j = __('product.product_sku');
            $column_k = __('sale.deliver_quantity');
            $column_l = __('lang_v1.unit');

            //Gán các dòng dữ liệu
            $row_index = 0;
            foreach($sells as $sell){
                $transaction_date = Carbon::parse($sell->transaction_date)->format('d/m/Y H:i');
                $sell_orders = [];

                foreach($sell->sell_lines as $sell_line){
                    foreach ($sell_line->plate_lines as $plate_line){
                        $unit = $plate_line->product->unit;
                        $is_exist = false;

                        foreach ($sell_orders as $key => $sell_order){
                            if($sell_order['variation_id'] == $plate_line->variation_id && $sell_order['unit_type'] == $unit->type){
                                if($sell_order['unit_price'] == $plate_line->unit_price){
                                    $sell_orders[$key]['quantity'] += $plate_line->quantity * $plate_line->width * $plate_line->height;

                                    $is_exist = true;
                                }
                                break;
                            }
                        }

                        $area = $plate_line->width * $plate_line->height * $plate_line->quantity;
                        $unit_price_before_discount = $plate_line->unit_price_before_discount;
                        $unit_price_after_discount =  $unit_price_before_discount - $plate_line->line_discount_amount;

                        if(!$is_exist){
                            $quantity = $plate_line->quantity * $plate_line->width * $plate_line->height;
                            $unit_name = $unit->actual_name;

                            if ($unit->type == 'pcs' || ($unit->type == 'area' && $plate_line->width == $unit->width)) {
                                $quantity = $plate_line->quantity;
                            }

                            $sell_orders[] = [
                                'product_name' => $plate_line->product->name,
                                'sku' => $plate_line->product->sku,
                                'variation_id' => $plate_line->variation_id,
                                'quantity' => $quantity,
                                'unit_price' => $unit_price_after_discount,
                                'unit_type' => $unit->type,
                                'unit_name' => $unit_name,
                                'base_unit_id' => $unit->base_unit_id,
                            ];
                        }
                    }
                }

                if(count($sell_orders) > 1){
                    $sell_order_remaining = array_slice($sell_orders,1);
                }else{
                    $sell_order_remaining = [];
                }

                $invoice_no = $sell->invoice_no;
                if ($sell->status == 'cancel') {
                    $sell->final_total = 0;
                    $sell->total_paid = 0;
                    $invoice_no .= ' ('. __('sale.cancelled') .')';
                }

                $shipping_statuses = $this->transactionUtil->shipping_statuses();
                if(!isset($shipping_statuses[$sell->shipping_status])){
                    $shipping_status = '';
                }else{
                    $shipping_status = $shipping_statuses[$sell->shipping_status];
                }

                if(!empty($sell_orders)){
                    $quantity = in_array($unit->type, ['area', 'meter']) ? round($sell_orders[0]['quantity'], 3) : round($sell_orders[0]['quantity']);
                }else{
                    $quantity = null;
                }

                $shipper_array = array_unique(explode(',', $sell->shipper));
                $shipper = implode(', ', $shipper_array);

                $export[$row_index][$column_a] = $transaction_date;
                $export[$row_index][$column_b] = $shipper;
                $export[$row_index][$column_c] = $invoice_no;
                $export[$row_index][$column_d] = $sell->name;
                $export[$row_index][$column_e] = $sell->contact_id;
                $export[$row_index][$column_f] = $sell->shipping_address;
                $export[$row_index][$column_g] = $sell->is_deliver ? __('sale.delivered') : __('sale.not_delivery');
                $export[$row_index][$column_h] = $shipping_status;
                $export[$row_index][$column_i] = !empty($sell_orders) ? $sell_orders[0]['product_name'] : null;
                $export[$row_index][$column_j] = !empty($sell_orders) ? $sell_orders[0]['sku'] : null;
                $export[$row_index][$column_k] = $quantity;
                $export[$row_index][$column_l] = !empty($sell_orders) ? $sell_orders[0]['unit_name'] : '';

//                $first_sell_line_index = $row_index;
                foreach ($sell_order_remaining as $sell_order){
                    if (!empty($sell_order['base_unit_id'])) {
                        $unit_name_order = Unit::find($sell_order['base_unit_id'])->actual_name;
                    } else {
                        $unit_name_order = $sell_order['unit_name'];
                    }
                    $row_index++;
                    $export[$row_index][$column_a] = null;
                    $export[$row_index][$column_b] = null;
                    $export[$row_index][$column_c] = null;
                    $export[$row_index][$column_d] = null;
                    $export[$row_index][$column_e] = null;
                    $export[$row_index][$column_f] = null;
                    $export[$row_index][$column_g] = null;
                    $export[$row_index][$column_h] = null;
                    $export[$row_index][$column_i] = $sell_order['product_name'];
                    $export[$row_index][$column_j] = $sell_order['sku'];
                    $export[$row_index][$column_k] = round($sell_order['quantity'], 3);
                    $export[$row_index][$column_l] = $unit_name_order;
                }

//                $merge[] = 'A'.($first_sell_line_index).':A'.($row_index);
//                $merge[] = 'B'.($first_sell_line_index).':B'.($row_index);
//                $merge[] = 'C'.($first_sell_line_index).':C'.($row_index);
//                $merge[] = 'D'.($first_sell_line_index).':D'.($row_index);
//                $merge[] = 'E'.($first_sell_line_index).':E'.($row_index);
//                $merge[] = 'F'.($first_sell_line_index).':F'.($row_index);
//                $merge[] = 'L'.($first_sell_line_index).':L'.($row_index);
//                $merge[] = 'M'.($first_sell_line_index).':M'.($row_index);
//                $merge[] = 'N'.($first_sell_line_index).':N'.($row_index);
//                $merge[] = 'O'.($first_sell_line_index).':O'.($row_index);
//                $merge[] = 'P'.($first_sell_line_index).':P'.($row_index);

                $row_index++;
            }

            $file_name = 'danh-sach-don-xuat-kho';
            if(isset($start) && isset($end)){
                $file_name .= '_'.$start.'_'.$end;
            }
            $file_name .= '.xlsx';

            $export_collect = collect($export);

            //Sheet style
            $header_style = (new StyleBuilder())
                ->setFontName('Arial')
                ->setFontSize(12)
//                ->setCellAlignment(CellAlignment::CENTER)
                ->setFontBold()
                ->setShouldWrapText()
                ->build();

            $rows_style = (new StyleBuilder())
                ->setFontName('Arial')
                ->setFontSize(12)
//                ->setCellAlignment(CellAlignment::LEFT)
                ->setShouldWrapText()
                ->build();

            return (new FastExcel($export_collect))
                ->headerStyle($header_style)
                ->rowsStyle($rows_style)
                ->download($file_name);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                'msg' => $e->getMessage()
            ];
            return redirect(url()->previous())->with('status', $output);
        }
    }

    public function checkInvoiceUpdate(Request $request) {
        if ($request->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $id = $request->transaction_id;
            $location_id = $request->location_id;

            $transaction = Transaction::find($id);
            $sell_details = $this->transactionUtil->getQueryStockDeliver($id, $transaction, $location_id, $business_id);

            return response()->json([
                'success' => true,
                'data' => [
                    'sell_lines' => $sell_details,
                    'transaction' => $transaction,
                ]
            ]);
        }
    }

    public function checkInvoiceCancelled(Request $request) {
        if ($request->ajax()) {
            $id = $request->id;
            $transaction = Transaction::find($id);

            if($transaction && $transaction->is_deliver){
                return response()->json([
                    'success' => false,
                    'message' => __('messages.invoice_delivered'),
                ]);
            }

            $has_payment = TransactionPayment::where('transaction_id', $id)
                ->where('approval_status', 'approved')
                ->first();

            if($has_payment){
                $message = __('messages.confirm_cancel_sell_pos_with_payment');
            }else{
                $message = __('messages.invoice_delivered');
            }

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __('messages.invoice_delivered'),
        ]);
    }


    public function getTotalFilterByDay(Request $request) {
        if ($request->ajax()) {
            $start_date = $request->start_date;
            $end_date = $request->end_date;

            $transactions = TransactionPayment::join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
                ->whereDate('transaction_payments.paid_on', '>=', $start_date)
                ->whereDate('transaction_payments.paid_on', '<=', $end_date)
                ->whereIn('t.type', ['sell', 'receipt', 'expense'])
                ->where('transaction_payments.approval_status', 'approved')
                ->select(
                    't.type',
                    'amount',
                    'method',
                    'bank_account_number'
                )
                ->get()
                ->toArray();

            $total_receipt_bank = 0;
            $total_receipt_cash = 0;
            $total_expense_bank = 0;
            $total_expense_cash = 0;
            $total_money_payment_cash = 0;
            $total_money_payment_bank = 0;

            if (!empty($transactions)) {
                foreach ($transactions as $transaction) {
                    if ($transaction['type'] == 'sell') {
                        if ($transaction['method'] == 'bank_transfer') {
                            $total_money_payment_bank += $transaction['amount'];
                        } else {
                            $total_money_payment_cash += $transaction['amount'];
                        }
                    } elseif ($transaction['type'] == 'receipt') {
                        if ($transaction['method'] == 'bank_transfer') {
                            $total_receipt_bank += $transaction['amount'];
                        } else {
                            $total_receipt_cash += $transaction['amount'];
                        }
                    } elseif ($transaction['type'] == 'expense') {
                        if ($transaction['method'] == 'bank_transfer') {
                            $total_expense_bank += $transaction['amount'];
                        } else {
                            $total_expense_cash += $transaction['amount'];
                        }
                    }
                }
            }

            $total_cash = $total_money_payment_cash + $total_expense_cash + $total_receipt_cash;
            $total_bank = $total_money_payment_bank + $total_expense_bank + $total_receipt_bank;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_money_payment_bank' => $total_bank,
                    'total_money_payment_cash' => $total_cash
                ]
            ]);
        }
    }

    public function updateCodeBySeller(Request $request) {
        try {
            $transaction_id = $request->transaction_id;
            $id = $request->payment_id;
            $note = $request->cod_note;
            $cod_amount = $this->transactionUtil->num_uf($request->cod_amount);
            if ($id) {
                TransactionPayment::find($id)->update([
                    'note' => $note,
                    'amount' => $cod_amount
                ]);
            } else {
                $payments[] = [
                    'type' => 'cod',
                    'amount' => $cod_amount,
                    'note' => $note
                ];

                if(!empty($payments)){
                    $this->transactionUtil->createOrUpdatePaymentLines($transaction_id, $payments);
                }
            }

            $output = ['success' => true,
                'msg' => __("lang_v1.update_cod_success")
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    public function storeDebitPaper(Request $request, $id) {
        try {
            $business_id = request()->session()->get('user.business_id');
            $transaction = Transaction::find($id);
            Media::uploadMedia($business_id, $transaction, $request, 'documents');

            $output = ['success' => true,
                'msg' => __("lang_v1.store_debit_paper_success")
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    public function printShippingInvoice(Request $request, $transaction_id) {
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

                $receipt = $this->shippingReceiptContent($business_id, $transaction->location_id, $transaction_id, $printer_type, $is_package_slip, false);

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

    public function printShippingInvoiceWithoutHeader(Request $request, $transaction_id) {
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

                $receipt = $this->shippingReceiptContent($business_id, $transaction->location_id, $transaction_id, $printer_type, $is_package_slip, false, true);

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

    public function getReverseSize($plate_stock_id)
    {
        if (!auth()->user()->can('sale.reverse_size')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $plate_stock = PlateStock::find($plate_stock_id);

            $view = view('stock_deliver.partials.reverse_size_modal')
                ->with(compact('plate_stock'))->render();

            $output = [
                'view' => $view
            ];

            return json_encode($output);
        }
    }

    public function postReverseSize($plate_stock_id, Request $request)
    {
        if (!auth()->user()->can('sale.reverse_size')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $reverse_quantity = $this->transactionUtil->num_uf($request->input('reverse_quantity'));

            DB::beginTransaction();
            $old_plate_stock = PlateStock::find($plate_stock_id);
            $old_plate_stock->qty_available -= $reverse_quantity;
            $old_plate_stock->save();

            $new_plate_stock = PlateStock::where('location_id', $old_plate_stock->location_id)
                ->where('variation_id', $old_plate_stock->variation_id)
                ->whereRaw("CAST(plate_stocks.width AS DECIMAL(10,3)) = ".$old_plate_stock->height)
                ->whereRaw("CAST(plate_stocks.height AS DECIMAL(10,3)) = ".$old_plate_stock->width)
                ->where('warehouse_id', $old_plate_stock->warehouse_id)
                ->where('is_origin', $old_plate_stock->is_origin)
                ->first();

            if($new_plate_stock){
                $new_plate_stock->qty_available += $reverse_quantity;
                $new_plate_stock->save();
            }else{
                $new_plate_stock = PlateStock::create([
                    'location_id' => $old_plate_stock->location_id,
                    'product_id' => $old_plate_stock->product_id,
                    'variation_id' => $old_plate_stock->variation_id,
                    'width' => $old_plate_stock->height,
                    'height' => $old_plate_stock->width,
                    'warehouse_id' => $old_plate_stock->warehouse_id,
                    'qty_available' => $reverse_quantity,
                    'is_origin' => $old_plate_stock->is_origin,
                ]);
            }

            DB::commit();

//            $data = [
//                'new_plate_stock_id' => $new_plate_stock->id,
//            ];

            $output = ['success' => true,
//                'data' => $data,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' =>__("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    public function getAccounts(Request $request)
    {
        if (!empty($request->input('method'))) {
            $account_type = $request->input('method') == 'cash' ? 'cash' : 'bank';
            $business_id = request()->session()->get('user.business_id');

            $accounts = Account::forDropdown($business_id, true, false, true, $account_type);
            $html = '';
            if (!empty($accounts)) {
                foreach ($accounts as $key => $account) {
                    $html .= '<option value="' . $key .'">' .$account . '</option>';
                }
            }
            echo $html;
            exit;
        }
    }

    public function confirmBulkDebitPaper(Request $request) {
        $data = $request->input('selected_rows');
        if (!empty($data)) {
            foreach ($data as $datum) {
                $transaction = Transaction::find($datum);
                if ($transaction->is_deliver) {
                    $transaction->update([
                        'is_confirm_debit_paper' => 1
                    ]);
                } else {
                    return response()->json([
                        'success' => 0,
                        'message' => __('lang_v1.order_is_not_deliver')
                    ]);
                }
            }

            return response()->json([
                'success' => 1,
                'message' => __('lang_v1.accepted_success')
            ]);
        }
    }

    public function confirmBulkRemaining(Request $request) {
        $data = $request->input('selected_rows');
        $method = $request->input('method');
        $bank_account = $request->input('bank_account');

        if (!empty($data)) {
            $business_id = $request->session()->get('user.business_id');
            $locked = Transaction::whereIn('id', $data)
                ->where('locked_for_pay_remaining_amount', 1)
                ->first();

            if($locked){
                return response()->json([
                    'success' => 0,
                    'message' => __('lang_v1.locked_for_pay_remaining_amount')
                ]);
            }else{
                Transaction::whereIn('id', $data)
                    ->update(['locked_for_pay_remaining_amount' => 1]);
            }

            $total_paid = TransactionPayment::whereIn('transaction_id', $data)
                ->where('approval_status', 'approved')
                ->select('transaction_id', DB::raw('SUM(IF( type <> "expense", amount, amount*-1))as total_paid'))
                ->groupBy('transaction_id')
                ->pluck('total_paid', 'transaction_id')
                ->toArray();

            DB::beginTransaction();

            $commit = true;
            $due = $paid = $partial = [];

            foreach ($data as $datum) {
                $inputs = [];
                $transaction_id = $datum;
                $transaction = Transaction::where('business_id', $business_id)->findOrFail($transaction_id);
                $canNotUpdate = $this->transactionUtil->canNotUpdate($transaction);
                $can_not_create_payment = $canNotUpdate['can_not_create_payment'];

                if ($transaction->payment_status != 'paid' && !$can_not_create_payment) {
                    $amount = $transaction->final_total - ($total_paid[$datum] ?? 0);
                    if ($amount < 0) {
                        $amount = 0;
                    }

                    $prefix_type = 'purchase_payment';
                    if (in_array($transaction->type, ['sell', 'sell_return'])) {
                        $prefix_type = 'sell_payment';
                    } elseif ($transaction->type == 'expense') {
                        $prefix_type = 'expense_payment';
                    }

                    //Set paid on
                    if ($this->moduleUtil->isClosedEndOfDay()) {
                        $paid_on = date('Y-m-d', strtotime('now +1 days'));
                        $paid_on .= ' 00:00:00';
                    }else{
                        $paid_on = date('Y-m-d H:i:s');
                    }
                    $inputs['paid_on'] = $paid_on;
                    $inputs['transaction_id'] = $transaction_id;
                    $inputs['amount'] = $amount;
                    $inputs['method'] = $method;
                    $inputs['created_by'] = auth()->user()->id;
                    $inputs['type'] = 'normal';
                    $inputs['account_id'] = $bank_account;
                    $inputs['payment_for'] = $transaction->contact_id;
                    $inputs['business_id'] = $request->session()->get('business.id');
                    $inputs['document'] = null;
                    $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);
                    $inputs['payment_ref_no'] = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);
                    $inputs['cashier_confirmed_id'] = auth()->user()->id;

                    if(!auth()->user()->can('sell.confirm_bank_transfer_method') && $method == 'bank_transfer'){
                        $inputs['approval_status'] = 'pending';
                    }else{
                        $inputs['approval_status'] = 'approved';
                    }

                    $tp = TransactionPayment::create($inputs);
                    if (!$tp) {
                        $commit = false;
                        break;
                    }

                    //update payment status
                    $status = $this->transactionUtil->calculatePaymentStatus($transaction_id, $transaction->final_total);
                    if ($status == 'due') {
                        $due[] = $transaction_id;
                    } elseif ($status == 'paid') {
                        $paid[] = $transaction_id;
                    } elseif ($status = 'partial') {
                        $partial[] = $transaction_id;
                    }

                    $notificationUtil = new NotificationUtil();
                    $notificationUtil->transactionPaymentNotification($tp);

                    $inputs['transaction_type'] = $transaction->type;
                    event(new TransactionPaymentAdded($tp, $inputs));
                } else {
                    $commit = false;
                    break;
                }
            }

            if ($commit) {
                if (!empty($due)) {
                    Transaction::whereIn('id', $due)->update(['payment_status' => 'due']);
                } elseif (!empty($paid)) {
                    Transaction::whereIn('id', $paid)->update(['payment_status' => 'paid']);
                } elseif (!empty($partial)) {
                    Transaction::whereIn('id', $partial)->update(['payment_status' => 'partial']);
                }

                DB::commit();

                $transaction_ids_json = json_encode($data);
                Log::info("Confirm multi remaining payment: {$transaction_ids_json}");

                Transaction::whereIn('id', $data)
                    ->update(['locked_for_pay_remaining_amount' => 0]);

                return response()->json([
                    'success' => 1,
                    'message' => __('lang_v1.accepted_success')
                ]);
            }

            DB::rollBack();

            Transaction::whereIn('id', $data)
                ->update(['locked_for_pay_remaining_amount' => 0]);

            return response()->json([
                'success' => 0,
                'message' => __('lang_v1.exist_order_has_been_paid')
            ]);
        }
    }

    public function cancelRemaining(Request $request) {
        if (request()->ajax()) {
            if (!auth()->user()->can('sell.confirm_bank_transfer_method')) {
                $output = [
                    'success' => false,
                    'msg' => __("sale.not_allow_reject_payment")
                ];
                return $output;
            }

            try {
                $id = $request->input('id');
                $transaction = Transaction::find($id);

                //Check if closed end of day
                $current_date = date('Y-m-d');
                $transaction_date = date('Y-m-d', strtotime($transaction->transaction_date));
                if ($this->moduleUtil->isClosedEndOfDay() && $transaction_date <= $current_date) {
                    $output = ['success' => false,
                        'msg' => __("sale.can_not_cancel_remaining_payment")
                    ];
                    return $output;
                }

                //Get paid on
                if ($this->moduleUtil->isClosedEndOfDay()) {
                    $paid_on = date('Y-m-d', strtotime('now +1 days'));
                }else{
                    $paid_on = date('Y-m-d');
                }

                $payment_ids = TransactionPayment::where('type', 'normal')
                    ->where('transaction_id', $id)
                    ->whereDate('paid_on', $paid_on)
                    ->pluck('id')
                    ->toArray();

                if(!empty($payment_ids)){
                    DB::beginTransaction();
                    foreach ($payment_ids as $payment_id){
                        $payment = TransactionPayment::find($payment_id);
                        if($payment){
                            $payment->delete();
                        }

                        $account_transaction = AccountTransaction::where('transaction_payment_id', $payment_id)->first();
                        if($account_transaction){
                            $account_transaction->delete();
                        }
                    }

                    //update payment status
                    $this->transactionUtil->updatePaymentStatus($transaction->id);
                    DB::commit();

                    $output = ['success' => true,
                        'msg' => __('lang_v1.cancel_remaining_success')
                    ];
                }

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output = ['success' => false,
                    'msg' => __("messages.something_went_wrong")
                ];
            }

            return response()->json($output);
        }

        return [
            'code' => 403,
            'message' => 'Unauthorized action.'
        ];
    }

    public function getReverseQuantity(Request $request)
    {
        if (!auth()->user()->can('sale.reverse_size')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $reverse_plate_stock_id = $request->input('reverse_plate_stock_id');

            $plate_stock = PlateStock::find($reverse_plate_stock_id);
            if($plate_stock){
                $quantity_available = $plate_stock->qty_available;
            }else{
                $quantity_available = 0;
            }

            $output = [
                'success' => true,
                'data' => $quantity_available,
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' =>__("messages.something_went_wrong")
            ];
        }

        return $output;
    }
}
