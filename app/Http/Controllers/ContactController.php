<?php

namespace App\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\CustomerGroup;
use App\Exports\ContactExport;
use App\Exports\SellExport;
use App\Notifications\CustomerNotification;
use App\PurchaseLine;
use App\Transaction;
use App\TransactionPlateLinesReturn;
use App\Unit;
use App\User;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use App\Variation;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use App\SellingPriceGroup;

class ContactController extends Controller
{
    protected $commonUtil;
    protected $contactUtil;
    protected $transactionUtil;
    protected $moduleUtil;
    protected $notificationUtil;
    protected $productUtil;

    /**
     * Constructor
     *
     * @param Util $commonUtil
     * @return void
     */
    public function __construct(
        Util $commonUtil,
        ModuleUtil $moduleUtil,
        TransactionUtil $transactionUtil,
        NotificationUtil $notificationUtil,
        ContactUtil $contactUtil,
        ProductUtil $productUtil
    ) {
        $this->commonUtil = $commonUtil;
        $this->contactUtil = $contactUtil;
        $this->moduleUtil = $moduleUtil;
        $this->transactionUtil = $transactionUtil;
        $this->notificationUtil = $notificationUtil;
        $this->productUtil = $productUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $type = request()->get('type');

        $types = ['supplier', 'customer'];

        if (empty($type) || !in_array($type, $types)) {
            return redirect()->back();
        }

        if (request()->ajax()) {
            if ($type == 'supplier') {
                return $this->indexSupplier();
            } elseif ($type == 'customer') {
                return $this->indexCustomer();
            } else {
                die("Not Found");
            }
        }

        $reward_enabled = (request()->session()->get('business.enable_rp') == 1 && in_array($type, ['customer'])) ? true : false;

        return view('contact.index')
            ->with(compact('type', 'reward_enabled'));
    }

