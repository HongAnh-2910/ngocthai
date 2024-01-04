<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Contact;
use App\Transaction;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ReduceDebtController extends Controller
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
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('sell.add_reduce_debt')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $transactions = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->leftJoin('users as u', 'transactions.created_by', '=', 'u.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'reduce_debt')
                ->select([
                    'transactions.status',
                    'transactions.id',
                    'transactions.transaction_date',
                    'contacts.id as customer_id',
                    'contacts.name',
                    'contacts.contact_id',
                    'transactions.final_total',
                    'transactions.additional_notes',
                    DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by"),
                ]);

            if (request()->has('created_by')) {
                $created_by = request()->get('created_by');
                if (!empty($created_by)) {
                    $transactions->where('transactions.created_by', $created_by);
                }
            }

            if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $transactions->where('contacts.id', $customer_id);
            }

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $transactions->whereDate('transactions.transaction_date', '>=', $start)
                    ->whereDate('transactions.transaction_date', '<=', $end);
            }

            if (!empty(request()->input('created_by'))) {
                $transactions->where('transactions.created_by', request()->input('created_by'));
            }

            if (!empty(request()->input('status'))) {
                $transactions->where('transactions.status', request()->input('status'));
            }

            $transactions->groupBy('transactions.id');

            $datatable = Datatables::of($transactions)
                ->removeColumn('id')
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn('name', function ($row) {
                    return '<a href="' . action("ContactController@show", [$row->customer_id]) . '" target="_blank">' . $row->name . '</a>';
                })
                ->editColumn(
                    'final_total', function ($row) {
                    return '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="' . $row->final_total . '">' . $row->final_total . '</span>';
                })
                ->editColumn('status', function ($row) {
                    $reduce_debt_statuses = $this->transactionUtil->reduce_debt_statuses();
                    $reduce_debt_colors = [
                        'pending' => 'bg-yellow',
                        'final' => 'bg-green',
                    ];
                    $html = '<span class="label ' . $reduce_debt_colors[$row->status] .'">' . $reduce_debt_statuses[$row->status] . '</span>';
//                    $html = '<a href="#" class="btn-modal" data-href="' . action('SellController@editShipping', [$row->id]) . '" data-container=".view_modal"><span class="label ' . $reduce_debt_colors[$row->status] .'">' . $reduce_debt_statuses[$row->status] . '</span></a>';

                    return $html;
                })
                ->addColumn(
                    'action','
                        <button data-href="{{action(\'ReduceDebtController@edit\', [$id])}}" class="btn btn-xs btn-primary edit_reduce_debt_button"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>&nbsp;
                        <button data-href="{{action(\'ReduceDebtController@destroy\', [$id])}}" class="btn btn-xs btn-danger delete_reduce_debt_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>'
                )
                ->addColumn('mass_action', function ($row) {
                    return  '<input type="checkbox" class="row-select" value="' . $row->id .'">' ;
                });

            $rawColumns = ['final_total', 'action', 'mass_action', 'name', 'status'];

            return $datatable->rawColumns($rawColumns)
                ->make(true);
        }

        $customers = Contact::customersDropdown($business_id, false);
        $users = User::forDropdown($business_id, false, false, true);
        $reduce_debt_statuses = $this->transactionUtil->reduce_debt_statuses();

        return view('reduce_debt.index')->with(compact('customers','users', 'reduce_debt_statuses'));
    }

    public function add($contact_id)
    {
        if (!auth()->user()->can('sell.add_reduce_debt')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $contact = Contact::find($contact_id);

        //Set transaction date
        if ($this->moduleUtil->isClosedEndOfDay()) {
            $transaction_date = date('Y-m-d', strtotime('now +1 days'));
            $transaction_date .= ' 00:00';
        }else{
            $transaction_date = date('Y-m-d H:i:s');
        }

        $customers = Contact::customersDropdown($business_id, false);

        return view('reduce_debt.create')
            ->with(compact('contact', 'transaction_date', 'customers'));
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
        if (!auth()->user()->can('sell.add_reduce_debt')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $inputs = $request->only(['contact_id', 'final_total', 'additional_notes']);
            $business_id = $request->session()->get('user.business_id');
            $inputs['business_id'] = $business_id;
            $inputs['created_by'] = $request->session()->get('user.id');
            $inputs['type'] = 'reduce_debt';
            $inputs['final_total'] = $this->transactionUtil->num_uf($inputs['final_total']);
            $inputs['status'] = auth()->user()->can('sell.confirm_reduce_debt') ? 'final' : 'pending';

            if (empty($request->input('transaction_date'))) {
                $inputs['transaction_date'] =  \Carbon::now();
            } else {
                $inputs['transaction_date'] = $this->productUtil->uf_date($request->input('transaction_date'), true);
            }

            Transaction::create($inputs);

            /*$notificationUtil = new NotificationUtil();
            $notificationUtil->transactionPaymentNotification($tp);*/

            $output = ['success' => true,
                'msg' => auth()->user()->can('sell.confirm_reduce_debt') ? __("contact.create_reduce_debt_success") : __("contact.create_reduce_debt_pending"),
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' => __("messages.something_went_wrong")
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
        if (!auth()->user()->can('sell.add_reduce_debt')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $transaction = Transaction::with('contact')
                ->find($id);

            //Check if closed end of day
            $current_date = date('Y-m-d');
            $transaction_date = date('Y-m-d', strtotime($transaction->transaction_date));
            if ($transaction->status != 'draft' && $this->moduleUtil->isClosedEndOfDay() && $transaction_date <= $current_date) {
                $output = ['success' => 0,
                    'msg' => __('messages.can_not_update_after_closed_app')
                ];

                return redirect('contacts/reduce-debts')->with('status', $output);
            }

            $customers = Contact::customersDropdown($business_id, false);

            return view('reduce_debt.edit')
                ->with(compact('transaction', 'customers'));
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
        if (!auth()->user()->can('sell.add_reduce_debt')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $inputs = $request->only(['contact_id', 'final_total', 'additional_notes']);
                $business_id = $request->session()->get('user.business_id');

                $transaction = Transaction::where('business_id', $business_id)->findOrFail($id);
                $transaction->final_total = $this->transactionUtil->num_uf($inputs['final_total']);
                $transaction->additional_notes = $inputs['additional_notes'];
                $transaction->contact_id = $inputs['contact_id'];
                $transaction->save();

                $output = ['success' => true,
                    'msg' => __("contact.update_reduce_debt_success")
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('sell.add_reduce_debt')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->user()->business_id;

                $transaction = Transaction::where('business_id', $business_id)->findOrFail($id);

                //Check if closed end of day
                $current_date = date('Y-m-d');
                $transaction_date = date('Y-m-d', strtotime($transaction->transaction_date));
                if ($transaction->status != 'draft' && $this->moduleUtil->isClosedEndOfDay() && $transaction_date <= $current_date) {
                    $output = ['success' => 0,
                        'msg' => __('messages.can_not_update_after_closed_app')
                    ];

                    return redirect('contacts/reduce-debts')->with('status', $output);
                }

                $transaction->delete();

                $output = ['success' => true,
                    'msg' => __("contact.delete_reduce_debt_success")
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

    public function confirmBulk(Request $request) {
        if (!auth()->user()->can('sell.confirm_reduce_debt')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $transaction_ids = $request->input('selected_rows');

            if (!empty($transaction_ids)) {
                $business_id = $request->session()->get('user.business_id');

                foreach ($transaction_ids as $key => $transaction_id) {
                    $transaction_ids[$key] = intval($transaction_id);
                }

                DB::beginTransaction();
                Transaction::whereIn('id', $transaction_ids)
                    ->update(['status' => 'final']);
                DB::commit();

                $output = response()->json([
                    'success' => 1,
                    'message' => __('contact.confirm_reduce_debt_success')
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }
}
