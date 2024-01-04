<?php

namespace App\Http\Controllers;

use App\Business;
use App\BusinessLocation;

use App\Category;
use App\PlateStock;
use App\Product;
use App\PurchaseLine;

use App\Transaction;
use App\TransactionSellLine;
use App\Unit;
use App\Utils\ModuleUtil;

use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Variation;
use App\Warehouse;
use Datatables;

use DB;
use Illuminate\Http\Request;

class StockAdjustmentController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $productUtil;
    protected $transactionUtil;
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil, ModuleUtil $moduleUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('stock.to_deliver')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $stock_adjustments = Transaction::join(
                    'business_locations AS BL',
                    'transactions.location_id',
                    '=',
                    'BL.id'
                )
                ->leftJoin('users as u', 'transactions.created_by', '=', 'u.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'stock_adjustment')
                ->select(
                    'transactions.id',
                    'transaction_date',
                    'ref_no',
                    'BL.name as location_name',
                    'adjustment_type',
                    'final_total',
                    'total_amount_recovered',
                    'additional_notes',
                    'transactions.id as DT_RowId',
                    'transactions.status',
                    DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by")
                );

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $stock_adjustments->whereIn('transactions.location_id', $permitted_locations);
            }

            /*$hide = '';
            $start_date = request()->get('start_date');
            $end_date = request()->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $stock_adjustments->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
                $hide = 'hide';
            }*/

            $location_id = request()->get('location_id');
            if (!empty($location_id)) {
                $stock_adjustments->where('transactions.location_id', $location_id);
            }

            return Datatables::of($stock_adjustments)
                ->addColumn('action', function ($row){
                    //Check if the transaction can be edited or not.
                    $is_closed = false;
                    $edit_days = request()->session()->get('business.transaction_edit_days');
                    if ($row->status != 'draft' && !$this->transactionUtil->canBeEdited($row->id, $edit_days)) {
                        $is_closed = true;
                    }

                    //Check if closed end of day
                    $current_date = date('Y-m-d');
                    $transaction_date = date('Y-m-d', strtotime($row->transaction_date));
                    if ($this->moduleUtil->isClosedEndOfDay() && $transaction_date <= $current_date) {
                        $is_closed = true;
                    }

                    $html = '<button type="button" title="'. __("stock_adjustment.view_details") .'" class="btn btn-primary btn-xs view_stock_adjustment"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>';
                    if(!$is_closed){
                        $html .= '&nbsp;<button type="button" data-href="'. action("StockAdjustmentController@destroy", [$row->id]) .'" class="btn btn-danger btn-xs delete_stock_adjustment"><i class="fa fa-trash" aria-hidden="true"></i> '. __("messages.delete") .'</button>';
                    }

                    return $html;
                })
                /*->addColumn('action', function ($row) use ($hide){

                    $html = '<button type="button" title="'. __("stock_adjustment.view_details") .'" class="btn btn-primary btn-xs view_stock_adjustment"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>&nbsp;
                        <button type="button" data-href="'. action("StockAdjustmentController@destroy", [$row->id]) .'" class="btn btn-danger btn-xs delete_stock_adjustment ' . $hide . '"><i class="fa fa-trash" aria-hidden="true"></i> '. __("messages.delete") .'</button>';
                    return $html;
                })*/
                ->removeColumn('id')
                ->editColumn(
                    'final_total',
                    '<span class="display_currency" data-currency_symbol="true">{{$final_total}}</span>'
                )
                ->editColumn(
                    'total_amount_recovered', function ($row) {
                        return '<span class="display_currency" data-currency_symbol="true">' . round_int($row->total_amount_recovered, env('DIGIT', 4)) . '</span>';
                })
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn('adjustment_type', function ($row) {
                    return __('stock_adjustment.' . $row->adjustment_type);
                })
                ->editColumn('status', function ($row){
                    $status_color = $row->status == 'received' ? 'bg-green' : 'bg-yellow';
                    $status_text = $row->status == 'received' ? __('stock_adjustment.confirmed') : __('stock_adjustment.not_confirmed');
                    $status_icon = $row->status == 'received' ? '<i class="fas fa-check"></i>' : '<i class="fas fa-times"></i>';

                    if(auth()->user()->can('stock.approve_adjustment_stock') && $row->status != 'received'){
                        $html = '<button data-href="'. action("StockAdjustmentController@approveStockAdjustment", [$row->id]) .'" class="btn label approve_stock_adjustment '. $status_color .'">'. $status_icon .' '. $status_text .'</button>';
                    }else{
                        $html = '<span class="label '. $status_color .'">'. $status_icon .' '. $status_text .'</span>';
                    }
                    return $html;
                })
                ->rawColumns(['final_total', 'action', 'status', 'total_amount_recovered'])
                ->make(true);
        }

        return view('stock_adjustment.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('stock.to_deliver')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action('StockAdjustmentController@index'));
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $default_location = null;
        if (count($business_locations) == 1) {
            $location = BusinessLocation::query()->where('id', $business_locations->keys())->first();
            $default_location = $location->id;
        }

        $categories = Category::forDropdown($business_id, 'product');
        $products = Variation::leftjoin('products', 'variations.product_id', '=', 'products.id')
            ->select([
                DB::raw('IF(products.type = "variable", CONCAT(products.name, " - ", variations.name, " (", variations.sub_sku, ")"), CONCAT(products.name, " (", variations.sub_sku, ")")) as product'),
                'variations.id'
            ])->pluck('product', 'variations.id')
            ->toArray();

        $permitted_warehouses = auth()->user()->getPermittedWarehouses();
        if ($permitted_warehouses != 'all') {
            $warehouse = Warehouse::forDropdown($business_id, false, [], $permitted_warehouses);
        }else{
            $warehouse = Warehouse::forDropdown($business_id);
        }

        //Set transaction date
        if ($this->moduleUtil->isClosedEndOfDay()) {
            $transaction_date = date('Y-m-d', strtotime('now +1 days'));
            $transaction_date .= ' 00:00';
        }else{
            $transaction_date = date('Y-m-d H:i:s');
        }

        $business = Business::find($business_id);
        $max_weight_dont_need_confirm = $business->max_weight_dont_need_confirm;
        $max_pcs_dont_need_confirm = $business->max_pcs_dont_need_confirm;

        return view('stock_adjustment.create')
            ->with(compact('business_locations', 'categories', 'products', 'warehouse', 'default_location', 'transaction_date', 'max_weight_dont_need_confirm', 'max_pcs_dont_need_confirm'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('stock.to_deliver')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input_data = $request->only([ 'location_id', 'transaction_date', 'adjustment_type', 'additional_notes', 'total_amount_recovered', 'ref_no']);
            $business_id = $request->session()->get('user.business_id');

            //Check if subscribed or not
            if (!$this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse(action('StockAdjustmentController@index'));
            }

            $user_id = $request->session()->get('user.id');

            $input_data['type'] = 'stock_adjustment';
            $input_data['business_id'] = $business_id;
            $input_data['created_by'] = $user_id;
            $input_data['transaction_date'] = $this->productUtil->uf_date($input_data['transaction_date'], true);
            $input_data['total_amount_recovered'] = $this->productUtil->num_uf($input_data['total_amount_recovered']);

            $business_locations = BusinessLocation::forDropdown($business_id);
            $default_location = null;
            if (count($business_locations) == 1) {
                foreach ($business_locations as $id => $name) {
                    $default_location = BusinessLocation::findOrFail($id);
                }
            }
            $location_id = $default_location ? $default_location->id : $request->input('location_id');
            $input_data['location_id'] = $location_id;

            //Update reference count
            $ref_count = $this->productUtil->setAndGetReferenceCount('stock_adjustment');
            //Generate reference number
            if (empty($input_data['ref_no'])) {
                $input_data['ref_no'] = $this->productUtil->generateReferenceNumber('stock_adjustment', $ref_count);
            }

            $input_data['final_total'] = 0;
            $plate_stocks = $request->input('plate_stocks');

            DB::beginTransaction();
            if (!empty($plate_stocks)) {
                $product_data = [];
                $has_approval = true;
                $business = Business::find($business_id);
                $max_weight_dont_need_confirm = floatval($business->max_weight_dont_need_confirm);
                $max_pcs_dont_need_confirm = intval($business->max_pcs_dont_need_confirm);

                foreach ($plate_stocks as $plate_stock) {
                    $current_plate_stock = PlateStock::find($plate_stock['id']);
                    $plate_stock['quantity'] = $this->transactionUtil->num_uf($plate_stock['quantity']);
                    $plate_stock['width'] = $this->transactionUtil->num_uf($plate_stock['width']);
                    $plate_stock['height'] = $this->transactionUtil->num_uf($plate_stock['height']);
                    $plate_stock['origin_width'] = $this->transactionUtil->num_uf($plate_stock['origin_width']);
                    $plate_stock['origin_height'] = $this->transactionUtil->num_uf($plate_stock['origin_height']);
                    if ($plate_stock['unit_type'] != Unit::PCS) {
                        $compareSize = !bccomp($plate_stock['width'], $current_plate_stock->width, env('SCALE', 2)) &&
                            !bccomp($plate_stock['height'], $current_plate_stock->height, env('SCALE', 2));

                        if ($plate_stock['quantity'] <= 0 || $current_plate_stock->qty_available < $plate_stock['quantity']) {
                            continue;
                        }

                        if ($compareSize) {
                            continue;
                        }

                    } elseif ($plate_stock['quantity'] <= 0 || $current_plate_stock->qty_available < $plate_stock['quantity']) {
                        continue;
                    }

                    $area = $plate_stock['width'] * $plate_stock['height'];

                    $adjustment_line = [
                        'product_id' => $current_plate_stock->product_id,
                        'variation_id' => $current_plate_stock->variation_id,
                        'width' => $plate_stock['width'],
                        'height' => $plate_stock['height'],
                        'quantity' => $area,
                        'quantity_line' => $plate_stock['quantity'],
                        'warehouse_id' => $current_plate_stock->warehouse_id,
                        'is_origin' => $current_plate_stock->is_origin,
                        'plate_stock_id' => $current_plate_stock->id,
                    ];
                    $product_data[] = $adjustment_line;
                    $input_data['final_total'] += $area * $current_plate_stock->variation->default_sell_price;

                    $total_for_confirm = 0;
                    $max_dont_need_confirm = 0;

                    if ($plate_stock['unit_type'] == 'pcs'){
                        $total_for_confirm = $plate_stock['quantity'];
                        $max_dont_need_confirm = $max_pcs_dont_need_confirm;
                    }elseif (in_array($plate_stock['unit_type'], ['meter', 'area'])){
                        $total_for_confirm = ($plate_stock['origin_width'] * $plate_stock['origin_height'] * $plate_stock['quantity'] - $plate_stock['width'] * $plate_stock['height'] * $plate_stock['quantity']) * $plate_stock['weight'];
                        $max_dont_need_confirm = $max_weight_dont_need_confirm;
                    }

                    if(!auth()->user()->can('stock.approve_adjustment_stock') && ($max_dont_need_confirm == 0 || $total_for_confirm > $max_dont_need_confirm)){
                        $has_approval = false;
                    }
                }

                if (!empty($product_data)) {
                    $input_data['status'] = 'pending';
                    $msg = __('stock_adjustment.stock_adjustment_pending');

                    if($has_approval){
                        $msg = __('stock_adjustment.stock_adjustment_added_successfully');
                        $input_data['status'] = 'received';
                    }

                    $stock_adjustment = Transaction::create($input_data);
                    $stock_adjustment->stock_adjustment_lines()->createMany($product_data);

                    if($input_data['status'] == 'received'){
                        $result = $this->decreasePlateStock($stock_adjustment);

                        if(!$result){
                            DB::rollback();
                            return redirect('stock-adjustments')->with('status', [
                                'success' => 0,
                                'msg' => trans("messages.create_stock_adjustment_not_allow")
                            ]);
                        }
                    }
                } else {
                    DB::rollback();
                    return redirect('stock-adjustments')->with('status', [
                        'success' => 0,
                        'msg' => trans("messages.wrong_size_input")
                    ]);
                }
            }

            $output = ['success' => 1,
                'msg' => $msg
            ];

            DB::commit();
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

        return redirect('stock-adjustments')->with('status', $output);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!auth()->user()->can('stock.to_deliver')) {
            abort(403, 'Unauthorized action.');
        }

        $stock_adjustment_details = Transaction::
        join(
            'stock_adjustment_lines as sl',
            'sl.transaction_id',
            '=',
            'transactions.id'
        )
            ->join('products as p', 'sl.product_id', '=', 'p.id')
            ->join('units', 'p.unit_id', '=', 'units.id')
            ->join('variations as v', 'sl.variation_id', '=', 'v.id')
            ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            ->where('transactions.id', $id)
            ->where('transactions.type', 'stock_adjustment')
            ->leftjoin('purchase_lines as pl', 'sl.lot_no_line_id', '=', 'pl.id')
            ->leftjoin('warehouses as wh', 'sl.warehouse_id', '=', 'wh.id')
            ->leftjoin('plate_stocks', 'sl.plate_stock_id', '=', 'plate_stocks.id')
            ->select(
                'p.name as product',
                'p.type as type',
                'pv.name as product_variation',
                'v.name as variation',
                'v.sub_sku',
                'plate_stocks.width as before_width',
                'plate_stocks.height as before_height',
                'sl.width as after_width',
                'sl.height as after_height',
                'sl.quantity',
                'sl.quantity_line',
                'wh.name as warehouse',
                'units.actual_name as unit_name',
                'units.type as unit_type',
                'sl.unit_price',
                'pl.lot_number',
                'pl.exp_date'
            )
            ->groupBy('sl.id')
            ->get();

        $lot_n_exp_enabled = false;
        if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
            $lot_n_exp_enabled = true;
        }

        return view('stock_adjustment.partials.details')
            ->with(compact('stock_adjustment_details', 'lot_n_exp_enabled'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Transaction  $stockAdjustment
     * @return \Illuminate\Http\Response
     */
    public function edit(Transaction $stockAdjustment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Transaction  $stockAdjustment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Transaction $stockAdjustment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('stock.to_deliver')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            if (request()->ajax()) {
                DB::beginTransaction();

                $stock_adjustment = Transaction::where('id', $id)
                    ->where('type', 'stock_adjustment')
                    ->with(['stock_adjustment_lines'])
                    ->first();

                //Add deleted product quantity to available quantity
                if ($stock_adjustment->status == 'received'){
                    $result = $this->transactionUtil->updatePlateStock($stock_adjustment);

                    if (!$result) {
                        DB::rollback();
                        return ['success' => 0,
                            'msg' => __('messages.delete_stock_adjustment_not_allow')
                        ];
                    }
                }

                $stock_adjustment->delete();

                //Remove Mapping between stock adjustment & purchase.

                $output = ['success' => 1,
                    'msg' => __('stock_adjustment.delete_success')
                ];

                DB::commit();
            }
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
     * Sets expired purchase line as stock adjustmnet
     *
     * @param int $purchase_line_id
     * @return json $output
     */
    public function removeExpiredStock($purchase_line_id)
    {
        if (!auth()->user()->can('purchase.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $purchase_line = PurchaseLine::where('id', $purchase_line_id)
                ->with(['transaction'])
                ->first();

            if (!empty($purchase_line)) {
                DB::beginTransaction();

                $qty_unsold = $purchase_line->quantity - $purchase_line->quantity_sold - $purchase_line->quantity_adjusted - $purchase_line->quantity_returned;
                $final_total = $purchase_line->purchase_price_inc_tax * $qty_unsold;

                $user_id = request()->session()->get('user.id');
                $business_id = request()->session()->get('user.business_id');

                //Update reference count
                $ref_count = $this->productUtil->setAndGetReferenceCount('stock_adjustment');

                $stock_adjstmt_data = [
                    'type' => 'stock_adjustment',
                    'business_id' => $business_id,
                    'created_by' => $user_id,
                    'transaction_date' => \Carbon::now()->format('Y-m-d'),
                    'total_amount_recovered' => 0,
                    'location_id' => $purchase_line->transaction->location_id,
                    'adjustment_type' => 'normal',
                    'final_total' => $final_total,
                    'ref_no' => $this->productUtil->generateReferenceNumber('stock_adjustment', $ref_count)
                ];

                //Create stock adjustment transaction
                $stock_adjustment = Transaction::create($stock_adjstmt_data);

                $stock_adjustment_line = [
                    'product_id' => $purchase_line->product_id,
                    'variation_id' => $purchase_line->variation_id,
                    'quantity' => $qty_unsold,
                    'unit_price' => $purchase_line->purchase_price_inc_tax,
                    'removed_purchase_line' => $purchase_line->id
                ];

                //Create stock adjustment line with the purchase line
                $stock_adjustment->stock_adjustment_lines()->create($stock_adjustment_line);

                //Decrease available quantity
                $this->productUtil->decreaseProductQuantity(
                    $purchase_line->product_id,
                    $purchase_line->variation_id,
                    $purchase_line->transaction->location_id,
                    $qty_unsold
                );

                //Map Stock adjustment & Purchase.
                $business = ['id' => $business_id,
                    'accounting_method' => request()->session()->get('business.accounting_method'),
                    'location_id' => $purchase_line->transaction->location_id
                ];
                $this->transactionUtil->mapPurchaseSell($business, $stock_adjustment->stock_adjustment_lines, 'stock_adjustment', false, $purchase_line->id);

                DB::commit();

                $output = ['success' => 1,
                    'msg' => __('lang_v1.stock_removed_successfully')
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
        return $output;
    }

    public function getSellEntryRow(Request $request)
    {
        if (!$request->ajax()) {
            return 'permission_denied';
        }

        $business_id = request()->session()->get('user.business_id');
        $plate_stock_ids = $request->input('plate_stock_ids');

        if(empty($plate_stock_ids)){
            return '';
        }

        $plate_stocks = PlateStock::with(['product', 'product.unit', 'variation', 'warehouse'])
            ->whereIn('id', $plate_stock_ids)
            ->get();

        $permitted_warehouses = auth()->user()->getPermittedWarehouses();
        if ($permitted_warehouses != 'all') {
            $warehouses = Warehouse::forDropdown($business_id, false, [], $permitted_warehouses);
        }else{
            $warehouses = Warehouse::forDropdown($business_id);
        }

        return view('stock_adjustment.partials.sell_entry_row')
            ->with(compact('plate_stocks', 'warehouses'));
    }

    public function approveStockAdjustment($id) {
        if (!auth()->user()->can('stock.approve_adjustment_stock')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->user()->business_id;

                DB::beginTransaction();
                $transaction = Transaction::where('business_id', $business_id)->find($id);
                $result = $this->decreasePlateStock($transaction);
                if (!$result) {
                    DB::rollback();
                    return ['success' => 0,
                        'msg' => __('messages.approval_stock_adjustment_not_allow')
                    ];
                }
                DB::commit();

                $output = ['success' => true,
                    'msg' => __("stock_adjustment.approve_success")
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
        return [
            'code' => 403,
            'msg' => 'Unauthorized action.'
        ];
    }

    public function decreasePlateStock($transaction)
    {
        foreach($transaction->stock_adjustment_lines as $stock_adjustment_line){
            $current_plate_stock = PlateStock::find($stock_adjustment_line->plate_stock_id);
            $product = Product::query()->with('unit')->find($stock_adjustment_line->product_id);

            if (empty($current_plate_stock)) {
                return false;
            }

            if ($stock_adjustment_line->quantity_line > $current_plate_stock->qty_available) {
                return false;
            }

            //TODO: Update current width plate stock
            if (($stock_adjustment_line->width == 0 || $stock_adjustment_line->height == 0) ||
                ($stock_adjustment_line->width != 0 && $stock_adjustment_line->height != 0)) {
                $current_plate_stock->qty_available -= $stock_adjustment_line->quantity_line;
                $current_plate_stock->save();
            }

            if ($product->unit->type != Unit::PCS) {
                if ($stock_adjustment_line->width != 0 && $stock_adjustment_line->height != 0) {
                    // TODO: Create new plate with case width <> 0
                    $existPlateStock = PlateStock::query()
                        ->where('location_id', $transaction->location_id)
                        ->where('variation_id', $stock_adjustment_line->variation_id)
                        ->whereRaw("CAST(width AS DECIMAL(10,3)) = ".floatval($stock_adjustment_line->width))
                        ->whereRaw("CAST(height AS DECIMAL(10,3)) = ".floatval($stock_adjustment_line->height))
                        ->where('warehouse_id', $stock_adjustment_line->warehouse_id)
                        ->where('is_origin', $stock_adjustment_line->is_origin)
                        ->first();

                    if (empty($existPlateStock)) {
                        PlateStock::query()->create([
                            'product_id'    => $stock_adjustment_line->product_id,
                            'variation_id'  => $stock_adjustment_line->variation_id,
                            'location_id'   => $transaction->location_id,
                            'width'         => $stock_adjustment_line->width,
                            'height'        => $stock_adjustment_line->height,
                            'warehouse_id'  => $stock_adjustment_line->warehouse_id,
                            'qty_available' => $stock_adjustment_line->quantity_line,
                            'is_origin'     => $stock_adjustment_line->is_origin,
                        ]);
                    } else {
                        $existPlateStock->qty_available += $stock_adjustment_line->quantity_line;
                        $existPlateStock->save();
                    }
                }
            }
        }

        $transaction->status = 'received';
        $transaction->save();

        return true;
    }

    /*public function decreasePlateStock($transaction)
    {
        $transaction->status = 'received';
        $transaction->save();

        foreach($transaction->stock_adjustment_lines as $stock_adjustment_line){
            $current_plate_stock = PlateStock::find($stock_adjustment_line->plate_stock_id);
            $product = Product::query()->with('unit')->find($stock_adjustment_line->product_id);

            if (empty($current_plate_stock)) {
                continue;
            }

            if ($current_plate_stock->qty_available < $stock_adjustment_line->quantity_line &&
                $stock_adjustment_line->width == $current_plate_stock->width &&
                $stock_adjustment_line->height == $current_plate_stock->height) {
                continue;
            }

            //TODO: Update current width plate stock
            if (($stock_adjustment_line->width == 0 || $stock_adjustment_line->height == 0) ||
                ($stock_adjustment_line->width != 0 && $stock_adjustment_line->height != 0)) {
                $current_plate_stock->qty_available -= $stock_adjustment_line->quantity_line;
                $current_plate_stock->save();
            }

            if ($product->unit->type != Unit::PCS) {
                if ($stock_adjustment_line->width != 0 && $stock_adjustment_line->height != 0) {
                    // TODO: Create new plate with case width <> 0
                    $existPlateStock = PlateStock::query()
                        ->where('location_id', $transaction->location_id)
                        ->where('variation_id', $stock_adjustment_line->variation_id)
                        ->whereRaw("CAST(width AS DECIMAL(10,3)) = ".floatval($stock_adjustment_line->width))
                        ->whereRaw("CAST(height AS DECIMAL(10,3)) = ".floatval($stock_adjustment_line->height))
                        ->where('warehouse_id', $stock_adjustment_line->warehouse_id)
                        ->where('is_origin', $stock_adjustment_line->is_origin)
                        ->first();

                    if (empty($existPlateStock)) {
                        PlateStock::query()->create([
                            'product_id'    => $stock_adjustment_line->product_id,
                            'variation_id'  => $stock_adjustment_line->variation_id,
                            'location_id'   => $transaction->location_id,
                            'width'         => $stock_adjustment_line->width,
                            'height'        => $stock_adjustment_line->height,
                            'warehouse_id'  => $stock_adjustment_line->warehouse_id,
                            'qty_available' => $stock_adjustment_line->quantity_line,
                            'is_origin'     => $stock_adjustment_line->is_origin,
                        ]);
                    } else {
                        $existPlateStock->qty_available += $stock_adjustment_line->quantity_line;
                        $existPlateStock->save();
                    }
                }
            }
        }

        return true;
    }*/
}
