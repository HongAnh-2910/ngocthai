<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\PlateStock;
use App\Transaction;
use App\Contact;
use App\TransactionPayment;
use App\TransactionPlateLine;
use App\TransactionPlateLinesReturn;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ContactUtil;

use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class SellReturnController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $productUtil;
    protected $transactionUtil;
    protected $contactUtil;
    protected $businessUtil;
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil, ContactUtil $contactUtil, BusinessUtil $businessUtil, ModuleUtil $moduleUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->contactUtil = $contactUtil;
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('return.list') && !auth()->user()->can('sell.receipt_expense')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            $sells = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')

                    ->join(
                        'business_locations AS bl',
                        'transactions.location_id',
                        '=',
                        'bl.id'
                    )
                    ->join(
                        'transactions as T1',
                        'transactions.return_parent_id',
                        '=',
                        'T1.id'
                    )
                    ->leftJoin(
                        'transaction_payments AS TP',
                        'transactions.id',
                        '=',
                        'TP.transaction_id'
                    )
                    ->where('transactions.business_id', $business_id)
                    ->where('transactions.type', 'sell_return')
//                    ->where('transactions.status', 'final')
                    ->select(
                        'transactions.id',
                        'transactions.return_parent_id',
                        'transactions.transaction_date',
                        'transactions.invoice_no',
                        'transactions.discount_amount',
                        'transactions.discount_type',
                        'transactions.return_note',
                        'transactions.status',
                        'contacts.id as customer_id',
                        'contacts.name',
                        'contacts.contact_id',
                        'transactions.final_total',
                        'transactions.payment_status',
                        'TP.method',
                        'TP.approval_status',
                        'bl.name as business_location',
                        'T1.invoice_no as parent_sale',
                        'T1.id as parent_sale_id',
                        DB::raw("SUM(IF(TP.approval_status = 'approved', TP.amount, 0)) as amount_paid"),
                        DB::raw("(transactions.final_total - SUM(IF(TP.approval_status = 'approved', TP.amount, 0))) as payment_due")
                    );

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

            //Add condition for location,used in sales representative expense report
            if (request()->has('location_id')) {
                $location_id = request()->get('location_id');
                if (!empty($location_id)) {
                    $sells->where('transactions.location_id', $location_id);
                }
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

            $sells->groupBy('transactions.id');

            return Datatables::of($sells)
                ->addColumn(
                    'action', function ($row) {
                        $html = '';
                        $html .= '<div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                                data-toggle="dropdown" aria-expanded="false">' .
                                    __("messages.actions") .
                                    '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                </span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right" role="menu" style="min-width: 150px !important;left: 0 !important;">';
                        if (auth()->user()->can("direct_sell.access") || auth()->user()->can("sell.view") || auth()->user()->can("return.list")) {
                            if (auth()->user()->can('sell.view') || auth()->user()->can('return.list')) {
                                $html .= '<li><a href="#" class="btn-modal" data-container=".view_modal" data-href="' . action('SellReturnController@show', [$row->parent_sale_id]) . '"><i style="margin-right: 10px;" class="fas fa-eye" aria-hidden="true"></i>' . __("messages.view_and_approval") . '</a></li>';
                            }
                            if (auth()->user()->can("sell.update") || auth()->user()->can("return.update")) {
                                $transaction = Transaction::find($row->id);
                                $html_edit = '<li><a href="'. action('SellReturnController@edit', [$row->parent_sale_id]) .'" ><i class="fa fa-edit" aria-hidden="true"></i>'. __("messages.edit") .'</a></li>';
                                $html_cancel = '';
                                if ($transaction->status != 'cancel') {
                                    $html_cancel .= '<li><a class="cancel_sell" data-href="' . action('SellPosController@cancel', [$row->id]) . '"><i class="fa fa-power-off"></i> ' . __("messages.cancel") . '</a></li>';
                                }

                                if ($transaction->status == 'cancel') {
                                    $html_edit = '';
                                }

                                if (!empty($transaction) && in_array($transaction->status, ['final'])) {
                                    $html_edit = '';
                                }

                                if ($transaction->payment_status != 'due') {
                                    $html_cancel = '';
                                }
                                $html = $html . $html_edit . $html_cancel;
                            }
                        }
                        /*if (auth()->user()->can("sell.cancel")) {
                            if ($transaction->status != 'cancel') {
                                $html .= '<li><a class="cancel_sell" data-href="' . action('SellPosController@cancel', [$row->id]) . '"><i class="fa fa-power-off"></i> ' . __("messages.cancel") . '</a></li>';
                            }
                        }*/
                        if (auth()->user()->can("sell.view") || auth()->user()->can("direct_sell.access")) {
                            $html .= '<li><a href="#" class="print-invoice" data-href="'. action('SellReturnController@printInvoice', [$row->id]) .'"><i class="fa fa-print" aria-hidden="true"></i>'. __("messages.print") .'</a></li>';
                        }
                        $html .= '</ul></div>';
                        return $html;
                })
                ->removeColumn('id')
                ->editColumn('final_total', function ($row) {
                    return $html = '<span class="display_currency final_total" data-currency_symbol="true" data-orig-value="'. round_int($row->final_total, env('DIGIT', 4)) .'">'. round_int($row->final_total, env('DIGIT', 4)) .'</span>' ;
                })
                ->editColumn('parent_sale', function ($row) {
                    return '<button type="button" class="btn btn-link btn-modal" data-container=".view_modal" data-href="' . action('SellController@show', [$row->parent_sale_id]) . '">' . $row->parent_sale . '</button>';
                })
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn('status', function ($row) {
                    $payment_status = '';
                    if ($row->status == 'reject') {
                        $payment_status .= '<span class="label bg-red">';
                    } elseif ($row->status == 'final') {
                        $payment_status .= '<span class="label bg-light-green">';
                    } else {
                        $payment_status .= '<span class="label bg-yellow">';
                    }

                    if ($row->status == 'final') {
                        $payment_status .= __('lang_v1.approved');
                    } elseif ($row->status == 'reject')  {
                        $payment_status .= __('lang_v1.reject');
                    } else {
                        $payment_status .= __('lang_v1.approval_pending');
                    }

                    return $payment_status;
                })
                ->editColumn('payment_status', function ($row) {
                    if (auth()->user()->can('sell.payments') || auth()->user()->can('sell.view') || auth()->user()->can('return.list')) {
                        $payment_status = '<a href="' . action("TransactionPaymentController@show", [$row->id]) . '" class="view_payment_modal payment-status payment-status-label" data-orig-value="' . $row->payment_status . '" data-status-name="' . __('lang_v1.' . $row->payment_status) . '">';
                        if ($row->payment_status == 'partial') {
                            $payment_status .= '<span class="label bg-aqua">';
                        } elseif ($row->payment_status == 'due') {
                            $payment_status .= '<span class="label bg-yellow">';
                        } elseif ($row->payment_status == 'paid') {
                            $payment_status .= '<span class="label bg-light-green">';
                        } else {
                            $payment_status .= '<span class="label bg-red">';
                        }

                        if (in_array($row->status, ['pending', 'reject'])) {
                            $payment_status .= __('lang_v1.pending');
                        } else {
                            $payment_status .= __('lang_v1.' . $row->payment_status);
                        }

                        if ($row->status == 'cancel') {
                            $payment_status = '<span class="payment-status-label label bg-red" data-orig-value="' . $row->status . '" data-status-name="' . __('sale.reject') . '">' . __('sale.reject') . '</span>';
                        }
                        $payment_status .= '</span></a>';
                        return $payment_status;
                    }
                })
                ->editColumn('payment_due', function ($row) {
                    return '<span class="display_currency payment_due" data-currency_symbol="true" data-orig-value="' . round_int($row->payment_due, env('DIGIT', 4)) . '">'. round_int($row->payment_due, env('DIGIT', 4)) .'</sapn>';
                })
                ->editColumn('invoice_no', function ($row) {
                    $invoice_no = $row->invoice_no;
                    if ($row->status == 'cancel') {
                        $invoice_no .= '&nbsp;<small class="label bg-yellow label-round no-print" title="' . __('lang_v1.sell_cancel_message') .'"><i class="fas fa-power-off"></i></small>';
                    }

                    return $invoice_no;
                })
                ->editColumn('name', function ($row) {
                    return '<a href="' . action("ContactController@show", [$row->customer_id]) . '" target="_blank">' . $row->name . '</a>';
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("sell.view") || auth()->user()->can("return.list")) {
                            return  action('SellReturnController@show', [$row->parent_sale_id]) ;
                        } else {
                            return '';
                        }
                    }])
                ->rawColumns(['final_total', 'action', 'parent_sale', 'payment_status', 'payment_due', 'invoice_no', 'status', 'return_note', 'name'])
                ->make(true);
        }
        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);

        $sales_representative = User::forDropdown($business_id, false, false, true);

        return view('sell_return.index')->with(compact('business_locations', 'customers', 'sales_representative'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    // public function create()
    // {
    //     if (!auth()->user()->can('sell.create')) {
    //         abort(403, 'Unauthorized action.');
    //     }

    //     $business_id = request()->session()->get('user.business_id');

    //     //Check if subscribed or not
    //     if (!$this->moduleUtil->isSubscribed($business_id)) {
    //         return $this->moduleUtil->expiredResponse(action('SellReturnController@index'));
    //     }

    //     $business_locations = BusinessLocation::forDropdown($business_id);
    //     //$walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);

    //     return view('sell_return.create')
    //         ->with(compact('business_locations'));
    // }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function add($id)
    {
        if (!auth()->user()->can('sell.create') && !auth()->user()->can('sell.create_return_bill')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        }

        $sell = Transaction::where('business_id', $business_id)
                            ->with([
                                'sell_lines.plate_lines',
                                'sell_lines.plate_lines.selected_plate_stock',
                                'sell_lines.plate_lines.selected_plate_stock.product',
                                'sell_lines.plate_lines.selected_plate_stock.variation',
                                'sell_lines.plate_lines.selected_plate_stock.variation.group_prices',
                                'sell_lines.plate_lines.selected_plate_stock.warehouse',
                                'sell_lines',
                                'location',
                                'return_parent',
                                'contact',
                                'tax',
                                'sell_lines.sub_unit',
                                'sell_lines.product',
                                'sell_lines.product.unit'])
                            ->find($id);

        foreach ($sell->sell_lines as $key => $value) {
            if (!empty($value->sub_unit_id)) {
                $formated_sell_line = $this->transactionUtil->recalculateSellLineTotals($business_id, $value);
                $sell->sell_lines[$key] = $formated_sell_line;
            }

            $sell->sell_lines[$key]->formatted_qty = $this->transactionUtil->num_f($value->quantity, false, null, true);
        }

        $permitted_warehouses = auth()->user()->getPermittedWarehouses();
        if ($permitted_warehouses != 'all') {
            $warehouses = Warehouse::forDropdown($business_id, false, [], $permitted_warehouses);
        }else{
            $warehouses = Warehouse::forDropdown($business_id);
        }

        $plate_lines = TransactionPlateLine::where('transaction_id', $sell->id)
            ->get();

        $return_price_types = $this->productUtil->return_price_types();

        //Set transaction date
        if ($this->moduleUtil->isClosedEndOfDay()) {
            $transaction_date = date('Y-m-d', strtotime('now +1 days'));
            $transaction_date .= ' 00:00';
        }else{
            $transaction_date = date('Y-m-d H:i:s');
        }

        return view('sell_return.add')
            ->with(compact('sell', 'warehouses', 'plate_lines', 'return_price_types', 'transaction_date'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('sell.create_return_bill') && !auth()->user()->can('return.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->except('_token');

            if (!empty($input['products'])) {
                $business_id = $request->session()->get('user.business_id');

                //Validate when duplicate add sell return
                if($request->input('action') == 'add'){
                    $plate_lines_return = TransactionPlateLinesReturn::where('transaction_id', $input['transaction_id'])
                        ->first();
                    if($plate_lines_return){
                        return redirect('sell-return')->with('status', [
                            'success' => 0,
                            'msg' => __('sale.can_not_duplicate_sell_return')
                        ]);
                    }
                }

                $user_id = $request->session()->get('user.id');
                $shop_return_amount = $this->productUtil->num_uf($input['shop_return_amount']);
                $discount = [
                    'discount_type' => $input['discount_type'],
                    'discount_amount' => $input['discount_amount']
                ];

                //Get parent sale
                $sell = Transaction::where('business_id', $business_id)
                                ->with(['sell_lines', 'sell_lines.sub_unit'])
                                ->findOrFail($input['transaction_id']);

                //Check if any sell return exists for the sale
                $sell_return = Transaction::where('business_id', $business_id)
                        ->where('type', 'sell_return')
                        ->where('return_parent_id', $sell->id)
                        ->first();

                $final_total = $input['total_sell_return'];

                if (!empty($sell_return) && in_array($sell_return->status, ['reject', 'final'])) {
                    return redirect('sell-return')->with('status', [
                        'success' => 0,
                        'msg' => __('lang_v1.something_went_wrong')
                    ]);
                }

                if (empty($request->input('transaction_date'))) {
                    $transaction_date =  \Carbon::now();
                } else {
                    $transaction_date = $this->productUtil->uf_date($request->input('transaction_date'), true);
                }

                $sell_return_data = [
                    'status' => 'pending',
                    'transaction_date' => $transaction_date,
                    'invoice_no' => $input['invoice_no'],
                    'discount_type' => $discount['discount_type'],
                    'discount_amount' => $this->productUtil->num_uf($input['discount_amount']),
                    'total_before_tax' => $input['total_before_tax'],
                    'shop_return_amount' => $shop_return_amount,
                    'final_total' => $final_total,
                    'return_note' => $input['return_note'],
                ];

                DB::beginTransaction();

                //Generate reference number
                if (empty($sell_return_data['invoice_no'])) {
                    //Update reference count
                    $ref_count = $this->productUtil->setAndGetReferenceCount('sell_return');
                    $sell_return_data['invoice_no'] = $this->productUtil->generateReferenceNumber('sell_return', $ref_count);
                }

                if (empty($sell_return)) {
                    $sell_return_data['business_id'] = $business_id;
                    $sell_return_data['location_id'] = $sell->location_id;
                    $sell_return_data['contact_id'] = $sell->contact_id;
                    $sell_return_data['customer_group_id'] = $sell->customer_group_id;
                    $sell_return_data['type'] = 'sell_return';
                    $sell_return_data['created_by'] = $user_id;
                    $sell_return_data['return_parent_id'] = $sell->id;
                    $sell_return = Transaction::create($sell_return_data);
                } else {
                    $sell_return->update($sell_return_data);
                }

                $product_lines = $request->input('products');
                $count_plate_return = [];
                $trans_plate_stock_return = [];

                foreach ($product_lines as $product_line) {
                    if(isset($product_line['return_plates'])){
                        foreach ($product_line['return_plates'] as $key => $value) {
                            $count_plate_return[] = $value;
                            $value['id'] = (isset($value['sell_return_id']) && $value['sell_return_id'] != null) ? $value['sell_return_id'] : 0;

                            if ($value['id'] != 0) {
                                $trans_plate_stock_return[] = $value['id'];
                            }

                            // insert or update transaction plate line return
                            TransactionPlateLinesReturn::updateOrCreate([
                                'id' => $value['id'],
                            ], [
                                'transaction_id' => $input['transaction_id'],
                                'width' => $value['width'],
                                'height' => $value['height'],
                                'plate_stock_id' => $product_line['plate_stock_id'],
                                'quantity_returned' => $value['quantity'],
                                'quantity' => $value['quantity'],
                                'unit_price' => $value['unit_price_hidden'],
                                'transaction_sell_line_id' => $product_line['sell_line_id'],
                                'transaction_plate_line_id' => $product_line['plate_line_id'],
                                'variation_id' => $product_line['variation_id'],
                                'warehouse_id' => $value['warehouse_id'],
                                'sell_price_type' => $value['sell_price_type']
                            ]);
                        }
                    }
                }

                // Remove row
                $plate_sell_return_origin = TransactionPlateLinesReturn::where('transaction_id', $input['transaction_id'])
                    ->pluck('id')
                    ->toArray();

                if (count($count_plate_return) < count($plate_sell_return_origin)) {
                    if (!empty(array_unique($trans_plate_stock_return))) {
                        foreach ($plate_sell_return_origin as $value) {
                            if (!in_array($value, array_unique($trans_plate_stock_return))) {
                                TransactionPlateLinesReturn::with('variation.product.unit')->where('id', $value)->first()->delete();
                            }
                        }
                    }
                }

//                $notifyUtil = new NotificationUtil();
//                $notifyUtil->approvalSellReturnNotification($sell_return);
                DB::commit();

                $output = ['success' => 1,
                            'msg' => __('lang_v1.create_sell_return_success'),
                        ];
            }
        } catch (\Exception $e) {
            DB::rollBack();

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            } else {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
                $msg = __('messages.something_went_wrong');
            }

            $output = ['success' => 0,
                            'msg' => $msg
                        ];
        }

        return redirect('sell-return')->with('status', $output);
    }

    public function confirmApproval($id){
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('return.list')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        DB::connection('')->enableQueryLog();
        $sell = Transaction::where('business_id', $business_id)
            ->where('id', $id)
            ->with(
                'contact',
                'return_parent',
                'tax',
                'plate_lines.variations',
                'plate_lines.sell_line.sub_unit',
                'location',
                'plate_lines.plate_line_return.warehouse',
                'plate_lines.plate_line_return.variation.product.unit',
                'plate_lines.product.unit'
            )
            ->first();

        return view('sell_return.approval_return_form')
            ->with(compact('sell'));
    }

    public function rejectReturnSell($id){
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('return.list')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $sell = Transaction::query()->where('business_id', $business_id)
            ->where('id', $id)
            ->first();

        if (!empty($sell)) {
            if (in_array($sell->status, ['reject', 'final'])) {
                return json_encode(['success' => false, 'msg' => __('lang_v1.reject_fail')]);
            }

            $sell->update(['status' => 'reject']);

            TransactionPayment::where('transaction_id', $id)->update(['approval_status' => 'reject']);

            return json_encode(['success' => true, 'msg' => __('lang_v1.reject_success')]);
        }

        return json_encode(['success' => false, 'msg' => __('lang_v1.reject_fail')]);
    }

    public function approveReturnSell(Request $request){
        $business_id = $request->session()->get('user.business_id');
        $sell = Transaction::where('business_id', $business_id)
            ->with(['sell_lines', 'sell_lines.sub_unit'])
            ->findOrFail($request->id);

        //Check if any sell return exists for the sale
        $sell_return = Transaction::where('business_id', $business_id)
            ->where('type', 'sell_return')
            ->where('return_parent_id', $sell->id)
            ->first();
        //Update payment status
        try {

            DB::beginTransaction();
            $this->transactionUtil->updatePaymentStatus($sell_return->id, $sell_return->final_total);
            $sell_return->update(['status' => 'final']);

            $product_lines = $request->input('products');

            $count_plate_return = [];
            $trans_plate_stock_return = [];
            foreach ($product_lines as $product_line) {
                if (isset($product_line['return_plates'])) {
                    foreach ($product_line['return_plates'] as $key => $value) {
                        $value['id'] = (isset($value['sell_return_id']) && $value['sell_return_id'] != null) ? $value['sell_return_id'] : 0;

                        if ($value['id'] != 0) {
                            $trans_plate_stock_return[] = $value['id'];
                        }
                        $count_plate_return[] = $value;

                        $plate_sell_return = TransactionPlateLinesReturn::find($value['id']);

                        if ($value['type'] != 'pcs') {
                            $plate_stock = PlateStock::where('location_id', $sell->location_id)
                                ->where('variation_id', $product_line['variation_id'])
                                ->whereRaw("CAST(width AS DECIMAL(10,3)) = ".floatval($value['width']))
                                ->whereRaw("CAST(height AS DECIMAL(10,3)) = ".floatval($value['height']))
//                                ->whereRaw('width = ' . $value['width'])
//                                ->whereRaw('height = ' . $value['height'])
                                ->where('warehouse_id', $value['warehouse_id'])
                                ->first();

                            if ($plate_stock) {
                                if ($plate_sell_return) {
                                    if (bccomp($value['width'], $plate_sell_return->width, 3)) {
                                        $old_plate_width = PlateStock::where('location_id', $sell->location_id)
                                            ->where('variation_id', $product_line['variation_id'])
                                            ->whereRaw("CAST(width AS DECIMAL(10,3)) = ".$plate_sell_return->width)
                                            ->whereRaw("CAST(height AS DECIMAL(10,3)) = ".floatval($value['height']))
                                            ->where('warehouse_id', $value['warehouse_id'])
                                            ->first();
                                        if ($old_plate_width) {
                                            $old_plate_width->update([
                                                'qty_available' => $old_plate_width->qty_available - $value['quantity']
                                            ]);
                                            $plate_stock->update([
                                                'qty_available' => $plate_stock->qty_available + $value['quantity']
                                            ]);
                                        }
                                    }
                                    if ($value['warehouse_id'] != $plate_sell_return->warehouse_id) {
                                        $old_plate = PlateStock::where('location_id', $sell->location_id)
                                            ->where('variation_id', $product_line['variation_id'])
                                            ->whereRaw("CAST(width AS DECIMAL(10,3)) = ".floatval($value['width']))
                                            ->whereRaw("CAST(height AS DECIMAL(10,3)) = ".floatval($value['height']))
                                            ->where('warehouse_id', $plate_sell_return->warehouse_id)
                                            ->first();
                                        if ($old_plate) {
                                            $old_plate->update([
                                                'qty_available' => $old_plate->qty_available - $plate_sell_return->quantity
                                            ]);

                                            $plate_stock->update([
                                                'qty_available' => $plate_stock->qty_available + $plate_sell_return->quantity
                                            ]);
                                        }
                                    }
                                    $new_quantity = $plate_stock->qty_available + $plate_sell_return->quantity;

                                } else {
                                    $new_quantity = $plate_stock->qty_available + $value['quantity'];
                                }
                                $plate_stock->update([
                                    'qty_available' => $new_quantity
                                ]);
                            } else {
                                if ($plate_sell_return) {
                                    $plate_stock_db = PlateStock::where('location_id', $sell->location_id)
                                        ->where('variation_id', $plate_sell_return->variation_id)
                                        ->whereRaw("CAST(width AS DECIMAL(10,3)) = ".$plate_sell_return->width)
                                        ->whereRaw("CAST(height AS DECIMAL(10,3)) = ".$plate_sell_return->height)
                                        ->where('warehouse_id', $plate_sell_return->warehouse_id)
                                        ->first();
                                    if ($plate_stock_db) {
                                        $plate_stock_db->update([
                                            'qty_available' => $plate_stock_db->qty_available - $plate_sell_return->quantity
                                        ]);
                                    }
                                }
                                PlateStock::create([
                                    'product_id' => $product_line['product_id'],
                                    'variation_id' => $product_line['variation_id'],
                                    'location_id' => $sell->location_id,
                                    'width' => $value['width'],
                                    'height' => $value['height'],
                                    'warehouse_id' => $value['warehouse_id'],
                                    'qty_available' => $value['quantity']
                                ]);
                            }
                        } else {
                            $plate_stock = PlateStock::where('location_id', $sell->location_id)
                                ->where('variation_id', $product_line['variation_id'])
                                ->where('warehouse_id', $value['warehouse_id'])
                                ->first();

                            if ($plate_stock) {
                                if ($plate_sell_return) {
                                    if ($value['warehouse_id'] != $plate_sell_return->warehouse_id) {
                                        $old_plate = PlateStock::where('location_id', $sell->location_id)
                                            ->where('variation_id', $product_line['variation_id'])
                                            ->where('warehouse_id', $plate_sell_return->warehouse_id)
                                            ->first();
                                        if ($old_plate) {
                                            $old_plate->update([
                                                'qty_available' => $old_plate->qty_available - $plate_sell_return->quantity
                                            ]);
                                            $plate_stock->update([
                                                'qty_available' => $plate_stock->qty_available + $plate_sell_return->quantity
                                            ]);
                                        }
                                    }
                                    $new_quantity = $plate_stock->qty_available + $plate_sell_return->quantity;
                                } else {
                                    $new_quantity = $plate_stock->qty_available + $value['quantity'];
                                }
                                $plate_stock->update([
                                    'qty_available' => $new_quantity
                                ]);
                            } else {
                                if ($plate_sell_return) {
                                    $plate_stock_db = PlateStock::where('location_id', $sell->location_id)
                                        ->where('variation_id', $plate_sell_return->variation_id)
                                        ->where('warehouse_id', $plate_sell_return->warehouse_id)
                                        ->first();
                                    if ($plate_stock_db) {
                                        $plate_stock_db->update([
                                            'qty_available' => $plate_stock_db->qty_available - $plate_sell_return->quantity
                                        ]);
                                    }
                                }
                                PlateStock::create([
                                    'product_id' => $product_line['product_id'],
                                    'variation_id' => $product_line['variation_id'],
                                    'location_id' => $sell->location_id,
                                    'width' => $value['width'],
                                    'height' => $value['height'],
                                    'warehouse_id' => $value['warehouse_id'],
                                    'qty_available' => $value['quantity']
                                ]);
                            }
                        }

                    }
                }
            }

            // Remove row
            $plate_sell_return_origin = TransactionPlateLinesReturn::where('transaction_id', $request->id)
                ->pluck('id')
                ->toArray();

            if (count($count_plate_return) < count($plate_sell_return_origin)) {
                if (!empty(array_unique($trans_plate_stock_return))) {
                    foreach ($plate_sell_return_origin as $value) {
                        if (!in_array($value, array_unique($trans_plate_stock_return))) {
                            $plate_stock_return = TransactionPlateLinesReturn::with('variation.product.unit')->where('id', $value)->first()->toArray();
                            if ($plate_stock_return['variation']['product']['unit']['type'] != 'pcs') {
                                $plate_stock = PlateStock::where('location_id', $sell->location_id)
                                    ->where('variation_id', $plate_stock_return['variation_id'])
                                    ->whereRaw("CAST(width AS DECIMAL(10,3)) = ".$plate_stock_return['width'])
                                    ->whereRaw("CAST(height AS DECIMAL(10,3)) = ".$plate_stock_return['height'])
                                    ->where('warehouse_id', $plate_stock_return['warehouse_id'])
                                    ->first();
                            } else {
                                $plate_stock = PlateStock::where('location_id', $sell->location_id)
                                    ->where('variation_id', $plate_stock_return['variation_id'])
                                    ->where('warehouse_id', $plate_stock_return['warehouse_id'])
                                    ->first();
                            }

                            if ($plate_stock) {
                                $plate_remove = TransactionPlateLinesReturn::find($value);
                                $plate_stock->update([
                                    'qty_available' => $plate_stock->qty_available - $plate_remove->quantity
                                ]);
                                $plate_remove->delete();
                            }
                        }
                    }
                }
            }
            DB::commit();
            $output = [
                'success' => 1,
                'msg' => __('lang_v1.approval_success'),
            ];
        } catch (\Exception $exception) {
            Log::error('Error when approval sell return. Reason: ' . $exception->getMessage());
            DB::rollBack();
            $output = [
                'success' => 0,
                'msg' => __('lang_v1.approval_not_success'),
            ];
        }

//        $receipt = $this->receiptContent($business_id, $sell_return->location_id, $sell_return->id);

        return redirect('sell-return')->with('status', $output);
    }

    /*public function store(Request $request)
    {
        if (!auth()->user()->can('sell.create_return_bill') && !auth()->user()->can('return.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->except('_token');

            if (!empty($input['products'])) {
                $business_id = $request->session()->get('user.business_id');
                $user_id = $request->session()->get('user.id');
                $shop_return_amount = $this->productUtil->num_uf($input['shop_return_amount']);

                $discount = [
                    'discount_type' => $input['discount_type'],
                    'discount_amount' => $input['discount_amount']
                ];

                //Get parent sale
                $sell = Transaction::where('business_id', $business_id)
                    ->with(['sell_lines', 'sell_lines.sub_unit'])
                    ->findOrFail($input['transaction_id']);

                //Check if any sell return exists for the sale
                $sell_return = Transaction::where('business_id', $business_id)
                    ->where('type', 'sell_return')
                    ->where('return_parent_id', $sell->id)
                    ->first();

                $sell_return_data = [
                    'transaction_date' => $this->productUtil->uf_date($request->input('transaction_date')),
                    'invoice_no' => $input['invoice_no'],
                    'discount_type' => $discount['discount_type'],
                    'discount_amount' => $this->productUtil->num_uf($input['discount_amount']),
                    'total_before_tax' => $this->productUtil->num_uf($input['total_return_discount_hidden'] + $input['total_sell_return'] - $input['invoice_discount']),
                    'shop_return_amount' => $shop_return_amount,
                    'final_total' => $this->productUtil->num_uf($input['total_sell_return'] - $input['invoice_discount'] + $shop_return_amount),
                ];

                DB::beginTransaction();

                //Generate reference number
                if (empty($sell_return_data['invoice_no'])) {
                    //Update reference count
                    $ref_count = $this->productUtil->setAndGetReferenceCount('sell_return');
                    $sell_return_data['invoice_no'] = $this->productUtil->generateReferenceNumber('sell_return', $ref_count);
                }

                if (empty($sell_return)) {
                    $sell_return_data['business_id'] = $business_id;
                    $sell_return_data['location_id'] = $sell->location_id;
                    $sell_return_data['contact_id'] = $sell->contact_id;
                    $sell_return_data['customer_group_id'] = $sell->customer_group_id;
                    $sell_return_data['type'] = 'sell_return';
                    $sell_return_data['status'] = 'final';
                    $sell_return_data['created_by'] = $user_id;
                    $sell_return_data['return_parent_id'] = $sell->id;
                    $sell_return = Transaction::create($sell_return_data);
                } else {
                    $sell_return->update($sell_return_data);
                }

                //Update payment status
                $this->transactionUtil->updatePaymentStatus($sell_return->id, $sell_return->final_total);

                $product_lines = $request->input('products');

                $count_plate_return = [];
                $trans_plate_stock_return = [];
                foreach ($product_lines as $product_line) {
                    $arr_push = [];
                    foreach ($product_line as $key => $value) {
                        if (gettype($key) != 'integer') {
                            $arr_push[$key] = $value;
                        }
                    }
//                    dd($arr_push);

                    foreach ($product_line as $key => $value) {
                        if (gettype($key) == 'integer') {
                            $value['id'] = (isset($value['sell_return_id']) && $value['sell_return_id'] != null) ? $value['sell_return_id'] : 0;

                            if ($value['id'] != 0) {
                                $trans_plate_stock_return[] = $value['id'];
                            }
                            $arr_formated = array_merge($value, $arr_push);
                            $count_plate_return[] = $arr_formated;

                            $plate_sell_return = TransactionPlateLinesReturn::find($arr_formated['id']);

                            if ($arr_formated['type'] != 'pcs') {
                                $plate_stock = PlateStock::where('location_id', $sell->location_id)
                                    ->where('variation_id', $arr_formated['variation_id'])
                                    ->whereRaw('width = ' . $arr_formated['width'])
                                    ->whereRaw('height = ' . $arr_formated['height'])
                                    ->where('warehouse_id', $arr_formated['warehouse_id'])
                                    ->first();

                                if ($plate_stock) {
                                    if ($plate_sell_return) {
                                        if ($value['width'] != $plate_sell_return->width) {
                                            $old_plate_width = PlateStock::where('location_id', $sell->location_id)
                                                ->where('variation_id', $arr_formated['variation_id'])
                                                ->whereRaw('width = ' . $plate_sell_return->width)
                                                ->whereRaw('height = ' . $arr_formated['height'])
                                                ->where('warehouse_id', $arr_formated['warehouse_id'])
                                                ->first();
                                            if ($old_plate_width) {
                                                $old_plate_width->update([
                                                    'qty_available' => $old_plate_width->qty_available - $arr_formated['quantity']
                                                ]);
                                                $plate_stock->update([
                                                    'qty_available' => $plate_stock->qty_available + $arr_formated['quantity']
                                                ]);
                                            }
                                        }
                                        if ($arr_formated['warehouse_id'] != $plate_sell_return->warehouse_id) {
                                            $old_plate = PlateStock::where('location_id', $sell->location_id)
                                                ->where('variation_id', $arr_formated['variation_id'])
                                                ->whereRaw('width = ' . $arr_formated['width'])
                                                ->whereRaw('height = ' . $arr_formated['height'])
                                                ->where('warehouse_id', $plate_sell_return->warehouse_id)
                                                ->first();
                                            if ($old_plate) {
                                                $old_plate->update([
                                                    'qty_available' => $old_plate->qty_available - $plate_sell_return->quantity
                                                ]);

                                                $plate_stock->update([
                                                    'qty_available' => $plate_stock->qty_available + $plate_sell_return->quantity
                                                ]);
                                            }
                                        }
                                        if ($plate_sell_return->quantity != $arr_formated['quantity']) {
                                            $new_quantity = $plate_stock->qty_available - ($plate_sell_return->quantity - $arr_formated['quantity']);
                                        } else {
                                            $new_quantity = $plate_stock->qty_available;
                                        }
                                    } else {
                                        $new_quantity = $plate_stock->qty_available + $arr_formated['quantity'];
                                    }
                                    $plate_stock->update([
                                        'qty_available' => $new_quantity
                                    ]);
                                } else {
                                    if ($plate_sell_return) {
                                        $plate_stock_db = PlateStock::where('location_id', $sell->location_id)
                                            ->where('variation_id', $plate_sell_return->variation_id)
                                            ->whereRaw('width = ' . $plate_sell_return->width)
                                            ->whereRaw('height = ' . $plate_sell_return->height)
                                            ->where('warehouse_id', $plate_sell_return->warehouse_id)
                                            ->first();
                                        if ($plate_stock_db) {
                                            $plate_stock_db->update([
                                                'qty_available' => $plate_stock_db->qty_available - $plate_sell_return->quantity
                                            ]);
                                        }
                                    }
                                    PlateStock::create([
                                        'product_id' => $arr_formated['product_id'],
                                        'variation_id' => $arr_formated['variation_id'],
                                        'location_id' => $sell->location_id,
                                        'width' => $arr_formated['width'],
                                        'height' => $arr_formated['height'],
                                        'warehouse_id' => $arr_formated['warehouse_id'],
                                        'qty_available' => $arr_formated['quantity']
                                    ]);
                                }
                            } else {
                                $plate_stock = PlateStock::where('location_id', $sell->location_id)
                                    ->where('variation_id', $arr_formated['variation_id'])
                                    ->where('warehouse_id', $arr_formated['warehouse_id'])
                                    ->first();

                                if ($plate_stock) {
                                    if ($plate_sell_return) {
                                        if ($arr_formated['warehouse_id'] != $plate_sell_return->warehouse_id) {
                                            $old_plate = PlateStock::where('location_id', $sell->location_id)
                                                ->where('variation_id', $arr_formated['variation_id'])
                                                ->where('warehouse_id', $plate_sell_return->warehouse_id)
                                                ->first();
                                            if ($old_plate) {
                                                $old_plate->update([
                                                    'qty_available' => $old_plate->qty_available - $plate_sell_return->quantity
                                                ]);
                                                $plate_stock->update([
                                                    'qty_available' => $plate_stock->qty_available + $plate_sell_return->quantity
                                                ]);
                                            }
                                        }
                                        if ($plate_sell_return->quantity != $arr_formated['quantity']) {
                                            $new_quantity = $plate_stock->qty_available - ($plate_sell_return->quantity - $arr_formated['quantity']);
                                        } else {
                                            $new_quantity = $plate_stock->qty_available;
                                        }
                                    } else {
                                        $new_quantity = $plate_stock->qty_available + $arr_formated['quantity'];
                                    }
                                    $plate_stock->update([
                                        'qty_available' => $new_quantity
                                    ]);
                                } else {
                                    if ($plate_sell_return) {
                                        $plate_stock_db = PlateStock::where('location_id', $sell->location_id)
                                            ->where('variation_id', $plate_sell_return->variation_id)
                                            ->where('warehouse_id', $plate_sell_return->warehouse_id)
                                            ->first();
                                        if ($plate_stock_db) {
                                            $plate_stock_db->update([
                                                'qty_available' => $plate_stock_db->qty_available - $plate_sell_return->quantity
                                            ]);
                                        }
                                    }
                                    PlateStock::create([
                                        'product_id' => $arr_formated['product_id'],
                                        'variation_id' => $arr_formated['variation_id'],
                                        'location_id' => $sell->location_id,
                                        'width' => $arr_formated['width'],
                                        'height' => $arr_formated['height'],
                                        'warehouse_id' => $arr_formated['warehouse_id'],
                                        'qty_available' => $arr_formated['quantity']
                                    ]);
                                }
                            }

                            // insert or update transaction plate line return
                            $create_or_update = TransactionPlateLinesReturn::updateOrCreate([
                                'id' => $arr_formated['id'],
                            ], [
                                'transaction_id' => $input['transaction_id'],
                                'width' => $arr_formated['width'],
                                'height' => $arr_formated['height'],
                                'plate_stock_id' => $arr_formated['plate_stock_id'],
                                'quantity_returned' => $arr_formated['quantity'],
                                'quantity' => $arr_formated['quantity'],
                                'unit_price' => $arr_formated['unit_price_hidden'],
                                'transaction_sell_line_id' => $arr_formated['sell_line_id'],
                                'transaction_plate_line_id' => $arr_formated['plate_line_id'],
                                'variation_id' => $arr_formated['variation_id'],
                                'warehouse_id' => $arr_formated['warehouse_id'],
                                'sell_price_type' => $arr_formated['sell_price_type']
                            ]);

                            $trans_plate_stock_return[] = $create_or_update->id;
                        }
                    }
                }

                // Remove row
                $plate_sell_return_origin = TransactionPlateLinesReturn::where('transaction_id', $input['transaction_id'])
                    ->pluck('id')
                    ->toArray();
                if (count($count_plate_return) < count($plate_sell_return_origin)) {
                    if (!empty(array_unique($trans_plate_stock_return))) {
                        foreach ($plate_sell_return_origin as $value) {
                            if (!in_array($value, array_unique($trans_plate_stock_return))) {
                                $plate_stock_return = TransactionPlateLinesReturn::where('id', $value)->first()->toArray();
                                if ($arr_formated['type'] != 'pcs') {
                                    $plate_stock = PlateStock::where('location_id', $sell->location_id)
                                        ->where('variation_id', $plate_stock_return['variation_id'])
                                        ->whereRaw('width = ' . $plate_stock_return['width'])
                                        ->whereRaw('height = ' . $plate_stock_return['height'])
                                        ->where('warehouse_id', $plate_stock_return['warehouse_id'])
                                        ->first();
                                } else {
                                    $plate_stock = PlateStock::where('location_id', $sell->location_id)
                                        ->where('variation_id', $plate_stock_return['variation_id'])
                                        ->where('warehouse_id', $plate_stock_return['warehouse_id'])
                                        ->first();
                                }

                                if ($plate_stock) {
                                    $plate_remove = TransactionPlateLinesReturn::find($value);
                                    $plate_stock->update([
                                        'qty_available' => $plate_stock->qty_available - $plate_remove->quantity
                                    ]);
                                    $plate_remove->delete();
                                }
                            }
                        }
                    }
                }

                $receipt = $this->receiptContent($business_id, $sell_return->location_id, $sell_return->id);
                DB::commit();

                $output = ['success' => 1,
                    'msg' => __('lang_v1.create_sell_return_success'),
//                            'receipt' => json_encode($receipt)
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            } else {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
                $msg = __('messages.something_went_wrong');
            }

            $output = ['success' => 0,
                'msg' => $msg
            ];
        }

        return redirect('sell-return')->with('status', $output);
    }*/

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('return.list')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $sell = Transaction::where('business_id', $business_id)
                                ->where('id', $id)
                                ->with(
                                    'contact',
                                    'return_parent',
                                    'tax',
                                    'plate_lines.variations',
                                    'plate_lines.sell_line.sub_unit',
                                    'location',
                                    'plate_lines.plate_line_return',
                                    'plate_lines.plate_line_return.warehouse',
                                    'plate_lines.plate_line_return.variation.product.unit',
                                    'plate_lines.product.unit'
                                )
                                ->first();

        return view('sell_return.show')
            ->with(compact('sell'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('sell.update') && !auth()->user()->can('return.update')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $sell = Transaction::where('business_id', $business_id)
            ->with([
                'sell_lines.plate_lines',
                'sell_lines.plate_lines.selected_plate_stock',
                'sell_lines.plate_lines.selected_plate_stock.product',
                'sell_lines.plate_lines.selected_plate_stock.variation',
                'sell_lines.plate_lines.selected_plate_stock.warehouse',
                'sell_lines',
                'location',
                'return_parent',
                'contact',
                'tax',
                'sell_lines.sub_unit',
                'sell_lines.product',
                'sell_lines.product.unit'])
            ->find($id);

        //Check if closed end of day
        $current_date = date('Y-m-d');
        $transaction_date = date('Y-m-d', strtotime($sell->transaction_date));
        if ($this->moduleUtil->isClosedEndOfDay() && $transaction_date <= $current_date) {
            $output = ['success' => 0,
                'msg' => __('messages.can_not_update_after_closed_app')
            ];

            return redirect('sell-return')->with('status', $output);
        }

        if (!empty($sell->return_parent)) {
            if ($sell->return_parent->status == 'cancel') {
                abort(403, __('lang_v1.can_not_access'));
            }
        }

        $transaction_sell_return = Transaction::where('return_parent_id', $id)->first();
        $plate_line_return = TransactionPlateLinesReturn::with(['plate_stock.product.unit', 'plate_line'])
            ->where('transaction_id', $id)
            ->get();

        foreach ($sell->sell_lines as $key => $value) {
            if (!empty($value->sub_unit_id)) {
                $formated_sell_line = $this->transactionUtil->recalculateSellLineTotals($business_id, $value);
                $sell->sell_lines[$key] = $formated_sell_line;
            }

            $sell->sell_lines[$key]->formatted_qty = $this->transactionUtil->num_f($value->quantity, false, null, true);
        }

        $edit = 'edit';

        $permitted_warehouses = auth()->user()->getPermittedWarehouses();
        if ($permitted_warehouses != 'all') {
            $warehouses = Warehouse::forDropdown($business_id, false, [], $permitted_warehouses);
        }else{
            $warehouses = Warehouse::forDropdown($business_id);
        }

        $plate_lines = TransactionPlateLine::where('transaction_id', $sell->id)
            ->get();

        $return_price_types = $this->productUtil->return_price_types();

        $transaction_date = $sell->return_parent->transaction_date;

        return view('sell_return.add')
            ->with(compact('sell', 'warehouses', 'plate_lines', 'edit', 'plate_line_return', 'transaction_sell_return', 'return_price_types', 'transaction_date'));
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Return the row for the product
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getProductRow()
    {
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
        $printer_type = null
    ) {
        $output = ['is_enabled' => false,
                    'print_type' => 'browser',
                    'html_content' => null,
                    'printer_config' => [],
                    'data' => []
                ];

        $business_details = $this->businessUtil->getDetails($business_id);
        $location_details = BusinessLocation::find($location_id);

        //Check if printing of invoice is enabled or not.
        if ($location_details->print_receipt_on_invoice == 1) {
            //If enabled, get print type.
            $output['is_enabled'] = true;

            $invoice_layout = $this->businessUtil->invoiceLayout($business_id, $location_id, $location_details->invoice_layout_id);

            //Check if printer setting is provided.
            $receipt_printer_type = is_null($printer_type) ? $location_details->receipt_printer_type : $printer_type;

            $receipt_details = $this->transactionUtil->getReceiptDetails($transaction_id, $location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type);

            //If print type browser - return the content, printer - return printer config data, and invoice format config
            if ($receipt_printer_type == 'printer') {
                $output['print_type'] = 'printer';
                $output['printer_config'] = $this->businessUtil->printerConfig($business_id, $location_details->printer_id);
                $output['data'] = $receipt_details;
            } else {
                $output['html_content'] = view('sell_return.receipt', compact('receipt_details'))->render();
            }
        }

        return $output;
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
//            try {
                $output = ['success' => 0,
                        'msg' => trans("messages.something_went_wrong")
                        ];

                $business_id = $request->session()->get('user.business_id');

                $transaction = Transaction::where('business_id', $business_id)
                                ->where('id', $transaction_id)
                                ->first();

                if (empty($transaction)) {
                    return $output;
                }

                $receipt = $this->receiptContent($business_id, $transaction->location_id, $transaction_id, 'browser');

                if (!empty($receipt)) {
                    $output = ['success' => 1, 'receipt' => $receipt];
                }
//            } catch (\Exception $e) {
//                $output = ['success' => 0,
//                        'msg' => trans("messages.something_went_wrong")
//                        ];
//            }

            return $output;
        }
    }

    public function getSellReturnEntryRow(Request $request)
    {
        if (!$request->ajax()) {
            return 'permission_denied';
        }

        $business_id = request()->session()->get('user.business_id');
        $plate_line_id = $request->input('plate_line_id');

        $plate_line = TransactionPlateLine::with([
                'sell_line.sub_unit',
                'selected_plate_stock.variation',
            ])
            ->find($plate_line_id);

        if(!$plate_line){
            return '';
        }

        $row_index = $request->input('row_index');
        $sub_row_index = $request->input('sub_row_index');

        $permitted_warehouses = auth()->user()->getPermittedWarehouses();
        if ($permitted_warehouses != 'all') {
            $warehouses = Warehouse::forDropdown($business_id, false, [], $permitted_warehouses);
        }else{
            $warehouses = Warehouse::forDropdown($business_id);
        }

        $return_price_types = $this->productUtil->return_price_types();

        return view('sell_return.partials.sell_return_entry_row')
            ->with(compact(
                'plate_line',
                'warehouses',
                'row_index',
                'sub_row_index',
                'return_price_types'
            ));
    }
}
