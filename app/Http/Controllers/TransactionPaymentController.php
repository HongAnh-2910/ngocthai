<?php

namespace App\Http\Controllers;

use App\AccountTransaction;
use App\Contact;

use App\Events\TransactionPaymentAdded;
use App\Events\TransactionPaymentDeleted;

use App\Events\TransactionPaymentUpdated;
use App\Transaction;
use App\TransactionPayment;

use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\TransactionUtil;

use Carbon\Carbon;
use Datatables;
use DB;
use Illuminate\Http\Request;
use App\Utils\ProductUtil;

class TransactionPaymentController extends Controller
{
    protected $transactionUtil;
    protected $moduleUtil;
    protected $productUtil;
    protected $businessUtil;

    /**
     * Constructor
     *
     * @param TransactionUtil $transactionUtil
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil, ModuleUtil $moduleUtil, ProductUtil $productUtil, BusinessUtil $businessUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->productUtil = $productUtil;
        $this->businessUtil = $businessUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        /*if (!auth()->user()->can('purchase.payments') || !auth()->user()->can('sell.payments')) {
            abort(403, 'Unauthorized action.');
        }*/

        try {
            $business_id = $request->session()->get('user.business_id');
            $transaction_id = $request->input('transaction_id');
            $transaction = Transaction::where('business_id', $business_id)->findOrFail($transaction_id);

            if ($transaction->payment_status != 'paid') {
                $inputs = $request->only(['amount', 'method', 'note', 'card_number', 'card_holder_name',
                'card_transaction_number', 'card_type', 'card_month', 'card_year', 'card_security',
                'cheque_number', 'bank_account_number']);
                $inputs['paid_on'] = $this->transactionUtil->uf_date($request->input('paid_on'), true);
                $inputs['transaction_id'] = $transaction->id;
                $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);
                $inputs['created_by'] = auth()->user()->id;
                $inputs['payment_for'] = $transaction->contact_id;
                if ($transaction->type == 'sell_return') {
                    $inputs['type'] = 'return';
                }else{
                    $inputs['type'] = 'normal';
                }

                if (!empty($request->input('account_id'))) {
                    $inputs['account_id'] = $request->input('account_id');
                }

                $prefix_type = 'purchase_payment';
                if (in_array($transaction->type, ['sell', 'sell_return'])) {
                    $prefix_type = 'sell_payment';
                } elseif ($transaction->type == 'expense') {
                    $prefix_type = 'expense_payment';
                }

                if(!auth()->user()->can('sell.confirm_bank_transfer_method') && $request->input('method') == 'bank_transfer'){
                    $inputs['approval_status'] = 'pending';
                }else{
                    $inputs['approval_status'] = 'approved';
                }

                $inputs['cashier_confirmed_id'] = auth()->user()->id;
                $inputs['business_id'] = $request->session()->get('business.id');
                $inputs['document'] = $this->transactionUtil->uploadFile($request, 'document', 'documents');

//                DB::beginTransaction();

                $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);
                //Generate reference number
                $inputs['payment_ref_no'] = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

                $tp = TransactionPayment::create($inputs);

                //update payment status
                $this->transactionUtil->updatePaymentStatus($transaction_id, $transaction->final_total);

                $notificationUtil = new NotificationUtil();
                $notificationUtil->transactionPaymentNotification($tp);

