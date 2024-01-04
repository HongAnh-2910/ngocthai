<?php

namespace App\Http\Controllers;

use App\AccountTransaction;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Contact;
use App\CustomerGroup;
use App\Product;
use App\PurchaseLine;
use App\Target;
use App\TaxRate;
use App\Transaction;
use App\Unit;
use App\User;
use App\Utils\BusinessUtil;

use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;

use App\Variation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\Util;
use App\TargetSaleLine;
use App\TargetCategoryLine;

class TargetController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $productUtil;
    protected $transactionUtil;
    protected $moduleUtil;
    protected $util;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil, BusinessUtil $businessUtil, ModuleUtil $moduleUtil, Util $util)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;
        $this->util = $util;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('target.view') && !auth()->user()->can('target.create')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {

            $targets = Target::where('business_id', $business_id)
                    ->select(
                    'targets.id',
                    'targets.start_date',
                    'targets.end_date',
                    'targets.type',
                    'targets.amount',
                    'profit'
                );

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $targets->whereDate('targets.start_date', '>=', $start)
                    ->whereDate('targets.end_date', '<=', $end);
            }
            return Datatables::of($targets)
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
                        if (auth()->user()->can("target.view")) {
                            $html .= '<li><a href="#" data-href="' . action('TargetController@show', [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fa fa-eye" aria-hidden="true"></i>' . __("messages.view") . '</a></li>';
                        }
                        if (auth()->user()->can("target.update")) {
                            $html .= '<li><a href="' . action('TargetController@edit', [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i>' . __("messages.edit") . '</a></li>';
                        }
                        if (auth()->user()->can("target.delete")) {
                            $html .= '<li><a href="' . action('TargetController@destroy', [$row->id]) . '" class="delete-purchase"><i class="fa fa-trash"></i>' . __("messages.delete") . '</a></li>';
                        }
                        $html .= '</ul></div>';
                        return $html;
                    }
                )
                ->removeColumn('id')
                ->editColumn('start_date', '{{@format_date($start_date)}}')
                ->editColumn('end_date', '{{@format_date($end_date)}}')
                ->editColumn(
                    'type',
                    '{{__(\'target.type_\' . $type)}}'
                )
                ->addColumn('target_total', function ($row) {
                    $target_total_html = __('target.type_'.$row->type).':<br>';
                    switch ($row->type){
                        case 'amount':
                            $target_total_html .= '<b>'.number_format($row->amount).' đ'.'</b>';
                            break;
                        case 'product':
                            foreach ($row->target_sale_lines as $target_sale_line){
                                $variable = '';
                                $unit = $target_sale_line->product->unit->type == Unit::PCS ? $target_sale_line->product->unit->actual_name : 'm<sup>2</sup>';

                                if ( $target_sale_line->product->type == 'variable' ) {
                                    $variable = " ({$target_sale_line->variation->product_variation->name} - {$target_sale_line->variation->name})";
                                }

                                $target_total_html .= '<b>'. ($target_sale_line->product->unit->type == Unit::PCS ? number_format($target_sale_line->quantity) : number_format($target_sale_line->quantity, 2)) . ' ' . $unit . ' </b> ' . $target_sale_line->product->name . $variable . '<br>';
                            }
                            break;
                        case 'category':
                            foreach ($row->target_category_lines as $target_category_line){
                                if ($target_category_line->sub_category_id){
                                    $target_total_html .= '<b>'.number_format($target_category_line->quantity, 2).' m<sup>2</sup></b> '.$target_category_line->category->name.' <b>>></b> '.$target_category_line->sub_category->name.'<br>';
                                }else{
                                    $target_total_html .= '<b>'.number_format($target_category_line->quantity, 2).' m<sup>2</sup></b> '.$target_category_line->category->name.'<br>';
                                }
                            }
                            break;
                        case 'profit':
                            $target_total_html .= '<b>'.number_format($row->profit).' đ</b>';
                            break;
                    }
                    return $target_total_html;
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        return  action('TargetController@show', [$row->id]) ;
                    }])
                ->rawColumns(['action', 'target_total'])
                ->make(true);
        }

        return view('target.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('target.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);

        $categories = Category::forDropdown($business_id);
        $types = $this->util->target_types();
        $type_default = 'revenue';

        return view('target.create')
            ->with(compact('currency_details', 'categories', 'types', 'type_default'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('target.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $target_data = $request->only(['type', 'note', 'start_date', 'end_date']);

            //TODO: Check for "Undefined index: total_before_tax" issue
            //Adding temporary fix by validating
            $request->validate([
                'type' => 'required',
                'start_date' => 'required',
                'end_date' => 'required'
            ]);

            $user_id = $request->session()->get('user.id');
            $target_data['business_id'] = $business_id;
            $target_data['created_by'] = $user_id;

            DB::beginTransaction();

            switch ($target_data['type']){
                case 'amount':
                    $request->input('amount');
                    $target_data['amount'] = $this->transactionUtil->num_uf($request->input('amount'));
                    break;
                case 'profit':
                    $target_data['profit'] = $this->transactionUtil->num_uf($request->input('profit'));
                    break;
            }
            $target = Target::create($target_data);

            switch ($target_data['type']){
                case 'product':
                    $products = $request->input('purchases');
                    $target_sale_line_data = [];
                    foreach ($products as $key => $product){
                        if ($product['quantity'] > 0){
                            $target_sale_line_data[$key]['target_id'] = $target->id;
                            $target_sale_line_data[$key]['product_id'] = $product['product_id'];
                            $target_sale_line_data[$key]['variation_id'] = $product['variation_id'];
                            $target_sale_line_data[$key]['quantity'] = $product['quantity'];
                            $target_sale_line_data[$key]['created_at'] = date('Y-m-d H:i:s');
                            $target_sale_line_data[$key]['updated_at'] = date('Y-m-d H:i:s');
                            $target_sale_line_data[$key]['business_id'] = $business_id;
                            $target_sale_line_data[$key]['created_by'] = $user_id;
                        }
                    }
                    TargetSaleLine::insert($target_sale_line_data);
                    break;
                case 'category':
                    $target_category_line_data = [];
                    $category_ids = $request->input('category_ids');
                    $sub_category_ids = $request->input('sub_category_ids');
                    $quantities = $request->input('quantities');

                    foreach ($category_ids as $key => $category_id){
                        $target_category_line_data[$key]['target_id'] = $target->id;
                        $target_category_line_data[$key]['category_id'] = $category_id;
                        $target_category_line_data[$key]['sub_category_id'] = $sub_category_ids[$key] ? $sub_category_ids[$key] : null;
                        $target_category_line_data[$key]['quantity'] = $quantities[$key];
                        $target_category_line_data[$key]['created_at'] = date('Y-m-d H:i:s');
                        $target_category_line_data[$key]['updated_at'] = date('Y-m-d H:i:s');
                    }
                    TargetCategoryLine::insert($target_category_line_data);
                    break;
            }
            DB::commit();

            $output = ['success' => 1,
                'msg' => __('target.target_add_success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect('targets')->with('status', $output);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!auth()->user()->can('target.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $taxes = TaxRate::where('business_id', $business_id)
            ->pluck('name', 'id');
        $target = Target::where('business_id', $business_id)
            ->where('id', $id)
            ->with(
                'target_sale_lines.product.unit',
                'target_category_lines'
            )
            ->firstOrFail();

        $categoryIds = $target->target_category_lines->map(function ($item) {
            return $item->category_id;
        });

        $variationIds = $target->target_sale_lines->map(function ($item) {
            return $item->variation_id;
        });

        $categories = Category::query()->whereIn('id', $categoryIds)->pluck('name', 'id')->toArray();
        $variations = Variation::dropdownForAward([], $variationIds);

        return view('target.show')
            ->with(compact('taxes', 'target', 'categories', 'variations'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('target.update')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);

        $target = Target::where('business_id', $business_id)
            ->where('id', $id)
            ->with(
                'target_sale_lines',
                'target_sale_lines.variation.product.unit',
                'target_sale_lines.variation.product_variation',
                'target_category_lines'
            )
            ->first();
        $categories = Category::forDropdown($business_id);
        $types = $this->util->target_types();

        return view('target.edit')
            ->with(compact(
                'target',
                'currency_details',
                'categories',
                'types'
            ));
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
        if (!auth()->user()->can('target.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $request->validate([
                'type' => 'required',
                'start_date' => 'required',
                'end_date' => 'required'
            ]);

            $target = Target::query()->findOrFail($id);
            $business_id = request()->session()->get('user.business_id');
            $user_id = request()->session()->get('user.id');
            $update_data = $request->only(['type', 'note', 'start_date', 'end_date']);
            $target->target_sale_lines()->delete();
            $target->target_category_lines()->delete();

            DB::beginTransaction();

            switch ($update_data['type']){
                case 'amount':
                    $update_data['amount'] = $this->transactionUtil->num_uf($request->input('amount'));
                    $update_data['profit'] = 0;
                    break;
                case 'profit':
                    $update_data['profit'] = $this->transactionUtil->num_uf($request->input('profit'));
                    $update_data['amount'] = 0;
                    break;
            }
            $target->update($update_data);

            switch ($update_data['type']){
                case 'product':
                    //Remove old data
//                    $target->target_sale_lines()->delete();
                    //TargetSaleLine::where('target_id', $id)->delete();

                    //Create new data
                    $products = $request->input('purchases');
                    $target_sale_line_data = [];
                    foreach ($products as $key => $product){
                        if ($product['quantity'] > 0){
                            $target_sale_line_data[$key]['target_id'] = $target->id;
                            $target_sale_line_data[$key]['product_id'] = $product['product_id'];
                            $target_sale_line_data[$key]['variation_id'] = $product['variation_id'];
                            $target_sale_line_data[$key]['quantity'] = $this->transactionUtil->num_uf($product['quantity']);
                            $target_sale_line_data[$key]['created_at'] = date('Y-m-d H:i:s');
                            $target_sale_line_data[$key]['updated_at'] = date('Y-m-d H:i:s');
                            $target_sale_line_data[$key]['business_id'] = $business_id;
                            $target_sale_line_data[$key]['created_by'] = $user_id;
                        }
                    }
                    TargetSaleLine::insert($target_sale_line_data);
                    break;
                case 'category':
                    //Remove old data
//                    $target->target_category_lines()->delete();
                    //TargetCategoryLine::where('target_id', $id)->delete();

                    //Create new data
                    $target_category_line_data = [];
                    $category_ids = $request->input('category_ids');
                    $sub_category_ids = $request->input('sub_category_ids');
                    $quantities = $request->input('quantities');

                    foreach ($category_ids as $key => $category_id){
                        $target_category_line_data[$key]['target_id'] = $target->id;
                        $target_category_line_data[$key]['category_id'] = $category_id;
                        $target_category_line_data[$key]['sub_category_id'] = $sub_category_ids[$key] ? $sub_category_ids[$key] : null;
                        $target_category_line_data[$key]['quantity'] = $this->transactionUtil->num_uf($quantities[$key]);
                        $target_category_line_data[$key]['created_at'] = date('Y-m-d H:i:s');
                        $target_category_line_data[$key]['updated_at'] = date('Y-m-d H:i:s');
                    }
                    TargetCategoryLine::insert($target_category_line_data);
                    break;
            }
            DB::commit();

            $output = ['success' => 1,
                'msg' => __('target.target_update_success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                'msg' => $e->getMessage()
            ];
            return back()->with('status', $output);
        }

        return redirect('targets')->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('target.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            if (request()->ajax()) {
                $business_id = request()->session()->get('user.business_id');

                $target = Target::where('id', $id)
                    ->where('business_id', $business_id)
                    ->with(['target_sale_lines', 'target_category_lines'])
                    ->first();

                DB::beginTransaction();

                /*switch ($target->type){
                    case 'product':
                        $target->target_sale_lines()->delete();
                        break;
                    case 'category':
                        $target->target_category_lines()->delete();
                        break;
                }*/
                $target->target_sale_lines()->delete();
                $target->target_category_lines()->delete();
                $target->delete();

                DB::commit();

                $output = ['success' => true,
                    'msg' => __('lang_v1.target_delete_success')
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => false,
                'msg' => $e->getMessage()
            ];
        }

        return $output;
    }

    /**
     * Retrieves products list.
     *
     * @return \Illuminate\Http\Response
     */
    public function getProducts()
    {
        if (request()->ajax()) {
            $term = request()->term;

            $check_enable_stock = true;
            if (isset(request()->check_enable_stock)) {
                $check_enable_stock = filter_var(request()->check_enable_stock, FILTER_VALIDATE_BOOLEAN);
            }

            if (empty($term)) {
                return json_encode([]);
            }

            $business_id = request()->session()->get('user.business_id');
            $q = Product::leftJoin(
                'variations',
                'products.id',
                '=',
                'variations.product_id'
            )
                ->where(function ($query) use ($term) {
                    $query->where('products.name', 'like', '%' . $term .'%');
                    $query->orWhere('sku', 'like', '%' . $term .'%');
                    $query->orWhere('sub_sku', 'like', '%' . $term .'%');
                })
                ->active()
                ->whereNull('variations.deleted_at')
                ->select(
                    'products.id as product_id',
                    'products.name',
                    'products.type',
                    // 'products.sku as sku',
                    'variations.id as variation_id',
                    'variations.name as variation',
                    'variations.sub_sku as sub_sku'
                )
                ->groupBy('variation_id');

            if ($check_enable_stock) {
                $q->where('enable_stock', 1);
            }
            $products = $q->get();

            $products_array = [];
            foreach ($products as $product) {
                $products_array[$product->product_id]['name'] = $product->name;
                $products_array[$product->product_id]['sku'] = $product->sub_sku;
                $products_array[$product->product_id]['type'] = $product->type;
                $products_array[$product->product_id]['variations'][]
                    = [
                    'variation_id' => $product->variation_id,
                    'variation_name' => $product->variation,
                    'sub_sku' => $product->sub_sku
                ];
            }

            $result = [];
            $i = 1;
            $no_of_records = $products->count();
            if (!empty($products_array)) {
                foreach ($products_array as $key => $value) {
                    if ($no_of_records > 1 && $value['type'] != 'single') {
                        $result[] = [ 'id' => $i,
                            'text' => $value['name'] . ' - ' . $value['sku'],
                            'variation_id' => 0,
                            'product_id' => $key
                        ];
                    }
                    $name = $value['name'];
                    foreach ($value['variations'] as $variation) {
                        $text = $name;
                        if ($value['type'] == 'variable') {
                            $text = $text . ' (' . $variation['variation_name'] . ')';
                        }
                        $i++;
                        $result[] = [ 'id' => $i,
                            'text' => $text . ' - ' . $variation['sub_sku'],
                            'product_id' => $key ,
                            'variation_id' => $variation['variation_id'],
                        ];
                    }
                    $i++;
                }
            }

            return json_encode($result);
        }
    }

    /**
     * Retrieves products list.
     *
     * @return \Illuminate\Http\Response
     */
    public function getPurchaseEntryRow(Request $request)
    {
        if (request()->ajax()) {
            $product_id = $request->input('product_id');
            $variation_id = $request->input('variation_id');
            $business_id = request()->session()->get('user.business_id');

            $hide_tax = 'hide';
            if ($request->session()->get('business.enable_inline_tax') == 1) {
                $hide_tax = '';
            }

            $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);

            if (!empty($product_id)) {
                $row_count = $request->input('row_count');
                $product = Product::where('id', $product_id)
                    ->with(['unit'])
                    ->first();

                $sub_units = $this->productUtil->getSubUnits($business_id, $product->unit->id, false, $product_id);

                $query = Variation::where('product_id', $product_id)
                    ->with(['product_variation', 'product.unit']);
                if ($variation_id !== '0') {
                    $query->where('id', $variation_id);
                }

                $variations =  $query->get();

                $taxes = TaxRate::where('business_id', $business_id)
                    ->get();

                return view('target.partials.purchase_entry_row')
                    ->with(compact(
                        'product',
                        'variations',
                        'row_count',
                        'variation_id',
                        'taxes',
                        'currency_details',
                        'hide_tax',
                        'sub_units'
                    ));
            }
        }
    }

    public function getCategoryEntryRow(Request $request)
    {
        if (request()->ajax()) {
            $value = $request->input('value');

            $business_id  = session()->get('user.business_id');
            $categories = Category::forDropdown($business_id);

            return view('target.partials.category_entry_row')
                ->with(compact(
                    'categories',
                    'value'
                ));
        }
    }
}