    /**
     * Returns the database object for supplier
     *
     * @return \Illuminate\Http\Response
     */
    private function indexSupplier()
    {
        if (!auth()->user()->can('supplier.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $contact = Contact::leftjoin('transactions AS t', 'contacts.id', '=', 't.contact_id')
                    ->where('contacts.business_id', $business_id)
                    ->onlySuppliers()
                    ->select(['contacts.contact_id', 'supplier_business_name', 'name', 'contacts.created_at', 'mobile',
                        'contacts.type', 'contacts.id',
                        DB::raw("SUM(IF(t.type = 'purchase', final_total, 0)) as total_purchase"),
                        DB::raw("SUM(IF(t.type = 'purchase', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as purchase_paid"),
                        DB::raw("SUM(IF(t.type = 'purchase_return', final_total, 0)) as total_purchase_return"),
                        DB::raw("SUM(IF(t.type = 'purchase_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as purchase_return_paid"),
                        DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance"),
                        DB::raw("SUM(IF(t.type = 'opening_balance', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as opening_balance_paid"),
                        'email', 'tax_number', 'contacts.pay_term_number', 'contacts.pay_term_type', 'contacts.custom_field1', 'contacts.custom_field2', 'contacts.custom_field3', 'contacts.custom_field4',
                        'contacts.contact_status'
                        ])
                    ->groupBy('contacts.id');

        return Datatables::of($contact)
            ->addColumn(
                'due',
                '<span class="display_currency contact_due" data-orig-value="{{$total_purchase - $purchase_paid}}" data-currency_symbol=true data-highlight=false>{{$total_purchase - $purchase_paid }}</span>'
            )
            ->addColumn(
                'return_due',
                '<span class="display_currency return_due" data-orig-value="{{$total_purchase_return - $purchase_return_paid}}" data-currency_symbol=true data-highlight=false>{{$total_purchase_return - $purchase_return_paid }}</span>'
            )
            ->addColumn(
                'action',
                function ($row) {
                    $html = '<div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                        data-toggle="dropdown" aria-expanded="false">' .
                        __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    $due_amount = $row->total_purchase + $row->opening_balance - $row->purchase_paid - $row->opening_balance_paid;

                    /*if ($due_amount > 0) {
                        $html .= '<li><a href="' . action('TransactionPaymentController@getPayContactDue', [$row->id]) . '?type=purchase" class="pay_purchase_due"><i class="fas fa-money-bill-alt" aria-hidden="true"></i>' . __("contact.pay_due_amount") . '</a></li>';
                    }*/

                    $return_due = $row->total_purchase_return - $row->purchase_return_paid;
                    /*if ($return_due > 0) {
                        $html .= '<li><a href="' . action('TransactionPaymentController@getPayContactDue', [$row->id]) . '?type=purchase_return" class="pay_purchase_due"><i class="fas fa-money-bill-alt" aria-hidden="true"></i>' . __("lang_v1.receive_purchase_return_due") . '</a></li>';
                    }*/
                    if (auth()->user()->can('supplier.view')) {
                        $html .= '<li><a href="' . action('ContactController@show', [$row->id]) . '"><i class="fas fa-eye" aria-hidden="true"></i>' . __("messages.view") . '</a></li>';
                    }
                    if (auth()->user()->can('supplier.update')) {
                        $html .= '<li><a href="' . action('ContactController@edit', [$row->id]) . '" class="edit_contact_button"><i class="glyphicon glyphicon-edit"></i>' .  __("messages.edit") . '</a></li>';
                    }
                    if (auth()->user()->can('supplier.delete')) {
                        $html .= '<li><a href="' . action('ContactController@destroy', [$row->id]) . '" class="delete_contact_button"><i class="glyphicon glyphicon-trash"></i>' . __("messages.delete") . '</a></li>';
                    }

                    if (auth()->user()->can('customer.update')) {
                        $html .= '<li><a href="' . action('ContactController@updateStatus', [$row->id]) . '"class="update_contact_status"><i class="fas fa-power-off fa-fw"></i>';

                        if ($row->contact_status == "active") {
                            $html .= __("messages.deactivate");
                        } else {
                            $html .= __("messages.activate");
                        }

                        $html .= "</a></li>";
                    }

                    /*$html .= '<li class="divider"></li>';
                    if (auth()->user()->can('supplier.view')) {
                        $html .= '
                                <li>
                                    <a href="' . action('ContactController@show', [$row->id]). '?view=ledger">
                                        <i class="fas fa-scroll" aria-hidden="true"></i>
                                        ' . __("lang_v1.ledger") . '
                                    </a>
                                </li>';

                        if (in_array($row->type, ["both", "supplier"])) {
                            $html .= '<li>
                                <a href="' . action('ContactController@show', [$row->id]) . '?view=purchase">
                                    <i class="fas fa-arrow-circle-down" aria-hidden="true"></i>
                                    ' . __("purchase.purchases") . '
                                </a>
                            </li>
                            <li>
                                <a href="' . action('ContactController@show', [$row->id]) . '?view=stock_report">
                                    <i class="fas fa-hourglass-half" aria-hidden="true"></i>
                                    ' . __("report.stock_report") . '
                                </a>
                            </li>';
                        }

                        if (in_array($row->type, ["both", "customer"])) {
                            $html .=  '<li>
                                <a href="' . action('ContactController@show', [$row->id]). '?view=sales">
                                    <i class="fas fa-arrow-circle-up" aria-hidden="true"></i>
                                    ' . __("sale.sells") . '
                                </a>
                            </li>';
                        }

                        $html .= '<li>
                                <a href="' . action('ContactController@show', [$row->id]) . '?view=documents_and_notes">
                                    <i class="fas fa-paperclip" aria-hidden="true"></i>
                                     ' . __("lang_v1.documents_and_notes") . '
                                </a>
                            </li>';
                    }*/
                    $html .= '</ul></div>';

                    return $html;
                }
            )
            ->editColumn('opening_balance', function ($row) {
                $html = '<span class="display_currency" data-currency_symbol="true" data-orig-value="' . $row->opening_balance . '">' . $row->opening_balance . '</span>';

                return $html;
            })
            ->editColumn('pay_term', '
                @if(!empty($pay_term_type) && !empty($pay_term_number))
                    {{$pay_term_number}}
                    @lang("lang_v1.".$pay_term_type)
                @endif
            ')
            ->editColumn('name', function ($row) {
                $html = '<a href="' . action('ContactController@show', [$row->id]) . '">';

                if ($row->contact_status == 'inactive') {
                    $html .= $row->name . ' <small class="label pull-right bg-red no-print">' . __("lang_v1.inactive") . '</small>';
                } else {
                    $html .= $row->name;
                }

                $html .= '</a>';
                return $html;
            })
            ->editColumn('created_at', '{{@format_date($created_at)}}')
            ->removeColumn('opening_balance_paid')
            ->removeColumn('type')
            ->removeColumn('id')
            ->removeColumn('total_purchase')
            ->removeColumn('purchase_paid')
            ->removeColumn('total_purchase_return')
            ->removeColumn('purchase_return_paid')
            ->rawColumns(['action', 'opening_balance', 'pay_term', 'due', 'return_due', 'name'])
            ->make(true);
    }

    /**
     * Returns the database object for customer
     *
     * @return \Illuminate\Http\Response
     */
    private function indexCustomer()
    {
        if (!auth()->user()->can('customer.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $query = Contact::leftjoin('transactions AS t', 'contacts.id', '=', 't.contact_id')
                    ->leftjoin('customer_groups AS cg', 'contacts.customer_group_id', '=', 'cg.id')
                    ->where('contacts.business_id', $business_id)
                    ->onlyCustomers()
            ->select(['contacts.contact_id', 'contacts.name', 'contacts.created_at', 'total_rp', 'cg.name as customer_group', 'city', 'state', 'country', 'landmark', 'mobile', 'contacts.id', 'is_default',
                DB::raw("IF(contacts.selling_price_group_id = -1, '". __('lang_v1.none') ."', IF(contacts.selling_price_group_id = 0, '". __('lang_v1.default_selling_price') ."', (SELECT name FROM selling_price_groups WHERE id = contacts.selling_price_group_id))) as price_group"),
                DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', final_total, 0)) as total_invoice"),
                DB::raw("SUM(IF((t.type = 'sell' OR t.type = 'receipt' OR t.type = 'expense') AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id AND approval_status='approved'), 0)) as invoice_received"),
                DB::raw("SUM(IF(t.type = 'sell_return' AND t.status = 'final', final_total, 0)) as total_sell_return"),
                DB::raw("SUM(IF(t.type = 'sell_return' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id AND approval_status='approved'), 0)) as sell_return_paid"),
                DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance"),
                DB::raw("SUM(IF(t.type = 'opening_balance', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as opening_balance_paid"),
                DB::raw("SUM(IF(t.type = 'purchase', final_total, 0)) as total_purchase"),
                DB::raw("SUM(IF(t.type = 'reduce_debt' AND t.status = 'final', final_total, 0)) as reduce_debt"),
                'email', 'tax_number', 'contacts.pay_term_number', 'contacts.pay_term_type', 'contacts.credit_limit', 'contacts.custom_field1', 'contacts.custom_field2', 'contacts.custom_field3', 'contacts.custom_field4', 'contacts.type',
                'contacts.contact_status'
            ])
            ->groupBy('contacts.id');

        $contacts = Datatables::of($query)
            ->addColumn('address', '{{implode(", ", array_filter([$landmark, $city, $state, $country]))}}')
            ->addColumn('due', function ($row) {
                $due = $row->total_invoice - $row->invoice_received + $row->sell_return_paid - $row->total_sell_return + $row->opening_balance - $row->total_purchase - $row->reduce_debt;
                $html = '<span class="display_currency contact_due" data-currency_symbol="true" data-orig-value="' . $due . '" data-highlight=true>' . $due . '</span>';
                return $html;
            })
            ->addColumn(
                'return_due',
                '<span class="display_currency return_due" data-orig-value="{{$total_sell_return - $sell_return_paid}}" data-currency_symbol=true data-highlight=false>{{$total_sell_return - $sell_return_paid }}</span>'
            )
            ->addColumn(
                'action',
                function ($row) {
                    $html = '<div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                        data-toggle="dropdown" aria-expanded="false">' .
                        __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    /*$return_due = $row->total_sell_return - $row->sell_return_paid;
                    if ($return_due > 0) {
                        $html .= '<li><a href="' . action('TransactionPaymentController@getPayContactDue', [$row->id]) . '?type=sell_return" class="pay_purchase_due"><i class="fas fa-money-bill-alt" aria-hidden="true"></i>' . __("lang_v1.pay_sell_return_due") . '</a></li>';
                    }*/
                    if (auth()->user()->can('customer.view')) {
                        $html .= '<li><a href="' . action('ContactController@show', [$row->id]) . '"><i class="fas fa-eye" aria-hidden="true"></i>' . __("messages.view") . '</a></li>';
                    }
                    if (auth()->user()->can('customer.update')) {
                        $html .= '<li><a href="' . action('ContactController@edit', [$row->id]) . '" class="edit_contact_button"><i class="glyphicon glyphicon-edit"></i>' .  __("messages.edit") . '</a></li>';
                    }
                    if (!$row->is_default && auth()->user()->can('customer.delete')) {
                        $html .= '<li><a href="' . action('ContactController@destroy', [$row->id]) . '" class="delete_contact_button"><i class="glyphicon glyphicon-trash"></i>' . __("messages.delete") . '</a></li>';
                    }

                    if (auth()->user()->can('customer.update')) {
                        $html .= '<li><a href="' . action('ContactController@updateStatus', [$row->id]) . '"class="update_contact_status"><i class="fas fa-power-off"></i>';

                        if ($row->contact_status == "active") {
                            $html .= __("messages.deactivate");
                        } else {
                            $html .= __("messages.activate");
                        }

                        $html .= "</a></li>";
                    }

                    if (auth()->user()->can('sell.add_reduce_debt')) {
                        $html .= '<li><a href="#" class="btn-modal" data-href="' . action('ReduceDebtController@add', [$row->id]) . '" data-container=".reduce_debt_modal">
                            <i class="fas fa-arrow-down" aria-hidden="true"></i>' . __("contact.reduce_debt") . '</a>
                        </li>';
                    }

                    /*$html .= '<li class="divider"></li>';
                    if (auth()->user()->can('customer.view')) {
                        $html .= '
                                <li>
                                    <a href="' . action('ContactController@show', [$row->id]). '?view=ledger">
                                        <i class="fas fa-scroll" aria-hidden="true"></i>
                                        ' . __("lang_v1.ledger") . '
                                    </a>
                                </li>';

                        if (in_array($row->type, ["both", "supplier"])) {
                            $html .= '<li>
                                <a href="' . action('ContactController@show', [$row->id]) . '?view=purchase">
                                    <i class="fas fa-arrow-circle-down" aria-hidden="true"></i>
                                    ' . __("purchase.purchases") . '
                                </a>
                            </li>
                            <li>
                                <a href="' . action('ContactController@show', [$row->id]) . '?view=stock_report">
                                    <i class="fas fa-hourglass-half" aria-hidden="true"></i>
                                    ' . __("report.stock_report") . '
                                </a>
                            </li>';
                        }

                        if (in_array($row->type, ["both", "customer"])) {
                            $html .=  '<li>
                                <a href="' . action('ContactController@show', [$row->id]). '?view=sales">
                                    <i class="fas fa-arrow-circle-up" aria-hidden="true"></i>
                                    ' . __("sale.sells") . '
                                </a>
                            </li>';
                        }

                        $html .= '<li>
                                <a href="' . action('ContactController@show', [$row->id]) . '?view=documents_and_notes">
                                    <i class="fas fa-paperclip" aria-hidden="true"></i>
                                     ' . __("lang_v1.documents_and_notes") . '
                                </a>
                            </li>';
                    }*/
                    $html .= '</ul></div>';

                    return $html;
                }
            )
            ->editColumn('opening_balance', function ($row) {
                $html = '<span class="display_currency" data-currency_symbol="true" data-orig-value="' . $row->opening_balance . '">' . $row->opening_balance . '</span>';

                return $html;
            })
            ->editColumn('credit_limit', function ($row) {
                $html = __('lang_v1.no_limit');
                if (!is_null($row->credit_limit)) {
                    $html = '<span class="display_currency" data-currency_symbol="true" data-orig-value="' . $row->credit_limit . '">' . $row->credit_limit . '</span>';
                }

                return $html;
            })
            ->editColumn('pay_term', '
                @if(!empty($pay_term_type) && !empty($pay_term_number))
                    {{$pay_term_number}}
                    @lang("lang_v1.".$pay_term_type)
                @endif
            ')
            ->editColumn('name', function ($row) {
                $html = '<a href="' . action('ContactController@show', [$row->id]) . '">';

                if ($row->contact_status == 'inactive') {
                    $html .= $row->name . ' <small class="label pull-right bg-red no-print">' . __("lang_v1.inactive") . '</small>';
                } else {
                    $html .= $row->name;
                }

                $html .= '</a>';
                return $html;
            })
            ->editColumn('total_rp', '{{$total_rp ?? 0}}')
            ->editColumn('created_at', '{{@format_date($created_at)}}')
            ->removeColumn('total_invoice')
            ->removeColumn('opening_balance_paid')
            ->removeColumn('invoice_received')
            ->removeColumn('state')
            ->removeColumn('country')
            ->removeColumn('city')
            ->removeColumn('type')
            ->removeColumn('id')
            ->removeColumn('is_default')
            ->removeColumn('total_sell_return')
            ->removeColumn('sell_return_paid')
            ->filterColumn('address', function ($query, $keyword) {
                $query->whereRaw("CONCAT(COALESCE(landmark, ''), ', ', COALESCE(city, ''), ', ', COALESCE(state, ''), ', ', COALESCE(country, '') ) like ?", ["%{$keyword}%"]);
            });
        $reward_enabled = (request()->session()->get('business.enable_rp') == 1) ? true : false;
        if (!$reward_enabled) {
            $contacts->removeColumn('total_rp');
        }
        return $contacts->rawColumns(['action', 'opening_balance', 'credit_limit', 'pay_term', 'due', 'return_due', 'name'])
                        ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('supplier.create') && !auth()->user()->can('customer.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
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
        $selling_price_groups = SellingPriceGroup::forDropdown($business_id, true);

        $type_validate = 'create';

        return view('contact.create')
            ->with(compact('types', 'customer_groups', 'type_validate', 'selling_price_groups'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('supplier.create') && !auth()->user()->can('customer.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            if (!$this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse();
            }

            $input = $request->only(['type', 'supplier_business_name',
                'name', 'tax_number', 'pay_term_number', 'pay_term_type', 'mobile', 'landline', 'alternate_number', 'city', 'state', 'country', 'landmark', 'customer_group_id', 'contact_id', 'custom_field1', 'custom_field2', 'custom_field3', 'custom_field4', 'email', 'shipping_address', 'position', 'selling_price_group_id']);
            $input['business_id'] = $business_id;
            $input['created_by'] = $request->session()->get('user.id');

            $input['credit_limit'] = $request->input('credit_limit') != '' ? $this->commonUtil->num_uf($request->input('credit_limit')) : null;

            if ($input['type'] != 'supplier' && !empty($input['selling_price_group_id']) && $input['selling_price_group_id'] == -1) {
                return [
                    'success' => false,
                    'msg' => __("messages.selling_price_group_is_null")
                ];
            }

            if (empty($input['contact_id'])) {
                return [
                    'success' => false,
                    'msg' => __("messages.contact_id_is_null")
                ];
            }

            //Check Contact id
            $count = 0;
            if (!empty($input['contact_id'])) {
                $count = Contact::where('business_id', $input['business_id'])
                                ->where('contact_id', $input['contact_id'])
                                ->count();
            }

            if ($count == 0) {
                //Update reference count
                $ref_count = $this->commonUtil->setAndGetReferenceCount('contacts');

                if (empty($input['contact_id'])) {
                    //Generate reference number
                    $input['contact_id'] = $this->commonUtil->generateReferenceNumber('contacts', $ref_count);
                }

                $contact = Contact::create($input);

                //Add opening balance
                if (!empty($request->input('opening_balance'))) {
                    $this->transactionUtil->createOpeningBalanceTransaction($business_id, $contact->id, $request->input('opening_balance'));
                }

                $selling_price_groups = SellingPriceGroup::forDropdown($business_id, true);
                $contact->price_group_name = $selling_price_groups[$contact->selling_price_group_id];

                $output = ['success' => true,
                            'data' => $contact,
                            'msg' => __("contact.added_success")
                        ];
            } else {
                throw new \Exception("Error Processing Request", 1);
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => false,
                            'msg' =>__("messages.something_went_wrong")
                        ];
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
        if (!auth()->user()->can('supplier.view') && !auth()->user()->can('customer.view') && !auth()->user()->can('contacts_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $contact = $this->contactUtil->getContactInfo($business_id, $id);

        $reward_enabled = (request()->session()->get('business.enable_rp') == 1 && in_array($contact->type, ['customer', 'both'])) ? true : false;

        $contact_dropdown = Contact::contactDropdown($business_id, false, false);

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        //get contact view type : ledger, notes etc.
        $view_type = request()->get('view');
        if (is_null($view_type)) {
            $view_type = 'ledger';
        }

        $contact_view_tabs = $this->moduleUtil->getModuleData('get_contact_view_tabs');

        $opening_balance = Transaction::where('contact_id', $id)
            ->where('type', 'opening_balance')
            ->orderBy('id', 'DESC')
            ->first();

        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d');

        if (!empty(request()->start_date) && !empty(request()->end_date)) {
            $start_date = request()->start_date;
            $end_date =  request()->end_date;
        }

        $sellCustomerData = $this->getSellCustomer($id, $start_date, $end_date);
        $sell_customer = $sellCustomerData['sell_customer'];
        $all_final_total = $sellCustomerData['all_final_total'];
        $all_total_sell_paid = $sellCustomerData['all_total_sell_paid'];
        $total_ending_balance_debt = $sellCustomerData['total_ending_balance_debt'];
        $total_ending_balance_positive = $sellCustomerData['total_ending_balance_positive'];
        $total_debt =  $sellCustomerData['total_debt'];

        return view('contact.show')
             ->with(compact(
                 'start_date',
                 'end_date',
                 'contact',
                 'reward_enabled',
                 'contact_dropdown',
                 'business_locations',
                 'view_type',
                 'contact_view_tabs',
                 'total_debt',
                 'opening_balance',
                 'sell_customer',
                 'all_final_total',
                 'all_total_sell_paid',
                 'total_ending_balance_debt',
                 'total_ending_balance_positive'
             ));
    }

    private function getSellCustomer($contact_id, $start_date, $end_date){
        $sell_customer = Transaction::where('contact_id', $contact_id)
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
            ->whereDate('transaction_date', '>=', $start_date)
            ->whereDate('transaction_date', '<=', $end_date)
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

        $sell_customer = $sell_customer->orderBy('transaction_date', 'asc')->get();

        $all_final_total = 0;
        $all_total_sell_paid = 0;
        $total_debt = $this->calculateDebtCustomer($contact_id, $start_date);

        if ($total_debt >= 0){
            $previous_total_due_customer = $total_debt;
            $previous_total_due_business = 0;
        }else{
            $previous_total_due_customer = 0;
            $previous_total_due_business = abs($total_debt);
        }

        foreach ($sell_customer as $sell){
            //TODO: Get invoice_no
            if(in_array($sell->type, ['sell', 'sell_return'])){
                $invoice_no = '<span>'. $sell->invoice_no .'</span>';
                if ($sell->type == 'sell_return') {
                    $sell_return = Transaction::where('id', $sell->id)->first();
                    $invoice_return = Transaction::where('id', $sell_return->return_parent_id)->first();
                    $invoice_no .= '&nbsp;&nbsp;<small class="label bg-red label-round no-print"><i class="fas fa-undo"></i></small>&nbsp;<small style="color: rgba(245,54,92,0)">' . $invoice_return->invoice_no .'</small>';
                }
            }else{
                $invoice_no = $sell->ref_no;
            }

            $sell->invoice_no_html = $invoice_no;

            //TODO: Get products of sell
            $html = '';
            if ($sell->type == 'expense') {
                $html = $sell->expense_note;
            } elseif ($sell->type == 'receipt') {
                $html = $sell->receipt_note;
            } elseif ($sell->type == 'purchase') {
                foreach ($sell->purchase_lines as $purchase_line) {
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
                foreach ($sell->sell_lines as $sell_line) {
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

                if ($sell->type == 'sell_return') {
                    $sell_return = Transaction::where('id', $sell->id)->first();
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

            $sell->product_sku_html = $html;

            //TODO: Get note
            $html = '';
            if($sell->type == 'reduce_debt'){
                $html = __('contact.reduce_debt');
            }else{
                if($sell->shipping_charges > 0){
                    $html .= __('sale.shipping_charges').': '. number_format(round_int($sell->shipping_charges, env('DIGIT', 4))) .'đ';
                }
                if($sell->tax_amount > 0){
                    if(!empty($html)){
                        $html .= '<br>';
                    }
                    $html .= __('purchase.purchase_tax').': '. number_format(round_int($sell->tax_amount, env('DIGIT', 4))) .'đ';
                }
            }

            $sell->note = $html;

            //TODO: Get final_total
            $final_total = 0;
            if ($sell->type == 'receipt' || $sell->type == 'purchase') {
                $html = '--';
            }elseif ($sell->type == 'reduce_debt'){
                if ($sell->final_total < 0){
                    $final_total = abs($sell->final_total);
                    $html = '<span class="display_currency final_total_footer" data-currency_symbol="true" data-orig-value="'. $final_total .'">'. $final_total .'</span>';
                }else{
                    $html = '--';
                }
            }elseif($sell->type == 'sell_return'){
                $final_total = 0;

                foreach ($sell->payment_lines as $payment) {
                    $final_total += $payment->amount;
                }

                if ($final_total == 0) {
                    $html = '--';
                }else{
                    $html = '<span class="display_currency final_total_footer" data-currency_symbol="true" data-orig-value="'. $final_total .'">'. $final_total .'</span>';
                }
            }else{
                $final_total = $sell->final_total;
                $html = '<span class="display_currency final_total_footer" data-currency_symbol="true" data-orig-value="'. $final_total .'">'. $final_total .'</span>';
            }

            $final_total = $final_total > 0 ? $final_total : 0;
            $all_final_total += $final_total;
            $sell->final_total_html = $html;
            $sell->final_total_number = $final_total;

            //TODO: Get total_sell_paid
            if ($sell->type == 'receipt' || $sell->type == 'purchase' || ($sell->type == 'sell_return' && $sell->status != 'cancel')) {
                $total_sell_paid = $sell->final_total;
            }elseif ($sell->type == 'reduce_debt'){
                $total_sell_paid = ($sell->final_total > 0) ? $sell->final_total : 0;
            }else{
                $payments = $sell->payment_lines;
                $total_sell_paid = 0;

                foreach ($payments as $payment) {
                    if ($payment->approval_status == 'approved') {
                        if (($sell->type == 'sell' && $sell->status == 'final') || ($sell->type == 'sell_return' && $sell->status != 'cancel')) {
                            $total_sell_paid += $payment->amount;
                        }
                    }
                }
            }

            if ($total_sell_paid > 0) {
                $html = '<span class="display_currency total_sell_paid_footer" data-currency_symbol="true" data-orig-value="'. $total_sell_paid .'">'. $total_sell_paid .'</span>';
            } else {
                $html = '--';
            }

            $total_sell_paid = $total_sell_paid > 0 ? $total_sell_paid : 0;
            $all_total_sell_paid += $total_sell_paid;
            $sell->total_sell_paid_html = $html;
            $sell->total_sell_paid = $total_sell_paid;

            //TODO: Get total_due_customer
            $total_due_customer = $previous_total_due_customer + $final_total - $previous_total_due_business - $total_sell_paid;

            if ($total_due_customer > 0) {
                $sell->total_due_customer_html = '<span class="display_currency total_due_customer" data-currency_symbol="true" data-orig-value="'. $total_due_customer .'">'. $total_due_customer .'</span>';;
            } else {
                $total_due_customer = 0;
                $sell->total_due_customer_html = '--';
            }

            $sell->total_due_customer = $total_due_customer;

            //TODO: Get total_due_business
            $total_due_business = $previous_total_due_business + $total_sell_paid - $previous_total_due_customer - $final_total;
            $sell->total_due_business_html = '';

            if ($total_due_business > 0) {
                $sell->total_due_business_html = '<span class="display_currency total_due_business" data-currency_symbol="true" data-orig-value="'. $total_due_business .'">'. $total_due_business .'</span>';;
            } else {
                $total_due_business = 0;
                $sell->total_due_business_html = '--';
            }

            $sell->total_due_business = $total_due_business;

            //Set previous value
            $previous_total_due_customer = $total_due_customer;
            $previous_total_due_business = $total_due_business;
        }

        $total_ending_balance_debt = $previous_total_due_customer >= 0 ? $previous_total_due_customer : 0;
        $total_ending_balance_positive = $previous_total_due_business >= 0 ? $previous_total_due_business : 0;

        return [
            'sell_customer' => $sell_customer,
            'all_final_total' => $all_final_total,
            'all_total_sell_paid' => $all_total_sell_paid,
            'total_ending_balance_debt' => $total_ending_balance_debt,
            'total_ending_balance_positive' => $total_ending_balance_positive,
            'total_debt' => $total_debt,
        ];
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('supplier.update') && !auth()->user()->can('customer.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $contact = Contact::where('business_id', $business_id)->find($id);

            if (!$this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse();
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

            $ob_transaction =  Transaction::where('contact_id', $id)
                                            ->where('type', 'opening_balance')
                                            ->first();
            $opening_balance = !empty($ob_transaction->final_total) ? $ob_transaction->final_total : 0;

            //Deduct paid amount from opening balance.
            if (!empty($opening_balance)) {
                $opening_balance_paid = $this->transactionUtil->getTotalAmountPaid($ob_transaction->id);
                if (!empty($opening_balance_paid)) {
                    $opening_balance = $opening_balance - $opening_balance_paid;
                }

                $opening_balance = round($opening_balance, 0);
            }
            $type_validate = 'edit';
            $selling_price_groups = SellingPriceGroup::forDropdown($business_id, true);

            return view('contact.edit')
                ->with(compact('contact', 'types', 'customer_groups', 'opening_balance', 'type_validate', 'selling_price_groups'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('supplier.update') && !auth()->user()->can('customer.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $input = $request->only(['type', 'supplier_business_name', 'name', 'tax_number', 'pay_term_number', 'pay_term_type', 'mobile', 'landline', 'alternate_number', 'city', 'state', 'country', 'landmark', 'customer_group_id', 'contact_id', 'custom_field1', 'custom_field2', 'custom_field3', 'custom_field4', 'email', 'shipping_address', 'position', 'selling_price_group_id']);

                $input['credit_limit'] = $request->input('credit_limit') != '' ? $this->commonUtil->num_uf($request->input('credit_limit')) : null;

                $business_id = $request->session()->get('user.business_id');

                if (!$this->moduleUtil->isSubscribed($business_id)) {
                    return $this->moduleUtil->expiredResponse();
                }

                $count = 0;

                if ($input['type'] != 'supplier' && !empty($input['selling_price_group_id']) && $input['selling_price_group_id'] == -1) {
                    return [
                        'success' => false,
                        'msg' => __("messages.selling_price_group_is_null")
                    ];
                }

                if (empty($input['contact_id'])) {
                    return [
                        'success' => false,
                        'msg' => __("messages.contact_id_is_null")
                    ];
                }

                //Check Contact id
                if (!empty($input['contact_id'])) {
                    $count = Contact::where('business_id', $business_id)
                            ->where('contact_id', $input['contact_id'])
                            ->where('id', '!=', $id)
                            ->count();
                }

                if ($count == 0) {
                    $contact = Contact::where('business_id', $business_id)->findOrFail($id);
                    foreach ($input as $key => $value) {
                        $contact->$key = $value;
                    }
                    $contact->save();

//                    //Get opening balance if exists
//                    $ob_transaction =  Transaction::where('contact_id', $id)
//                                            ->where('type', 'opening_balance')
//                                            ->first();
//
//                    if (!empty($ob_transaction)) {
//                        $amount = $this->commonUtil->num_uf($request->input('opening_balance'));
//                        $opening_balance_paid = $this->transactionUtil->getTotalAmountPaid($ob_transaction->id);
//                        if (!empty($opening_balance_paid)) {
//                            $amount += $opening_balance_paid;
//                        }
//
//                        $ob_transaction->final_total = $amount;
//                        $ob_transaction->save();
//                        //Update opening balance payment status
//                        $this->transactionUtil->updatePaymentStatus($ob_transaction->id, $ob_transaction->final_total);
//                    } else {
//                        //Add opening balance
//                        if (!empty($request->input('opening_balance'))) {
//                            $this->transactionUtil->createOpeningBalanceTransaction($business_id, $contact->id, $request->input('opening_balance'));
//                        }
//                    }

                    $output = ['success' => true,
                                'msg' => __("contact.updated_success")
                                ];
                } else {
                    throw new \Exception("Error Processing Request", 1);
                }
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output = ['success' => false,
                            'msg' => __("messages.something_went_wrong")
                        ];
            }

            return $output;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('supplier.delete') && !auth()->user()->can('customer.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->user()->business_id;

                //Check if any transaction related to this contact exists
                $count = Transaction::where('business_id', $business_id)
                                    ->where('contact_id', $id)
                                    ->count();
                if ($count == 0) {
                    $contact = Contact::where('business_id', $business_id)->findOrFail($id);
                    if (!$contact->is_default) {
                        $contact->delete();
                    }
                    $output = ['success' => true,
                                'msg' => __("contact.deleted_success")
                                ];
                } else {
                    $output = ['success' => false,
                                'msg' => __("lang_v1.you_cannot_delete_this_contact")
                                ];
                }
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output = ['success' => false,
                            'msg' => __("messages.something_went_wrong")
                        ];
            }

            return $output;
        }
    }

    /**
     * Retrieves list of customers, if filter is passed then filter it accordingly.
     *
     * @param  string  $q
     * @return JSON
     */
    public function getCustomers()
    {
        if (request()->ajax()) {
            $term = request()->input('q', '');
            $is_edit = request()->input('is_edit', 'false');

            $business_id = request()->session()->get('user.business_id');
            $user_id = request()->session()->get('user.id');

            $contacts = Contact::where('business_id', $business_id)
                ->active()
                ->onlyCustomers();

            $selected_contacts = User::isSelectedContacts($user_id);
            if ($selected_contacts) {
                $contacts->join('user_contact_access AS uca', 'contacts.id', 'uca.contact_id')
                ->where('uca.user_id', $user_id);
            }

            if (!empty($term)) {
                $contacts->where(function ($query) use ($term) {
                    $query->where('name', 'like', '%' . $term .'%')
                            ->orWhere('supplier_business_name', 'like', '%' . $term .'%')
                            ->orWhere('mobile', 'like', '%' . $term .'%')
                            ->orWhere('contacts.contact_id', 'like', '%' . $term .'%');
                });
            }

            $contacts->select(
                'contacts.id',
                DB::raw("IF(contacts.contact_id IS NULL OR contacts.contact_id='', name, CONCAT(name, ' (', contacts.contact_id, ')')) AS text"),
                'contacts.name',
                'shipping_address',
                'mobile',
                'landmark',
                'city',
                'state',
                'pay_term_number',
                'pay_term_type',
                'selling_price_group_id'
            );

            if ($is_edit == 'true'){
                $price_group = request()->input('price_group', 0);
                $contacts->addSelect(DB::raw('IF(selling_price_group_id = '. $price_group .' OR selling_price_group_id = -1, false, true) AS disabled'));
            }

            if (request()->session()->get('business.enable_rp') == 1) {
                $contacts->addSelect('total_rp');
            }
            $contacts = $contacts->get();
            return json_encode($contacts);
        }
    }

    /*public function getCustomers()
    {
        if (request()->ajax()) {
            $term = request()->input('q', '');
            $is_edit = request()->input('is_edit', 'false');

            $business_id = request()->session()->get('user.business_id');
            $user_id = request()->session()->get('user.id');

            $contacts = Contact::where('contacts.business_id', $business_id)
                ->leftJoin('transactions AS t', 'contacts.id', '=', 't.contact_id')
                ->active()
                ->onlyCustomers();

            $selected_contacts = User::isSelectedContacts($user_id);
            if ($selected_contacts) {
                $contacts->join('user_contact_access AS uca', 'contacts.id', 'uca.contact_id')
                    ->where('uca.user_id', $user_id);
            }

            if (!empty($term)) {
                $contacts->where(function ($query) use ($term) {
                    $query->where('contacts.name', 'like', '%' . $term .'%')
                        ->orWhere('contacts.supplier_business_name', 'like', '%' . $term .'%')
                        ->orWhere('contacts.mobile', 'like', '%' . $term .'%')
                        ->orWhere('contacts.contact_id', 'like', '%' . $term .'%');
                });
            }

            $total_debt_query = "FORMAT(SUM(IF(t.type = 'sell' AND t.status = 'final', final_total, 0))
                - SUM(IF((t.type = 'sell' OR t.type = 'receipt' OR t.type = 'expense') AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id AND approval_status='approved'), 0))
                + SUM(IF(t.type = 'sell_return' AND t.status <> 'cancel', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id AND approval_status='approved'), 0))
                - SUM(IF(t.type = 'sell_return' AND t.status = 'final', final_total, 0))
                + SUM(IF(t.type = 'opening_balance', final_total, 0))
                - SUM(IF(t.type = 'purchase', final_total, 0))
                - SUM(IF(t.type = 'reduce_debt' AND t.status = 'final', final_total, 0))
            , 0)";

            $contacts->select(
                'contacts.id',
                DB::raw("IF(contacts.contact_id IS NULL OR contacts.contact_id='', name, CONCAT(name, ' (', contacts.contact_id, ')', ' - Nợ ', $total_debt_query, ' đ')) AS text"),
                'contacts.name',
                'contacts.shipping_address',
                'contacts.mobile',
                'contacts.landmark',
                'contacts.city',
                'contacts.state',
                'contacts.pay_term_number',
                'contacts.pay_term_type',
                'contacts.selling_price_group_id'
            );

            if ($is_edit == 'true'){
                $price_group = request()->input('price_group', 0);
                $contacts->addSelect(DB::raw('IF(contacts.selling_price_group_id = '. $price_group .' OR contacts.selling_price_group_id = -1, false, true) AS disabled'));
            }

            if (request()->session()->get('business.enable_rp') == 1) {
                $contacts->addSelect('contacts.total_rp');
            }
            $contacts = $contacts->get();
            return json_encode($contacts);
        }
    }*/

    /**
     * Checks if the given contact id already exist for the current business.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function checkContactId(Request $request)
    {
        $contact_id = $request->input('contact_id');

        $valid = 'true';
        if (!empty($contact_id)) {
            $business_id = $request->session()->get('user.business_id');
            $hidden_id = $request->input('hidden_id');

            $query = Contact::where('business_id', $business_id)
                            ->where('contact_id', $contact_id);
            if (!empty($hidden_id)) {
                $query->where('id', '!=', $hidden_id);
            }
            $count = $query->count();
            if ($count > 0) {
                $valid = 'false';
            }
        }
        echo $valid;
        exit;
    }

    /**
     * Checks if price group is null.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function checkSellPriceGroup(Request $request)
    {
        $priceGroup = $request->input('selling_price_group_id');
        $type = $request->input('type');

        $valid = 'true';
        if ($type != 'supplier' && !empty($priceGroup) && $priceGroup == -1) {
            $valid = 'false';
        }
        echo $valid;
        exit;
    }

    public function checkMobile(Request $request) {
        $mobile = $request->input('mobile');
        $type_validate = $request->input('type_validate');
        $type = $request->input('type');
        $id = $request->input('id');
        $valid = 'true';

        if (!empty($mobile)) {
            $business_id = $request->session()->get('user.business_id');
            $query = Contact::where('business_id', $business_id)
                ->where('mobile', $mobile);
            if ($type == 'customer') {
                $query = $query->where('type', 'customer');
            } elseif ($type == 'supplier') {
                $query = $query->where('type', 'supplier');
            }

            if ($type_validate == 'create') {
                $query = $query->count();
            } else {
                $query = $query->where('id', '<>', $id);
            }
            $query = $query->count();
            if ($query > 0) {
                $valid = 'false';
            }
        }

        echo $valid;
        exit;
    }

    public function checkTaxNumber(Request $request) {
        $tax_number = $request->input('tax_number');
        $type_validate = $request->input('type_validate');
        $id = $request->input('id');
        $valid = 'true';

        if (!empty($tax_number)) {
            $business_id = $request->session()->get('user.business_id');
            $query = Contact::where('business_id', $business_id)
                ->where('tax_number', $tax_number);

            if ($type_validate == 'create') {
                $query = $query->count();
            } else {
                $query = $query->where('id', '<>', $id)->count();
            }
            if ($query > 0) {
                $valid = 'false';
            }
        }

        echo $valid;
        exit;
    }

    /**
     * Shows import option for contacts
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function getImportContacts()
    {
        if (!auth()->user()->can('supplier.create') && !auth()->user()->can('customer.create')) {
            abort(403, 'Unauthorized action.');
        }

        $zip_loaded = extension_loaded('zip') ? true : false;

        //Check if zip extension it loaded or not.
        if ($zip_loaded === false) {
            $output = ['success' => 0,
                            'msg' => 'Please install/enable PHP Zip archive for import'
                        ];

            return view('contact.import')
                ->with('notification', $output);
        } else {
            return view('contact.import');
        }
    }

    /**
     * Imports contacts
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function postImportContacts(Request $request)
    {
        if (!auth()->user()->can('supplier.create') && !auth()->user()->can('customer.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $notAllowed = $this->commonUtil->notAllowedInDemo();
            if (!empty($notAllowed)) {
                return $notAllowed;
            }

            //Set maximum php execution time
            ini_set('max_execution_time', 0);

            if ($request->hasFile('contacts_csv')) {
                $file = $request->file('contacts_csv');
                $parsed_array = Excel::toArray([], $file);
                //Remove header row
                $imported_data = array_splice($parsed_array[0], 1);

                $business_id = $request->session()->get('user.business_id');
                $user_id = $request->session()->get('user.id');

                $formated_data = [];

                $is_valid = true;
                $error_msg = '';

                DB::beginTransaction();
                foreach ($imported_data as $key => $value) {
                    //Check if 17 no. of columns exists
                    if (count($value) != 19) {
                        $is_valid =  false;
                        $error_msg = "Số lượng cột không khớp";
                        break;
                    }

                    $row_no = $key + 1;
                    $contact_array = [];

                    //Check contact type
                    $contact_type = '';
                    $contact_types = [
                        1 => 'customer',
                        2 => 'supplier',
                        3 => 'both'
                    ];
                    if (!empty($value[0])) {
                        $contact_type = strtolower(trim($value[0]));
                        if (in_array($contact_type, [1, 2, 3])) {
                            $contact_array['type'] = $contact_types[$contact_type];
                        } else {
                            $is_valid =  false;
                                $error_msg = "Loại lên hệ không hợp lệ ở dòng số $row_no";
                            break;
                        }
                    } else {
                        $is_valid =  false;
                        $error_msg = "Loại lên hệ không được trống ở dòng số $row_no";
                        break;
                    }

                    //Check contact name
                    if (!empty($value[1])) {
                        $contact_array['name'] = $value[1];
                    } else {
                        $is_valid =  false;
                        $error_msg = "Tên lên hệ không được trống ở dòng số $row_no";
                        break;
                    }

                    if (in_array($contact_type, [2, 3])) {
                        //Check business name
                        $contact_array['supplier_business_name'] = empty(trim($value[2])) ? '' : trim($value[2]);
//                        if (!empty(trim($value[2]))) {
//                            $contact_array['supplier_business_name'] = $value[2];
//                        } else {
//                            $is_valid =  false;
//                            $error_msg = "Tên doanh nghiệp không được trống ở dòng số $row_no";
//                            break;
//                        }
                    }

                    //Check contact ID
                    if (!empty(trim($value[3]))) {
                        $count = Contact::where('business_id', $business_id)
                                    ->where('contact_id', $value[3])
                                    ->count();

                        if ($count == 0) {
                            $contact_array['contact_id'] = $value[3];
                        } else {
                            $is_valid =  false;
                            $error_msg = "ID contact đã tồn tại ở dòng $row_no";
                            break;
                        }
                    }

                    //Tax number
                    if (!empty(trim($value[4]))) {
                        $contact_array['tax_number'] = $value[4];
                    }

                    //Check opening balance
                    if (!empty(trim($value[5]))) {
                        if (is_numeric($value[5])) {
                            $contact_array['opening_balance'] = trim($value[5]);
                        } else {
                            $is_valid =  false;
                            $error_msg = "Số dư đầu kỳ phải là dạng số ở dòng $row_no";
                            break;
                        }
                    }

                    //Check pay term
                    if (!empty(trim($value[6]))) {
                        if (is_numeric(trim($value[6]))) {
                            $contact_array['pay_term_number'] = trim($value[6]);
                        } else {
                            $is_valid =  false;
                            $error_msg = "Thời hạn thanh toán phải là số ở dòng $row_no";
                            break;
                        }
                    }

                    //Check pay period
                    $pay_term_type = strtolower(trim($value[7]));
                    if (!empty($pay_term_type)) {
                        if (in_array($pay_term_type, ['days', 'months'])) {
                            $contact_array['pay_term_type'] = $pay_term_type;
                        } else {
                            $is_valid = false;
                            $error_msg = "Loại thanh toán phải là days (ngày) và months (tháng) ở dòng số $row_no";
                            break;
                        }
                    }

                    if (empty(trim($value[6])) ^ empty($pay_term_type)) {
                        $is_valid = false;
                        $error_msg = "Chưa điền đủ  loại thanh toán và thời hạn thanh toán ở dòng số $row_no";
                        break;
                    }

                    //Check credit limit
                    if (trim($value[8]) != '' && in_array($contact_type, ['customer', 'both'])) {
                        $contact_array['credit_limit'] = trim($value[8]);
                    }

                    //Check email
                    if (!empty(trim($value[9]))) {
                        if (filter_var(trim($value[9]), FILTER_VALIDATE_EMAIL)) {
                            $contact_array['email'] = $value[9];
                        } else {
                            $is_valid =  false;
                            $error_msg = "Email không hợp lệ ở dòng $row_no";
                            break;
                        }
                    }

                    //Mobile number
                    if (!empty(trim($value[10]))) {
                        if (is_numeric(trim($value[10]))) {
                            $contact_array['mobile'] = $value[10];
                        } else {
                            $is_valid =  false;
                            $error_msg = "Số điện thoại phải là dạng số ở dòng $row_no";
                            break;
                        }
                    } /*else {
                        $is_valid =  false;
                        $error_msg = "Số điện thoại không được trống ở dòng $row_no";
                        break;
                    }*/

                    //Alt contact number
                    $contact_array['alternate_number'] = !empty(trim($value[11])) && is_numeric(trim($value[11])) ? '' : trim($value[11]);

                    //Landline
                    $contact_array['landline'] = !empty(trim($value[12])) && is_numeric(trim($value[12])) ? '' : trim($value[12]);

                    //Country
                    $contact_array['country'] = $value[13];

                    //State
                    $contact_array['state'] = $value[14];

                    //City
                    $contact_array['city'] = $value[15];

                    //Landmark
                    $contact_array['landmark'] = $value[16];

                    //Selling price group
                    $price_group_name = trim($value[17]);
                    if (!empty($price_group_name)) {
                        if($price_group_name == 'Giá bán mặc định'){
                            $contact_array['selling_price_group_id'] = 0;
                        }else{
                            $price_group = SellingPriceGroup::where('name', $price_group_name)->first();
                            if($price_group){
                                $contact_array['selling_price_group_id'] = $price_group->id;
                            }else{
                                $is_valid =  false;
                                $error_msg = "Nhóm giá bán không tồn tại ở dòng $row_no";
                                break;
                            }
                        }
                    }else{
                        $contact_array['selling_price_group_id'] = -1;
                    }

                    //Customer group
                    $customerGroupName = trim($value[18]);

                    if (!empty($customerGroupName)) {
                        $customerGroup = CustomerGroup::query()->where('name', $customerGroupName)->first();
                        if($customerGroup){
                            $contact_array['customer_group_id'] = $customerGroup->id;
                        }
                    }else{
                        $contact_array['customer_group_id'] = null;
                    }

                    $formated_data[] = $contact_array;
                }

                if (!$is_valid) {
                    throw new \Exception($error_msg);
                }

                if (!empty($formated_data)) {
                    foreach ($formated_data as $contact_data) {
                        $ref_count = $this->transactionUtil->setAndGetReferenceCount('contacts');
                        //Set contact id if empty
                        if (empty($contact_data['contact_id'])) {
                            $contact_data['contact_id'] = $this->commonUtil->generateReferenceNumber('contacts', $ref_count);
                        }

                        $opening_balance = 0;
                        if (isset($contact_data['opening_balance'])) {
                            $opening_balance = $contact_data['opening_balance'];
                            unset($contact_data['opening_balance']);
                        }

                        $contact_data['business_id'] = $business_id;
                        $contact_data['created_by'] = $user_id;

                        $contact = Contact::create($contact_data);

                        if (!empty($opening_balance)) {
                            $this->transactionUtil->createOpeningBalanceTransaction($business_id, $contact->id, $opening_balance);
                        }
                    }
                }

                $output = ['success' => 1,
                            'msg' => __('product.file_imported_successfully')
                        ];

                DB::commit();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                            'msg' => $e->getMessage()
                        ];
            return redirect()->route('contacts.import')->with('notification', $output);
        }

        return redirect()->action('ContactController@index', ['type' => 'customer'])->with('status', $output);
    }

    /**
     * Shows ledger for contacts
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function getLedger()
    {
        if (!auth()->user()->can('supplier.view') && !auth()->user()->can('customer.view') && !auth()->user()->can('contacts_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $contact_id = request()->input('contact_id');

        $start_date = request()->start_date;
        $end_date =  request()->end_date;

        $contact = Contact::find($contact_id);

        $ledger_details = $this->transactionUtil->getLedgerDetails($contact_id, $start_date, $end_date);

        if (request()->input('action') == 'pdf') {
            $for_pdf = true;
            $html = view('contact.ledger')
             ->with(compact('ledger_details', 'contact', 'for_pdf'))->render();
            $mpdf = $this->getMpdf();
            $mpdf->WriteHTML($html);
            $mpdf->Output();
        }

        return view('contact.ledger')
             ->with(compact('ledger_details', 'contact'));
    }

    public function postCustomersApi(Request $request)
    {
        try {
            $api_token = $request->header('API-TOKEN');

            $api_settings = $this->moduleUtil->getApiSettings($api_token);

            $business = Business::find($api_settings->business_id);

            $data = $request->only(['name', 'email']);

            $customer = Contact::where('business_id', $api_settings->business_id)
                                ->where('email', $data['email'])
                                ->whereIn('type', ['customer', 'both'])
                                ->first();

            if (empty($customer)) {
                $data['type'] = 'customer';
                $data['business_id'] = $api_settings->business_id;
                $data['created_by'] = $business->owner_id;
                $data['mobile'] = 0;

                $ref_count = $this->commonUtil->setAndGetReferenceCount('contacts', $business->id);

                $data['contact_id'] = $this->commonUtil->generateReferenceNumber('contacts', $ref_count, $business->id);

                $customer = Contact::create($data);
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            return $this->respondWentWrong($e);
        }

        return $this->respond($customer);
    }

    /**
     * Function to send ledger notification
     *
     */
    public function sendLedger(Request $request)
    {
        $notAllowed = $this->notificationUtil->notAllowedInDemo();
        if (!empty($notAllowed)) {
            return $notAllowed;
        }

        try {
            $data = $request->only(['to_email', 'subject', 'email_body', 'cc', 'bcc']);
            $emails_array = array_map('trim', explode(',', $data['to_email']));

            $contact_id = $request->input('contact_id');
            $business_id = request()->session()->get('business.id');

            $start_date = request()->input('start_date');
            $end_date =  request()->input('end_date');

            $contact = Contact::find($contact_id);

            $ledger_details = $this->transactionUtil->getLedgerDetails($contact_id, $start_date, $end_date);

            $orig_data = [
                'email_body' => $data['email_body'],
                'subject' => $data['subject']
            ];

            $tag_replaced_data = $this->notificationUtil->replaceTags($business_id, $orig_data, null, $contact);
            $data['email_body'] = $tag_replaced_data['email_body'];
            $data['subject'] = $tag_replaced_data['subject'];

            //replace balance_due
            $data['email_body'] = str_replace('{balance_due}', $this->notificationUtil->num_f($ledger_details['balance_due']), $data['email_body']);

            $data['email_settings'] = request()->session()->get('business.email_settings');


            $for_pdf = true;
            $html = view('contact.ledger')
             ->with(compact('ledger_details', 'contact', 'for_pdf'))->render();
            $mpdf = $this->getMpdf();
            $mpdf->WriteHTML($html);

            $file = config('constants.mpdf_temp_path') . '/' . time() . '_ledger.pdf';
            $mpdf->Output($file, 'F');

            $data['attachment'] =  $file;
            $data['attachment_name'] =  'ledger.pdf';
            \Notification::route('mail', $emails_array)
                    ->notify(new CustomerNotification($data));

            if (file_exists($file)) {
                unlink($file);
            }

            $output = ['success' => 1, 'msg' => __('lang_v1.notification_sent_successfully')];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                            'msg' => "File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage()
                        ];
        }

        return $output;
    }

    /**
     * Function to get product stock details for a supplier
     *
     */
    public function getSupplierStockReport($supplier_id)
    {
        $pl_query_string = $this->commonUtil->get_pl_quantity_sum_string();
        $query = PurchaseLine::join('transactions as t', 't.id', '=', 'purchase_lines.transaction_id')
                        ->join('products as p', 'p.id', '=', 'purchase_lines.product_id')
                        ->join('variations as v', 'v.id', '=', 'purchase_lines.variation_id')
                        ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                        ->join('units as u', 'p.unit_id', '=', 'u.id')
                        ->where('t.type', 'purchase')
                        ->where('t.contact_id', $supplier_id)
                        ->select(
                            'p.name as product_name',
                            'v.name as variation_name',
                            'pv.name as product_variation_name',
                            'p.type as product_type',
                            'u.short_name as product_unit',
                            'v.sub_sku',
                            DB::raw('SUM(quantity) as purchase_quantity'),
                            DB::raw('SUM(quantity_returned) as total_quantity_returned'),
                            DB::raw('SUM(quantity_sold) as total_quantity_sold'),
                            DB::raw("SUM( COALESCE(quantity - ($pl_query_string), 0) * purchase_price_inc_tax) as stock_price"),
                            DB::raw("SUM( COALESCE(quantity - ($pl_query_string), 0)) as current_stock")
                        )->groupBy('purchase_lines.variation_id');

        if (!empty(request()->location_id)) {
            $query->where('t.location_id', request()->location_id);
        }

        $product_stocks =  Datatables::of($query)
                            ->editColumn('product_name', function ($row) {
                                $name = $row->product_name;
                                if ($row->product_type == 'variable') {
                                    $name .= ' - ' . $row->product_variation_name . '-' . $row->variation_name;
                                }
                                return $name . ' (' . $row->sub_sku . ')';
                            })
                            ->editColumn('purchase_quantity', function ($row) {
                                $purchase_quantity = 0;
                                if ($row->purchase_quantity) {
                                    $purchase_quantity =  (float)$row->purchase_quantity;
                                }

                                return '<span data-is_quantity="true" class="display_currency" data-currency_symbol=false  data-orig-value="' . $purchase_quantity . '" data-unit="' . $row->product_unit . '" >' . $purchase_quantity . '</span> ' . $row->product_unit;
                            })
                            ->editColumn('total_quantity_sold', function ($row) {
                                $total_quantity_sold = 0;
                                if ($row->total_quantity_sold) {
                                    $total_quantity_sold =  (float)$row->total_quantity_sold;
                                }

                                return '<span data-is_quantity="true" class="display_currency" data-currency_symbol=false  data-orig-value="' . $total_quantity_sold . '" data-unit="' . $row->product_unit . '" >' . $total_quantity_sold . '</span> ' . $row->product_unit;
                            })
                            ->editColumn('stock_price', function ($row) {
                                $stock_price = 0;
                                if ($row->stock_price) {
                                    $stock_price =  (float)$row->stock_price;
                                }

                                return '<span class="display_currency" data-currency_symbol=true >' . $stock_price . '</span> ';
                            })
                            ->editColumn('current_stock', function ($row) {
                                $current_stock = 0;
                                if ($row->current_stock) {
                                    $current_stock =  (float)$row->current_stock;
                                }

                                return '<span data-is_quantity="true" class="display_currency" data-currency_symbol=false  data-orig-value="' . $current_stock . '" data-unit="' . $row->product_unit . '" >' . $current_stock . '</span> ' . $row->product_unit;
                            });

        return $product_stocks->rawColumns(['current_stock', 'stock_price', 'total_quantity_sold', 'purchase_quantity'])->make(true);
    }

    public function updateStatus($id)
    {
        if (!auth()->user()->can('supplier.update') && !auth()->user()->can('customer.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $contact = Contact::where('business_id', $business_id)->find($id);
            $contact->contact_status = $contact->contact_status == 'active' ? 'inactive' : 'active';
            $contact->save();

            $output = ['success' => true,
                                'msg' => __("contact.updated_success")
                                ];
            return $output;
        }
    }

    /**
     * Display contact locations on map
     *
     */
    public function contactMap()
    {
        if (!auth()->user()->can('supplier.view') && !auth()->user()->can('customer.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $contacts = Contact::where('business_id', $business_id)
                        ->active()
                        ->whereNotNull('position')
                        ->get();

        return view('contact.contact_map')
             ->with(compact('contacts'));
    }

    public function getPurchaseSupplier($contact_id) {
        if (!auth()->user()->can('purchase.view') && !auth()->user()->can('view_own_purchase')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            $purchases = $this->transactionUtil->getListPurchases($business_id);
            $purchases->where('transactions.contact_id', $contact_id);

            if (!empty(request()->status)) {
                $purchases->where('transactions.status', request()->status);
            }

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $purchases->whereDate('transactions.transaction_date', '>=', $start)
                    ->whereDate('transactions.transaction_date', '<=', $end);
            }

            return Datatables::of($purchases)
                ->removeColumn('id')
                ->editColumn('ref_no', function ($row) {
                    return !empty($row->return_exists) ? $row->ref_no . ' <small class="label bg-red label-round no-print" title="' . __('lang_v1.some_qty_returned') .'"><i class="fas fa-undo"></i></small>' : $row->ref_no;
                })
                ->editColumn(
                    'final_total',
                    '<span class="display_currency final_total" data-currency_symbol="true" data-orig-value="{{$final_total}}">{{$final_total}}</span>'
                )
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn(
                    'status',
                    '<a href="#" @if(auth()->user()->can("purchase.update") || auth()->user()->can("purchase.update_status")) class="update_status no-print" data-purchase_id="{{$id}}" data-status="{{$status}}" @endif><span class="label @transaction_status($status) status-label" data-status-name="{{__(\'lang_v1.\' . $status)}}" data-orig-value="{{$status}}">{{__(\'lang_v1.\' . $status)}}
                        </span></a>'
                )
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("purchase.view")) {
                            return  action('PurchaseController@show', [$row->id]) ;
                        } else {
                            return '';
                        }
                    }])
                ->rawColumns(['final_total', 'status', 'ref_no'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $orderStatuses = $this->productUtil->orderStatuses();

        return view('contacts.show')
            ->with(compact('business_locations', 'orderStatuses'));
    }

    public function exportExcel($contact_id, Request $request) {
        try {
            $business_id = request()->session()->get('user.business_id');
            $contact = Contact::where('id', $contact_id)->first();
            $start_date = request()->input('start_date');
            $end_date = request()->input('end_date');

            $sellCustomerData = $this->getSellCustomer($contact_id, $start_date, $end_date);
            $transactions = $sellCustomerData['sell_customer'];
            $all_final_total = $sellCustomerData['all_final_total'];
            $all_total_sell_paid = $sellCustomerData['all_total_sell_paid'];
            $total_ending_balance_debt = $sellCustomerData['total_ending_balance_debt'];
            $total_ending_balance_positive = $sellCustomerData['total_ending_balance_positive'];
            $total_debt =  $sellCustomerData['total_debt'];
            $merge = [];

            //Gán giá trị cho từng ô trong sheet
            $export[1]['A'] = __('contact.customer') . ' - ' . $contact->name;
            $export[2]['A'] = __('sale.invoice_no');
            $export[2]['B'] = __('business.days');
            $export[2]['C'] = __('product.sku');
            $export[2]['D'] = __('sale.product');
            $export[2]['E'] = __('sale.note');

            $export[2]['F'] = __('purchase.amount');
            $export[2]['H'] = __('purchase.amount');
            $export[2]['H'] = __('lang_v1.balance_amount');
            $export[2]['I'] = __('lang_v1.balance_amount');

            $export[3]['A'] = null;
            $export[3]['B'] = null;
            $export[3]['C'] = null;
            $export[3]['D'] = null;
            $export[3]['E'] = null;
            $export[3]['F'] = __('account.debit');
            $export[3]['G'] = __('account.credit');
            $export[3]['H'] = __('account.debit');
            $export[3]['I'] = __('account.credit');

            $export[4]['A'] = __('lang_v1.opening_balance');
            $export[4]['B'] = null;
            $export[4]['C'] = null;
            $export[4]['D'] = null;
            $export[4]['E'] = null;
            $export[4]['F'] = null;
            $export[4]['G'] = null;
            $export[4]['H'] = $total_debt >= 0 ? $total_debt : '';
            $export[4]['I'] = $total_debt < 0 ? abs($total_debt) : '';

            //Gán các dòng dữ liệu
            $row_index = 5;

            foreach ($transactions as $transaction) {
                // Ngày giao dịch
                $transaction_date = Carbon::parse($transaction->transaction_date)->format('d-m-Y');

                // Hóa đơn số
                $invoice_no = in_array($transaction->type, ['sell', 'sell_return']) ? $transaction->invoice_no : $transaction->ref_no;
                $note = '';
                $products = [];

                if ($transaction->type == 'sell') {
                    //Ghi chú phí vận chuyển & VAT
                    $note = '';
                    if($transaction->shipping_charges > 0){
                        $note .= __('sale.shipping_charges').': '. number_format(round_int($transaction->shipping_charges, env('DIGIT', 4))) .'đ';
                    }
                    if($transaction->tax > 0){
                        if(!empty($note)){
                            $note .= '<br>';
                        }
                        $note .= __('purchase.purchase_tax').': '. number_format(round_int($transaction->tax, env('DIGIT', 4))) .'đ';
                    }

                    // Thông tin sản phẩm
                    foreach ($transaction->sell_lines as $sell_line) {
                        $product_name = $sell_line->variations->product->name;
                        $variation = $sell_line->variations->name;
                        if($sell_line->product->unit->type == 'area'){
                            $unit = number_format($sell_line->height, 2) . 'm x ' . number_format($sell_line->width, 2) . 'm x ';
                        }elseif($sell_line->product->unit->type == 'meter'){
                            $unit = number_format($sell_line->width, 2) . 'm x ';
                        }else{
                            $unit = '';
                        }
                        $quantity = number_format($sell_line->quantity_line);
                        $unit_price = $sell_line->unit_price;

                        if ($sell_line->variations->product->type == 'single') {
                            $product_detail = $product_name . ' ('. $unit . $quantity . ' x ' . number_format($unit_price) .')';
                        } else {
                            $product_detail = $product_name . ' ('. $unit . $quantity . ' x ' . number_format($unit_price) .')' . ' ('. $variation .')';
                        }

                        $products[] = [
                            'sku' => $sell_line->variations->product->sku,
                            'detail' => $product_detail,
                        ];
                    }
                } elseif ($transaction->type == 'sell_return') {
                    // Hóa đơn số
                    $sell_return = Transaction::where('id', $transaction->id)->first();
                    $invoice_return = Transaction::where('id', $sell_return->return_parent_id)->first();
                    $invoice_no .= ' (Trả hàng đơn ' . $invoice_return->invoice_no . ')';

                    // Thông tin sản phẩm
                    $trans_return = Transaction::where('id', $sell_return->return_parent_id)->first();
                    $plates_return = TransactionPlateLinesReturn::with('variation.product', 'variation.product.unit')->where('transaction_id', $trans_return->id)->get();

                    foreach ($plates_return as $plate_return) {
                        $variation_return = Variation::with('product', 'product.unit')->where('id', $plate_return->variation_id)->first();
                        if ($variation_return) {
                            $product_name = $variation_return->product->name;
                            $variation = $variation_return->name;
                            $quantity = number_format($plate_return->quantity);
                            $unit_price = $plate_return->unit_price;

                            if($variation_return->product->unit->type == 'area'){
                                $unit = number_format($plate_return->height, 2) . 'm x ' . number_format($plate_return->width, 2) . 'm x ';
                            }elseif($variation_return->product->unit->type == 'meter'){
                                $unit = number_format($plate_return->width, 2) . 'm x ';
                            }else{
                                $unit = '';
                            }

                            if ($variation_return->product->type == 'single') {
                                $product_detail = $product_name . ' ('. $unit . $quantity . ' x ' . number_format($unit_price) .')';
                            } else {
                                $product_detail = $product_name . ' ('. $unit . $quantity . ' x ' . number_format($unit_price) .')' . ' ('. $variation .')';
                            }

                            $products[] = [
                                'sku' => $variation_return->product->sku,
                                'detail' => $product_detail,
                            ];
                        }
                    }
                } elseif ($transaction->type == 'receipt') {
                    $products[] = [
                        'sku' => '',
                        'detail' => $transaction->receipt_note,
                    ];
                } elseif ($transaction->type == 'expense') {
                    $products[] = [
                        'sku' => '',
                        'detail' => $transaction->expense_note,
                    ];
                } elseif ($transaction->type == 'purchase') {
                    // Thông tin sản phẩm
                    foreach ($transaction->purchase_lines as $purchase_line) {
                        $product_name = $purchase_line->variations->product->name;
                        $variation = $purchase_line->variations->name;
                        if($purchase_line->product->unit->type == 'area'){
                            $unit = number_format($purchase_line->height, 2) . 'm x ' . number_format($purchase_line->width, 2) . 'm x ';
                        }elseif($purchase_line->product->unit->type == 'meter'){
                            $unit = number_format($purchase_line->width, 2) . 'm x ';
                        }else{
                            $unit = '';
                        }
                        $quantity = number_format($purchase_line->quantity_line);
                        $unit_price = $purchase_line->purchase_price;

                        if ($purchase_line->variations->product->type == 'single') {
                            $product_detail = $product_name . ' ('. $unit . $quantity . ' x ' . number_format($unit_price) .')' . PHP_EOL;
                        } else {
                            $product_detail = $product_name . ' ('. $unit . $quantity . ' x ' . number_format($unit_price) .')' . ' ('. $variation .')' . PHP_EOL;
                        }

                        $products[] = [
                            'sku' => $purchase_line->variations->product->sku,
                            'detail' => $product_detail,
                        ];
                    }
                }

                $export[$row_index]['A'] = $invoice_no;
                $export[$row_index]['B'] = $transaction_date;
                $export[$row_index]['C'] = !empty($products) ? $products[0]['sku'] : '';
                $export[$row_index]['D'] = !empty($products) ? $products[0]['detail'] : '';
                $export[$row_index]['E'] = $note;
                $export[$row_index]['F'] = $transaction->final_total_number != 0 ? round($transaction->final_total_number) : '';
                $export[$row_index]['G'] = $transaction->total_sell_paid != 0 ? round($transaction->total_sell_paid) : '';
                $export[$row_index]['H'] = $transaction->total_due_customer != 0 ? round($transaction->total_due_customer) : '';
                $export[$row_index]['I'] = $transaction->total_due_business != 0 ? round($transaction->total_due_business) : '';
                $row_index++;

                if(count($products) > 1){
                    $remaining_products = array_slice($products,1);
                    foreach ($remaining_products as $remaining_product){
                        $export[$row_index]['A'] = null;
                        $export[$row_index]['B'] = null;
                        $export[$row_index]['C'] = $remaining_product['sku'];
                        $export[$row_index]['D'] = $remaining_product['detail'];
                        $export[$row_index]['E'] = null;
                        $export[$row_index]['F'] = null;
                        $export[$row_index]['G'] = null;
                        $export[$row_index]['H'] = null;
                        $export[$row_index]['I'] = null;
                        $row_index++;
                    }
                }
            }

            // Tổng
            $row_index++;
            $export[$row_index]['A'] = __('lang_v1.grand_total');
            $export[$row_index]['B'] = null;
            $export[$row_index]['C'] = null;
            $export[$row_index]['D'] = null;
            $export[$row_index]['E'] = null;
            $export[$row_index]['F'] = round($all_final_total);
            $export[$row_index]['G'] = round($all_total_sell_paid);
            $export[$row_index]['H'] = null;
            $export[$row_index]['I'] = null;

            $row_index++;
            $export[$row_index]['A'] = __('sale.ending_balance');
            $export[$row_index]['B'] = null;
            $export[$row_index]['C'] = null;
            $export[$row_index]['D'] = null;
            $export[$row_index]['E'] = null;
            $export[$row_index]['F'] = null;
            $export[$row_index]['G'] = null;
            $export[$row_index]['H'] = round($total_ending_balance_debt);
            $export[$row_index]['I'] = round($total_ending_balance_positive);

            $file_name = Str::slug($contact->name);
            if(isset($start_date) && isset($end_date)){
                $file_name .= '_'. $start_date .'_'. $end_date;
            }
            $file_name .= '.xls';

            return Excel::download(new ContactExport($export, $row_index, $merge), $file_name);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                'msg' => $e->getMessage()
            ];
            return redirect(url()->previous())->with('status', $output);
        }
    }

    /*public function getDebtCustomer($contact_id) {
        if (request()->ajax()) {
            $start_date = request()->input('start_date');
            $total_debt = $this->calculateDebtCustomer($contact_id, $start_date);

            return response()->json([
                'success' => true,
                'debt' => $total_debt
            ]);
        }

        return response()->json([
            'success' => false,
        ]);
    }*/

    private function calculateDebtCustomer($contact_id, $start_date){
        $business_id = request()->session()->get('user.business_id');
        $contact_debt = $this->contactUtil->getQueryDebt($business_id, $contact_id);

        if (!empty($start_date)) {
            $contact_debt->whereDate('transaction_date', '<', $start_date);
        }

        $contact_debt = $contact_debt->first();
        $total_debt = $contact_debt->total_invoice - $contact_debt->invoice_received + $contact_debt->sell_return_paid - $contact_debt->total_sell_return + $contact_debt->opening_balance - $contact_debt->total_purchase - $contact_debt->reduce_debt;

        return $total_debt;
    }
}