                $inputs['transaction_type'] = $transaction->type;
                event(new TransactionPaymentAdded($tp, $inputs));
//                DB::commit();
            }

            $output = ['success' => true,
                            'msg' => $inputs['approval_status'] == 'approved' ? __('purchase.payment_added_success') : __('purchase.bank_transfer_pending')
                        ];
        } catch (\Exception $e) {
//            DB::rollBack();

            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => false,
                          'msg' => __('messages.something_went_wrong')
                      ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    public function storeRemaining(Request $request)
    {
//        if (!auth()->user()->can('purchase.payments') || !auth()->user()->can('sell.payments')) {
//            abort(403, 'Unauthorized action.');
//        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $transaction_id = $request->input('transaction_id');
            $transaction = Transaction::where('business_id', $business_id)->findOrFail($transaction_id);

            if ($transaction->payment_status != 'paid') {
                $inputs = $request->only(['amount', 'method', 'note', 'card_number', 'card_holder_name',
                    'card_transaction_number', 'card_type', 'card_month', 'card_year', 'card_security',
                    'cheque_number', 'bank_account_number']);
                $inputs['paid_on'] = $this->transactionUtil->uf_date($request->input('paid_on'), true);
                $inputs['transaction_id'] = $transaction->id;
                $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);
                $inputs['created_by'] = auth()->user()->id;
                $inputs['payment_for'] = $transaction->contact_id;
                $inputs['type'] = 'normal';

                if (!empty($request->input('account_id'))) {
                    $inputs['account_id'] = $request->input('account_id');
                }

                $prefix_type = 'purchase_payment';
                if (in_array($transaction->type, ['sell', 'sell_return'])) {
                    $prefix_type = 'sell_payment';
                } elseif ($transaction->type == 'expense') {
                    $prefix_type = 'expense_payment';
                }

                if(!auth()->user()->can('sell.confirm_bank_transfer_method') && $request->input('method') == 'bank_transfer'){
                    $inputs['approval_status'] = 'pending';
                }else{
                    $inputs['approval_status'] = 'approved';
                }

                $inputs['cashier_confirmed_id'] = auth()->user()->id;
                $inputs['business_id'] = $request->session()->get('business.id');
                $inputs['document'] = $this->transactionUtil->uploadFile($request, 'document', 'documents');

//                DB::beginTransaction();
                $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);
                //Generate reference number
                $inputs['payment_ref_no'] = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

                $tp = TransactionPayment::create($inputs);

                //update payment status
                $this->transactionUtil->updatePaymentStatus($transaction_id, $transaction->final_total);

                $notificationUtil = new NotificationUtil();
                $notificationUtil->transactionPaymentNotification($tp);

                $inputs['transaction_type'] = $transaction->type;
                event(new TransactionPaymentAdded($tp, $inputs));
//                DB::commit();

                $output = [
                    'success' => true,
                    'msg' => $inputs['approval_status'] == 'approved' ? __('purchase.payment_updated_success') : __('purchase.bank_transfer_pending'),
                ];
            }else{
                $output = ['success' => false,
                    'msg' => __('sale.order_has_been_paid')
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!auth()->user()->can('purchase.create') && !auth()->user()->can('sell.create') && !auth()->user()->can('sell.receipt_expense')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $transaction = Transaction::where('id', $id)
                                        ->with(['contact', 'business', 'transaction_for'])
                                        ->first();
            $payments_query = TransactionPayment::where('transaction_id', $id);

            $accounts_enabled = false;
            if ($this->moduleUtil->isModuleEnabled('account')) {
                $accounts_enabled = true;
                $payments_query->with(['payment_account']);
            }

            $payments = $payments_query->get();

            $payment_types = $this->transactionUtil->payment_types();
            $approval_statuses = $this->productUtil->approvalStatuses();
            $today = Carbon::today()->toDateString();

            $canNotUpdate = $this->transactionUtil->canNotUpdate($transaction);
            $can_not_create_payment = $canNotUpdate['can_not_create_payment'];
            $can_not_approval_payment = $canNotUpdate['can_not_approval_payment'];

            return view('transaction_payment.show_payments')
                    ->with(compact('transaction', 'payments', 'payment_types', 'accounts_enabled', 'approval_statuses', 'today', 'can_not_create_payment', 'can_not_approval_payment'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('purchase.payments') && !auth()->user()->can('sell.payments')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $payment_line = TransactionPayment::findOrFail($id);

            $transaction = Transaction::where('id', $payment_line->transaction_id)
                                        ->where('business_id', $business_id)
                                        ->with(['contact', 'location'])
                                        ->first();

            $payment_types = $this->transactionUtil->payment_types($transaction->location);

            //Accounts
            $account_type = ($payment_line->method == 'cash' || $payment_line->method == 'other') ? 'cash' : 'bank';
            $accounts = $this->moduleUtil->accountsDropdown($business_id, true, false, true, $account_type);

            $show_reject_button = $payment_line->method == 'bank_transfer' && auth()->user()->can('sell.confirm_bank_transfer_method');

            //Set paid on
            if ($this->moduleUtil->isClosedEndOfDay()) {
                $paid_on = date('Y-m-d', strtotime('now +1 days'));
                $paid_on .= ' 00:00';
            }else{
                $paid_on = date('Y-m-d H:i:s');
            }

            return view('transaction_payment.edit_payment_row')
                        ->with(compact('transaction', 'payment_types', 'payment_line', 'accounts', 'show_reject_button', 'paid_on'));
        }
    }

    public function editNormal($id)
    {
        if (!auth()->user()->can('purchase.create') && !auth()->user()->can('sell.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $payment_line = TransactionPayment::findOrFail($id);
            $today = Carbon::now();

            $transaction = Transaction::where('id', $payment_line->transaction_id)
                ->where('business_id', $business_id)
                ->with(['contact', 'location'])
                ->first();

            $payment_types = $this->transactionUtil->payment_types($transaction->location);

            //Accounts
            $account_type = ($payment_line->method == 'cash' || $payment_line->method == 'other') ? 'cash' : 'bank';
            $accounts = $this->moduleUtil->accountsDropdown($business_id, true, false, true, $account_type);

            $show_reject_button = $payment_line->method == 'bank_transfer' && auth()->user()->can('sell.confirm_bank_transfer_method');

            //Set paid on
            if ($this->moduleUtil->isClosedEndOfDay()) {
                $paid_on = date('Y-m-d', strtotime('now +1 days'));
                $paid_on .= ' 00:00';
            }else{
                $paid_on = date('Y-m-d H:i:s');
            }

            return view('sell_of_cashier.partials.edit_normal_payment_row')
                ->with(compact('transaction', 'payment_types', 'payment_line', 'accounts', 'today', 'show_reject_button', 'paid_on'));
        }
    }

    public function editDeposit($id)
    {
        if (!auth()->user()->can('sell.payments') && !auth()->user()->can('sell.receipt_expense')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $payment_line = TransactionPayment::findOrFail($id);
            $today = Carbon::now();

            $transaction = Transaction::where('id', $payment_line->transaction_id)
                ->where('business_id', $business_id)
                ->with(['contact', 'location'])
                ->first();

            $payment_types = $this->transactionUtil->payment_types($transaction->location);

            //Accounts
            $account_type = ($payment_line->method == 'cash' || $payment_line->method == 'other') ? 'cash' : 'bank';
            $accounts = $this->moduleUtil->accountsDropdown($business_id, true, false, true, $account_type);

            $show_reject_button = $payment_line->method == 'bank_transfer' && auth()->user()->can('sell.confirm_bank_transfer_method');

            //Set paid on
            if ($this->moduleUtil->isClosedEndOfDay()) {
                $paid_on = date('Y-m-d', strtotime('now +1 days'));
                $paid_on .= ' 00:00';
            }else{
                $paid_on = date('Y-m-d H:i:s');
            }

            return view('sell_of_cashier.partials.edit_deposit_payment_row')
                ->with(compact('transaction', 'payment_types', 'payment_line', 'accounts', 'today', 'show_reject_button', 'paid_on'));
        }
    }

    public function editCod($id)
    {
        if (!auth()->user()->can('sell.payments') && !auth()->user()->can('sell.receipt_expense')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $payment_line = TransactionPayment::findOrFail($id);
            $today = Carbon::now();

            $transaction = Transaction::where('id', $payment_line->transaction_id)
                ->where('business_id', $business_id)
                ->with(['contact', 'location'])
                ->first();

            $payment_types = $this->transactionUtil->payment_types($transaction->location);

            //Accounts
            $account_type = ($payment_line->method == 'cash' || $payment_line->method == 'other') ? 'cash' : 'bank';
            $accounts = $this->moduleUtil->accountsDropdown($business_id, true, false, true, $account_type);

            $show_reject_button = $payment_line->method == 'bank_transfer' && auth()->user()->can('sell.confirm_bank_transfer_method');

            //Set paid on
            if ($this->moduleUtil->isClosedEndOfDay()) {
                $paid_on = date('Y-m-d', strtotime('now +1 days'));
                $paid_on .= ' 00:00';
            }else{
                $paid_on = date('Y-m-d H:i:s');
            }

            return view('sell_of_cashier.partials.edit_cod_payment_row')
                ->with(compact('transaction', 'payment_types', 'payment_line', 'accounts', 'today', 'show_reject_button', 'paid_on'));
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
        if (!auth()->user()->can('purchase.payments') && !auth()->user()->can('sell.payments')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $inputs = $request->only(['amount', 'method', 'note', 'card_number', 'card_holder_name',
            'card_transaction_number', 'card_type', 'card_month', 'card_year', 'card_security',
            'cheque_number', 'bank_account_number']);
            $inputs['paid_on'] = $this->transactionUtil->uf_date($request->input('paid_on'), true);
            $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);

            if (!empty($request->input('account_id'))) {
                $inputs['account_id'] = $request->input('account_id');
            }

            $payment = TransactionPayment::findOrFail($id);

            if(!empty($request->input('confirmed')) && $request->input('confirmed')){
                //TODO: Appcept payment
                //Update parent payment if exists
                if (!empty($payment->parent_id)) {
                    $parent_payment = TransactionPayment::find($payment->parent_id);
                    $parent_payment->amount = $parent_payment->amount - ($payment->amount - $inputs['amount']);

                    $parent_payment->save();
                }

                $business_id = $request->session()->get('user.business_id');

                $transaction = Transaction::where('business_id', $business_id)
                                    ->find($payment->transaction_id);
                $document_name = $this->transactionUtil->uploadFile($request, 'document', 'documents');
                if (!empty($document_name)) {
                    $inputs['document'] = $document_name;
                }

                if(!auth()->user()->can('sell.confirm_bank_transfer_method') && $request->input('method') == 'bank_transfer'){
                    $inputs['approval_status'] = 'pending';
                    $inputs['cashier_confirmed_id'] = auth()->user()->id;
                }else{
                    $inputs['approval_status'] = 'approved';
                    if($payment->cashier_confirmed_id){
                        $inputs['admin_confirmed_id'] = auth()->user()->id;
                    }else{
                        $inputs['cashier_confirmed_id'] = auth()->user()->id;
                    }
                }

                DB::beginTransaction();

                $payment->update($inputs);

                //update payment status
                $this->transactionUtil->updatePaymentStatus($payment->transaction_id);

                $notificationUtil = new NotificationUtil();
                $notificationUtil->transactionPaymentNotification($payment);

                DB::commit();

                //event
                event(new TransactionPaymentUpdated($payment, $transaction->type));

                $output = [
                    'success' => true,
                    'msg' => $inputs['approval_status'] == 'approved' ? __('purchase.payment_updated_success') : __('purchase.bank_transfer_pending'),
                ];
            }else{
                //TODO: Reject payment
                DB::beginTransaction();
                $payment->delete();

                $account_transaction = AccountTransaction::where('transaction_payment_id', $payment->id)->first();
                if($account_transaction){
                    $account_transaction->delete();
                }

                //update payment status
                $this->transactionUtil->updatePaymentStatus($payment->transaction_id);
                DB::commit();

                $output = ['success' => true,
                    'msg' => __('purchase.payment_reject_success')
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => false,
                          'msg' => __('messages.something_went_wrong')
                      ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    public function updateNormal(Request $request, $id)
    {
        if (!auth()->user()->can('purchase.payments') && !auth()->user()->can('sell.payments')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $inputs = $request->only(['amount', 'type', 'method', 'note', 'card_number', 'card_holder_name',
                'card_transaction_number', 'card_type', 'card_month', 'card_year', 'card_security',
                'cheque_number', 'bank_account_number']);
            $inputs['paid_on'] = $this->transactionUtil->uf_date($request->input('paid_on'), true);
            $inputs['amount'] = $inputs['type'] == 'expense' ? (-1*$this->transactionUtil->num_uf($inputs['amount'])) : $this->transactionUtil->num_uf($inputs['amount']);

            if (!empty($request->input('account_id'))) {
                $inputs['account_id'] = $request->input('account_id');
            }

            $payment = TransactionPayment::findOrFail($id);

            if(!empty($request->input('confirmed')) && $request->input('confirmed')){
                //TODO: Appcept payment
                //Update parent payment if exists
                if (!empty($payment->parent_id)) {
                    $parent_payment = TransactionPayment::find($payment->parent_id);
                    $parent_payment->amount = $parent_payment->amount - ($payment->amount - $inputs['amount']);

                    $parent_payment->save();
                }

                $business_id = $request->session()->get('user.business_id');

                $transaction = Transaction::where('business_id', $business_id)
                    ->find($payment->transaction_id);
                $document_name = $this->transactionUtil->uploadFile($request, 'document', 'documents');
                if (!empty($document_name)) {
                    $inputs['document'] = $document_name;
                }

                if(!auth()->user()->can('sell.confirm_bank_transfer_method') && $request->input('method') == 'bank_transfer'){
                    $inputs['approval_status'] = 'pending';
                    $inputs['cashier_confirmed_id'] = auth()->user()->id;
                }else{
                    $inputs['approval_status'] = 'approved';
                    if($payment->cashier_confirmed_id){
                        $inputs['admin_confirmed_id'] = auth()->user()->id;
                    }else{
                        $inputs['cashier_confirmed_id'] = auth()->user()->id;
                    }
                }

                DB::beginTransaction();

                $payment->update($inputs);

                //update payment status
                $this->transactionUtil->updatePaymentStatus($payment->transaction_id);

                DB::commit();

                //event
                event(new TransactionPaymentUpdated($payment, $transaction->type));

                $output = [
                    'success' => true,
                    'msg' => $inputs['approval_status'] == 'approved' ? __('purchase.payment_updated_success') : __('purchase.bank_transfer_pending'),
                ];
            }else{
                //TODO: Reject payment
                DB::beginTransaction();
                $payment->delete();

                $account_transaction = AccountTransaction::where('transaction_payment_id', $payment->id)->first();
                if($account_transaction){
                    $account_transaction->delete();
                }

                //update payment status
                $this->transactionUtil->updatePaymentStatus($payment->transaction_id);
                DB::commit();

                $output = ['success' => true,
                    'msg' => __('purchase.payment_reject_success')
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    public function updateDeposit(Request $request, $id)
    {
        if (!auth()->user()->can('purchase.payments') && !auth()->user()->can('sell.payments')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $inputs = $request->only(['amount', 'method', 'note', 'card_number', 'card_holder_name',
                'card_transaction_number', 'card_type', 'card_month', 'card_year', 'card_security',
                'cheque_number', 'bank_account_number']);
            $inputs['paid_on'] = $this->transactionUtil->uf_date($request->input('paid_on'), true);
            $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);

            if (!empty($request->input('account_id'))) {
                $inputs['account_id'] = $request->input('account_id');
            }

            $payment = TransactionPayment::findOrFail($id);

            if(!empty($request->input('confirmed')) && $request->input('confirmed')){
                //TODO: Accept payment
                //Update parent payment if exists
                if (!empty($payment->parent_id)) {
                    $parent_payment = TransactionPayment::find($payment->parent_id);
                    $parent_payment->amount = $parent_payment->amount - ($payment->amount - $inputs['amount']);

                    $parent_payment->save();
                }

                $business_id = $request->session()->get('user.business_id');

                $transaction = Transaction::where('business_id', $business_id)
                    ->find($payment->transaction_id);
                $document_name = $this->transactionUtil->uploadFile($request, 'document', 'documents');
                if (!empty($document_name)) {
                    $inputs['document'] = $document_name;
                }

                if(!auth()->user()->can('sell.confirm_bank_transfer_method') && $request->input('method') == 'bank_transfer'){

                    if (empty($payment) || $payment->approval_status != 'unapproved') {
                        return redirect()->back()->with('status', ['success' => 0, 'msg' => __('purchase.payment_was_added')]);
                    }

                    $inputs['approval_status'] = 'pending';
                    $inputs['cashier_confirmed_id'] = auth()->user()->id;
                }else{
                    if (empty($payment) || $payment->approval_status == 'approved') {
                        return redirect()->back()->with('status', ['success' => 0, 'msg' => __('purchase.payment_was_approved')]);
                    }

                    $inputs['approval_status'] = 'approved';
                    if($payment->cashier_confirmed_id){
                        $inputs['admin_confirmed_id'] = auth()->user()->id;
                    }else{
                        $inputs['cashier_confirmed_id'] = auth()->user()->id;
                    }
                }

                DB::beginTransaction();

                $payment->update($inputs);

                //update payment status
                $this->transactionUtil->updatePaymentStatus($payment->transaction_id);

                $notificationUtil = new NotificationUtil();
                $notificationUtil->transactionPaymentNotification($payment);

                DB::commit();

                //event
                event(new TransactionPaymentUpdated($payment, $transaction->type));

                $output = [
                    'success' => true,
                    'msg' => $inputs['approval_status'] == 'approved' ? __('purchase.payment_updated_success') : __('purchase.bank_transfer_pending'),
                ];
            }else{
                //TODO: Reject payment
                DB::beginTransaction();
                $payment->delete();

                $account_transaction = AccountTransaction::where('transaction_payment_id', $payment->id)->first();
                if($account_transaction){
                    $account_transaction->delete();
                }

                //update payment status
                $this->transactionUtil->updatePaymentStatus($payment->transaction_id);
                DB::commit();

                $output = ['success' => true,
                    'msg' => __('purchase.deposit_reject_success')
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    public function updateCod(Request $request, $id)
    {
        if (!auth()->user()->can('purchase.payments') && !auth()->user()->can('sell.payments')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $inputs = $request->only(['amount', 'method', 'note', 'card_number', 'card_holder_name',
                'card_transaction_number', 'card_type', 'card_month', 'card_year', 'card_security',
                'cheque_number', 'bank_account_number']);
            $inputs['paid_on'] = $this->transactionUtil->uf_date($request->input('paid_on'), true);
            $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);

            if (!empty($request->input('account_id'))) {
                $inputs['account_id'] = $request->input('account_id');
            }

            $payment = TransactionPayment::findOrFail($id);

            if(!empty($request->input('confirmed')) && $request->input('confirmed')){
                //TODO: Accept cod payment
                //Update parent payment if exists
                if (!empty($payment->parent_id)) {
                    $parent_payment = TransactionPayment::find($payment->parent_id);
                    $parent_payment->amount = $parent_payment->amount - ($payment->amount - $inputs['amount']);

                    $parent_payment->save();
                }

                $business_id = $request->session()->get('user.business_id');

                $transaction = Transaction::where('business_id', $business_id)
                    ->find($payment->transaction_id);
                $document_name = $this->transactionUtil->uploadFile($request, 'document', 'documents');
                if (!empty($document_name)) {
                    $inputs['document'] = $document_name;
                }

                if(!auth()->user()->can('sell.confirm_bank_transfer_method') && $request->input('method') == 'bank_transfer'){

                    if (empty($payment) || $payment->approval_status != 'unapproved') {
                        return redirect()->back()->with('status', ['success' => 0, 'msg' => __('purchase.payment_was_added')]);
                    }

                    $inputs['approval_status'] = 'pending';
                    $inputs['cashier_confirmed_id'] = auth()->user()->id;
                }else{
                    if (empty($payment) || $payment->approval_status == 'approved') {
                        return redirect()->back()->with('status', ['success' => 0, 'msg' => __('purchase.payment_was_approved')]);
                    }

                    $inputs['approval_status'] = 'approved';
                    if($payment->cashier_confirmed_id){
                        $inputs['admin_confirmed_id'] = auth()->user()->id;
                    }else{
                        $inputs['cashier_confirmed_id'] = auth()->user()->id;
                    }
                }

                DB::beginTransaction();

                $payment->update($inputs);

                //update payment status
                $this->transactionUtil->updatePaymentStatus($payment->transaction_id);

                /*$final_amount = TransactionPayment::where('transaction_id', $payment->transaction_id)
                    ->sum('amount');
                $final_amount += $payment->amount;
                $this->transactionUtil->updatePaymentStatus($payment->transaction_id, $final_amount);*/

                $notificationUtil = new NotificationUtil();
                $notificationUtil->transactionPaymentNotification($payment);

                DB::commit();

                //event
                event(new TransactionPaymentUpdated($payment, $transaction->type));

                $output = [
                    'success' => true,
                    'msg' => $inputs['approval_status'] == 'approved' ? __('purchase.payment_updated_success') : __('purchase.bank_transfer_pending'),
                ];
            }else{
                //TODO: Reject cod payment
                DB::beginTransaction();
                $payment->delete();

                $account_transaction = AccountTransaction::where('transaction_payment_id', $payment->id)->first();
                if($account_transaction){
                    $account_transaction->delete();
                }

                //update payment status
                $this->transactionUtil->updatePaymentStatus($payment->transaction_id);
                DB::commit();

                $output = ['success' => true,
                    'msg' => __('purchase.cod_reject_success')
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    public function createPaymentWithCod($transaction, $insert_data)
    {
        if (!is_object($transaction)) {
            $transaction = Transaction::findOrFail($transaction);
        }

        $prefix_type = 'sell_payment';
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('purchase.payments') && !auth()->user()->can('sell.payments')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $payment = TransactionPayment::findOrFail($id);

                //Update parent payment if exists
                if (!empty($payment->parent_id)) {
                    $parent_payment = TransactionPayment::find($payment->parent_id);
                    $parent_payment->amount -= $payment->amount;

                    if ($parent_payment->amount <= 0) {
                        $parent_payment->delete();
                    } else {
                        $parent_payment->save();
                    }
                }

                $payment->delete();

                $account_transaction = AccountTransaction::where('transaction_payment_id', $payment->id)->first();
                if($account_transaction){
                    $account_transaction->delete();
                }

                //update payment status
                $this->transactionUtil->updatePaymentStatus($payment->transaction_id);

                event(new TransactionPaymentDeleted($payment->id, $payment->account_id));

                $output = ['success' => true,
                                'msg' => __('purchase.payment_deleted_success')
                            ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output = ['success' => false,
                                'msg' => __('messages.something_went_wrong')
                            ];
            }

            return $output;
        }
    }

    /**
     * Adds new payment to the given transaction.
     *
     * @param  int  $transaction_id
     * @return \Illuminate\Http\Response
     */
    public function addPayment($transaction_id)
    {
        if (!auth()->user()->can('purchase.payments') && !auth()->user()->can('sell.payments')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $transaction = Transaction::where('id', $transaction_id)
                                        ->where('business_id', $business_id)
                                        ->with(['contact', 'location'])
                                        ->first();
            if ($transaction->payment_status != 'paid') {
                $payment_types = $this->transactionUtil->payment_types($transaction->location);

                $paid_amount = $this->transactionUtil->getTotalPaid($transaction_id);
                $amount = $transaction->final_total - $paid_amount;
                if ($amount < 0) {
                    $amount = 0;
                }

                $amount_formated = $this->transactionUtil->num_f($amount);

                $payment_line = new TransactionPayment();
                $payment_line->amount = $amount;
                $payment_line->method = 'cash';

                //Set paid on
                if ($this->moduleUtil->isClosedEndOfDay()) {
                    $paid_on = date('Y-m-d', strtotime('now +1 days'));
                    $paid_on .= ' 00:00';
                }else{
                    $paid_on = date('Y-m-d H:i:s');
                }
                $payment_line->paid_on = $paid_on;

                //Accounts
                $accounts = $this->moduleUtil->accountsDropdown($business_id, true, false, true, 'cash');


                $view = view('transaction_payment.payment_row')
                    ->with(compact('transaction', 'payment_types', 'payment_line', 'amount_formated', 'accounts', 'paid_on'))->render();

                $output = [ 'status' => 'due',
                                    'view' => $view];
            } else {
                $output = [ 'status' => 'paid',
                                'view' => '',
                                'msg' => __('purchase.amount_already_paid')  ];
            }

            return json_encode($output);
        }
    }

    public function approvePayment($payment_id){
        if (!auth()->user()->can('sell.accept_received_money_to_custom') && !auth()->user()->can('sell.confirm_bank_transfer_method')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $payment = TransactionPayment::find($payment_id);

                if(auth()->user()->can('sell.confirm_bank_transfer_method') && $payment->method == 'bank_transfer'){
                    $today = Carbon::today()->toDateString();
                    $total_money_on_day = $this->transactionUtil->calculateTotalMoneyOnDay($today);
                    $update_total_bank = $total_money_on_day['total_money_payment_bank'];
                    $payment->approval_status = 'approved';
                    $payment->admin_confirmed_id = auth()->user()->id;
                    $payment->save();

                    $notificationUtil = new NotificationUtil();
                    $notificationUtil->transactionPaymentNotification($payment);

                    $output = ['success' => true,
                        'msg' => __("purchase.accepted_confirm_bank_transfer_success"),
                        'data' => [
                            'update_total_bank' => $update_total_bank
                        ]
                    ];
                }else{
                    $output = ['success' => false,
                        'msg' => __("purchase.not_allow_confirm_bank_transfer")
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

        return [
            'code' => 403,
            'message' => 'Unauthorized action.'
        ];
    }

    public function rejectPayment($payment_id){
        if (!auth()->user()->can('sell.confirm_bank_transfer_method')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $payment = TransactionPayment::find($payment_id);

                if(auth()->user()->can('sell.confirm_bank_transfer_method') && $payment->method == 'bank_transfer'){
                    DB::beginTransaction();
                    $payment->delete();

                    $account_transaction = AccountTransaction::where('transaction_payment_id', $payment->id)->first();
                    if($account_transaction){
                        $account_transaction->delete();
                    }

                    //update payment status
                    $this->transactionUtil->updatePaymentStatus($payment->transaction_id);
                    DB::commit();

                    $output = ['success' => true,
                        'msg' => __('purchase.payment_reject_success')
                    ];
                }else{
                    $output = ['success' => false,
                        'msg' => __("purchase.not_allow_reject_bank_transfer")
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

        return [
            'code' => 403,
            'message' => 'Unauthorized action.'
        ];
    }

    public function addRemaining($transaction_id)
    {
        if (!auth()->user()->can('purchase.payments') && !auth()->user()->can('sell.payments')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $transaction = Transaction::where('id', $transaction_id)
                ->where('business_id', $business_id)
                ->with(['contact', 'location', 'business', 'transaction_for'])
                ->first();

            $approval_status = TransactionPayment::where('transaction_id', $transaction_id)
                ->where('type', '<>', 'cod')
                ->where('type', '<>', 'deposit')
                ->where('approval_status', 'pending')
                ->select(DB::raw('IF((SELECT SUM(IF(is_return = 1, -1 * amount, amount)) FROM transaction_payments AS TP WHERE
                        transaction_id='. $transaction_id .') >= '. $transaction->final_total .', IF(COUNT(1) > 0, "pending", "approved"), IF(COUNT(1) > 0, "pending", "unapproved")) AS approval_status'))
                ->first()
                ->approval_status;

            if($approval_status == 'pending'){
                $payments = TransactionPayment::where('transaction_id', $transaction_id)
                    ->where('type', '<>', 'cod')
                    ->where('type', '<>', 'deposit')
                    ->get();

                $payment_types = $this->transactionUtil->payment_types();
                $approval_statuses = $this->productUtil->approvalStatuses();

                $view = view('sell_of_cashier.partials.show_payments')
                    ->with(compact('transaction', 'payments', 'payment_types', 'approval_statuses'))->render();

                $output = [ 'status' => 'due',
                    'view' => $view];
            }else{
                $payment_types = $this->transactionUtil->payment_types($transaction->location);

                $paid_amount = $this->transactionUtil->getTotalPaidV1($transaction_id);
                $amount = $transaction->final_total - $paid_amount;
                if ($amount < 0) {
                    $amount = 0;
                }

                $amount_formated = $this->transactionUtil->num_f($amount);

                $payment_line = new TransactionPayment();
                $payment_line->amount = $amount;
                $payment_line->method = 'cash';

                //Set paid on
                if ($this->moduleUtil->isClosedEndOfDay()) {
                    $paid_on = date('Y-m-d', strtotime('now +1 days'));
                    $paid_on .= ' 00:00';
                }else{
                    $paid_on = date('Y-m-d H:i:s');
                }
                $payment_line->paid_on = $paid_on;

                //Accounts
                $accounts = $this->moduleUtil->accountsDropdown($business_id, true, false, true, 'cash');

                $view = view('sell_of_cashier.partials.remaining_payment_row')
                    ->with(compact('transaction', 'payment_types', 'payment_line', 'amount_formated', 'accounts'))->render();

                $output = [ 'status' => 'due',
                    'view' => $view];
            }

            /*if ($transaction->payment_status != 'paid') {

            } else {
                $output = [ 'status' => 'paid',
                    'view' => '',
                    'msg' => __('purchase.amount_already_paid')  ];
            }*/

            return json_encode($output);
        }
    }

    /**
     * Shows contact's payment due modal
     *
     * @param  int  $contact_id
     * @return \Illuminate\Http\Response
     */
    public function getPayContactDue($contact_id)
    {
        if (!auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $due_payment_type = request()->input('type');
            $query = Contact::where('contacts.id', $contact_id)
                            ->join('transactions AS t', 'contacts.id', '=', 't.contact_id');
            if ($due_payment_type == 'purchase') {
                $query->select(
                    DB::raw("SUM(IF(t.type = 'purchase', final_total, 0)) as total_purchase"),
                    DB::raw("SUM(IF(t.type = 'purchase', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as total_paid"),
                    'contacts.name',
                    'contacts.supplier_business_name',
                    'contacts.id as contact_id'
                    );
            } elseif ($due_payment_type == 'purchase_return') {
                $query->select(
                    DB::raw("SUM(IF(t.type = 'purchase_return', final_total, 0)) as total_purchase_return"),
                    DB::raw("SUM(IF(t.type = 'purchase_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as total_return_paid"),
                    'contacts.name',
                    'contacts.supplier_business_name',
                    'contacts.id as contact_id'
                    );
            } elseif ($due_payment_type == 'sell') {
                $query->select(
                    DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', final_total, 0)) as total_invoice"),
                    DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as total_paid"),
                    'contacts.name',
                    'contacts.supplier_business_name',
                    'contacts.id as contact_id'
                );
            } elseif ($due_payment_type == 'sell_return') {
                $query->select(
                    DB::raw("SUM(IF(t.type = 'sell_return', final_total, 0)) as total_sell_return"),
                    DB::raw("SUM(IF(t.type = 'sell_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as total_return_paid"),
                    'contacts.name',
                    'contacts.supplier_business_name',
                    'contacts.id as contact_id'
                    );
            }

            //Query for opening balance details
            $query->addSelect(
                DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance"),
                DB::raw("SUM(IF(t.type = 'opening_balance', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as opening_balance_paid")
            );
            $contact_details = $query->first();

            $payment_line = new TransactionPayment();
            if ($due_payment_type == 'purchase') {
                $contact_details->total_purchase = empty($contact_details->total_purchase) ? 0 : $contact_details->total_purchase;
                $payment_line->amount = $contact_details->total_purchase -
                                    $contact_details->total_paid;
            } elseif ($due_payment_type == 'purchase_return') {
                $payment_line->amount = $contact_details->total_purchase_return -
                                    $contact_details->total_return_paid;
            } elseif ($due_payment_type == 'sell') {
                $contact_details->total_invoice = empty($contact_details->total_invoice) ? 0 : $contact_details->total_invoice;

                $payment_line->amount = $contact_details->total_invoice -
                                    $contact_details->total_paid;
            } elseif ($due_payment_type == 'sell_return') {
                $payment_line->amount = $contact_details->total_sell_return -
                                    $contact_details->total_return_paid;
            }

            //If opening balance due exists add to payment amount
            $contact_details->opening_balance = !empty($contact_details->opening_balance) ? $contact_details->opening_balance : 0;
            $contact_details->opening_balance_paid = !empty($contact_details->opening_balance_paid) ? $contact_details->opening_balance_paid : 0;
            $ob_due = $contact_details->opening_balance - $contact_details->opening_balance_paid;
            if ($ob_due > 0) {
                $payment_line->amount += $ob_due;
            }

            $amount_formated = $this->transactionUtil->num_f($payment_line->amount);

            $contact_details->total_paid = empty($contact_details->total_paid) ? 0 : $contact_details->total_paid;

            $payment_line->method = 'cash';
            $payment_line->paid_on = \Carbon::now()->toDateTimeString();

            $payment_types = $this->transactionUtil->payment_types();

            //Accounts
            $accounts = $this->moduleUtil->accountsDropdown($business_id, true, false, false, 'cash');

            if ($payment_line->amount > 0) {
                return view('transaction_payment.pay_supplier_due_modal')
                        ->with(compact('contact_details', 'payment_types', 'payment_line', 'due_payment_type', 'ob_due', 'amount_formated', 'accounts'));
            }
        }
    }

    /**
     * Adds Payments for Contact due
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postPayContactDue(Request  $request)
    {
        if (!auth()->user()->can('purchase.create') && !auth()->user()->can('sell.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $contact_id = $request->input('contact_id');
            $inputs = $request->only(['amount', 'method', 'note', 'card_number', 'card_holder_name',
                'card_transaction_number', 'card_type', 'card_month', 'card_year', 'card_security',
                'cheque_number', 'bank_account_number']);
            $inputs['paid_on'] = $this->transactionUtil->uf_date($request->input('paid_on'), true);
            $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);
            $inputs['created_by'] = auth()->user()->id;
            $inputs['payment_for'] = $contact_id;
            $inputs['business_id'] = $request->session()->get('business.id');

//            if ($inputs['method'] == 'custom_pay_1') {
//                $inputs['transaction_no'] = $request->input('transaction_no_1');
//            } elseif ($inputs['method'] == 'custom_pay_2') {
//                $inputs['transaction_no'] = $request->input('transaction_no_2');
//            } elseif ($inputs['method'] == 'custom_pay_3') {
//                $inputs['transaction_no'] = $request->input('transaction_no_3');
//            }
            $due_payment_type = $request->input('due_payment_type');

            $prefix_type = 'purchase_payment';
            if (in_array($due_payment_type, ['sell', 'sell_return'])) {
                $prefix_type = 'sell_payment';
            }
            $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);
            //Generate reference number
            $payment_ref_no = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

            $inputs['payment_ref_no'] = $payment_ref_no;

            if (!empty($request->input('account_id'))) {
                $inputs['account_id'] = $request->input('account_id');
            }

            //Upload documents if added
            $inputs['document'] = $this->transactionUtil->uploadFile($request, 'document', 'documents');

            DB::beginTransaction();

            $parent_payment = TransactionPayment::create($inputs);

            $inputs['transaction_type'] = $due_payment_type;

            event(new TransactionPaymentAdded($parent_payment, $inputs));

            //Distribute above payment among unpaid transactions

            $this->transactionUtil->payAtOnce($parent_payment, $due_payment_type);

            DB::commit();
            $output = ['success' => true,
                            'msg' => __('purchase.payment_added_success')
                        ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => false,
                          'msg' => __('messages.something_went_wrong')
                      ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    /**
     * view details of single..,
     * payment.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function viewPayment($payment_id)
    {
        if (!auth()->user()->can('purchase.payments') && !auth()->user()->can('sell.payments')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('business.id');
            $single_payment_line = TransactionPayment::findOrFail($payment_id);

            $transaction = null;
            if (!empty($single_payment_line->transaction_id)) {
                $transaction = Transaction::where('id', $single_payment_line->transaction_id)
                                ->with(['contact', 'location', 'transaction_for'])
                                ->first();
            } else {
                $child_payment = TransactionPayment::where('business_id', $business_id)
                        ->where('parent_id', $payment_id)
                        ->with(['transaction', 'transaction.contact', 'transaction.location', 'transaction.transaction_for'])
                        ->first();
                $transaction = $child_payment->transaction;
            }

            $payment_types = $this->transactionUtil->payment_types();
            $user_confirm = $this->transactionUtil->getUserConfirmPayment($single_payment_line->cashier_confirmed_id, $single_payment_line->admin_confirmed_id);
            $approval_statuses = $this->productUtil->approvalStatuses();

            return view('transaction_payment.single_payment_view')
                    ->with(compact('single_payment_line', 'transaction', 'payment_types', 'user_confirm', 'approval_statuses'));
        }
    }

    /**
     * Retrieves all the child payments of a parent payments
     * payment.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function showChildPayments($payment_id)
    {
        if (!auth()->user()->can('purchase.payments') && !auth()->user()->can('sell.payments')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('business.id');

            $child_payments = TransactionPayment::where('business_id', $business_id)
                                                    ->where('parent_id', $payment_id)
                                                    ->with(['transaction', 'transaction.contact'])
                                                    ->get();

            $payment_types = $this->transactionUtil->payment_types();

            return view('transaction_payment.show_child_payments')
                    ->with(compact('child_payments', 'payment_types'));
        }
    }

    /**
    * Retrieves list of all opening balance payments.
    *
    * @param  int  $contact_id
    * @return \Illuminate\Http\Response
    */

    public function getOpeningBalancePayments($contact_id)
    {
        if (!auth()->user()->can('purchase.payments') && !auth()->user()->can('sell.payments')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('business.id');
        if (request()->ajax()) {
            $query = TransactionPayment::leftjoin('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'opening_balance')
                ->where('t.contact_id', $contact_id)
                ->where('transaction_payments.business_id', $business_id)
                ->select(
                    'transaction_payments.amount',
                    'method',
                    'paid_on',
                    'transaction_payments.payment_ref_no',
                    'transaction_payments.document',
                    'transaction_payments.id',
                    'cheque_number',
                    'card_transaction_number',
                    'bank_account_number'
                )
                ->groupBy('transaction_payments.id');


            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            return Datatables::of($query)
                ->editColumn('paid_on', '{{@format_datetime($paid_on)}}')
                ->editColumn('method', function ($row) {
                    $method = __('lang_v1.' . $row->method);
                    if ($row->method == 'bank_transfer') {
                        $method .= '<br>(' . __('lang_v1.bank_account_no') . ': ' . $row->bank_account_number . ')';
                    }
//                    if ($row->method == 'cheque') {
//                        $method .= '<br>(' . __('lang_v1.cheque_no') . ': ' . $row->cheque_number . ')';
//                    } elseif ($row->method == 'card') {
//                        $method .= '<br>(' . __('lang_v1.card_transaction_no') . ': ' . $row->card_transaction_number . ')';
//                    } elseif ($row->method == 'bank_transfer') {
//                        $method .= '<br>(' . __('lang_v1.bank_account_no') . ': ' . $row->bank_account_number . ')';
//                    } elseif ($row->method == 'custom_pay_1') {
//                        $method = __('lang_v1.custom_payment_1') . '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
//                    } elseif ($row->method == 'custom_pay_2') {
//                        $method = __('lang_v1.custom_payment_2') . '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
//                    } elseif ($row->method == 'custom_pay_3') {
//                        $method = __('lang_v1.custom_payment_3') . '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
//                    }
                    return $method;
                })
                ->editColumn('amount', function ($row) {
                    return '<span class="display_currency paid-amount" data-orig-value="' . $row->amount . '" data-currency_symbol = true>' . $row->amount . '</span>';
                })
                ->addColumn('action', '<button type="button" class="btn btn-primary btn-xs view_payment" data-href="{{ action("TransactionPaymentController@viewPayment", [$id]) }}"><i class="fas fa-eye"></i> @lang("messages.view")
                    </button> <button type="button" class="btn btn-info btn-xs edit_payment" 
                    data-href="{{action("TransactionPaymentController@edit", [$id]) }}"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                    &nbsp; <button type="button" class="btn btn-danger btn-xs delete_payment" 
                    data-href="{{ action("TransactionPaymentController@destroy", [$id]) }}"
                    ><i class="fa fa-trash" aria-hidden="true"></i> @lang("messages.delete")</button> @if(!empty($document))<a href="{{asset("/uploads/documents/" . $document)}}" class="btn btn-success btn-xs" download=""><i class="fa fa-download"></i> @lang("purchase.download_document")</a>@endif')
                ->rawColumns(['amount', 'method', 'action'])
                ->make(true);
        }
    }

    public function checkConfirmBankTransferPermission(){
        if (request()->ajax()) {
            if (auth()->user()->can('sell.confirm_bank_transfer_method')) {
                return [
                    'success' => true
                ];
            }
        }

        return [
            'success' => false
        ];
    }
}
