<?php

namespace App\Http\Controllers;

use App\Account;

use App\AccountTransaction;
use App\BusinessLocation;
use App\Contact;
use App\Events\TransactionPaymentAdded;
use App\Events\TransactionPaymentUpdated;
use App\ExpenseCategory;
use App\TaxRate;
use App\Transaction;
use App\TransactionExpense;
use App\TransactionPayment;
use App\TransactionReceipt;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ExpenseController extends Controller
{
    protected $transactionUtil;
    protected $moduleUtil;
    protected $businessUtil;
    protected $dummyPaymentLine;

    /**
    * Constructor
    *
    * @param TransactionUtil $transactionUtil
    * @return void
    */
    public function __construct(TransactionUtil $transactionUtil, ModuleUtil $moduleUtil, BusinessUtil $businessUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->businessUtil = $businessUtil;
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
        if (!auth()->user()->can('expense.access') && !auth()->user()->can('view_own_expense')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $expenses = Transaction::leftJoin('expense_categories AS ec', 'transactions.expense_category_id', '=', 'ec.id')
                        ->join(
                            'business_locations AS bl',
                            'transactions.location_id',
                            '=',
                            'bl.id'
                        )
                        ->leftJoin('tax_rates as tr', 'transactions.tax_id', '=', 'tr.id')
                        ->leftJoin('users AS U', 'transactions.expense_for', '=', 'U.id')
                        ->leftJoin('users AS usr', 'transactions.created_by', '=', 'usr.id')
                        ->leftJoin(
                            'transaction_payments AS TP',
                            'transactions.id',
                            '=',
                            'TP.transaction_id'
                        )
                        ->where('transactions.business_id', $business_id)
                        ->where('transactions.type', 'expense')
                        ->select(
                            'transactions.id',
                            'transactions.document',
                            'transaction_date',
                            'ref_no',
                            'ec.name as category',
                            'payment_status',
                            'additional_notes',
                            'final_total',
                            'bl.name as location_name',
                            DB::raw("CONCAT(COALESCE(U.surname, ''),' ',COALESCE(U.first_name, ''),' ',COALESCE(U.last_name,'')) as expense_for"),
                            DB::raw("CONCAT(tr.name ,' (', tr.amount ,' )') as tax"),
                            DB::raw('SUM(TP.amount) as amount_paid'),
                            DB::raw("CONCAT(COALESCE(usr.surname, ''),' ',COALESCE(usr.first_name, ''),' ',COALESCE(usr.last_name,'')) as added_by")
                        )
                        ->groupBy('transactions.id');

            //Add condition for location,used in sales representative expense report & list of expense
            if (request()->has('location_id')) {
                $location_id = request()->get('location_id');
                if (!empty($location_id)) {
                    $expenses->where('transactions.location_id', $location_id);
                }
            }

            //Add condition for start and end date filter, uses in sales representative expense report & list of expense
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $expenses->whereDate('transaction_date', '>=', $start)
                        ->whereDate('transaction_date', '<=', $end);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $expenses->whereIn('transactions.location_id', $permitted_locations);
            }

            //Add condition for payment status for the list of expense
            if (request()->has('payment_status')) {
                $payment_status = request()->get('payment_status');
                if (!empty($payment_status)) {
                    $expenses->where('transactions.payment_status', $payment_status);
                }
            }

            $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);
            if (!$is_admin && auth()->user()->can('view_own_expense')) {
                $expenses->where('transactions.created_by', request()->session()->get('user.id'));
            }

            return Datatables::of($expenses)
                ->addColumn(
                    'action',
                    '<div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                            data-toggle="dropdown" aria-expanded="false"> @lang("messages.actions")<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                </span>
                        </button>
                    <ul class="dropdown-menu dropdown-menu-left" role="menu">
                    <li><a href="{{action(\'ExpenseController@edit\', [$id])}}"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</a></li>
                    @if($document)
                        <li><a href="{{ url(\'uploads/documents/\' . $document)}}" 
                        download=""><i class="fa fa-download" aria-hidden="true"></i> @lang("purchase.download_document")</a></li>
                        @if(isFileImage($document))
                            <li><a href="#" data-href="{{ url(\'uploads/documents/\' . $document)}}" class="view_uploaded_document"><i class="fa fa-picture-o" aria-hidden="true"></i>@lang("lang_v1.view_document")</a></li>
                        @endif
                    @endif
                    <li>
                        <a data-href="{{action(\'ExpenseController@destroy\', [$id])}}" class="delete_expense"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</a></li>
                    <li class="divider"></li> 
                    @if($payment_status != "paid")
                        <li><a href="{{action("TransactionPaymentController@addPayment", [$id])}}" class="add_payment_modal"><i class="fas fa-money-bill-alt" aria-hidden="true"></i> @lang("purchase.add_payment")</a></li>
                    @endif
                    <li><a href="{{action("ExpenseController@show", [$id])}}" class="view_expense_modal"><i class="fas fa-eye" aria-hidden="true" ></i> @lang("expense.view_expense")</a></li>
                    <li><a href="{{action("TransactionPaymentController@show", [$id])}}" class="view_payment_modal"><i class="fas fa-money-bill-alt" aria-hidden="true" ></i> @lang("purchase.view_payments")</a></li>
                    </ul></div>'
                )
                ->removeColumn('id')
                ->editColumn(
                    'final_total',
                    '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="{{$final_total}}">{{$final_total}}</span>'
                )
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn(
                    'payment_status',
                    '<a href="{{ action("TransactionPaymentController@show", [$id])}}" class="view_payment_modal payment-status no-print" data-orig-value="{{$payment_status}}" data-status-name="{{__(\'lang_v1.\' . $payment_status)}}"><span class="label @payment_status($payment_status)">{{__(\'lang_v1.\' . $payment_status)}}
                        </span></a><span class="print_section">{{__(\'lang_v1.\' . $payment_status)}}</span>'
                )
                ->addColumn('payment_due', function ($row) {
                    $due = $row->final_total - $row->amount_paid;
                    return '<span class="display_currency payment_due" data-currency_symbol="true" data-orig-value="' . $due . '">' . $due . '</span>';
                })
                ->rawColumns(['final_total', 'action', 'payment_status', 'payment_due'])
                ->make(true);
        }

        $business_id = request()->session()->get('user.business_id');

        $categories = ExpenseCategory::where('business_id', $business_id)
                            ->pluck('name', 'id');

        $users = User::forDropdown($business_id, false, true, true);

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('expense.index')
            ->with(compact('categories', 'business_locations', 'users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('expense.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action('ExpenseController@index'));
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        $expense_categories = ExpenseCategory::where('business_id', $business_id)
                                ->pluck('name', 'id');
        $users = Contact::contactDropdown($business_id);

        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);

        $payment_line = $this->dummyPaymentLine;

        $payment_types = $this->transactionUtil->payment_types();
        $packages = [];

        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false, true);
        }
        $array = [0];

        return view('expense.create_new')
            ->with(compact('expense_categories', 'business_locations', 'users', 'taxes', 'payment_line', 'payment_types', 'accounts', 'array', 'packages'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('expense.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            //Check if subscribed or not
            if (!$this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse(action('ExpenseController@index'));
            }

            //Validate document size
            $request->validate([
                'document' => 'file|max:'. (config('constants.document_size_limit') / 1000)
            ]);

            $transaction_data = $request->only([ 'ref_no', 'transaction_date', 'location_id', 'final_total', 'expense_for', 'additional_notes', 'tax_id']);

            $user_id = $request->session()->get('user.id');

            //upload document
            $document_name = $this->transactionUtil->uploadFile($request, 'document', 'documents');
            if (!empty($document_name)) {
                $transaction_data['document'] = $document_name;
            }
            $transaction_data['business_id'] = $business_id;
            $transaction_data['created_by'] = $user_id;
            $transaction_data['type'] = 'expense';
            $transaction_data['status'] = 'final';
            $transaction_data['contact_id'] = $transaction_data['expense_for'];
            $transaction_data['payment_status'] = 'due';
            $transaction_data['transaction_date'] = $this->transactionUtil->uf_date($transaction_data['transaction_date'], true);
            $transaction_data['created_at'] = Carbon::now()->format('Y-m-d H:i:s');
            $transaction_data['updated_at'] = Carbon::now()->format('Y-m-d H:i:s');

            //Update reference count
            $ref_count = $this->transactionUtil->setAndGetReferenceCount('expense');
            //Generate reference number
            if (empty($transaction_data['ref_no'])) {
                $transaction_data['ref_no'] = $this->transactionUtil->generateReferenceNumber('expense', $ref_count);
            }

            $expenses = $request->input('expenses');

            $dataSave = [];
            $finalTotal = 0;
            DB::beginTransaction();

            foreach ($expenses as $expense) {
                $data['total_money']        = $this->transactionUtil->num_uf($expense['final_total']);
                $data['type']               = $expense['type'];
                $data['note']               = $expense['description'];
                $data['ref_transaction_id'] = empty($expense['package_id']) ? 0 : $expense['package_id'];
                $data['contact_id']         = $expense['customer_id'];
                $data['created_at']         = Carbon::now()->format('Y-m-d H:i:s');
                $data['updated_at']         = Carbon::now()->format('Y-m-d H:i:s');

//                if (!empty($expense['package_id'])) {
//                    $subTransaction = Transaction::query()->find($expense['package_id'])->update();
//                }

                if ($expense['type'] == TransactionExpense::INCOME) {
                    $finalTotal += $data['total_money'];
                } else {
                    $finalTotal -= $data['total_money'];
                }

                $dataSave[] = $data;
            }

            $transaction_data['final_total'] = $finalTotal;
            $transaction_data['total_before_tax'] = $finalTotal;

            $transaction = Transaction::create($transaction_data);

            $dataSave = array_map(function ($arr) use ($transaction){
                return array_merge($arr, ['transaction_id' => $transaction->id]);
            }, $dataSave);

            TransactionExpense::insert($dataSave);

            //add expense payment
            $this->transactionUtil->createOrUpdatePaymentLines($transaction, $request->input('payment'), $business_id);

            //update payment status
            $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

            DB::commit();

            $output = ['success' => 1,
                            'msg' => __('expense.expense_add_success')
                        ];
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                            'msg' => __('messages.something_went_wrong')
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
        $business_id = request()->session()->get('user.business_id');
        $transaction = Transaction::query()->with('expenses')->find($id);
        $users       = Contact::contactDropdown($business_id);

        $refTranId = $transaction->expenses->map(function ($item) {
            return $item->ref_transaction_id;
        })->toArray();

        $listRefTrans = Transaction::query()->find($refTranId)->pluck('invoice_no', 'id')->toArray();

        return view('expense.show')->with(compact('transaction', 'users', 'listRefTrans'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('expense.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action('ExpenseController@index'));
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        $payment_line = $this->dummyPaymentLine;

        $payment_types = $this->transactionUtil->payment_types();

        $business_id = request()->session()->get('user.business_id');
        $transaction = Transaction::query()->with('expenses')->find($id);
        $users       = Contact::contactDropdown($business_id);

        $refTranId = $transaction->expenses->map(function ($item) {
            return $item->ref_transaction_id;
        })->toArray();

        $listRefTrans = Transaction::query()->find($refTranId)->pluck('invoice_no', 'id')->toArray();

        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);

        $payment_total = TransactionPayment::query()->where('transaction_id',  $transaction->id)->get();
        $payment_total = $payment_total->sum('amount');

        $packages = [];

        return view('expense.edit')
            ->with(compact('business_locations','users', 'taxes', 'transaction', 'listRefTrans', 'packages', 'payment_line', 'payment_types', 'payment_total'));
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
        if (!auth()->user()->can('expense.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {

            $transaction = Transaction::query()->find($id);
            //Validate document size
            $request->validate([
                'document' => 'file|max:'. (config('constants.document_size_limit') / 1000)
            ]);

            $transaction_data = $request->only([ 'ref_no', 'transaction_date', 'location_id', 'final_total', 'expense_for', 'additional_notes', 'expense_category_id', 'tax_id']);

            $business_id = $request->session()->get('user.business_id');

            //Check if subscribed or not
            if (!$this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse(action('ExpenseController@index'));
            }

            $transaction_data['transaction_date'] = $this->transactionUtil->uf_date($transaction_data['transaction_date'], true);

            //upload document
            $document_name = $this->transactionUtil->uploadFile($request, 'document', 'documents');
            if (!empty($document_name)) {
                $transaction_data['document'] = $document_name;
            }

            $dataSave = [];
            $finalTotal = 0;

            $expenses = $request->input('expenses');

            foreach ($expenses as $expense) {
                $data['total_money']        = $this->transactionUtil->num_uf($expense['final_total']);
                $data['type']               = $expense['type'];
                $data['note']               = $expense['description'];
                $data['ref_transaction_id'] = empty($expense['package_id']) ? 0 : $expense['package_id'];
                $data['contact_id']         = $expense['customer_id'];
                $data['created_at']         = Carbon::now()->format('Y-m-d H:i:s');
                $data['updated_at']         = Carbon::now()->format('Y-m-d H:i:s');

                if ($expense['type'] == TransactionExpense::INCOME) {
                    $finalTotal += $data['total_money'];
                } else {
                    $finalTotal -= $data['total_money'];
                }

                $dataSave[] = $data;
            }

            $transaction_data['final_total']      = $finalTotal;
            $transaction_data['total_before_tax'] = $finalTotal;

            DB::beginTransaction();

            $transaction->update($transaction_data);

            $dataSave = array_map(function ($arr) use ($transaction){
                return array_merge($arr, ['transaction_id' => $transaction->id]);
            }, $dataSave);

            TransactionExpense::query()->where('transaction_id', $transaction->id)->delete();

            TransactionExpense::insert($dataSave);

//            //add expense payment
//            $this->transactionUtil->createOrUpdatePaymentLines($transaction, $request->input('payment'), $business_id);
//
//            //update payment status
//            $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

            $output = ['success' => 1,
                            'msg' => __('expense.expense_update_success')
                        ];
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                            'msg' => __('messages.something_went_wrong')
                        ];
        }

        return redirect('expenses')->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('expense.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');

                $expense = Transaction::where('business_id', $business_id)
                                        ->where('type', 'expense')
                                        ->where('id', $id)
                                        ->first();
                $expense->delete();

                //Delete account transactions
                AccountTransaction::where('transaction_id', $expense->id)->delete();

                $output = ['success' => true,
                            'msg' => __("expense.expense_delete_success")
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

    public function addExpenseRow(Request $request){
        if (!auth()->user()->can('sell.receipt_expense')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $key = $request->index + 1;

        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action('ExpenseController@index'));
        }

        $array_receipt = TransactionReceipt::whereDate('created_at', '=', Carbon::today()->toDateString())->get()->toArray();
        if (count($array_receipt) == 0) {
            $array_receipt = [0];
        }

        $array_expense = TransactionExpense::whereDate('created_at', '=', Carbon::today()->toDateString())->get()->toArray();
        if (count($array_expense) == 0) {
            $array_expense = [0];
        }

        $expense_categories = ExpenseCategory::where('business_id', $business_id)
            ->pluck('name', 'id');
        $users = Contact::customersDropdown($business_id);
        $payment_line = $this->dummyPaymentLine;
        $payment_types = $this->transactionUtil->payment_types();

        return view('expense.partials.add_expense_row')->with(compact('key', 'expense_categories', 'users', 'payment_types', 'payment_line', 'array_receipt', 'array_expense'));
    }

    public function addReceiptRow(Request $request){
        if (!auth()->user()->can('sell.receipt_expense')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $key = $request->index + 1;

        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action('ExpenseController@index'));
        }

        $array_receipt = TransactionReceipt::whereDate('created_at', '=', Carbon::today()->toDateString())->get()->toArray();
        if (count($array_receipt) == 0) {
            $array_receipt = [0];
        }

        $array_expense = TransactionExpense::whereDate('created_at', '=', Carbon::today()->toDateString())->get()->toArray();
        if (count($array_expense) == 0) {
            $array_expense = [0];
        }

        $expense_categories = ExpenseCategory::where('business_id', $business_id)
            ->pluck('name', 'id');
        $users = Contact::customersDropdown($business_id);
        $payment_line = $this->dummyPaymentLine;
        $payment_types = $this->transactionUtil->payment_types();

        return view('expense.partials.add_receipt_row')->with(compact('key', 'expense_categories', 'users', 'payment_types', 'payment_line', 'array_receipt', 'array_expense'));
    }

    public function getTransactionForCustomer(Request $request){
        $contactId = empty($request->contact_id) ? 0 : $request->contact_id;

        if (empty($contactId)) {
            return json_encode([
                'success' => true,
                'message' => 'Chưa nhập thông tin khách hàng'
            ]);
        }

        $transactions = Transaction::query()
            ->where('contact_id', $contactId)
            ->where('status', 'final')
            ->where('type', 'sell')
            ->where('is_approved_by_cashier', '<>' ,Transaction::APPROVE_BY_CASHIER)
            ->select('invoice_no', 'id')
            ->get();

        return json_encode($transactions);
    }

    public function createReceiptRow() {
        if (!auth()->user()->can('sell.receipt_expense')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];

        $default_location = null;
        if (count($business_locations) == 1) {
            foreach ($business_locations as $id => $name) {
                $default_location = BusinessLocation::findOrFail($id);
            }
        }

        $users = Contact::customersNotIncludeWalkInGuestDropdown($business_id);
        $payment_types = $this->transactionUtil->payment_types();

        //Accounts
        $accounts = $this->moduleUtil->accountsDropdown($business_id, true, false, true, 'cash');

        //Set transaction date
        if ($this->moduleUtil->isClosedEndOfDay()) {
            $transaction_date = date('Y-m-d', strtotime('now +1 days'));
            $transaction_date .= ' 00:00';
        }else{
            $transaction_date = date('Y-m-d H:i:s');
        }

        return view('expense.partials.add_receipt_row')
            ->with(compact('users', 'payment_types', 'default_location', 'business_locations', 'bl_attributes', 'transaction_date', 'accounts'));
    }

    public function storeReceiptRow(Request $request) {
        if (!auth()->user()->can('sell.receipt_expense')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            if ($request->ajax()) {
                $location_id = $request->location_id;
                $transaction_date = $this->transactionUtil->uf_date($request->input('transaction_date'), true);
                $type = $request->type;
                $contact_id = in_array($type, ['recovery_dues', 'deposit']) ? $request->contact_id : null;
                $total_money = $this->transactionUtil->num_uf($request->total_money);
                $note = !empty($request->note) ? trim($request->note) : null;
                $method = $request->input('method');
                $bank_account_number = $method != 'cash' ? $request->bank_account : null;
                $user_id = auth()->user()->id;
                $business_id = request()->session()->get('user.business_id');

                DB::beginTransaction();

                //Update reference count
                $ref_count = $this->transactionUtil->setAndGetReferenceCount('receipt');
                //Generate reference number
                $ref_no = $this->transactionUtil->generateReferenceNumber('receipt', $ref_count, null, 'RC');

                $transaction = Transaction::create([
                    'business_id' => $business_id,
                    'location_id' => $location_id,
                    'type' => 'receipt',
                    'sub_type' => $type,
                    'status' => 'final',
                    'invoice_no' => '',
                    'ref_no' => $ref_no,
                    'contact_id' => $contact_id,
                    'transaction_date' => $transaction_date,
                    'final_total' => $total_money,
                    'created_by' => $user_id,
                    'additional_notes' => $note,
                ]);

                TransactionReceipt::create([
                    'type' => $type,
                    'transaction_id' => $transaction->id,
                    'contact_id' => $contact_id,
                    'total_money' => $total_money,
                    'note' => $note
                ]);

                $payment_data = [
                    'transaction_id' => $transaction->id,
                    'business_id' => $business_id,
                    'is_return' => 0,
                    'amount' => $total_money,
                    'method' => $method,
//                    'paid_on' => date('Y-m-d H:i:s'),
                    'paid_on' => $transaction_date,
                    'bank_account_number' => $bank_account_number,
                    'created_by' => $user_id,
                    'payment_for' => $transaction->contact_id,
                    'type' => 'receipt',
                    'note' => $note,
                    'account_id' => $request->input('account_id'),
                    'cashier_confirmed_id' => $user_id,
                ];

                if(!auth()->user()->can('sell.confirm_bank_transfer_method') && $request->input('method') == 'bank_transfer'){
                    $payment_data['approval_status'] = 'pending';
                }else{
                    $payment_data['approval_status'] = 'approved';
                }

                //add receipt payment
                $this->transactionUtil->createOrUpdatePaymentLines($transaction, [$payment_data], $business_id);

                /*$transaction_receipt = TransactionPayment::where('transaction_id', $transaction->id)->first();
                $notificationUtil = new NotificationUtil();
                $notificationUtil->transactionPaymentNotification($transaction_receipt);*/

                //update payment status
                $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
                DB::commit();

                $output = ['success' => true,
                    'msg' => $payment_data['approval_status'] == 'approved' ? __('expense.add_receipt_success') : __('purchase.bank_transfer_pending')
                ];
            }
        } catch (\Exception $e) {
            DB::rollback();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }
        return $output;
    }

    public function createPaymentLine($transaction, $insert_data, $prefix_type)
    {
        if (!is_object($transaction)) {
            $transaction = Transaction::findOrFail($transaction);
        }

        $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);
        //Generate reference number
        $insert_data['payment_ref_no'] = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

        $tp = TransactionPayment::create($insert_data);

        //update payment status
        $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
        event(new TransactionPaymentAdded($tp, $insert_data));

        //event
        event(new TransactionPaymentUpdated($tp, $transaction->type));
    }

    public function editReceiptRow($id) {
        if (!auth()->user()->can('sell.receipt_expense')) {
            abort(403, 'Unauthorized action.');
        }

        $receipt = TransactionReceipt::find($id);
        $method = Transaction::where('id', $receipt->transaction_id)
            ->with('payment_lines')
            ->first();

        $business_id = request()->session()->get('user.business_id');
        $users = Contact::customersNotIncludeWalkInGuestDropdown($business_id);
        $payment_types = $this->transactionUtil->payment_types();

        return view('expense.partials.edit_receipt_row')
            ->with(compact('receipt', 'method', 'users', 'payment_types'));
    }

    public function updateReceiptRow($id, Request $request) {
        if (!auth()->user()->can('sell.receipt_expense')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            if ($request->ajax()) {
                $business_id = request()->session()->get('user.business_id');
                $type = $request->type;
                $contact_id = in_array($type, ['recovery_dues', 'deposit']) ? $request->contact_id : null;
                $total_money = $this->transactionUtil->num_uf($request->total_money);
                $note = !empty($request->note) ? trim($request->note) : null;
                $method = $request->input('method');
                $bank_account_number = $method != 'cash' ? $request->bank_account : null;

                DB::beginTransaction();
                $receipt = TransactionReceipt::find($id);

                $transaction = Transaction::find($receipt->transaction_id);
                $transaction->update([
                    'contact_id' => $contact_id,
                    'transaction_date' => date('Y-m-d H:i:s'),
                    'final_total' => $total_money,
                    'created_by' => auth()->user()->id,
                ]);

                $receipt->update([
                    'type' => $type,
                    'contact_id' => $contact_id,
                    'total_money' => $total_money,
                    'note' => $note
                ]);

                $payment = TransactionPayment::where('transaction_id', $transaction->id)->first();

                $payment_data = [
                    'payment_id' => $payment->id,
                    'amount' => $total_money,
                    'method' => $method,
//                    'paid_on' => date('Y-m-d H:i:s'),
                    'bank_account_number' => $bank_account_number,
//                    'created_by' => $user_id,
                    'payment_for' => $contact_id,
                    'note' => $note
                ];

                if(!auth()->user()->can('sell.confirm_bank_transfer_method') && $request->input('method') == 'bank_transfer'){
                    $payment_data['approval_status'] = 'pending';
                    $payment_data['cashier_confirmed_id'] = auth()->user()->id;
                }else{
                    $payment_data['approval_status'] = 'approved';
                    if($payment->cashier_confirmed_id){
                        $payment_data['admin_confirmed_id'] = auth()->user()->id;
                    }else{
                        $payment_data['cashier_confirmed_id'] = auth()->user()->id;
                    }
                }

                //Update receipt payment
                $this->transactionUtil->createOrUpdatePaymentLines($transaction, [$payment_data], $business_id);

                $transaction_receipt = TransactionPayment::where('transaction_id', $transaction->id)->first();

                /*$notificationUtil = new NotificationUtil();
                $notificationUtil->transactionPaymentNotification($transaction_receipt);*/

                //Update receipt status
                $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

                DB::commit();

                $output = ['success' => true,
                    'msg' => $transaction_receipt->approval_status == 'approved' ? __('expense.update_receipt_success') : __('purchase.bank_transfer_pending')
                ];
            }
        } catch (\Exception $e) {
            DB::rollback();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    public function deleteReceiptRow(Request $request, $id) {
        if (!auth()->user()->can('sell.receipt_expense')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                DB::beginTransaction();
                $receipt = TransactionReceipt::find($id);
                $payment = TransactionPayment::where('transaction_id', $receipt->transaction_id);

                if ($receipt) {
                    $transaction = Transaction::find($receipt->transaction_id);
                    $receipt->delete();
                    $payment->delete();
                    $transaction->delete();
                }

                DB::commit();
                $output = ['success' => true,
                    'msg' => __("expense.delete_receipt_success"),
                ];

            } catch (\Exception $e) {
                DB::rollback();
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output = ['success' => false,
                    'msg' => '__("messages.something_went_wrong")'
                ];
            }

            return $output;
        }
    }

    public function createExpenseRow() {
        if (!auth()->user()->can('sell.receipt_expense')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];

        $default_location = null;
        if (count($business_locations) == 1) {
            foreach ($business_locations as $id => $name) {
                $default_location = BusinessLocation::findOrFail($id);
            }
        }

        $users = Contact::customersNotIncludeWalkInGuestDropdown($business_id);
        $payment_types = $this->transactionUtil->payment_types();

        //Accounts
        $accounts = $this->moduleUtil->accountsDropdown($business_id, true, false, true, 'cash');

        //Set transaction date
        if ($this->moduleUtil->isClosedEndOfDay()) {
            $transaction_date = date('Y-m-d', strtotime('now +1 days'));
            $transaction_date .= ' 00:00';
        }else{
            $transaction_date = date('Y-m-d H:i:s');
        }

        return view('expense.partials.add_expense_row')
            ->with(compact('users', 'payment_types', 'bl_attributes', 'default_location', 'business_locations', 'transaction_date', 'accounts'));
    }

    public function storeExpenseRow(Request $request) {
        if (!auth()->user()->can('sell.receipt_expense')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            if ($request->ajax()) {
                $type = $request->type;
                $location_id = $request->location_id;
                $transaction_date = date("Y-m-d H:i:s", strtotime(str_replace('/', '-', $request->transaction_date)));
                if (Carbon::parse($transaction_date)->format('Y-m-d') == Carbon::today()->toDateString()) {
                    $transaction_date = date('Y-m-d H:i:s');
                }
                $contact_id = $type == 'return_customer' ? $request->contact_id : null;
                $total_money = $this->transactionUtil->num_uf($request->total_money);
                $note = !empty($request->note) ? trim($request->note) : null;
                $method = $request->input('method');
                $bank_account_number = $method != 'cash' ? $request->bank_account : null;
                $user_id = auth()->user()->id;

                $business_id = request()->session()->get('user.business_id');

                $date = Carbon::parse($transaction_date)->format('Y-m-d');
                $total_money_on_day = $this->transactionUtil->calculateTotalMoneyOnDay($date);

                if ($method == 'cash') {
                    if ($total_money_on_day['total_money_payment_cash'] < $total_money) {
                        $output = [
                            'success' => false,
                            'msg' => [
                                'title_expense' => __("lang_v1.cannot_add_expense"),
                                'content_expense' => __("lang_v1.info_cannot_add_expense"),
                            ]
                        ];

                        return $output;
                    }
                }

                //Update reference count
                $ref_count = $this->transactionUtil->setAndGetReferenceCount('expense');
                //Generate reference number
                $ref_no = $this->transactionUtil->generateReferenceNumber('expense', $ref_count);

                DB::beginTransaction();
                $transaction = Transaction::create([
                    'business_id' => $business_id,
                    'location_id' => $location_id,
                    'type' => 'expense',
                    'status' => 'final',
                    'contact_id' => $contact_id,
                    'transaction_date' => $transaction_date,
                    'final_total' => $total_money,
                    'created_by' => $user_id,
                    'ref_no' => $ref_no,
                ]);

                TransactionExpense::create([
                    'type' => $type,
                    'transaction_id' => $transaction->id,
                    'contact_id' => $contact_id,
                    'total_money' => $total_money,
                    'note' => $note
                ]);

                $payment_data = [
                    'transaction_id' => $transaction->id,
                    'business_id' => $business_id,
                    'is_return' => 0,
                    'amount' => $total_money * -1,
                    'method' => $method,
//                    'paid_on' => date('Y-m-d H:i:s'),
                    'paid_on' => $transaction_date,
                    'bank_account_number' => $bank_account_number,
                    'created_by' => $user_id,
                    'payment_for' => $transaction->contact_id,
                    'type' => 'expense',
                    'note' => $note,
                    'account_id' => $request->input('account_id'),
                    'cashier_confirmed_id' => auth()->user()->id,
                ];

                if(!auth()->user()->can('sell.confirm_bank_transfer_method') && $request->input('method') == 'bank_transfer'){
                    $payment_data['approval_status'] = 'pending';
                }else{
                    $payment_data['approval_status'] = 'approved';
                }

                //add expense payment
                $this->transactionUtil->createOrUpdatePaymentLines($transaction, [$payment_data], $business_id);

                /*$transaction_expense = TransactionPayment::where('transaction_id', $transaction->id)->first();
                $notificationUtil = new NotificationUtil();
                $notificationUtil->transactionPaymentNotification($transaction_expense);*/

                //update payment status
                $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

                DB::commit();

                $output = ['success' => true,
                    'msg' => $payment_data['approval_status'] == 'approved' ? __('expense.add_expense_success') : __('purchase.bank_transfer_pending')
                    /*'data' => [
                        'method' => $method,
                        'total_cash' => $total_money_on_day['total_money_payment_cash'] - $total_money,
                        'total_bank' => $total_money_on_day['total_money_payment_bank'] - $total_money,
                    ]*/
                ];
            }
        } catch (\Exception $e) {
            DB::rollback();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }
        return $output;
    }

    public function editExpenseRow($id) {
        if (!auth()->user()->can('sell.receipt_expense')) {
            abort(403, 'Unauthorized action.');
        }

        $expense = TransactionExpense::find($id);
        $method = Transaction::where('id', $expense->transaction_id)
            ->with('payment_lines')
            ->first();

        $business_id = request()->session()->get('user.business_id');
        $users = Contact::customersNotIncludeWalkInGuestDropdown($business_id);
        $payment_types = $this->transactionUtil->payment_types();

        return view('expense.partials.edit_expense_row')
            ->with(compact('expense', 'method', 'users', 'payment_types'));
    }

    public function updateExpenseRow($id, Request $request) {
        if (!auth()->user()->can('sell.receipt_expense')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            if ($request->ajax()) {
                $business_id = request()->session()->get('user.business_id');
                $type = $request->type;
                $note = !empty($request->note) ? trim($request->note) : null;
                $contact_id = $type == 'return_customer' ? $request->contact_id : null;
                $total_money = $this->transactionUtil->num_uf($request->total_money);
                $method = $request->input('method');
                $bank_account_number = $method != 'cash' ? $request->bank_account : null;
                $today = Carbon::today()->toDateString();
                $total_money_on_day = $this->transactionUtil->calculateTotalMoneyOnDay($today);

                $expense = TransactionExpense::find($id);

                if ($method == 'cash') {
                    if (($total_money_on_day['total_money_payment_cash'] + $expense->total_money) < $total_money) {
                        $output = [
                            'success' => false,
                            'msg' => [
                                'title_expense' => __("lang_v1.cannot_add_expense"),
                                'content_expense' => __("lang_v1.info_cannot_add_expense"),
                            ]
                        ];

                        return $output;
                    }
                }

                DB::beginTransaction();
                $transaction = Transaction::find($expense->transaction_id);
                $transaction->update([
                    'contact_id' => $contact_id,
                    'transaction_date' => date('Y-m-d H:i:s'),
                    'final_total' => $total_money,
                    'created_by' => auth()->user()->id,
                ]);

                TransactionExpense::find($id)->update([
                    'type' => $type,
                    'contact_id' => $contact_id,
                    'total_money' => $total_money,
                    'note' => $note
                ]);

                $payment = TransactionPayment::where('transaction_id', $transaction->id)->first();

                $payment_data = [
                    'payment_id' => $payment->id,
                    'amount' => $total_money * -1,
                    'method' => $method,
//                    'paid_on' => date('Y-m-d H:i:s'),
                    'bank_account_number' => $bank_account_number,
//                    'created_by' => $user_id,
                    'payment_for' => $contact_id,
                    'note' => $note
                ];

                if(!auth()->user()->can('sell.confirm_bank_transfer_method') && $request->input('method') == 'bank_transfer'){
                    $payment_data['approval_status'] = 'pending';
                    $payment_data['cashier_confirmed_id'] = auth()->user()->id;
                }else{
                    $payment_data['approval_status'] = 'approved';
                    if($payment->cashier_confirmed_id){
                        $payment_data['admin_confirmed_id'] = auth()->user()->id;
                    }else{
                        $payment_data['cashier_confirmed_id'] = auth()->user()->id;
                    }
                }

                //Update receipt payment
                $this->transactionUtil->createOrUpdatePaymentLines($transaction, [$payment_data], $business_id);

                $transaction_expense = TransactionPayment::where('transaction_id', $transaction->id)->first();

                /*$notificationUtil = new NotificationUtil();
                $notificationUtil->transactionPaymentNotification($transaction_expense);*/

                //Update receipt status
                $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

                DB::commit();

                $output = ['success' => true,
                    'msg' => $transaction_expense->approval_status == 'approved' ? __('expense.update_expense_success') : __('purchase.bank_transfer_pending')
                ];
            }
        } catch (\Exception $e) {
            DB::rollback();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    public function deleteExpenseRow(Request $request, $id) {
        if (!auth()->user()->can('sell.receipt_expense')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                DB::beginTransaction();
                $expense = TransactionExpense::find($id);
                $payment = TransactionPayment::where('transaction_id', $expense->transaction_id);

                if ($expense) {
                    $transaction = Transaction::find($expense->transaction_id);
                    $expense->delete();
                    $payment->delete();
                    $transaction->delete();
                }

                DB::commit();
                $output = ['success' => true,
                    'msg' => __("expense.delete_receipt_success"),
                ];

            } catch (\Exception $e) {
                DB::rollback();
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output = ['success' => false,
                    'msg' => __("messages.something_went_wrong")
                ];
            }

            return $output;
        }
    }

    public function printReceiptInvoice(Request $request, $transaction_id)
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

                $receipt = $this->receiptContent($business_id, $transaction->location_id, $transaction->id, $printer_type, false);

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

    private function receiptContent(
        $business_id,
        $location_id,
        $transaction_id,
        $printer_type = null,
        $from_pos_screen = true
    ) {
        $output = [
            'is_enabled' => false,
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

        $receipt_details = $this->transactionUtil->getDepositReceiptDetails($transaction_id, $location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type, true);

        //If print type browser - return the content, printer - return printer config data, and invoice format config
        if ($receipt_printer_type == 'printer') {
            $output['print_type'] = 'printer';
            $output['printer_config'] = $this->businessUtil->printerConfig($business_id, $location_details->printer_id);
            $output['data'] = $receipt_details;
        } else {
            $layout = 'sale_pos.receipts.deposit_receipt';

            $output['html_content'] = view($layout, compact('receipt_details'))->render();
        }

        return $output;
    }
}
