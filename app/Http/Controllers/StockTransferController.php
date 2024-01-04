<?php

namespace App\Http\Controllers;

use App\BusinessLocation;

use App\Category;
use App\PlateStock;
use App\PurchaseLine;
use App\Transaction;
use App\TransactionSellLine;
use App\TransactionSellLinesPurchaseLines;
use App\Unit;
use App\Utils\ModuleUtil;

use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Variation;
use App\Warehouse;
use Datatables;

use DB;
use Illuminate\Http\Request;

class StockTransferController extends Controller
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
            $edit_days = request()->session()->get('business.transaction_edit_days');

            $stock_transfers = Transaction::join(
                'business_locations AS l1',
                'transactions.location_id',
                '=',
                'l1.id'
            )
                ->join('transactions as t2', 't2.transfer_parent_id', '=', 'transactions.id')
                ->join(
                    'business_locations AS l2',
                    't2.location_id',
                    '=',
                    'l2.id'
                )
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell_transfer')
                ->select(
                    'transactions.id',
                    'transactions.transaction_date',
                    'transactions.ref_no',
                    'l1.name as location_from',
                    'l2.name as location_to',
                    'transactions.shipping_charges',
                    'transactions.additional_notes',
                    'transactions.id as DT_RowId'
                );

            return Datatables::of($stock_transfers)
                ->addColumn('action', function ($row) use ($edit_days) {
                    //Check if closed end of day
                    $current_date = date('Y-m-d');
                    $transaction_date = date('Y-m-d', strtotime($row->transaction_date));
                    if ($this->moduleUtil->isClosedEndOfDay() && $transaction_date <= $current_date) {
                        $is_closed = true;
                    }else{
                        $is_closed = false;
                    }

                    $html = '<button type="button" title="' . __("stock_adjustment.view_details") . '" class="btn btn-primary btn-xs view_stock_transfer"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>';
                    $html .= ' <a href="#" class="print-invoice btn btn-info btn-xs" data-href="' . action('StockTransferController@printInvoice', [$row->id]) . '"><i class="fa fa-print" aria-hidden="true"></i> '. __("messages.print") .'</a>';

                    $date = \Carbon::parse($row->transaction_date)
                        ->addDays($edit_days);
                    $today = today();

                    if ($date->gte($today) && !$is_closed) {
                        $html .= '&nbsp;
                        <button type="button" data-href="' . action("StockTransferController@destroy", [$row->id]) . '" class="btn btn-danger btn-xs delete_stock_transfer"><i class="fa fa-trash" aria-hidden="true"></i> ' . __("messages.delete") . '</button>';
                    }

                    return $html;
                })
                ->removeColumn('id')
                ->editColumn(
                    'shipping_charges', function ($row) {
                        return '<span class="display_currency" data-currency_symbol="true">' . round_int($row->shipping_charges, env('DIGIT', 4)) . '</span>';
                })
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->rawColumns(['action', 'shipping_charges'])
                ->make(true);
        }

        return view('stock_transfer.index');
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
            return $this->moduleUtil->expiredResponse(action('StockTransferController@index'));
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

        return view('stock_transfer.create')
            ->with(compact('business_locations', 'categories', 'products', 'business_locations', 'warehouse', 'default_location', 'transaction_date'));
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
            $business_id = $request->session()->get('user.business_id');

            //Check if subscribed or not
            if (!$this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse(action('StockTransferController@index'));
            }

            DB::beginTransaction();

            $input_data = $request->only([ 'location_id', 'ref_no', 'transaction_date', 'additional_notes', 'shipping_charges']);

            $user_id = $request->session()->get('user.id');
            $input_data['type'] = 'sell_transfer';
            $input_data['business_id'] = $business_id;
            $input_data['created_by'] = $user_id;
            $input_data['transaction_date'] = $this->productUtil->uf_date($input_data['transaction_date'], true);
            $input_data['shipping_charges'] = $this->productUtil->num_uf($input_data['shipping_charges']);
            $input_data['status'] = 'final';
            $input_data['payment_status'] = 'paid';

            $business_locations = BusinessLocation::forDropdown($business_id);
            $default_location = null;
            if (count($business_locations) == 1) {
                foreach ($business_locations as $id => $name) {
                    $default_location = BusinessLocation::findOrFail($id);
                }
            }
            $location_id = $default_location ? $default_location->id : $request->input('location_id');
            $transfer_location_id = $default_location ? $default_location->id : $request->input('transfer_location_id');
            $input_data['location_id'] = $location_id;

            //Update reference count
            $ref_count = $this->productUtil->setAndGetReferenceCount('stock_transfer');
            //Generate reference number
            if (empty($input_data['ref_no'])) {
                $input_data['ref_no'] = $this->productUtil->generateReferenceNumber('stock_transfer', $ref_count);
            }

            $plate_stocks = $request->input('plate_stocks');
            $sell_lines = [];
            $purchase_lines = [];
            $input_data['final_total'] = 0;

            if (!empty($plate_stocks)) {
                foreach ($plate_stocks as $plate_stock) {
                    $current_plate_stock = PlateStock::find($plate_stock['id']);
                    $input_data['final_total'] += $current_plate_stock->width * $current_plate_stock->height * $current_plate_stock->variation->default_sell_price;

                    $sell_line_arr = [
                        'product_id' => $current_plate_stock->product_id,
                        'variation_id' => $current_plate_stock->variation_id,
                        'quantity' => $this->productUtil->num_uf($plate_stock['quantity']),
//                        'item_tax' => 0,
//                        'tax_id' => null
                    ];

                    $purchase_line_arr = $sell_line_arr;

                    $sell_lines[] = $sell_line_arr;
                    $purchase_lines[] = $purchase_line_arr;
                }
            }

            //Create Sell Transfer transaction
            $sell_transfer = Transaction::create($input_data);

            //Create Purchase Transfer at transfer location
            $input_data['type'] = 'purchase_transfer';
            $input_data['status'] = 'received';
            $input_data['transfer_parent_id'] = $sell_transfer->id;
            $input_data['location_id'] = $transfer_location_id;

            $purchase_transfer = Transaction::create($input_data);

            //And increase product stock at purchase location
            foreach ($plate_stocks as $plate_stock) {
                //TODO: Decrease plate stock from sell location
                $sell_plate_stock = PlateStock::find($plate_stock['id']);

                if ($sell_plate_stock->qty_available - $plate_stock['quantity'] < 0){
                    DB::rollBack();
                    $output = ['success' => 0,
                        'msg' => __('messages.transfer_out_of_stock')
                    ];
                    return redirect('stock-transfers')->with('status', $output);
                }

                $sell_plate_stock->qty_available -= $plate_stock['quantity'];
                $sell_plate_stock->save();

                TransactionSellLine::create([
                    'transaction_id' => $sell_transfer->id,
                    'product_id' => $sell_plate_stock->product_id,
                    'variation_id' => $sell_plate_stock->variation_id,
                    'quantity' => $plate_stock['quantity'] * $sell_plate_stock->width * $sell_plate_stock->height,
                    'quantity_returned' => 0,
                    'unit_price_before_discount' => $sell_plate_stock->variation->default_sell_price,
                    'unit_price' => $sell_plate_stock->variation->default_sell_price,
                    'line_discount_type' => 'fixed',
                    'line_discount_amount' => 0,
                    'unit_price_inc_tax' => $sell_plate_stock->variation->sell_price_inc_tax,
                    'item_tax' => 0,
                    'sub_unit_id' => $sell_plate_stock->product->unit_id,
                    'quantity_line' => $plate_stock['quantity'],
                    'width' => $sell_plate_stock->width,
                    'height' => $sell_plate_stock->height,
                    'warehouse_id_transfer_from' => $sell_plate_stock->warehouse_id,
                    'warehouse_id_transfer_to' => $plate_stock['warehouse_id'],
                    'is_origin' => $sell_plate_stock->is_origin,
                ]);

                //TODO: Increase product stock at purchase location
                $purchase_plate_stock = PlateStock::where('location_id', $transfer_location_id)
                    ->where('variation_id', $sell_plate_stock->variation_id)
                    ->whereRaw('width = ' . $sell_plate_stock->width)
                    ->whereRaw('height = ' . $sell_plate_stock->height)
                    ->where('warehouse_id', $plate_stock['warehouse_id'])
                    ->where('is_origin', $sell_plate_stock->is_origin)
                    ->first();

                if($purchase_plate_stock){
                    //Update new plate stock
                    $purchase_plate_stock->qty_available += $plate_stock['quantity'];
                    $purchase_plate_stock->save();
                }else{
                    //Create new plate stock
                    $purchase_plate_stock = PlateStock::create([
                        'product_id' => $sell_plate_stock->product_id,
                        'variation_id' => $sell_plate_stock->variation_id,
                        'location_id' => $transfer_location_id,
                        'width' => $sell_plate_stock->width,
                        'height' => $sell_plate_stock->height,
                        'warehouse_id' => $plate_stock['warehouse_id'],
                        'qty_available' => $plate_stock['quantity'],
                        'is_origin' => $sell_plate_stock->is_origin,
                    ]);
                }
            }

            $output = ['success' => 1,
                'msg' => __('lang_v1.stock_transfer_added_successfully')
            ];

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                'msg' => $e->getMessage()
            ];
        }

        return redirect('stock-transfers')->with('status', $output);
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
            'transaction_sell_lines as sl',
            'sl.transaction_id',
            '=',
            'transactions.id'
        )
            ->join('products as p', 'sl.product_id', '=', 'p.id')
            ->join('units', 'p.unit_id', '=', 'units.id')
            ->join('variations as v', 'sl.variation_id', '=', 'v.id')
            ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            ->where('transactions.id', $id)
            ->where('transactions.type', 'sell_transfer')
            ->leftjoin('purchase_lines as pl', 'sl.lot_no_line_id', '=', 'pl.id')
            ->leftjoin('warehouses as wh_from', 'sl.warehouse_id_transfer_from', '=', 'wh_from.id')
            ->leftjoin('warehouses as wh_to', 'sl.warehouse_id_transfer_to', '=', 'wh_to.id')
            ->select(
                'p.name as product',
                'p.type as type',
                'pv.name as product_variation',
                'v.name as variation',
                'v.sub_sku',
                'sl.width',
                'sl.height',
                'sl.quantity_line',
                'wh_from.name as warehouse_from',
                'wh_to.name as warehouse_to',
                'units.actual_name as unit_name',
                'units.type as unit_type'
//                'sl.quantity',
//                'sl.unit_price',
//                'pl.lot_number',
//                'pl.exp_date'
            )
            ->groupBy('sl.id')
            ->get();

        $lot_n_exp_enabled = false;
        if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
            $lot_n_exp_enabled = true;
        }

        return view('stock_transfer.partials.details')
            ->with(compact('stock_adjustment_details', 'lot_n_exp_enabled'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function destroy($id)
    {
        if (!auth()->user()->can('stock.to_deliver')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            if (request()->ajax()) {
                $edit_days = request()->session()->get('business.transaction_edit_days');
                if (!$this->transactionUtil->canBeEdited($id, $edit_days)) {
                    return ['success' => 0,
                        'msg' => __('messages.transaction_edit_not_allowed', ['days' => $edit_days])];
                }

                //Get sell transfer transaction
                $sell_transfer = Transaction::where('id', $id)
                    ->where('type', 'sell_transfer')
                    ->with(['sell_lines'])
                    ->first();

                //Get purchase transfer transaction
                $purchase_transfer = Transaction::where('transfer_parent_id', $sell_transfer->id)
                    ->where('type', 'purchase_transfer')
                    ->with(['purchase_lines'])
                    ->first();

                //Check if any transfer stock is deleted and delete purchase lines
                $purchase_lines = $purchase_transfer->purchase_lines;
                foreach ($purchase_lines as $purchase_line) {
                    if ($purchase_line->quantity_sold > 0) {
                        return [ 'success' => 0,
                            'msg' => __('lang_v1.stock_transfer_cannot_be_deleted')
                        ];
                    }
                }

                DB::beginTransaction();
                //Get purchase lines from transaction_sell_lines_purchase_lines and decrease quantity_sold
                $sell_lines = $sell_transfer->sell_lines;
                $checkSuccess = true;

                foreach ($sell_lines as $sell_line) {
                    $sell_plate_stock = PlateStock::query()
                        ->whereRaw('width = ' . $sell_line->width)
                        ->whereRaw('height = ' . $sell_line->height)
                        ->where('warehouse_id', $sell_line->warehouse_id_transfer_from)
                        ->where('variation_id', $sell_line->variation_id)
                        ->where('is_origin', $sell_line->is_origin)
                        ->first();

                    if (!empty($sell_plate_stock)) {
                        $sell_plate_stock->qty_available += $sell_line->quantity_line;
                        $sell_plate_stock->save();
                    }

                    $purchase_plate_stock = PlateStock::query()
                        ->whereRaw('width = ' . $sell_line->width)
                        ->whereRaw('height = ' . $sell_line->height)
                        ->where('warehouse_id', $sell_line->warehouse_id_transfer_to)
                        ->where('variation_id', $sell_line->variation_id)
                        ->where('location_id', $purchase_transfer->location_id)
                        ->where('is_origin', $sell_line->is_origin)
                        ->first();

                    if (!empty($purchase_plate_stock) && $purchase_plate_stock->qty_available >= $sell_line->quantity_line) {
                        $purchase_plate_stock->qty_available -= $sell_line->quantity_line;
                        $purchase_plate_stock->save();
                    } else {
                        $checkSuccess = false;
                        break;
                    }
                }

                if (!$checkSuccess) {
                    DB::rollBack();
                    return [
                        'success' => 0,
                        'msg' => __('lang_v1.stock_transfer_cannot_be_deleted')
                    ];
                }

                //Delete both transactions
                $sell_transfer->delete();
                $purchase_transfer->delete();

                $output = ['success' => 1,
                    'msg' => __('lang_v1.stock_transfer_delete_success')
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
     * Checks if ref_number and supplier combination already exists.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function printInvoice($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');

            $sell_transfer = Transaction::where('business_id', $business_id)
                ->where('id', $id)
                ->where('type', 'sell_transfer')
                ->with(
                    'contact',
                    'sell_lines',
                    'sell_lines.product',
                    'sell_lines.variations',
                    'sell_lines.variations.product_variation',
                    'sell_lines.lot_details',
                    'location',
                    'sell_lines.product.unit'
                )
                ->first();

            $purchase_transfer = Transaction::where('business_id', $business_id)
                ->where('transfer_parent_id', $sell_transfer->id)
                ->where('type', 'purchase_transfer')
                ->first();

            $stock_transfer_details = Transaction::
            join(
                'transaction_sell_lines as sl',
                'sl.transaction_id',
                '=',
                'transactions.id'
            )
                ->join('products as p', 'sl.product_id', '=', 'p.id')
                ->join('units', 'p.unit_id', '=', 'units.id')
                ->join('variations as v', 'sl.variation_id', '=', 'v.id')
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->where('transactions.id', $id)
                ->where('transactions.type', 'sell_transfer')
                ->leftjoin('purchase_lines as pl', 'sl.lot_no_line_id', '=', 'pl.id')
                ->leftjoin('warehouses as wh_from', 'sl.warehouse_id_transfer_from', '=', 'wh_from.id')
                ->leftjoin('warehouses as wh_to', 'sl.warehouse_id_transfer_to', '=', 'wh_to.id')
                ->select(
                    'p.name as product',
                    'p.type as type',
                    'pv.name as product_variation',
                    'units.actual_name as unit_name',
                    'units.type as unit_type',
                    'v.name as variation',
                    'v.sub_sku',
                    'sl.width',
                    'sl.height',
                    'sl.quantity_line',
                    'wh_from.name as warehouse_from',
                    'wh_to.name as warehouse_to'
                )
                ->groupBy('sl.id')
                ->get();

            $location_details = ['sell' => $sell_transfer->location, 'purchase' => $purchase_transfer->location];

            $lot_n_exp_enabled = false;
            if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                $lot_n_exp_enabled = true;
            }


            $output = ['success' => 1, 'receipt' => []];
            $output['receipt']['html_content'] = view('stock_transfer.print', compact('stock_transfer_details', 'sell_transfer', 'location_details', 'lot_n_exp_enabled'))->render();
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong')
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

        //Filter by warehouse
        $permitted_warehouses = auth()->user()->getPermittedWarehouses();
        if ($permitted_warehouses != 'all') {
            $warehouses = Warehouse::forDropdown($business_id, false, [], $permitted_warehouses);
        }else{
            $warehouses = Warehouse::forDropdown($business_id);
        }

        return view('stock_transfer.partials.sell_entry_row')
            ->with(compact('plate_stocks', 'warehouses'));
    }
}
