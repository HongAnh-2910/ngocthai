<?php

namespace App\Http\Controllers;

use App\Brands;
use App\Business;
use App\BusinessLocation;
use App\CashRegister;
use App\Category;

use App\Charts\CommonChart;
use App\Contact;

use App\CustomerGroup;
use App\ExpenseCategory;
use App\Exports\RevenueByDayReportExport;
use App\PlateStock;
use App\Product;
use App\PurchaseLine;
use App\Restaurant\ResTable;
use App\SellingPriceGroup;
use App\Target;
use App\Transaction;
use App\TransactionExpense;
use App\TransactionPayment;
use App\TransactionPlateLine;
use App\TransactionReceipt;
use App\TransactionSellLine;
use App\TransactionSellLinesPurchaseLines;
use App\TransactionShip;
use App\Unit;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use App\Variation;
use App\VariationLocationDetails;
use App\Warehouse;
use Box\Spout\Writer\Style\StyleBuilder;
use Carbon\Carbon;
//use Datatables;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Rap2hpoutre\FastExcel\FastExcel;
use Yajra\DataTables\DataTables;

class ReportController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $transactionUtil;
    protected $productUtil;
    protected $moduleUtil;
    protected $contactUtil;
    protected $businessUtil;
    protected $util;

    /**
     * Create a new controller instance.
     * @param $transactionUtil
     * @param $businessUtil
     * @param $productUtil
     * @param $moduleUtil
     * @param $contactUtil
     * @param $util
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil, BusinessUtil $businessUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil, ContactUtil $contactUtil, Util $util)
    {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;
        $this->contactUtil = $contactUtil;
        $this->businessUtil = $businessUtil;
        $this->util = $util;

        $this->shipping_status_colors = [
            'not_shipped' => 'bg-yellow',
            'shipped' => 'bg-green',
        ];
    }

    /**
     * Shows profit\loss of a business
     *
     * @return \Illuminate\Http\Response
     */
    public function getProfitLoss(Request $request)
    {
        if (!auth()->user()->can('profit_loss_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $location_id = $request->get('location_id');

            //For Opening stock date should be 1 day before
            $day_before_start_date = \Carbon::createFromFormat('Y-m-d', $start_date)->subDay()->format('Y-m-d');
            //Get Opening stock
            $opening_stock = $this->transactionUtil->getOpeningClosingStock($business_id, $day_before_start_date, $location_id, true);

            //Get Closing stock
            $closing_stock = $this->transactionUtil->getOpeningClosingStock(
                $business_id,
                $end_date,
                $location_id
            );

            //Get Purchase details
            $purchase_details = $this->transactionUtil->getPurchaseTotals(
                $business_id,
                $start_date,
                $end_date,
                $location_id
            );

            //Get Sell details
            $sell_details = $this->transactionUtil->getSellTotals(
                $business_id,
                $start_date,
                $end_date,
                $location_id
            );

            $transaction_types = [
                'purchase_return', 'sell_return', 'expense', 'stock_adjustment', 'sell_transfer', 'purchase', 'sell'
            ];

            $transaction_totals = $this->transactionUtil->getTransactionTotals(
                $business_id,
                $transaction_types,
                $start_date,
                $end_date,
                $location_id
            );

            $gross_profit = $this->transactionUtil->getGrossProfit(
                $business_id,
                $start_date,
                $end_date,
                $location_id
            );

            $data = [];

            $data['total_purchase_shipping_charge'] = $purchase_details['total_shipping_charges'] ?? 0;
            $data['total_sell_shipping_charge'] = !empty($sell_details['total_shipping_charges']) ? $sell_details['total_shipping_charges'] : 0;
            //Shipping
            $data['total_transfer_shipping_charges'] = !empty($transaction_totals['total_transfer_shipping_charges']) ? $transaction_totals['total_transfer_shipping_charges'] : 0;
            //Discounts
            $total_purchase_discount = $transaction_totals['total_purchase_discount'];
            $total_sell_discount = $transaction_totals['total_sell_discount'];
            $total_reward_amount = $transaction_totals['total_reward_amount'];
            $total_sell_round_off = $transaction_totals['total_sell_round_off'];

            //Stocks
            $data['opening_stock'] = !empty($opening_stock) ? $opening_stock : 0;
            $data['closing_stock'] = !empty($closing_stock) ? $closing_stock : 0;

            //Purchase
            $data['total_purchase'] = !empty($purchase_details['total_purchase_inc_tax']) ? $purchase_details['total_purchase_inc_tax'] : 0;
            $data['total_purchase_discount'] = !empty($total_purchase_discount) ? $total_purchase_discount : 0;
            $data['total_purchase_return'] = $transaction_totals['total_purchase_return_exc_tax'];

            //Sales
            $data['total_sell'] = !empty($sell_details['total_sell_exc_tax']) ? $sell_details['total_sell_exc_tax'] : 0;
            $data['total_sell_discount'] = !empty($total_sell_discount) ? $total_sell_discount : 0;
            $data['total_sell_return'] = $transaction_totals['total_sell_return_inc_tax'];
            $data['total_sell_round_off'] = !empty($total_sell_round_off) ? $total_sell_round_off : 0;
            $data['total_sell_tax'] = !empty($sell_details['total_sell_tax']) ? $sell_details['total_sell_tax'] : 0;

            //Expense
            $data['expense_for_customer'] =  $transaction_totals['expense_for_customer'];
//            $data['other_expense'] =  $transaction_totals['other_expense'];
            $data['total_expense'] =  $data['expense_for_customer'] + $data['total_purchase'] + $data['total_transfer_shipping_charges'];
//            $data['total_expense'] =  $data['expense_for_customer'] + $data['other_expense'] + $data['total_purchase'] + $data['total_transfer_shipping_charges'];

            //Stock adjustments
            $data['total_adjustment'] = $transaction_totals['total_adjustment'];
            $data['total_recovered'] = $transaction_totals['total_recovered'];

            // $data['closing_stock'] = $data['closing_stock'] - $data['total_adjustment'];

            $data['total_reward_amount'] = !empty($total_reward_amount) ? $total_reward_amount : 0;

            // $data['closing_stock'] = $data['closing_stock'] - $data['total_sell_return'];
            $module_parameters = [
                'business_id' => $business_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'location_id' => $location_id
            ];
            $modules_data = $this->moduleUtil->getModuleData('profitLossReportData', $module_parameters);

            $data['left_side_module_data'] = [];
            $data['right_side_module_data'] = [];
            $module_total = 0;
            if (!empty($modules_data)) {
                foreach ($modules_data as $module_data) {
                    if (!empty($module_data[0])) {
                        foreach ($module_data[0] as $array) {
                            $data['left_side_module_data'][] = $array;
                            if (!empty($array['add_to_net_profit'])) {
                                $module_total -= $array['value'];
                            }
                        }
                    }
                    if (!empty($module_data[1])) {
                        foreach ($module_data[1] as $array) {
                            $data['right_side_module_data'][] = $array;
                            if (!empty($array['add_to_net_profit'])) {
                                $module_total += $array['value'];
                            }
                        }
                    }
                }
            }

            $data['net_profit'] = $module_total + $data['total_sell']
                                    + $data['closing_stock']
                                    - $data['total_purchase']
                                    - $data['total_sell_discount']
                                    + $data['total_sell_round_off']
                                    - $data['total_reward_amount']
                                    - $data['opening_stock']
                                    - $data['total_expense']
                                    + $data['total_recovered']
                                    - $data['total_transfer_shipping_charges']
                                    - $data['total_purchase_shipping_charge']
                                    + $data['total_sell_shipping_charge']
                                    + $data['total_purchase_discount']
                                    + $data['total_purchase_return']
                                    - $data['total_sell_return'];

            //get gross profit from Project Module
            $module_parameters = [
                'business_id' => $business_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'location_id' => $location_id
            ];
            $project_module_data = $this->moduleUtil->getModuleData('grossProfit', $module_parameters);

            if (!empty($project_module_data['Project']['gross_profit'])) {
                $gross_profit = $gross_profit + $project_module_data['Project']['gross_profit'];
                $data['gross_profit_label'] = __('project::lang.project_invoice');
            }

            $data['gross_profit'] = $gross_profit;

            //get sub type for total sales
            $sales_by_subtype = Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'final');
            if (!empty($start_date) && !empty($end_date)) {
                if ($start_date == $end_date) {
                    $sales_by_subtype->whereDate('transaction_date', $end_date);
                } else {
                    $sales_by_subtype->whereBetween(DB::raw('transaction_date'), [$start_date, $end_date]);
                }
            }
            $sales_by_subtype = $sales_by_subtype->select(DB::raw('SUM(total_before_tax) as total_before_tax'), 'sub_type')
                ->groupBy('transactions.sub_type')
                ->get();
            $data['total_sell_by_subtype'] = $sales_by_subtype;
            $data['total_revenue'] = $data['total_sell'] - $data['total_sell_discount'] + $data['total_sell_shipping_charge'] + $data['total_sell_tax'] + $data['total_recovered'] - $data['total_sell_return'];
            $data['total_profit'] = $data['total_revenue'] - $data['total_expense'];

            return view('report.partials.profit_loss_details', compact('data'))->render();
        }

        $business_locations = BusinessLocation::forDropdown($business_id, true);
        return view('report.profit_loss', compact('business_locations'));
    }

    /**
     * Shows product report of a business
     *
     * @return \Illuminate\Http\Response
     */
    public function getPurchaseSell(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');

            $location_id = $request->get('location_id');

            $purchase_details = $this->transactionUtil->getPurchaseTotals($business_id, $start_date, $end_date, $location_id);

            $sell_details = $this->transactionUtil->getSellTotals(
                $business_id,
                $start_date,
                $end_date,
                $location_id
            );

            $transaction_types = [
                'purchase_return', 'sell_return'
            ];

            $transaction_totals = $this->transactionUtil->getTransactionTotals(
                $business_id,
                $transaction_types,
                $start_date,
                $end_date,
                $location_id
            );

            $total_purchase_return_inc_tax = $transaction_totals['total_purchase_return_inc_tax'];
            $total_sell_return_inc_tax = $transaction_totals['total_sell_return_inc_tax'];

            $difference = [
                'total' => $sell_details['total_sell_inc_tax'] + $total_sell_return_inc_tax - $purchase_details['total_purchase_inc_tax'] - $total_purchase_return_inc_tax,
                'due' => $sell_details['invoice_due'] - $purchase_details['purchase_due']
            ];

            return ['purchase' => $purchase_details,
                    'sell' => $sell_details,
                    'total_purchase_return' => $total_purchase_return_inc_tax,
                    'total_sell_return' => $total_sell_return_inc_tax,
                    'difference' => $difference
                ];
        }

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.purchase_sell')
                    ->with(compact('business_locations'));
    }

    /**
     * Shows report for Supplier
     *
     * @return \Illuminate\Http\Response
     */
    public function getCustomerSuppliers(Request $request)
    {
        if (!auth()->user()->can('contacts_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $total_invoice_query = "SUM(IF(t.type = 'sell' AND t.status = 'final', final_total, 0))";
            $invoice_received_query = "SUM(IF((t.type = 'sell' OR t.type = 'receipt' OR t.type = 'expense') AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id AND approval_status='approved'), 0))";
            $sell_return_paid_query = "SUM(IF(t.type = 'sell_return' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id AND approval_status='approved'), 0))";
            $total_sell_return_query = "SUM(IF(t.type = 'sell_return' AND t.status = 'final', final_total, 0))";
            $opening_balance_query = "SUM(IF(t.type = 'opening_balance', final_total, 0))";
            $total_purchase_query = "SUM(IF(t.type = 'purchase', final_total, 0))";
            $reduce_debt_query = "SUM(IF(t.type = 'reduce_debt' AND t.status = 'final', final_total, 0))";
            $opening_balance_paid_query = "SUM(IF(t.type = 'opening_balance', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0))";
            $due_query = "$total_invoice_query - $invoice_received_query + $sell_return_paid_query - $total_sell_return_query + $opening_balance_query - $total_purchase_query - $reduce_debt_query";

            $contacts = Contact::query()->leftjoin('transactions AS t', 'contacts.id', '=', 't.contact_id')
                ->leftjoin('customer_groups AS cg', 'contacts.customer_group_id', '=', 'cg.id')
                ->active()
                ->where('contacts.business_id', $business_id)
                ->select([
                    DB::raw("$total_invoice_query as total_invoice"),
                    DB::raw("$invoice_received_query as invoice_received"),
                    DB::raw("$total_sell_return_query as total_sell_return"),
                    DB::raw("$sell_return_paid_query as sell_return_paid"),
                    DB::raw("$opening_balance_query as opening_balance"),
                    DB::raw("$opening_balance_paid_query as opening_balance_paid"),
                    DB::raw("SUM(IF(t.type = 'purchase' AND t.status NOT IN ('cancel', 'draft'), (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as purchase_paid"),
                    DB::raw("SUM(IF(t.type = 'purchase_return' AND t.status NOT IN ('cancel', 'draft'), (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as purchase_return_received"),
                    DB::raw("SUM(IF(t.type = 'sell_return' AND t.status NOT IN ('cancel', 'draft'), (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id AND transaction_payments.approval_status = 'approved'), 0)) as invoice_return"),
                    DB::raw("(SUM(IF(t.type = 'sell' AND t.status NOT IN ('cancel', 'draft'), final_total, 0)) - SUM(IF(t.type = 'sell_return' AND t.status NOT IN ('cancel', 'draft'), t.final_total, 0))) as total_revenue"),
                    DB::raw("$total_purchase_query as total_purchase"),
                    DB::raw("$reduce_debt_query as reduce_debt"),
                    DB::raw("IF(($due_query) IS NULL OR ($due_query) = '', 0, $due_query) as due"),
//                    DB::raw("IF(($due_query) IS NULL OR ($due_query) = '', 0, ROUND($due_query)) as due"),
                    DB::raw("($opening_balance_query - $opening_balance_paid_query) as opening_balance_due"),
                    'contacts.supplier_business_name',
                    'contacts.name',
                    'contacts.id',
                    'contacts.contact_id',
                    'contacts.type as contact_type'
                ])
                ->groupBy('contacts.id');

            $permitted_locations = auth()->user()->permitted_locations();

            if ($permitted_locations != 'all') {
                $contacts->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($request->input('customer_group_id'))) {
                $contacts->where('contacts.customer_group_id', $request->input('customer_group_id'));
            }

            if (!empty($request->input('contact_type'))) {
                $contacts->whereIn('contacts.type', [$request->input('contact_type'), 'both']);
            }

            return Datatables::of($contacts)
                ->editColumn('name', function ($row) {
                    $name = $row->name;
                    if (!empty($row->supplier_business_name)) {
                        $name .= ', ' . $row->supplier_business_name;
                    }
                    return '<a href="' . action('ContactController@show', [$row->id]) . '" target="_blank" class="no-print">' .
                        $name .
                        '</a>';
                })
                ->editColumn('total_sell_return', function ($row) {
                    return '<span class="display_currency total_sell_return" data-orig-value="' . $row->total_sell_return . '" data-currency_symbol = true>' . $row->total_sell_return . '</span>';
                })
                ->editColumn('total_invoice', function ($row) {
                    return '<span class="display_currency total_invoice" data-orig-value="' . $row->total_invoice . '" data-currency_symbol = true>' . $row->total_invoice . '</span>';
                })
                ->editColumn('total_revenue', function ($row) {
                    return '<span class="display_currency total_revenue" data-orig-value="' . $row->total_revenue . '" data-currency_symbol = true>' . $row->total_revenue . '</span>';
                })
//                ->editColumn('due', function ($row) {
//                    return '<span class="display_currency total_due" data-test="_' . $row->due . '_" data-orig-value="' . $row->due . '" data-currency_symbol=true data-highlight=true>' . $row->due .'</span>';
//                })
//                ->addColumn('due', function ($row) {
//                    $html = '<span class="display_currency contact_due" data-currency_symbol="true" data-orig-value="' . $row->due . '" data-highlight=true>' . $row->due . '</span>';
//                    return $html;
//                })
                ->addColumn('due', function ($row) {
                    $due = $row->total_invoice - $row->invoice_received + $row->sell_return_paid - $row->total_sell_return + $row->opening_balance - $row->total_purchase - $row->reduce_debt;
                    $html = '<span class="display_currency contact_due" data-currency_symbol="true" data-orig-value="' . $due . '" data-highlight=true>' . $due . '</span>';
                    return $html;
                })
                ->editColumn(
                    'opening_balance_due',
                    '<span class="display_currency opening_balance_due" data-currency_symbol=true data-orig-value="{{$opening_balance - $opening_balance_paid}}">{{$opening_balance - $opening_balance_paid}}</span>'
                )
                ->orderColumn('due', function ($query, $order) {
                    $query->orderByRaw('due '.$order);
                })
                ->removeColumn('supplier_business_name')
                ->removeColumn('invoice_received')
                ->removeColumn('purchase_paid')
                ->removeColumn('id')
                ->rawColumns(['total_invoice', 'due', 'name', 'total_sell_return', 'opening_balance_due', 'total_revenue'])
                ->make(true);
        }

        $customer_group = CustomerGroup::forDropdown($business_id, false, true);
        $types = [
            '' => __('lang_v1.all'),
            'customer' => __('report.customer'),
            'supplier' => __('report.supplier')
        ];

        return view('report.contact')
        ->with(compact('customer_group', 'types'));
    }

    public function queryCalTotalDebt($business_id) {
        $total_invoice_query = "SUM(IF(t.type = 'sell' AND t.status = 'final', final_total, 0))";
        $invoice_received_query = "SUM(IF((t.type = 'sell' OR t.type = 'receipt' OR t.type = 'expense') AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id AND approval_status='approved'), 0))";
        $sell_return_paid_query = "SUM(IF(t.type = 'sell_return' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id AND approval_status='approved'), 0))";
        $total_sell_return_query = "SUM(IF(t.type = 'sell_return' AND t.status = 'final', final_total, 0))";
        $opening_balance_query = "SUM(IF(t.type = 'opening_balance', final_total, 0))";
        $total_purchase_query = "SUM(IF(t.type = 'purchase', final_total, 0))";
        $reduce_debt_query = "SUM(IF(t.type = 'reduce_debt' AND t.status = 'final', final_total, 0))";
        $due_query = "$total_invoice_query - $invoice_received_query + $sell_return_paid_query - $total_sell_return_query + $opening_balance_query - $total_purchase_query - $reduce_debt_query";

        return Contact::leftjoin('transactions AS t', 'contacts.id', '=', 't.contact_id')
            ->leftjoin('customer_groups AS cg', 'contacts.customer_group_id', '=', 'cg.id')
            ->active()
            ->where('contacts.business_id', $business_id)
            ->select([
                DB::raw("(IF(($due_query) IS NULL OR ($due_query) = '', 0, IF ($due_query >= 0, ROUND($due_query), 0))) as credit"),
                DB::raw("(IF(($due_query) IS NULL OR ($due_query) = '', 0, IF ($due_query < 0, ROUND($due_query), 0))) as debit"),
            ])
            ->groupBy('contacts.id');
    }

    public function calculateTotalDue(Request $request){
        if (!auth()->user()->can('contacts_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $sum = $this->queryCalTotalDebt($business_id);

            $permitted_locations = auth()->user()->permitted_locations();

            if ($permitted_locations != 'all') {
                $sum->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($request->input('customer_group_id'))) {
                $sum->where('contacts.customer_group_id', $request->input('customer_group_id'));
            }

            if (!empty($request->input('contact_type'))) {
                $sum->whereIn('contacts.type', [$request->input('contact_type'), 'both']);
            }

            $sum = $sum->get();

            return response()->json([
                'credit' => number_format($sum->sum('credit')),
                'debit' => number_format(-$sum->sum('debit'))
            ]);
        }

        return response()->json([
            'credit' => 0,
            'debit' => 0
        ]);
    }

    /**
     * Shows product stock report
     *
     * @return \Illuminate\Http\Response
     */
    public function getStockReport(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id);
        $default_location = null;
        if (count($business_locations) == 1) {
            foreach ($business_locations as $id => $name) {
                $default_location = BusinessLocation::findOrFail($id);
            }
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
            $warehouses = Warehouse::forDropdown($business_id, false, [], $permitted_warehouses);
        }else{
            $warehouses = Warehouse::forDropdown($business_id);
        }

        return view('report.stock_report')
            ->with(compact('categories', 'products', 'business_locations', 'default_location', 'warehouses'));
    }

    /**
     * Shows product stock details
     *
     * @return \Illuminate\Http\Response
     */
    public function getStockDetails(Request $request)
    {
        //Return the details in ajax call
        if ($request->ajax()) {
            $business_id = $request->session()->get('user.business_id');
            $product_id = $request->input('product_id');
            $query = Product::leftjoin('units as u', 'products.unit_id', '=', 'u.id')
                ->join('variations as v', 'products.id', '=', 'v.product_id')
                ->join('product_variations as pv', 'pv.id', '=', 'v.product_variation_id')
                ->leftjoin('variation_location_details as vld', 'v.id', '=', 'vld.variation_id')
                ->where('products.business_id', $business_id)
                ->where('products.id', $product_id)
                ->whereNull('v.deleted_at');

            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = '';
            if ($permitted_locations != 'all') {
                $query->whereIn('vld.location_id', $permitted_locations);
                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');

                $query->where('vld.location_id', $location_id);

                $location_filter .= "AND transactions.location_id=$location_id";
            }

            $product_details =  $query->select(
                'products.name as product',
                'u.short_name as unit',
                'pv.name as product_variation',
                'v.name as variation',
                'v.sub_sku as sub_sku',
                'v.sell_price_inc_tax',
                DB::raw("SUM(vld.qty_available) as stock"),
                DB::raw("(SELECT SUM(IF(transactions.type='sell', TSL.quantity - TSL.quantity_returned, -1* TPL.quantity) ) FROM transactions 
                        LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id

                        LEFT JOIN purchase_lines AS TPL ON transactions.id=TPL.transaction_id

                        WHERE transactions.status='final' AND transactions.type='sell' $location_filter 
                        AND (TSL.variation_id=v.id OR TPL.variation_id=v.id)) as total_sold"),
                DB::raw("(SELECT SUM(IF(transactions.type='sell_transfer', TSL.quantity, 0) ) FROM transactions 
                        LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell_transfer' $location_filter 
                        AND (TSL.variation_id=v.id)) as total_transfered"),
                DB::raw("(SELECT SUM(IF(transactions.type='stock_adjustment', SAL.quantity, 0) ) FROM transactions 
                        LEFT JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='stock_adjustment' $location_filter 
                        AND (SAL.variation_id=v.id)) as total_adjusted")
                // DB::raw("(SELECT SUM(quantity) FROM transaction_sell_lines LEFT JOIN transactions ON transaction_sell_lines.transaction_id=transactions.id WHERE transactions.status='final' $location_filter AND
                //     transaction_sell_lines.variation_id=v.id) as total_sold")
            )
                        ->groupBy('v.id')
                        ->get();

            return view('report.stock_details')
                        ->with(compact('product_details'));
        }
    }

    /**
     * Shows tax report of a business
     *
     * @return \Illuminate\Http\Response
     */
    public function getTaxReport(Request $request)
    {
        if (!auth()->user()->can('tax_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $location_id = $request->get('location_id');

            $input_tax_details = $this->transactionUtil->getInputTax($business_id, $start_date, $end_date, $location_id);

            $input_tax = view('report.partials.tax_details')->with(['tax_details' => $input_tax_details])->render();

            $output_tax_details = $this->transactionUtil->getOutputTax($business_id, $start_date, $end_date, $location_id);

            $expense_tax_details = $this->transactionUtil->getExpenseTax($business_id, $start_date, $end_date, $location_id);

            $output_tax = view('report.partials.tax_details')->with(['tax_details' => $output_tax_details])->render();

            $expense_tax = view('report.partials.tax_details')->with(['tax_details' => $expense_tax_details])->render();

            return ['input_tax' => $input_tax,
                    'output_tax' => $output_tax,
                    'expense_tax' => $expense_tax,
                    'tax_diff' => $output_tax_details['total_tax'] - $input_tax_details['total_tax'] - $expense_tax_details['total_tax']
                ];
        }

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.tax_report')
                    ->with(compact('business_locations'));
    }

    /**
     * Shows trending products
     *
     * @return \Illuminate\Http\Response
     */
    public function getTrendingProducts(Request $request)
    {
        if (!auth()->user()->can('trending_product_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $filters = request()->only(['category', 'sub_category', 'brand', 'unit', 'limit', 'location_id', 'product_type']);

        $date_range = request()->input('date_range');

        if (!empty($date_range)) {
            $date_range_array = explode('~', $date_range);
            $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
            $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));
        }

        $products = $this->productUtil->getTrendingProducts($business_id, $filters);

        $values = [];
        $labels = [];
        foreach ($products as $product) {
            $values[] = (float) $product->total_unit_sold;
            $labels[] = $product->product . ' (' . $product->unit . ')';
        }

        $chart = new CommonChart;
        $chart->labels($labels)
            ->dataset(__('report.total_unit_sold'), 'column', $values);

        $categories = Category::forDropdown($business_id, 'product');
        $brands = Brands::where('business_id', $business_id)
                            ->pluck('name', 'id');
        $units = Unit::where('business_id', $business_id)
                            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.trending_products')
                    ->with(compact('chart', 'categories', 'brands', 'units', 'business_locations'));
    }

    public function getTrendingProductsAjax()
    {
        $business_id = request()->session()->get('user.business_id');
    }
    /**
     * Shows expense report of a business
     *
     * @return \Illuminate\Http\Response
     */
    public function getExpenseReport(Request $request)
    {
        if (!auth()->user()->can('expense_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $filters = $request->only(['category', 'location_id']);

        $date_range = $request->input('date_range');

        if (!empty($date_range)) {
            $date_range_array = explode('~', $date_range);
            $filters['start_date'] = $this->transactionUtil->uf_date(trim($date_range_array[0]));
            $filters['end_date'] = $this->transactionUtil->uf_date(trim($date_range_array[1]));
        } else {
            $filters['start_date'] = \Carbon::now()->startOfMonth()->format('Y-m-d');
            $filters['end_date'] = \Carbon::now()->endOfMonth()->format('Y-m-d');
        }

        $expenses = $this->transactionUtil->getExpenseReport($business_id, $filters);

        $values = [];
        $labels = [];
        foreach ($expenses as $expense) {
            $values[] = (float) $expense->total_expense;
            $labels[] = !empty($expense->category) ? $expense->category : __('report.others');
        }

        $chart = new CommonChart;
        $chart->labels($labels)
            ->title(__('report.expense_report'))
            ->dataset(__('report.total_expense'), 'column', $values);

        $categories = ExpenseCategory::where('business_id', $business_id)
                            ->pluck('name', 'id');

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.expense_report')
                    ->with(compact('chart', 'categories', 'business_locations', 'expenses'));
    }

    /**
     * Shows stock adjustment report
     *
     * @return \Illuminate\Http\Response
     */
    public function getStockAdjustmentReport(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $query =  Transaction::where('business_id', $business_id)
                            ->where('type', 'stock_adjustment');

            //Check for permitted locations of a user
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('location_id', $permitted_locations);
            }

            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
            }
            $location_id = $request->get('location_id');
            if (!empty($location_id)) {
                $query->where('location_id', $location_id);
            }

            $stock_adjustment_details = $query->select(
                DB::raw("SUM(final_total) as total_amount"),
                DB::raw("SUM(total_amount_recovered) as total_recovered"),
                DB::raw("SUM(IF(adjustment_type = 'normal', final_total, 0)) as total_normal"),
                DB::raw("SUM(IF(adjustment_type = 'abnormal', final_total, 0)) as total_abnormal")
            )->first();
            return $stock_adjustment_details;
        }
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.stock_adjustment_report')
                    ->with(compact('business_locations'));
    }

    /**
     * Shows register report of a business
     *
     * @return \Illuminate\Http\Response
     */
    public function getRegisterReport(Request $request)
    {
        if (!auth()->user()->can('register_report.view')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $registers = CashRegister::join(
                'users as u',
                'u.id',
                '=',
                'cash_registers.user_id'
                )
                ->leftJoin(
                    'business_locations as bl',
                    'bl.id',
                    '=',
                    'cash_registers.location_id'
                )
                ->where('cash_registers.business_id', $business_id)
                ->select(
                    'cash_registers.*',
                    DB::raw(
                        "CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, ''), '<br>', COALESCE(u.email, '')) as user_name"
                    ),
                    'bl.name as location_name'
                );

            if (!empty($request->input('user_id'))) {
                $registers->where('cash_registers.user_id', $request->input('user_id'));
            }
            if (!empty($request->input('status'))) {
                $registers->where('cash_registers.status', $request->input('status'));
            }
            return Datatables::of($registers)
                ->editColumn('total_card_slips', function ($row) {
                    if ($row->status == 'close') {
                        return $row->total_card_slips;
                    } else {
                        return '';
                    }
                })
                ->editColumn('total_cheques', function ($row) {
                    if ($row->status == 'close') {
                        return $row->total_cheques;
                    } else {
                        return '';
                    }
                })
                ->editColumn('closed_at', function ($row) {
                    if ($row->status == 'close') {
                        return $this->productUtil->format_date($row->closed_at, true);
                    } else {
                        return '';
                    }
                })
                ->editColumn('created_at', function ($row) {
                    return $this->productUtil->format_date($row->created_at, true);
                })
                ->editColumn('closing_amount', function ($row) {
                    if ($row->status == 'close') {
                        return '<span class="display_currency" data-currency_symbol="true">' .
                        $row->closing_amount . '</span>';
                    } else {
                        return '';
                    }
                })
                ->addColumn('action', '<button type="button" data-href="{{action(\'CashRegisterController@show\', [$id])}}" class="btn btn-xs btn-info btn-modal" 
                    data-container=".view_register"><i class="fas fa-eye" aria-hidden="true"></i> @lang("messages.view")</button>')
                ->filterColumn('user_name', function ($query, $keyword) {
                    $query->whereRaw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, ''), '<br>', COALESCE(u.email, '')) like ?", ["%{$keyword}%"]);
                })
                ->rawColumns(['action', 'user_name', 'closing_amount'])
                ->make(true);
        }

        $users = User::forDropdown($business_id, false);

        return view('report.register_report')
                    ->with(compact('users'));
    }

    /**
     * Shows sales representative report
     *
     * @return \Illuminate\Http\Response
     */
    public function getSalesRepresentativeReport(Request $request)
    {
        if (!auth()->user()->can('sales_representative.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $users = User::allUsersDropdown($business_id, false);
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.sales_representative')
                ->with(compact('users', 'business_locations'));
    }

    /**
     * Shows sales representative total expense
     *
     * @return json
     */
    public function getSalesRepresentativeTotalExpense(Request $request)
    {
        if (!auth()->user()->can('sales_representative.view')) {
            abort(403, 'Unauthorized action.');
        }

        if ($request->ajax()) {
            $business_id = $request->session()->get('user.business_id');

            $filters = $request->only(['expense_for', 'location_id', 'start_date', 'end_date']);

            $total_expense = $this->transactionUtil->getExpenseReport($business_id, $filters, 'total');

            return $total_expense;
        }
    }

    /**
     * Shows sales representative total sales
     *
     * @return json
     */
    public function getSalesRepresentativeTotalSell(Request $request)
    {
        if (!auth()->user()->can('sales_representative.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');

            $location_id = $request->get('location_id');
            $created_by = $request->get('created_by');

            $sell_details = $this->transactionUtil->getSellTotals($business_id, $start_date, $end_date, $location_id, $created_by);

            //Get Sell Return details
            $transaction_types = [
                'sell_return'
            ];
            $sell_return_details = $this->transactionUtil->getTransactionTotals(
                $business_id,
                $transaction_types,
                $start_date,
                $end_date,
                $location_id,
                $created_by
            );

            $total_sell_return = !empty($sell_return_details['total_sell_return_exc_tax']) ? $sell_return_details['total_sell_return_exc_tax'] : 0;
            $total_sell = $sell_details['total_sell_exc_tax'] - $total_sell_return;

            return [
                'total_sell_exc_tax' => $sell_details['total_sell_exc_tax'],
                'total_sell_return_exc_tax' => $total_sell_return,
                'total_sell' => $total_sell
            ];
        }
    }

    /**
     * Shows sales representative total commission
     *
     * @return json
     */
    public function getSalesRepresentativeTotalCommission(Request $request)
    {
        if (!auth()->user()->can('sales_representative.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');

            $location_id = $request->get('location_id');
            $commission_agent = $request->get('commission_agent');

            $sell_details = $this->transactionUtil->getTotalSellCommission($business_id, $start_date, $end_date, $location_id, $commission_agent);

            //Get Commision
            $commission_percentage = User::find($commission_agent)->cmmsn_percent;
            $total_commission = $commission_percentage * $sell_details['total_sales_with_commission'] / 100;

            return ['total_sales_with_commission' =>
                        $sell_details['total_sales_with_commission'],
                    'total_commission' => $total_commission,
                    'commission_percentage' => $commission_percentage
                ];
        }
    }

    /**
     * Shows product stock expiry report
     *
     * @return \Illuminate\Http\Response
     */
    public function getStockExpiryReport(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //TODO:: Need to display reference number and edit expiry date button

        //Return the details in ajax call
        if ($request->ajax()) {
            $query = PurchaseLine::leftjoin(
                'transactions as t',
                'purchase_lines.transaction_id',
                '=',
                't.id'
            )
                            ->leftjoin(
                                'products as p',
                                'purchase_lines.product_id',
                                '=',
                                'p.id'
                            )
                            ->leftjoin(
                                'variations as v',
                                'purchase_lines.variation_id',
                                '=',
                                'v.id'
                            )
                            ->leftjoin(
                                'product_variations as pv',
                                'v.product_variation_id',
                                '=',
                                'pv.id'
                            )
                            ->leftjoin('business_locations as l', 't.location_id', '=', 'l.id')
                            ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                            ->where('t.business_id', $business_id)
                            //->whereNotNull('p.expiry_period')
                            //->whereNotNull('p.expiry_period_type')
                            //->whereNotNull('exp_date')
                            ->where('p.enable_stock', 1);
            // ->whereRaw('purchase_lines.quantity > purchase_lines.quantity_sold + quantity_adjusted + quantity_returned');

            $permitted_locations = auth()->user()->permitted_locations();

            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');
                $query->where('t.location_id', $location_id)
                        //If filter by location then hide products not available in that location
                        ->join('product_locations as pl', 'pl.product_id', '=', 'p.id')
                        ->where(function ($q) use ($location_id) {
                            $q->where('pl.location_id', $location_id);
                        });
            }

            if (!empty($request->input('category_id'))) {
                $query->where('p.category_id', $request->input('category_id'));
            }
            if (!empty($request->input('sub_category_id'))) {
                $query->where('p.sub_category_id', $request->input('sub_category_id'));
            }
            if (!empty($request->input('brand_id'))) {
                $query->where('p.brand_id', $request->input('brand_id'));
            }
            if (!empty($request->input('unit_id'))) {
                $query->where('p.unit_id', $request->input('unit_id'));
            }
            if (!empty($request->input('exp_date_filter'))) {
                $query->whereDate('exp_date', '<=', $request->input('exp_date_filter'));
            }

            $only_mfg_products = request()->get('only_mfg_products', 0);
            if (!empty($only_mfg_products)) {
                $query->where('t.type', 'production_purchase');
            }

            $report = $query->select(
                'p.name as product',
                'p.sku',
                'p.type as product_type',
                'v.name as variation',
                'pv.name as product_variation',
                'l.name as location',
                'mfg_date',
                'exp_date',
                'u.short_name as unit',
                DB::raw("SUM(COALESCE(quantity, 0) - COALESCE(quantity_sold, 0) - COALESCE(quantity_adjusted, 0) - COALESCE(quantity_returned, 0)) as stock_left"),
                't.ref_no',
                't.id as transaction_id',
                'purchase_lines.id as purchase_line_id',
                'purchase_lines.lot_number'
            )
            ->having('stock_left', '>', 0)
            ->groupBy('purchase_lines.exp_date')
            ->groupBy('purchase_lines.lot_number');

            return Datatables::of($report)
                ->editColumn('name', function ($row) {
                    if ($row->product_type == 'variable') {
                        return $row->product . ' - ' .
                        $row->product_variation . ' - ' . $row->variation;
                    } else {
                        return $row->product;
                    }
                })
                ->editColumn('mfg_date', function ($row) {
                    if (!empty($row->mfg_date)) {
                        return $this->productUtil->format_date($row->mfg_date);
                    } else {
                        return '--';
                    }
                })
                // ->editColumn('exp_date', function ($row) {
                //     if (!empty($row->exp_date)) {
                //         $carbon_exp = \Carbon::createFromFormat('Y-m-d', $row->exp_date);
                //         $carbon_now = \Carbon::now();
                //         if ($carbon_now->diffInDays($carbon_exp, false) >= 0) {
                //             return $this->productUtil->format_date($row->exp_date) . '<br><small>( <span class="time-to-now">' . $row->exp_date . '</span> )</small>';
                //         } else {
                //             return $this->productUtil->format_date($row->exp_date) . ' &nbsp; <span class="label label-danger no-print">' . __('report.expired') . '</span><span class="print_section">' . __('report.expired') . '</span><br><small>( <span class="time-from-now">' . $row->exp_date . '</span> )</small>';
                //         }
                //     } else {
                //         return '--';
                //     }
                // })
                ->editColumn('ref_no', function ($row) {
                    return '<button type="button" data-href="' . action('PurchaseController@show', [$row->transaction_id])
                            . '" class="btn btn-link btn-modal" data-container=".view_modal"  >' . $row->ref_no . '</button>';
                })
                ->editColumn('stock_left', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency stock_left" data-currency_symbol=false data-orig-value="' . $row->stock_left . '" data-unit="' . $row->unit . '" >' . $row->stock_left . '</span> ' . $row->unit;
                })
                ->addColumn('edit', function ($row) {
                    $html =  '<button type="button" class="btn btn-primary btn-xs stock_expiry_edit_btn" data-transaction_id="' . $row->transaction_id . '" data-purchase_line_id="' . $row->purchase_line_id . '"> <i class="fa fa-edit"></i> ' . __("messages.edit") .
                    '</button>';

                    if (!empty($row->exp_date)) {
                        $carbon_exp = \Carbon::createFromFormat('Y-m-d', $row->exp_date);
                        $carbon_now = \Carbon::now();
                        if ($carbon_now->diffInDays($carbon_exp, false) < 0) {
                            $html .=  ' <button type="button" class="btn btn-warning btn-xs remove_from_stock_btn" data-href="' . action('StockAdjustmentController@removeExpiredStock', [$row->purchase_line_id]) . '"> <i class="fa fa-trash"></i> ' . __("lang_v1.remove_from_stock") .
                            '</button>';
                        }
                    }

                    return $html;
                })
                ->rawColumns(['exp_date', 'ref_no', 'edit', 'stock_left'])
                ->make(true);
        }

        $categories = Category::forDropdown($business_id, 'product');
        $brands = Brands::where('business_id', $business_id)
                            ->pluck('name', 'id');
        $units = Unit::where('business_id', $business_id)
                            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $view_stock_filter = [
            \Carbon::now()->subDay()->format('Y-m-d') => __('report.expired'),
            \Carbon::now()->addWeek()->format('Y-m-d') => __('report.expiring_in_1_week'),
            \Carbon::now()->addDays(15)->format('Y-m-d') => __('report.expiring_in_15_days'),
            \Carbon::now()->addMonth()->format('Y-m-d') => __('report.expiring_in_1_month'),
            \Carbon::now()->addMonths(3)->format('Y-m-d') => __('report.expiring_in_3_months'),
            \Carbon::now()->addMonths(6)->format('Y-m-d') => __('report.expiring_in_6_months'),
            \Carbon::now()->addYear()->format('Y-m-d') => __('report.expiring_in_1_year')
        ];

        return view('report.stock_expiry_report')
                ->with(compact('categories', 'brands', 'units', 'business_locations', 'view_stock_filter'));
    }

    /**
     * Shows product stock expiry report
     *
     * @return \Illuminate\Http\Response
     */
    public function getStockExpiryReportEditModal(Request $request, $purchase_line_id)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $purchase_line = PurchaseLine::join(
                'transactions as t',
                'purchase_lines.transaction_id',
                '=',
                't.id'
            )
                                ->join(
                                    'products as p',
                                    'purchase_lines.product_id',
                                    '=',
                                    'p.id'
                                )
                                ->where('purchase_lines.id', $purchase_line_id)
                                ->where('t.business_id', $business_id)
                                ->select(['purchase_lines.*', 'p.name', 't.ref_no'])
                                ->first();

            if (!empty($purchase_line)) {
                if (!empty($purchase_line->exp_date)) {
                    $purchase_line->exp_date = date('m/d/Y', strtotime($purchase_line->exp_date));
                }
            }

            return view('report.partials.stock_expiry_edit_modal')
                ->with(compact('purchase_line'));
        }
    }

    /**
     * Update product stock expiry report
     *
     * @return \Illuminate\Http\Response
     */
    public function updateStockExpiryReport(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            //Return the details in ajax call
            if ($request->ajax()) {
                DB::beginTransaction();

                $input = $request->only(['purchase_line_id', 'exp_date']);

                $purchase_line = PurchaseLine::join(
                    'transactions as t',
                    'purchase_lines.transaction_id',
                    '=',
                    't.id'
                )
                                    ->join(
                                        'products as p',
                                        'purchase_lines.product_id',
                                        '=',
                                        'p.id'
                                    )
                                    ->where('purchase_lines.id', $input['purchase_line_id'])
                                    ->where('t.business_id', $business_id)
                                    ->select(['purchase_lines.*', 'p.name', 't.ref_no'])
                                    ->first();

                if (!empty($purchase_line) && !empty($input['exp_date'])) {
                    $purchase_line->exp_date = $this->productUtil->uf_date($input['exp_date']);
                    $purchase_line->save();
                }

                DB::commit();

                $output = ['success' => 1,
                            'msg' => __('lang_v1.updated_succesfully')
                        ];
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
     * Shows product stock expiry report
     *
     * @return \Illuminate\Http\Response
     */
    public function getCustomerGroup(Request $request)
    {
        if (!auth()->user()->can('contacts_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $query = Transaction::leftjoin('customer_groups AS CG', 'transactions.customer_group_id', '=', 'CG.id')
                        ->where('transactions.business_id', $business_id)
                        ->where('transactions.type', 'sell')
                        ->where('transactions.status', 'final')
                        ->groupBy('transactions.customer_group_id')
                        ->select(DB::raw("SUM(final_total) as total_sell"), 'CG.name');

            $group_id = $request->get('customer_group_id', null);
            if (!empty($group_id)) {
                $query->where('transactions.customer_group_id', $group_id);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('transactions.location_id', $permitted_locations);
            }

            $location_id = $request->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('transactions.location_id', $location_id);
            }

            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');

            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
            }


            return Datatables::of($query)
                ->editColumn('total_sell', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' . $row->total_sell . '</span>';
                })
                ->rawColumns(['total_sell'])
                ->make(true);
        }

        $customer_group = CustomerGroup::forDropdown($business_id, false, true);
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.customer_group')
            ->with(compact('customer_group', 'business_locations'));
    }

    /**
     * Shows product purchase report
     *
     * @return \Illuminate\Http\Response
     */
    public function getproductPurchaseReport(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);
            $query = PurchaseLine::join(
                'transactions as t',
                'purchase_lines.transaction_id',
                '=',
                't.id'
                    )
                    ->join(
                        'variations as v',
                        'purchase_lines.variation_id',
                        '=',
                        'v.id'
                    )
                    ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                    ->join('contacts as c', 't.contact_id', '=', 'c.id')
                    ->join('products as p', 'pv.product_id', '=', 'p.id')
                    ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                    ->where('t.business_id', $business_id)
                    ->where('t.type', 'purchase')
                    ->select(
                        'p.name as product_name',
                        'p.type as product_type',
                        'pv.name as product_variation',
                        'v.name as variation_name',
                        'v.sub_sku',
                        'c.name as supplier',
                        't.id as transaction_id',
                        't.ref_no',
                        't.transaction_date as transaction_date',
                        'purchase_lines.purchase_price_inc_tax as unit_purchase_price',
                        DB::raw('(purchase_lines.quantity - purchase_lines.quantity_returned) as purchase_qty'),
                        'purchase_lines.quantity_adjusted',
                        'u.short_name as unit',
                        DB::raw('((purchase_lines.quantity - purchase_lines.quantity_returned - purchase_lines.quantity_adjusted) * purchase_lines.purchase_price_inc_tax) as subtotal')
                    )
                    ->groupBy('purchase_lines.id');
            if (!empty($variation_id)) {
                $query->where('purchase_lines.variation_id', $variation_id);
            }
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            $location_id = $request->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $supplier_id = $request->get('supplier_id', null);
            if (!empty($supplier_id)) {
                $query->where('t.contact_id', $supplier_id);
            }

            return Datatables::of($query)
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }

                    return $product_name;
                })
                 ->editColumn('ref_no', function ($row) {
                     return '<a data-href="' . action('PurchaseController@show', [$row->transaction_id])
                            . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->ref_no . '</a>';
                 })
                 ->editColumn('purchase_qty', function ($row) {
                     return '<span data-is_quantity="true" class="display_currency purchase_qty" data-currency_symbol=false data-orig-value="' . (float)$row->purchase_qty . '" data-unit="' . $row->unit . '" >' . (float) $row->purchase_qty . '</span> ' . $row->unit;
                 })
                 ->editColumn('quantity_adjusted', function ($row) {
                     return '<span data-is_quantity="true" class="display_currency quantity_adjusted" data-currency_symbol=false data-orig-value="' . (float)$row->quantity_adjusted . '" data-unit="' . $row->unit . '" >' . (float) $row->quantity_adjusted . '</span> ' . $row->unit;
                 })
                 ->editColumn('subtotal', function ($row) {
                     return '<span class="display_currency row_subtotal" data-currency_symbol=true data-orig-value="' . $row->subtotal . '">' . $row->subtotal . '</span>';
                 })
                ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                ->editColumn('unit_purchase_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' . $row->unit_purchase_price . '</span>';
                })
                ->rawColumns(['ref_no', 'unit_purchase_price', 'subtotal', 'purchase_qty', 'quantity_adjusted'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $suppliers = Contact::suppliersDropdown($business_id);

        return view('report.product_purchase_report')
            ->with(compact('business_locations', 'suppliers'));
    }

    /**
     * Shows product purchase report
     *
     * @return \Illuminate\Http\Response
     */
    public function getproductSellReport(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);
            $query = TransactionSellLine::join(
                'transactions as t',
                'transaction_sell_lines.transaction_id',
                '=',
                't.id'
            )
                ->join(
                    'variations as v',
                    'transaction_sell_lines.variation_id',
                    '=',
                    'v.id'
                )
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('contacts as c', 't.contact_id', '=', 'c.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->leftjoin('tax_rates', 'transaction_sell_lines.tax_id', '=', 'tax_rates.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->select(
                    'p.name as product_name',
                    'p.type as product_type',
                    'pv.name as product_variation',
                    'v.name as variation_name',
                    'v.sub_sku',
                    'c.name as customer',
                    'c.contact_id',
                    't.id as transaction_id',
                    't.invoice_no',
                    't.transaction_date as transaction_date',
                    'transaction_sell_lines.unit_price_before_discount as unit_price',
                    'transaction_sell_lines.unit_price_inc_tax as unit_sale_price',
                    DB::raw('(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as sell_qty'),
                    'transaction_sell_lines.line_discount_type as discount_type',
                    'transaction_sell_lines.line_discount_amount as discount_amount',
                    'transaction_sell_lines.item_tax',
                    'tax_rates.name as tax',
                    'u.short_name as unit',
                    DB::raw('((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal')
                )
                ->groupBy('transaction_sell_lines.id');

            if (!empty($variation_id)) {
                $query->where('transaction_sell_lines.variation_id', $variation_id);
            }
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            $location_id = $request->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $customer_id = $request->get('customer_id', null);
            if (!empty($customer_id)) {
                $query->where('t.contact_id', $customer_id);
            }

            return Datatables::of($query)
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }

                    return $product_name;
                })
                 ->editColumn('invoice_no', function ($row) {
                     return '<a data-href="' . action('SellController@show', [$row->transaction_id])
                            . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->invoice_no . '</a>';
                 })
                ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                ->editColumn('unit_sale_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' . $row->unit_sale_price . '</span>';
                })
                ->editColumn('sell_qty', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (float)$row->sell_qty . '" data-unit="' . $row->unit . '" >' . (float) $row->sell_qty . '</span> ' .$row->unit;
                })
                 ->editColumn('subtotal', function ($row) {
                     return '<span class="display_currency row_subtotal" data-currency_symbol = true data-orig-value="' . $row->subtotal . '">' . $row->subtotal . '</span>';
                 })
                ->editColumn('unit_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' . $row->unit_price . '</span>';
                })
                ->editColumn('discount_amount', '
                    @if($discount_type == "percentage")
                        {{@number_format($discount_amount)}} %
                    @elseif($discount_type == "fixed")
                        {{@number_format($discount_amount)}}
                    @endif
                    ')
                ->editColumn('tax', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>'.
                            $row->item_tax.
                       '</span>'.'<br>'.'<span class="tax" data-orig-value="'.(float)$row->item_tax.'" data-unit="'.$row->tax.'"><small>('.$row->tax.')</small></span>';
                })
                ->rawColumns(['invoice_no', 'unit_sale_price', 'subtotal', 'sell_qty', 'discount_amount', 'unit_price', 'tax'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $customers = Contact::customersDropdown($business_id);

        return view('report.product_sell_report')
            ->with(compact('business_locations', 'customers'));
    }

    /**
     * Shows product purchase report with purchase details
     *
     * @return \Illuminate\Http\Response
     */
    public function getproductSellReportWithPurchase(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);
            $query = TransactionSellLine::join(
                'transactions as t',
                'transaction_sell_lines.transaction_id',
                '=',
                't.id'
            )
                ->join(
                    'transaction_sell_lines_purchase_lines as tspl',
                    'transaction_sell_lines.id',
                    '=',
                    'tspl.sell_line_id'
                )
                ->join(
                    'purchase_lines as pl',
                    'tspl.purchase_line_id',
                    '=',
                    'pl.id'
                )
                ->join(
                    'transactions as purchase',
                    'pl.transaction_id',
                    '=',
                    'purchase.id'
                )
                ->leftjoin('contacts as supplier', 'purchase.contact_id', '=', 'supplier.id')
                ->join(
                    'variations as v',
                    'transaction_sell_lines.variation_id',
                    '=',
                    'v.id'
                )
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->leftjoin('contacts as c', 't.contact_id', '=', 'c.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->select(
                    'p.name as product_name',
                    'p.type as product_type',
                    'pv.name as product_variation',
                    'v.name as variation_name',
                    'v.sub_sku',
                    'c.name as customer',
                    't.id as transaction_id',
                    't.invoice_no',
                    't.transaction_date as transaction_date',
                    'tspl.quantity as purchase_quantity',
                    'u.short_name as unit',
                    'supplier.name as supplier_name',
                    'purchase.ref_no as ref_no',
                    'purchase.type as purchase_type',
                    'pl.lot_number'
                );

            if (!empty($variation_id)) {
                $query->where('transaction_sell_lines.variation_id', $variation_id);
            }
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(t.transaction_date)'), [$start_date, $end_date]);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            $location_id = $request->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $customer_id = $request->get('customer_id', null);
            if (!empty($customer_id)) {
                $query->where('t.contact_id', $customer_id);
            }

            return Datatables::of($query)
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }

                    return $product_name;
                })
                 ->editColumn('invoice_no', function ($row) {
                     return '<a data-href="' . action('SellController@show', [$row->transaction_id])
                            . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->invoice_no . '</a>';
                 })
                ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                ->editColumn('unit_sale_price', function ($row) {
                    return '<span class="display_currency" data-currency_symbol = true>' . $row->unit_sale_price . '</span>';
                })
                ->editColumn('purchase_quantity', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency purchase_quantity" data-currency_symbol=false data-orig-value="' . (float)$row->purchase_quantity . '" data-unit="' . $row->unit . '" >' . (float) $row->purchase_quantity . '</span> ' .$row->unit;
                })
                ->editColumn('ref_no', '
                    @if($purchase_type == "opening_stock")
                        <i><small class="help-block">(@lang("lang_v1.opening_stock"))</small></i>
                    @else
                        {{$ref_no}}
                    @endif
                    ')
                ->rawColumns(['invoice_no', 'purchase_quantity', 'ref_no'])
                ->make(true);
        }
    }

    /**
     * Shows product lot report
     *
     * @return \Illuminate\Http\Response
     */
    public function getLotReport(Request $request)
    {
        if (!auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $query = Product::where('products.business_id', $business_id)
                    ->leftjoin('units', 'products.unit_id', '=', 'units.id')
                    ->join('variations as v', 'products.id', '=', 'v.product_id')
                    ->join('purchase_lines as pl', 'v.id', '=', 'pl.variation_id')
                    ->leftjoin(
                        'transaction_sell_lines_purchase_lines as tspl',
                        'pl.id',
                        '=',
                        'tspl.purchase_line_id'
                    )
                    ->join('transactions as t', 'pl.transaction_id', '=', 't.id');

            $permitted_locations = auth()->user()->permitted_locations();
            $location_filter = 'WHERE ';

            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);

                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter = " LEFT JOIN transactions as t2 on pls.transaction_id=t2.id WHERE t2.location_id IN ($locations_imploded) AND ";
            }

            if (!empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');
                $query->where('t.location_id', $location_id)
                    //If filter by location then hide products not available in that location
                    ->ForLocation($location_id);

                $location_filter = "LEFT JOIN transactions as t2 on pls.transaction_id=t2.id WHERE t2.location_id=$location_id AND ";
            }

            if (!empty($request->input('category_id'))) {
                $query->where('products.category_id', $request->input('category_id'));
            }

            if (!empty($request->input('sub_category_id'))) {
                $query->where('products.sub_category_id', $request->input('sub_category_id'));
            }

            if (!empty($request->input('brand_id'))) {
                $query->where('products.brand_id', $request->input('brand_id'));
            }

            if (!empty($request->input('unit_id'))) {
                $query->where('products.unit_id', $request->input('unit_id'));
            }

            $only_mfg_products = request()->get('only_mfg_products', 0);
            if (!empty($only_mfg_products)) {
                $query->where('t.type', 'production_purchase');
            }

            $products = $query->select(
                'products.name as product',
                'v.name as variation_name',
                'sub_sku',
                'pl.lot_number',
                'pl.exp_date as exp_date',
                DB::raw("( COALESCE((SELECT SUM(quantity - quantity_returned) from purchase_lines as pls $location_filter variation_id = v.id AND lot_number = pl.lot_number), 0) - 
                    SUM(COALESCE((tspl.quantity - tspl.qty_returned), 0))) as stock"),
                // DB::raw("(SELECT SUM(IF(transactions.type='sell', TSL.quantity, -1* TPL.quantity) ) FROM transactions
                //         LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id

                //         LEFT JOIN purchase_lines AS TPL ON transactions.id=TPL.transaction_id

                //         WHERE transactions.status='final' AND transactions.type IN ('sell', 'sell_return') $location_filter
                //         AND (TSL.product_id=products.id OR TPL.product_id=products.id)) as total_sold"),

                DB::raw("COALESCE(SUM(IF(tspl.sell_line_id IS NULL, 0, (tspl.quantity - tspl.qty_returned)) ), 0) as total_sold"),
                DB::raw("COALESCE(SUM(IF(tspl.stock_adjustment_line_id IS NULL, 0, tspl.quantity ) ), 0) as total_adjusted"),
                'products.type',
                'units.short_name as unit'
            )
            ->whereNotNull('pl.lot_number')
            ->groupBy('v.id')
            ->groupBy('pl.lot_number');

            return Datatables::of($products)
                ->editColumn('stock', function ($row) {
                    $stock = $row->stock ? $row->stock : 0 ;
                    return '<span data-is_quantity="true" class="display_currency total_stock" data-currency_symbol=false data-orig-value="' . (float)$stock . '" data-unit="' . $row->unit . '" >' . (float)$stock . '</span> ' . $row->unit;
                })
                ->editColumn('product', function ($row) {
                    if ($row->variation_name != 'DUMMY') {
                        return $row->product . ' (' . $row->variation_name . ')';
                    } else {
                        return $row->product;
                    }
                })
                ->editColumn('total_sold', function ($row) {
                    if ($row->total_sold) {
                        return '<span data-is_quantity="true" class="display_currency total_sold" data-currency_symbol=false data-orig-value="' . (float)$row->total_sold . '" data-unit="' . $row->unit . '" >' . (float)$row->total_sold . '</span> ' . $row->unit;
                    } else {
                        return '0' . ' ' . $row->unit;
                    }
                })
                ->editColumn('total_adjusted', function ($row) {
                    if ($row->total_adjusted) {
                        return '<span data-is_quantity="true" class="display_currency total_adjusted" data-currency_symbol=false data-orig-value="' . (float)$row->total_adjusted . '" data-unit="' . $row->unit . '" >' . (float)$row->total_adjusted . '</span> ' . $row->unit;
                    } else {
                        return '0' . ' ' . $row->unit;
                    }
                })
                ->editColumn('exp_date', function ($row) {
                    if (!empty($row->exp_date)) {
                        $carbon_exp = \Carbon::createFromFormat('Y-m-d', $row->exp_date);
                        $carbon_now = \Carbon::now();
                        if ($carbon_now->diffInDays($carbon_exp, false) >= 0) {
                            return $this->productUtil->format_date($row->exp_date) . '<br><small>( <span class="time-to-now">' . $row->exp_date . '</span> )</small>';
                        } else {
                            return $this->productUtil->format_date($row->exp_date) . ' &nbsp; <span class="label label-danger no-print">' . __('report.expired') . '</span><span class="print_section">' . __('report.expired') . '</span><br><small>( <span class="time-from-now">' . $row->exp_date . '</span> )</small>';
                        }
                    } else {
                        return '--';
                    }
                })
                ->removeColumn('unit')
                ->removeColumn('id')
                ->removeColumn('variation_name')
                ->rawColumns(['exp_date', 'stock', 'total_sold', 'total_adjusted'])
                ->make(true);
        }

        $categories = Category::forDropdown($business_id, 'product');
        $brands = Brands::where('business_id', $business_id)
                            ->pluck('name', 'id');
        $units = Unit::where('business_id', $business_id)
                            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.lot_report')
            ->with(compact('categories', 'brands', 'units', 'business_locations'));
    }

    /**
     * Shows purchase payment report
     *
     * @return \Illuminate\Http\Response
     */
    public function purchasePaymentReport(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $supplier_id = $request->get('supplier_id', null);
            $contact_filter1 = !empty($supplier_id) ? "AND t.contact_id=$supplier_id" : '';
            $contact_filter2 = !empty($supplier_id) ? "AND transactions.contact_id=$supplier_id" : '';

            $location_id = $request->get('location_id', null);

            $parent_payment_query_part = empty($location_id) ? "AND transaction_payments.parent_id IS NULL" : "";

            $query = TransactionPayment::leftjoin('transactions as t', function ($join) use ($business_id) {
                $join->on('transaction_payments.transaction_id', '=', 't.id')
                    ->where('t.business_id', $business_id)
                    ->whereIn('t.type', ['purchase', 'opening_balance']);
            })
                ->where('transaction_payments.business_id', $business_id)
                ->where(function ($q) use ($business_id, $contact_filter1, $contact_filter2, $parent_payment_query_part) {
                    $q->whereRaw("(transaction_payments.transaction_id IS NOT NULL AND t.type IN ('purchase', 'opening_balance')  $parent_payment_query_part $contact_filter1)")
                        ->orWhereRaw("EXISTS(SELECT * FROM transaction_payments as tp JOIN transactions ON tp.transaction_id = transactions.id WHERE transactions.type IN ('purchase', 'opening_balance') AND transactions.business_id = $business_id AND tp.parent_id=transaction_payments.id $contact_filter2)");
                })

                ->select(
                    DB::raw("IF(transaction_payments.transaction_id IS NULL, 
                                (SELECT c.name FROM transactions as ts
                                JOIN contacts as c ON ts.contact_id=c.id 
                                WHERE ts.id=(
                                        SELECT tps.transaction_id FROM transaction_payments as tps
                                        WHERE tps.parent_id=transaction_payments.id LIMIT 1
                                    )
                                ),
                                (SELECT c.name FROM transactions as ts JOIN
                                    contacts as c ON ts.contact_id=c.id
                                    WHERE ts.id=t.id 
                                )
                            ) as supplier"),
                    'transaction_payments.amount',
                    'method',
                    'paid_on',
                    'transaction_payments.payment_ref_no',
                    'transaction_payments.document',
                    't.ref_no',
                    't.id as transaction_id',
                    'cheque_number',
                    'card_transaction_number',
                    'bank_account_number',
                    'transaction_no',
                    'transaction_payments.id as DT_RowId'
                )
                ->groupBy('transaction_payments.id');

            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(paid_on)'), [$start_date, $end_date]);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $payment_types = $this->transactionUtil->payment_types();

            return Datatables::of($query)
                 ->editColumn('ref_no', function ($row) {
                     if (!empty($row->ref_no)) {
                         return '<a data-href="' . action('PurchaseController@show', [$row->transaction_id])
                            . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->ref_no . '</a>';
                     } else {
                         return '';
                     }
                 })
                ->editColumn('paid_on', '{{@format_datetime($paid_on)}}')
                ->editColumn('method', function ($row) use ($payment_types) {
                    $method = !empty($payment_types[$row->method]) ? $payment_types[$row->method] : '';
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
//                        $method .= '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
//                    } elseif ($row->method == 'custom_pay_2') {
//                        $method .= '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
//                    } elseif ($row->method == 'custom_pay_3') {
//                        $method .= '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
//                    }
                    return $method;
                })
                ->editColumn('amount', function ($row) {
                    return '<span class="display_currency paid-amount" data-currency_symbol = true data-orig-value="' . $row->amount . '">' . $row->amount . '</span>';
                })
                ->addColumn('action', '<button type="button" class="btn btn-primary btn-xs view_payment" data-href="{{ action("TransactionPaymentController@viewPayment", [$DT_RowId]) }}">@lang("messages.view")
                    </button> @if(!empty($document))<a href="{{asset("/uploads/documents/" . $document)}}" class="btn btn-success btn-xs" download=""><i class="fa fa-download"></i> @lang("purchase.download_document")</a>@endif')
                ->rawColumns(['ref_no', 'amount', 'method', 'action'])
                ->make(true);
        }
        $business_locations = BusinessLocation::forDropdown($business_id);
        $suppliers = Contact::suppliersDropdown($business_id, false);

        return view('report.purchase_payment_report')
            ->with(compact('business_locations', 'suppliers'));
    }

    /**
     * Shows sell payment report
     *
     * @return \Illuminate\Http\Response
     */
    public function sellPaymentReport(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        if ($request->ajax()) {
            $customer_id = $request->get('supplier_id', null);
            $contact_filter1 = !empty($customer_id) ? "AND t.contact_id=$customer_id" : '';
            $contact_filter2 = !empty($customer_id) ? "AND transactions.contact_id=$customer_id" : '';

            $location_id = $request->get('location_id', null);
            $parent_payment_query_part = empty($location_id) ? "AND transaction_payments.parent_id IS NULL" : "";

            $query = TransactionPayment::leftjoin('transactions as t', function ($join) use ($business_id) {
                $join->on('transaction_payments.transaction_id', '=', 't.id')
                    ->where('t.business_id', $business_id)
                    ->whereIn('t.type', ['sell', 'opening_balance']);
            })
                ->leftjoin('contacts as c', 't.contact_id', '=', 'c.id')
                ->leftjoin('customer_groups AS CG', 'c.customer_group_id', '=', 'CG.id')
                ->where('transaction_payments.business_id', $business_id)
                ->where(function ($q) use ($business_id, $contact_filter1, $contact_filter2, $parent_payment_query_part) {
                    $q->whereRaw("(transaction_payments.transaction_id IS NOT NULL AND t.type IN ('sell', 'opening_balance') $parent_payment_query_part $contact_filter1)")
                        ->orWhereRaw("EXISTS(SELECT * FROM transaction_payments as tp JOIN transactions ON tp.transaction_id = transactions.id WHERE transactions.type IN ('sell', 'opening_balance') AND transactions.business_id = $business_id AND tp.parent_id=transaction_payments.id $contact_filter2)");
                })
                ->select(
                    DB::raw("IF(transaction_payments.transaction_id IS NULL, 
                                (SELECT c.name FROM transactions as ts
                                JOIN contacts as c ON ts.contact_id=c.id 
                                WHERE ts.id=(
                                        SELECT tps.transaction_id FROM transaction_payments as tps
                                        WHERE tps.parent_id=transaction_payments.id LIMIT 1
                                    )
                                ),
                                (SELECT c.name FROM transactions as ts JOIN
                                    contacts as c ON ts.contact_id=c.id
                                    WHERE ts.id=t.id 
                                )
                            ) as customer"),
                    'transaction_payments.amount',
                    'transaction_payments.is_return',
                    'method',
                    'paid_on',
                    'transaction_payments.payment_ref_no',
                    'transaction_payments.document',
                    'transaction_payments.transaction_no',
                    't.invoice_no',
                    't.id as transaction_id',
                    'cheque_number',
                    'card_transaction_number',
                    'bank_account_number',
                    'transaction_payments.id as DT_RowId',
                    'CG.name as customer_group'
                )
                ->groupBy('transaction_payments.id');

            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(paid_on)'), [$start_date, $end_date]);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($request->get('customer_group_id'))) {
                $query->where('CG.id', $request->get('customer_group_id'));
            }

            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            if (!empty($request->get('payment_types'))) {
                $query->where('transaction_payments.method', $request->get('payment_types'));
            }
            $payment_types = $this->transactionUtil->payment_types();
            return Datatables::of($query)
                 ->editColumn('invoice_no', function ($row) {
                     if (!empty($row->transaction_id)) {
                         return '<a data-href="' . action('SellController@show', [$row->transaction_id])
                            . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->invoice_no . '</a>';
                     } else {
                         return '';
                     }
                 })
                ->editColumn('paid_on', '{{@format_datetime($paid_on)}}')
                ->editColumn('method', function ($row) use ($payment_types) {
                    $method = !empty($payment_types[$row->method]) ? $payment_types[$row->method] : '';
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
//                        $method .= '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
//                    } elseif ($row->method == 'custom_pay_2') {
//                        $method .= '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
//                    } elseif ($row->method == 'custom_pay_3') {
//                        $method .= '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
//                    }
                    if ($row->is_return == 1) {
                        $method .= '<br><small>(' . __('lang_v1.change_return') . ')</small>';
                    }
                    return $method;
                })
                ->editColumn('amount', function ($row) {
                    $amount = $row->is_return == 1 ? -1 * $row->amount : $row->amount;
                    return '<span class="display_currency paid-amount" data-orig-value="' . $amount . '" data-currency_symbol = true>' . $amount . '</span>';
                })
                ->addColumn('action', '<button type="button" class="btn btn-primary btn-xs view_payment" data-href="{{ action("TransactionPaymentController@viewPayment", [$DT_RowId]) }}">@lang("messages.view")
                    </button> @if(!empty($document))<a href="{{asset("/uploads/documents/" . $document)}}" class="btn btn-success btn-xs" download=""><i class="fa fa-download"></i> @lang("purchase.download_document")</a>@endif')
                ->rawColumns(['invoice_no', 'amount', 'method', 'action'])
                ->make(true);
        }
        $business_locations = BusinessLocation::forDropdown($business_id);
        $customers = Contact::customersDropdown($business_id, false);
        $payment_types = $this->transactionUtil->payment_types();
        $customer_groups = CustomerGroup::forDropdown($business_id, false, true);

        return view('report.sell_payment_report')
            ->with(compact('business_locations', 'customers', 'payment_types', 'customer_groups'));
    }


    /**
     * Shows tables report
     *
     * @return \Illuminate\Http\Response
     */
    public function getTableReport(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $query = ResTable::leftjoin('transactions AS T', 'T.res_table_id', '=', 'res_tables.id')
                        ->where('T.business_id', $business_id)
                        ->where('T.type', 'sell')
                        ->where('T.status', 'final')
                        ->groupBy('res_tables.id')
                        ->select(DB::raw("SUM(final_total) as total_sell"), 'res_tables.name as table');

            $location_id = $request->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('T.location_id', $location_id);
            }

            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');

            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
            }

            return Datatables::of($query)
                ->editColumn('total_sell', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">' . $row->total_sell . '</span>';
                })
                ->rawColumns(['total_sell'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.table_report')
            ->with(compact('business_locations'));
    }

    /**
     * Shows service staff report
     *
     * @return \Illuminate\Http\Response
     */
    public function getServiceStaffReport(Request $request)
    {
        if (!auth()->user()->can('sales_representative.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        $waiters = $this->transactionUtil->serviceStaffDropdown($business_id);

        return view('report.service_staff_report')
            ->with(compact('business_locations', 'waiters'));
    }

    /**
     * Shows product sell report grouped by date
     *
     * @return \Illuminate\Http\Response
     */
    public function getproductSellGroupedReport(Request $request)
    {
        if (!auth()->user()->can('purchase_n_sell_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id', null);

        $vld_str = '';
        if (!empty($location_id)) {
            $vld_str = "AND vld.location_id=$location_id";
        }

        if ($request->ajax()) {
            $variation_id = $request->get('variation_id', null);
            $query = TransactionSellLine::join(
                'transactions as t',
                'transaction_sell_lines.transaction_id',
                '=',
                't.id'
            )
                ->join(
                    'variations as v',
                    'transaction_sell_lines.variation_id',
                    '=',
                    'v.id'
                )
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('products as p', 'pv.product_id', '=', 'p.id')
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->select(
                    'p.name as product_name',
                    'p.enable_stock',
                    'p.type as product_type',
                    'pv.name as product_variation',
                    'v.name as variation_name',
                    'v.sub_sku',
                    't.id as transaction_id',
                    't.transaction_date as transaction_date',
                    DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m-%d") as formated_date'),
                    DB::raw("(SELECT SUM(vld.qty_available) FROM variation_location_details as vld WHERE vld.variation_id=v.id $vld_str) as current_stock"),
                    DB::raw('SUM(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) as total_qty_sold'),
                    'u.short_name as unit',
                    DB::raw('SUM((transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax) as subtotal')
                )
                ->groupBy('v.id')
                ->groupBy('formated_date');

            if (!empty($variation_id)) {
                $query->where('transaction_sell_lines.variation_id', $variation_id);
            }
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween(DB::raw('date(transaction_date)'), [$start_date, $end_date]);
            }

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }

            $customer_id = $request->get('customer_id', null);
            if (!empty($customer_id)) {
                $query->where('t.contact_id', $customer_id);
            }

            return Datatables::of($query)
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }

                    return $product_name;
                })
                ->editColumn('transaction_date', '{{@format_date($formated_date)}}')
                ->editColumn('total_qty_sold', function ($row) {
                    return '<span data-is_quantity="true" class="display_currency sell_qty" data-currency_symbol=false data-orig-value="' . (float)$row->total_qty_sold . '" data-unit="' . $row->unit . '" >' . (float) $row->total_qty_sold . '</span> ' .$row->unit;
                })
                ->editColumn('current_stock', function ($row) {
                    if ($row->enable_stock) {
                        return '<span data-is_quantity="true" class="display_currency current_stock" data-currency_symbol=false data-orig-value="' . (float)$row->current_stock . '" data-unit="' . $row->unit . '" >' . (float) $row->current_stock . '</span> ' .$row->unit;
                    } else {
                        return '';
                    }
                })
                 ->editColumn('subtotal', function ($row) {
                     return '<span class="display_currency row_subtotal" data-currency_symbol = true data-orig-value="' . $row->subtotal . '">' . $row->subtotal . '</span>';
                 })

                ->rawColumns(['current_stock', 'subtotal', 'total_qty_sold'])
                ->make(true);
        }
    }

    /**
     * Shows product stock details and allows to adjust mismatch
     *
     * @return \Illuminate\Http\Response
     */
    public function productStockDetails()
    {
        if (!auth()->user()->can('report.stock_details')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $stock_details = [];
        $location = null;
        $total_stock_calculated = 0;
        if (!empty(request()->input('location_id'))) {
            $variation_id = request()->get('variation_id', null);
            $location_id = request()->input('location_id');

            $location = BusinessLocation::where('business_id', $business_id)
                                        ->where('id', $location_id)
                                        ->first();

            $query = Variation::leftjoin('products as p', 'p.id', '=', 'variations.product_id')
                    ->leftjoin('units', 'p.unit_id', '=', 'units.id')
                    ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                    ->leftjoin('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                    ->where('p.business_id', $business_id)
                    ->where('vld.location_id', $location_id);
            if (!is_null($variation_id)) {
                $query->where('variations.id', $variation_id);
            }

            $stock_details = $query->select(
                DB::raw("(SELECT SUM(COALESCE(TSL.quantity, 0)) FROM transactions 
                        LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' AND transactions.location_id=$location_id 
                        AND TSL.variation_id=variations.id) as total_sold"),
                DB::raw("(SELECT SUM(COALESCE(TSL.quantity_returned, 0)) FROM transactions 
                        LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell' AND transactions.location_id=$location_id 
                        AND TSL.variation_id=variations.id) as total_sell_return"),
                DB::raw("(SELECT SUM(COALESCE(TSL.quantity,0)) FROM transactions 
                        LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell_transfer' AND transactions.location_id=$location_id 
                        AND TSL.variation_id=variations.id) as total_sell_transfered"),
                DB::raw("(SELECT SUM(COALESCE(PL.quantity,0)) FROM transactions 
                        LEFT JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='purchase_transfer' AND transactions.location_id=$location_id 
                        AND PL.variation_id=variations.id) as total_purchase_transfered"),
                DB::raw("(SELECT SUM(COALESCE(SAL.quantity, 0)) FROM transactions 
                        LEFT JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='stock_adjustment' AND transactions.location_id=$location_id 
                        AND SAL.variation_id=variations.id) as total_adjusted"),
                DB::raw("(SELECT SUM(COALESCE(PL.quantity, 0)) FROM transactions 
                        LEFT JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='purchase' AND transactions.location_id=$location_id
                        AND PL.variation_id=variations.id) as total_purchased"),
                DB::raw("(SELECT SUM(COALESCE(PL.quantity_returned, 0)) FROM transactions 
                        LEFT JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='purchase' AND transactions.location_id=$location_id
                        AND PL.variation_id=variations.id) as total_purchase_return"),
                DB::raw("(SELECT SUM(COALESCE(PL.quantity, 0)) FROM transactions 
                        LEFT JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='opening_stock' AND transactions.location_id=$location_id
                        AND PL.variation_id=variations.id) as total_opening_stock"),
                DB::raw("(SELECT SUM(COALESCE(PL.quantity, 0)) FROM transactions 
                        LEFT JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                        WHERE transactions.status='received' AND transactions.type='production_purchase' AND transactions.location_id=$location_id
                        AND PL.variation_id=variations.id) as total_manufactured"),
                DB::raw("(SELECT SUM(COALESCE(TSL.quantity, 0)) FROM transactions 
                        LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='production_sell' AND transactions.location_id=$location_id 
                        AND TSL.variation_id=variations.id) as total_ingredients_used"),
                DB::raw("SUM(vld.qty_available) as stock"),
                'variations.sub_sku as sub_sku',
                'p.name as product',
                'p.id as product_id',
                'p.type',
                'p.sku as sku',
                'units.short_name as unit',
                'p.enable_stock as enable_stock',
                'variations.sell_price_inc_tax as unit_price',
                'pv.name as product_variation',
                'variations.name as variation_name',
                'variations.id as variation_id'
            )
            ->groupBy('variations.id')
            ->get();

            foreach ($stock_details as $index => $row) {
                $total_sold = $row->total_sold ?: 0;
                $total_sell_return = $row->total_sell_return ?: 0;
                $total_sell_transfered = $row->total_sell_transfered ?: 0;

                $total_purchase_transfered = $row->total_purchase_transfered ?: 0;
                $total_adjusted = $row->total_adjusted ?: 0;
                $total_purchased = $row->total_purchased ?: 0;
                $total_purchase_return = $row->total_purchase_return ?: 0;
                $total_opening_stock = $row->total_opening_stock ?: 0;
                $total_manufactured = $row->total_manufactured ?: 0;
                $total_ingredients_used = $row->total_ingredients_used ?: 0;

                $total_stock_calculated = $total_opening_stock + $total_purchased + $total_purchase_transfered + $total_sell_return + $total_manufactured
                - ($total_sold + $total_sell_transfered + $total_adjusted + $total_purchase_return + $total_ingredients_used);

                $stock_details[$index]->total_stock_calculated = $total_stock_calculated;
            }
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        return view('report.product_stock_details')
            ->with(compact('stock_details', 'business_locations', 'location'));
    }

    /**
     * Adjusts stock availability mismatch if found
     *
     * @return \Illuminate\Http\Response
     */
    public function adjustProductStock()
    {
        if (!auth()->user()->can('report.stock_details')) {
            abort(403, 'Unauthorized action.');
        }

        if (!empty(request()->input('variation_id'))
            && !empty(request()->input('location_id'))
            && request()->has('stock')) {
            $business_id = request()->session()->get('user.business_id');

            $vld = VariationLocationDetails::leftjoin(
                'business_locations as bl',
                'bl.id',
                '=',
                'variation_location_details.location_id'
            )
                    ->where('variation_location_details.location_id', request()->input('location_id'))
                        ->where('variation_id', request()->input('variation_id'))
                        ->where('bl.business_id', $business_id)
                        ->select('variation_location_details.*')
                        ->first();

            if (!empty($vld)) {
                $vld->qty_available = request()->input('stock');
                $vld->save();
            }
        }

        return redirect()->back()->with(['status' => [
                'success' => 1,
                'msg' => __('lang_v1.updated_succesfully')
            ]]);
    }

    /**
     * Retrieves line orders/sales
     *
     * @return obj
     */
    public function serviceStaffLineOrders()
    {
        $business_id = request()->session()->get('user.business_id');

        $query = TransactionSellLine::leftJoin('transactions as t', 't.id', '=', 'transaction_sell_lines.transaction_id')
                ->leftJoin('variations as v', 'transaction_sell_lines.variation_id', '=', 'v.id')
                ->leftJoin('products as p', 'v.product_id', '=', 'p.id')
                ->leftJoin('units as u', 'p.unit_id', '=', 'u.id')
                ->leftJoin('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->leftJoin('users as ss', 'ss.id', '=', 'transaction_sell_lines.res_service_staff_id')
                ->leftjoin(
                    'business_locations AS bl',
                    't.location_id',
                    '=',
                    'bl.id'
                )
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->whereNotNull('transaction_sell_lines.res_service_staff_id');


        if (!empty(request()->service_staff_id)) {
            $query->where('transaction_sell_lines.res_service_staff_id', request()->service_staff_id);
        }

        if (request()->has('location_id')) {
            $location_id = request()->get('location_id');
            if (!empty($location_id)) {
                $query->where('t.location_id', $location_id);
            }
        }

        if (!empty(request()->start_date) && !empty(request()->end_date)) {
            $start = request()->start_date;
            $end =  request()->end_date;
            $query->whereDate('t.transaction_date', '>=', $start)
                        ->whereDate('t.transaction_date', '<=', $end);
        }

        $query->select(
            'p.name as product_name',
            'p.type as product_type',
            'v.name as variation_name',
            'pv.name as product_variation_name',
            'u.short_name as unit',
            't.id as transaction_id',
            'bl.name as business_location',
            't.transaction_date',
            't.invoice_no',
            'transaction_sell_lines.quantity',
            'transaction_sell_lines.unit_price_before_discount',
            'transaction_sell_lines.line_discount_type',
            'transaction_sell_lines.line_discount_amount',
            'transaction_sell_lines.item_tax',
            'transaction_sell_lines.unit_price_inc_tax',
            DB::raw('CONCAT(COALESCE(ss.first_name, ""), COALESCE(ss.last_name, "")) as service_staff')
        );

        $datatable = Datatables::of($query)
            ->editColumn('product_name', function ($row) {
                $name = $row->product_name;
                if ($row->product_type == 'variable') {
                    $name .= ' - ' . $row->product_variation_name . ' - ' . $row->variation_name;
                }
                return $name;
            })
            ->editColumn(
                'unit_price_inc_tax',
                '<span class="display_currency unit_price_inc_tax" data-currency_symbol="true" data-orig-value="{{$unit_price_inc_tax}}">{{$unit_price_inc_tax}}</span>'
            )
            ->editColumn(
                'item_tax',
                '<span class="display_currency item_tax" data-currency_symbol="true" data-orig-value="{{$item_tax}}">{{$item_tax}}</span>'
            )
            ->editColumn(
                'quantity',
                '<span class="display_currency quantity" data-unit="{{$unit}}" data-currency_symbol="false" data-orig-value="{{$quantity}}">{{$quantity}}</span> {{$unit}}'
            )
            ->editColumn(
                'unit_price_before_discount',
                '<span class="display_currency unit_price_before_discount" data-currency_symbol="true" data-orig-value="{{$unit_price_before_discount}}">{{$unit_price_before_discount}}</span>'
            )
            ->addColumn(
                'total',
                '<span class="display_currency total" data-currency_symbol="true" data-orig-value="{{$unit_price_inc_tax * $quantity}}">{{$unit_price_inc_tax * $quantity}}</span>'
            )
            ->editColumn(
                'line_discount_amount',
                function ($row) {
                    $discount = !empty($row->line_discount_amount) ? $row->line_discount_amount : 0;

                    if (!empty($discount) && $row->line_discount_type == 'percentage') {
                        $discount = $row->unit_price_before_discount * ($discount / 100);
                    }

                    return '<span class="display_currency total-discount" data-currency_symbol="true" data-orig-value="' . $discount . '">' . $discount . '</span>';
                }
            )
            ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')

            ->rawColumns(['line_discount_amount', 'unit_price_before_discount', 'item_tax', 'unit_price_inc_tax', 'item_tax', 'quantity', 'total'])
                  ->make(true);

        return $datatable;
    }

    /**
     * Lists profit by product, category, brand, location, invoice and date
     *
     * @return string $by = null
     */
    public function getProfit($by = null)
    {
        $business_id = request()->session()->get('user.business_id');

        $query = TransactionSellLine
            ::join('transactions as sale', 'transaction_sell_lines.transaction_id', '=', 'sale.id')
            ->leftjoin('transaction_sell_lines_purchase_lines as TSPL', 'transaction_sell_lines.id', '=', 'TSPL.sell_line_id')
            ->leftjoin(
                'purchase_lines as PL',
                'TSPL.purchase_line_id',
                '=',
                'PL.id'
            )
            ->join('products as P', 'transaction_sell_lines.product_id', '=', 'P.id')
            ->where('sale.business_id', $business_id)
            ->where('transaction_sell_lines.children_type', '!=', 'combo');
        //If type combo: find childrens, sale price parent - get PP of childrens
        $query->select(DB::raw('SUM(IF (TSPL.id IS NULL AND P.type="combo", ( 
    SELECT Sum((tspl2.quantity - tspl2.qty_returned) * (tsl.unit_price_inc_tax - pl2.purchase_price_inc_tax)) AS total
        FROM transaction_sell_lines AS tsl
            JOIN transaction_sell_lines_purchase_lines AS tspl2
        ON tsl.id=tspl2.sell_line_id 
        JOIN purchase_lines AS pl2 
        ON tspl2.purchase_line_id = pl2.id 
        WHERE tsl.parent_sell_line_id = transaction_sell_lines.id), IF(P.enable_stock=0,(transaction_sell_lines.quantity - transaction_sell_lines.quantity_returned) * transaction_sell_lines.unit_price_inc_tax,   
        (TSPL.quantity - TSPL.qty_returned) * (transaction_sell_lines.unit_price_inc_tax - PL.purchase_price_inc_tax)) )) AS gross_profit'));

        if (!empty(request()->start_date) && !empty(request()->end_date)) {
            $start = request()->start_date;
            $end =  request()->end_date;
            $query->whereDate('sale.transaction_date', '>=', $start)
                        ->whereDate('sale.transaction_date', '<=', $end);
        }

        if ($by == 'product') {
            $query->join('variations as V', 'transaction_sell_lines.variation_id', '=', 'V.id')
                ->leftJoin('product_variations as PV', 'PV.id', '=', 'V.product_variation_id')
                ->addSelect(DB::raw("IF(P.type='variable', CONCAT(P.name, ' - ', PV.name, ' - ', V.name, ' (', V.sub_sku, ')'), CONCAT(P.name, ' (', P.sku, ')')) as product"))
                ->groupBy('V.id');
        }

        if ($by == 'category') {
            $query->join('variations as V', 'transaction_sell_lines.variation_id', '=', 'V.id')
                ->leftJoin('categories as C', 'C.id', '=', 'P.category_id')
                ->addSelect("C.name as category")
                ->groupBy('C.id');
        }

        if ($by == 'brand') {
            $query->join('variations as V', 'transaction_sell_lines.variation_id', '=', 'V.id')
                ->leftJoin('brands as B', 'B.id', '=', 'P.brand_id')
                ->addSelect("B.name as brand")
                ->groupBy('B.id');
        }

        if ($by == 'location') {
            $query->join('business_locations as L', 'sale.location_id', '=', 'L.id')
                ->addSelect("L.name as location")
                ->groupBy('L.id');
        }

        if ($by == 'invoice') {
            $query->addSelect('sale.invoice_no', 'sale.id as transaction_id')
                ->groupBy('sale.invoice_no');
        }

        if ($by == 'date') {
            $query->addSelect("sale.transaction_date")
                ->groupBy(DB::raw('DATE(sale.transaction_date)'));
        }

        if ($by == 'day') {
            $results = $query->addSelect(DB::raw("DAYNAME(sale.transaction_date) as day"))
                ->groupBy(DB::raw('DAYOFWEEK(sale.transaction_date)'))
                ->get();

            $profits = [];
            foreach ($results as $result) {
                $profits[strtolower($result->day)] = $result->gross_profit;
            }
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

            return view('report.partials.profit_by_day')->with(compact('profits', 'days'));
        }

        if ($by == 'customer') {
            $query->join('contacts as CU', 'sale.contact_id', '=', 'CU.id')
            ->addSelect("CU.name as customer")
                ->groupBy('sale.contact_id');
        }

        $datatable = Datatables::of($query)
            ->editColumn(
                'gross_profit',
                '<span class="display_currency gross-profit" data-currency_symbol="true" data-orig-value="{{$gross_profit}}">{{$gross_profit}}</span>'
            );

        if ($by == 'category') {
            $datatable->editColumn(
                'category',
                '{{$category ?? __("lang_v1.uncategorized")}}'
            );
        }
        if ($by == 'brand') {
            $datatable->editColumn(
                'brand',
                '{{$brand ?? __("report.others")}}'
            );
        }

        if ($by == 'date') {
            $datatable->editColumn('transaction_date', '{{@format_date($transaction_date)}}');
        }

        $raw_columns = ['gross_profit'];
        if ($by == 'invoice') {
            $datatable->editColumn('invoice_no', function ($row) {
                return '<a data-href="' . action('SellController@show', [$row->transaction_id])
                            . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->invoice_no . '</a>';
            });
            $raw_columns[] = 'invoice_no';
        }
        return $datatable->rawColumns($raw_columns)
                  ->make(true);
    }

    /**
     * Shows items report from sell purchase mapping table
     *
     * @return \Illuminate\Http\Response
     */
    public function itemsReport()
    {
        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $query = TransactionSellLinesPurchaseLines::leftJoin('transaction_sell_lines 
                    as SL', 'SL.id', '=', 'transaction_sell_lines_purchase_lines.sell_line_id')
                ->leftJoin('stock_adjustment_lines 
                    as SAL', 'SAL.id', '=', 'transaction_sell_lines_purchase_lines.stock_adjustment_line_id')
                ->leftJoin('transactions as sale', 'SL.transaction_id', '=', 'sale.id')
                ->leftJoin('transactions as stock_adjustment', 'SAL.transaction_id', '=', 'stock_adjustment.id')
                ->join('purchase_lines as PL', 'PL.id', '=', 'transaction_sell_lines_purchase_lines.purchase_line_id')
                ->join('transactions as purchase', 'PL.transaction_id', '=', 'purchase.id')
                ->join('business_locations as bl', 'purchase.location_id', '=', 'bl.id')
                ->join(
                    'variations as v',
                    'PL.variation_id',
                    '=',
                    'v.id'
                    )
                ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
                ->join('products as p', 'PL.product_id', '=', 'p.id')
                ->join('units as u', 'p.unit_id', '=', 'u.id')
                ->leftJoin('contacts as suppliers', 'purchase.contact_id', '=', 'suppliers.id')
                ->leftJoin('contacts as customers', 'sale.contact_id', '=', 'customers.id')
                ->where('purchase.business_id', $business_id)
                ->select(
                    'v.sub_sku as sku',
                    'p.type as product_type',
                    'p.name as product_name',
                    'v.name as variation_name',
                    'pv.name as product_variation',
                    'u.short_name as unit',
                    'purchase.transaction_date as purchase_date',
                    'purchase.ref_no as purchase_ref_no',
                    'purchase.type as purchase_type',
                    'suppliers.name as supplier',
                    'PL.purchase_price_inc_tax as purchase_price',
                    'sale.transaction_date as sell_date',
                    'stock_adjustment.transaction_date as stock_adjustment_date',
                    'sale.invoice_no as sale_invoice_no',
                    'stock_adjustment.ref_no as stock_adjustment_ref_no',
                    'customers.name as customer',
                    'transaction_sell_lines_purchase_lines.quantity as quantity',
                    'SL.unit_price_inc_tax as selling_price',
                    'SAL.unit_price as stock_adjustment_price',
                    'transaction_sell_lines_purchase_lines.stock_adjustment_line_id',
                    'transaction_sell_lines_purchase_lines.sell_line_id',
                    'transaction_sell_lines_purchase_lines.purchase_line_id',
                    'transaction_sell_lines_purchase_lines.qty_returned',
                    'bl.name as location'
                );

            if (!empty(request()->purchase_start) && !empty(request()->purchase_end)) {
                $start = request()->purchase_start;
                $end =  request()->purchase_end;
                $query->whereDate('purchase.transaction_date', '>=', $start)
                            ->whereDate('purchase.transaction_date', '<=', $end);
            }
            if (!empty(request()->sale_start) && !empty(request()->sale_end)) {
                $start = request()->sale_start;
                $end =  request()->sale_end;
                $query->where(function ($q) use ($start, $end) {
                    $q->where(function ($qr) use ($start, $end) {
                        $qr->whereDate('sale.transaction_date', '>=', $start)
                           ->whereDate('sale.transaction_date', '<=', $end);
                    })->orWhere(function ($qr) use ($start, $end) {
                        $qr->whereDate('stock_adjustment.transaction_date', '>=', $start)
                           ->whereDate('stock_adjustment.transaction_date', '<=', $end);
                    });
                });
            }

            $supplier_id = request()->get('supplier_id', null);
            if (!empty($supplier_id)) {
                $query->where('suppliers.id', $supplier_id);
            }

            $customer_id = request()->get('customer_id', null);
            if (!empty($customer_id)) {
                $query->where('customers.id', $customer_id);
            }

            $location_id = request()->get('location_id', null);
            if (!empty($location_id)) {
                $query->where('purchase.location_id', $location_id);
            }

            $only_mfg_products = request()->get('only_mfg_products', 0);
            if (!empty($only_mfg_products)) {
                $query->where('purchase.type', 'production_purchase');
            }

            return Datatables::of($query)
                ->editColumn('product_name', function ($row) {
                    $product_name = $row->product_name;
                    if ($row->product_type == 'variable') {
                        $product_name .= ' - ' . $row->product_variation . ' - ' . $row->variation_name;
                    }

                    return $product_name;
                })
                ->editColumn('purchase_date', '{{@format_datetime($purchase_date)}}')
                ->editColumn('purchase_ref_no', function ($row) {
                    $html = $row->purchase_type == 'purchase' ? '<a data-href="' . action('PurchaseController@show', [$row->purchase_line_id])
                            . '" href="#" data-container=".view_modal" class="btn-modal">' . $row->purchase_ref_no . '</a>' : $row->purchase_ref_no;
                    if ($row->purchase_type == 'opening_stock') {
                        $html .= '(' . __('lang_v1.opening_stock') . ')';
                    }
                    return $html;
                })
                ->editColumn('purchase_price', function ($row) {
                    return '<span class="display_currency purchase_price" data-currency_symbol=true data-orig-value="' . $row->purchase_price . '">' . $row->purchase_price . '</span>';
                })
                ->editColumn('sell_date', '@if(!empty($sell_line_id)) {{@format_datetime($sell_date)}} @else {{@format_datetime($stock_adjustment_date)}} @endif')

                ->editColumn('sale_invoice_no', function ($row) {
                    $invoice_no = !empty($row->sell_line_id) ? $row->sale_invoice_no : $row->stock_adjustment_ref_no . '<br><small>(' . __('stock_adjustment.stock_adjustment') . '</small>' ;

                    return $invoice_no;
                })
                ->editColumn('quantity', function ($row) {
                    $html = '<span data-is_quantity="true" class="display_currency quantity" data-currency_symbol=false data-orig-value="' . (float)$row->quantity . '" data-unit="' . $row->unit . '" >' . (float) $row->quantity . '</span> ' . $row->unit;
                    if ($row->qty_returned > 0) {
                        $html .= '<small><i>(<span data-is_quantity="true" class="display_currency" data-currency_symbol=false>' . (float) $row->quantity . '</span> ' . $row->unit . ' ' . __('lang_v1.returned') . ')</i></small>';
                    }

                    return $html;
                })
                 ->editColumn('selling_price', function ($row) {
                     $selling_price = !empty($row->sell_line_id) ? $row->selling_price : $row->stock_adjustment_price;

                     return '<span class="display_currency row_selling_price" data-currency_symbol=true data-orig-value="' . $selling_price . '">' . $selling_price . '</span>';
                 })

                 ->addColumn('subtotal', function ($row) {
                     $selling_price = !empty($row->sell_line_id) ? $row->selling_price : $row->stock_adjustment_price;
                     $subtotal = $selling_price * $row->quantity;
                     return '<span class="display_currency row_subtotal" data-currency_symbol=true data-orig-value="' . $subtotal . '">' . $subtotal . '</span>';
                 })

                ->filterColumn('sale_invoice_no', function ($query, $keyword) {
                    $query->where('sale.invoice_no', 'like', ["%{$keyword}%"])
                          ->orWhere('stock_adjustment.ref_no', 'like', ["%{$keyword}%"]);
                })

                ->rawColumns(['subtotal', 'selling_price', 'quantity', 'purchase_price', 'sale_invoice_no', 'purchase_ref_no'])
                ->make(true);
        }

        $suppliers = Contact::suppliersDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);
        $business_locations = BusinessLocation::forDropdown($business_id);
        return view('report.items_report')->with(compact('suppliers', 'customers', 'business_locations'));
    }

    /**
     * Shows purchase report
     *
     * @return \Illuminate\Http\Response
     */
    public function purchaseReport()
    {
        if ((!auth()->user()->can('purchase.view') && !auth()->user()->can('purchase.create') && !auth()->user()->can('view_own_purchase')) || empty(config('constants.show_report_606'))) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            $payment_types = $this->transactionUtil->payment_types();
            $purchases = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                    ->join(
                        'business_locations AS BS',
                        'transactions.location_id',
                        '=',
                        'BS.id'
                    )
                    ->leftJoin(
                        'transaction_payments AS TP',
                        'transactions.id',
                        '=',
                        'TP.transaction_id'
                    )
                    ->where('transactions.business_id', $business_id)
                    ->where('transactions.type', 'purchase')
                    ->with(['payment_lines'])
                    ->select(
                        'transactions.id',
                        'transactions.ref_no',
                        'contacts.name',
                        'contacts.contact_id',
                        'final_total',
                        'total_before_tax',
                        'discount_amount',
                        'discount_type',
                        'tax_amount',
                        DB::raw('DATE_FORMAT(transaction_date, "%Y/%m") as purchase_year_month'),
                        DB::raw('DATE_FORMAT(transaction_date, "%d") as purchase_day')
                    )
                    ->groupBy('transactions.id');

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $purchases->whereIn('transactions.location_id', $permitted_locations);
            }

            if (!empty(request()->supplier_id)) {
                $purchases->where('contacts.id', request()->supplier_id);
            }
            if (!empty(request()->location_id)) {
                $purchases->where('transactions.location_id', request()->location_id);
            }
            if (!empty(request()->input('payment_status')) && request()->input('payment_status') != 'overdue') {
                $purchases->where('transactions.payment_status', request()->input('payment_status'));
            } elseif (request()->input('payment_status') == 'overdue') {
                $purchases->whereIn('transactions.payment_status', ['due', 'partial'])
                    ->whereNotNull('transactions.pay_term_number')
                    ->whereNotNull('transactions.pay_term_type')
                    ->whereRaw("IF(transactions.pay_term_type='days', DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number DAY) < CURDATE(), DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number MONTH) < CURDATE())");
            }

            if (!empty(request()->status)) {
                $purchases->where('transactions.status', request()->status);
            }

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $purchases->whereDate('transactions.transaction_date', '>=', $start)
                            ->whereDate('transactions.transaction_date', '<=', $end);
            }

            if (!auth()->user()->can('purchase.view') && auth()->user()->can('view_own_purchase')) {
                $purchases->where('transactions.created_by', request()->session()->get('user.id'));
            }

            return Datatables::of($purchases)
                ->removeColumn('id')
                ->editColumn(
                    'final_total',
                    '<span class="display_currency final_total" data-currency_symbol="true" data-orig-value="{{$final_total}}">{{$final_total}}</span>'
                )
                ->editColumn(
                    'total_before_tax',
                    '<span class="display_currency total_before_tax" data-currency_symbol="true" data-orig-value="{{$total_before_tax}}">{{$total_before_tax}}</span>'
                )
                ->editColumn(
                    'tax_amount',
                    '<span class="display_currency tax_amount" data-currency_symbol="true" data-orig-value="{{$tax_amount}}">{{$tax_amount}}</span>'
                )
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
                ->addColumn('payment_year_month', function ($row) {
                    $year_month = '';
                    if (!empty($row->payment_lines->first())) {
                        $year_month = \Carbon::parse($row->payment_lines->first()->paid_on)->format('Y/m');
                    }
                    return $year_month;
                })
                ->addColumn('payment_day', function ($row) {
                    $payment_day = '';
                    if (!empty($row->payment_lines->first())) {
                        $payment_day = \Carbon::parse($row->payment_lines->first()->paid_on)->format('d');
                    }
                    return $payment_day;
                })
                ->addColumn('payment_method', function ($row) use ($payment_types) {
                    $methods = array_unique($row->payment_lines->pluck('method')->toArray());
                    $count = count($methods);
                    $payment_method = '';
                    if ($count == 1) {
                        $payment_method = $payment_types[$methods[0]];
                    } elseif ($count > 1) {
                        $payment_method = __('lang_v1.checkout_multi_pay');
                    }

                    $html = !empty($payment_method) ? '<span class="payment-method" data-orig-value="' . $payment_method . '" data-status-name="' . $payment_method . '">' . $payment_method . '</span>' : '';

                    return $html;
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("purchase.view")) {
                            return  action('PurchaseController@show', [$row->id]) ;
                        } else {
                            return '';
                        }
                    }])
                ->rawColumns(['final_total', 'total_before_tax', 'tax_amount', 'discount_amount', 'payment_method'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $suppliers = Contact::suppliersDropdown($business_id, false);
        $orderStatuses = $this->productUtil->orderStatuses();

        return view('report.purchase_report')
            ->with(compact('business_locations', 'suppliers', 'orderStatuses'));
    }

    /**
     * Shows sale report
     *
     * @return \Illuminate\Http\Response
     */
    public function saleReport()
    {
        if ((!auth()->user()->can('sell.view') && !auth()->user()->can('sell.create') && !auth()->user()->can('direct_sell.access') && !auth()->user()->can('view_own_sell_only')) ||empty(config('constants.show_report_607'))) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);

        return view('report.sale_report')
            ->with(compact('business_locations', 'customers'));
    }

    public function calculateTotalRevenueByDay($date)
    {
        if (!auth()->user()->can('report.revenue_date')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Total sell by day
        $total_sell = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereDate('transaction_date', '=', $date)
            ->sum('final_total');

        $total_return = Transaction::where('business_id', $business_id)
            ->where('type', 'sell_return')
            ->where('status', 'final')
            ->whereDate('transaction_date', '=', $date)
            ->sum('final_total');

        $result['total_revenue'] = $total_sell - $total_return;

        //Get total revenue by month
        $start_date = date('Y-m', strtotime($date)).'-01';
        $total_return_by_month = Contact::join('transactions as t', 'contacts.id', '=', 't.contact_id')
            ->with('customer_group')
            ->where('contacts.business_id', $business_id)
            ->whereIn('t.type', ['sell', 'sell_return'])
            ->where('t.status', 'final')
            ->select(
                DB::raw("SUM(IF(t.type = 'sell_return', final_total * -1, final_total)) as total_sell")
            )
            ->whereDate('transaction_date', '>=', $start_date)
            ->whereDate('transaction_date', '<=', $date)
            ->first();
        $result['total_revenue_by_month'] = $total_return_by_month->total_sell;

        //Total customer debt
        $debt = Contact::leftjoin('transactions AS t', 'contacts.id', '=', 't.contact_id')
            ->leftjoin('customer_groups AS cg', 'contacts.customer_group_id', '=', 'cg.id')
            ->where('contacts.business_id', $business_id)
            ->onlyCustomers()
            ->select([
                DB::raw("SUM(IF(t.type = 'reduce_debt' AND t.status = 'final', final_total, 0)) as reduce_debt"),
                DB::raw("SUM(IF(t.type = 'purchase', final_total, 0)) as total_purchase"),
                DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', final_total, 0)) as total_invoice"),
                DB::raw("SUM(IF((t.type = 'sell' OR t.type = 'receipt' OR t.type = 'expense') AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id AND approval_status='approved'), 0)) as invoice_received"),
                DB::raw("SUM(IF(t.type = 'sell_return' AND t.status = 'final', final_total, 0)) as total_sell_return"),
                DB::raw("SUM(IF(t.type = 'sell_return' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id AND approval_status='approved'), 0)) as sell_return_paid"),
                DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance")
            ])->first();

        $queryDebt = $this->queryCalTotalDebt($business_id);
        $sum = $queryDebt->get();

        $result['total_debt'] = $debt->total_invoice - $debt->invoice_received + $debt->sell_return_paid - $debt->total_sell_return + $debt->opening_balance  - $debt->total_purchase - $debt->reduce_debt;
        $result['due'] = $sum->sum('credit');
        $result['payment'] = -$sum->sum('debit');

        $total_money_on_day = $this->transactionUtil->calculateTotalMoneyOnDay($date);
        $result['total_money_payment_cash'] = $total_money_on_day['total_money_payment_cash'];
        $result['total_money_payment_bank'] = $total_money_on_day['total_money_payment_bank'];

        return $result;
    }

    public function getTotalRevenueByDay()
    {
        if (request()->ajax()) {
            $date = request()->input('date');
            $output = $this->calculateTotalRevenueByDay($date);

            $output = ['success' => 1,
                'data' => $output,
            ];

            return $output;
        }
    }

    public function receiptRevenueReport() {
        if (!auth()->user()->can('report.revenue_date')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $receipt_customers = Contact::customersDropdown($business_id, false);
        $payment_types = $this->transactionUtil->payment_types();

        if(!empty(request()->date)){
            $date = request()->date;
        }else{
            $date = Carbon::today()->toDateString();
        }

        $total = $this->calculateTotalRevenueByDay($date);

        if (request()->ajax()) {
            $query = TransactionPayment::where('type', 'receipt')
                ->whereDate('paid_on', '=', $date)
                ->where('approval_status', 'approved')
                ->select(
                    'id',
                    'transaction_id',
                    'payment_for',
                    'amount',
                    'note'
                );

            if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $query->where('payment_for', $customer_id);
            }

            $receipt = DataTables::of($query)
                ->editColumn('payment_for', function ($row) {
                    $contact = Contact::find($row->payment_for);
                    return $contact ? '<a href="' . action("ContactController@show", [$contact->id]) . '" target="_blank">' . $contact->name . '</a>' : '--';
                })
                ->editColumn('note', function ($row) {
                    return !empty($row->note) ? $row->note : '--';
                })
                ->editColumn('amount', function ($row) {
                    $html = '<span class="display_currency total-money-receipt" data-currency_symbol="true" data-orig-value="'. $row->amount.'">'. $row->amount.'</span>';
                    return $html;
                })
                ->removeColumn('id');

            return $receipt->rawColumns(['amount', 'payment_for'])
                ->make(true);
        }

        return view('report.revenue_report', compact('payment_types', 'receipt_customers', 'total'));
    }

    public function exportRevenueByDayReport(Request $request)
    {
        if (!auth()->user()->can('report.revenue_date')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $receipt_customers = Contact::customersDropdown($business_id, false);
            $payment_types = $this->transactionUtil->payment_types();

            $total_sell = Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->whereDate('transaction_date', '=', Carbon::today()->toDateString())
                ->sum('final_total');

            $total_return = Transaction::where('business_id', $business_id)
                ->where('type', 'sell_return')
                ->where('status', 'final')
                ->whereDate('transaction_date', '=', Carbon::today()->toDateString())
                ->sum('final_total');

            $total_revenue = $total_sell - $total_return;

            $debt = Contact::leftjoin('transactions AS t', 'contacts.id', '=', 't.contact_id')
                ->leftjoin('customer_groups AS cg', 'contacts.customer_group_id', '=', 'cg.id')
                ->where('contacts.business_id', $business_id)
                ->onlyCustomers()
                ->select([
                    DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', final_total, 0)) as total_invoice"),
                    DB::raw("SUM(IF((t.type = 'sell' OR t.type = 'receipt' OR t.type = 'expense') AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id AND approval_status='approved'), 0)) as invoice_received"),
                    DB::raw("SUM(IF(t.type = 'sell_return' AND t.status = 'final', final_total, 0)) as total_sell_return"),
                    DB::raw("SUM(IF(t.type = 'sell_return' AND t.status <> 'cancel', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id AND approval_status='approved'), 0)) as sell_return_paid"),
                    DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance")
                ])->first();

            $total_debt = $debt->total_invoice - $debt->invoice_received + $debt->sell_return_paid - $debt->total_sell_return + $debt->opening_balance;
            $today = Carbon::today()->toDateString();
            $total_money_on_day = $this->transactionUtil->calculateTotalMoneyOnDay($today);

            $export[1]['A'] = 'BÁO CÁO NGÀY '. date('d/m/Y');
            $export[3]['A'] = 'DOANH THU THÁNG';
            $export[4]['A'] = 'DOANH THU NGÀY';
            $export[5]['A'] = 'TỔNG CÔNG NỢ';
            $export[6]['A'] = 'TIỀN MẶT';
            $export[6]['C'] = 'Nộp TK:';
            $export[6]['E'] = 'Còn TM:';
            $export[7]['A'] = 'KHÁCH CK';
            $export[9]['A'] = 'CHI PHÍ';
            $export[9]['E'] = 'THU NỢ';

            //TODO: Get receipts
            $receipts = TransactionPayment::leftJoin('contacts', 'transaction_payments.payment_for', '=', 'contacts.id')
                ->where('transaction_payments.type', 'receipt')
                ->select(
                    'transaction_payments.method',
                    'transaction_payments.payment_for',
                    DB::raw("IF(transaction_payments.payment_for IS NULL, contacts.name, '') as contact_name"),
                    'transaction_payments.amount',
                    'transaction_payments.note'
                );

            if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $receipts->where('transaction_payments.payment_for', $customer_id);
            }

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $receipts
                    ->whereDate('transaction_payments.paid_on', '>=', $start)
                    ->whereDate('transaction_payments.paid_on', '<=', $end);
            } else {
                $receipts->whereDate('paid_on', '=', Carbon::today()->toDateString());
            }

            /*$receipt = DataTables::of($query)
                ->editColumn('payment_for', function ($row) {
                    $contact = Contact::find($row->payment_for);
                    return $contact ? $contact->name : '--';
                })
                ->editColumn('note', function ($row) {
                    return !empty($row->note) ? $row->note : '--';
                })
                ->editColumn('amount', function ($row) {
                    $html = '<span class="display_currency total-money-receipt" data-currency_symbol="true" data-orig-value="'. $row->amount.'">'. $row->amount.'</span>';
                    return $html;
                })
                ->removeColumn('id');

            return $receipt->rawColumns(['amount'])
                ->make(true);*/

            $receipts = $receipts->get();
            $row_index_receipt = 9;
            $total_receipt = 0;

            foreach ($receipts as $receipt){
                $receipt_amount = $receipt->amount * -1;
                $total_receipt += $receipt_amount;

                $export[$row_index_receipt]['A'] = $receipt->contact_name;
                $export[$row_index_receipt]['B'] = $receipt_amount;
                $export[$row_index_receipt]['C'] = $receipt->method == 'bank' ? 'CK' : 'TM';

                $row_index_receipt++;
            }

            $export[9]['B'] = $total_receipt;

            //TODO: Get expenses
            $expenses = TransactionPayment::where('type', 'expense')
                ->select(
                    'id',
                    'transaction_id',
                    'payment_for',
                    'amount',
                    'note'
                );

            if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $expenses->where('payment_for', $customer_id);
            }

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $expenses
                    ->whereDate('paid_on', '>=', $start)
                    ->whereDate('paid_on', '<=', $end);
            } else {
                $expenses->whereDate('paid_on', '=', Carbon::today()->toDateString());
            }

            $expenses = $expenses->get();

            /*$expense = DataTables::of($query)
                ->editColumn('payment_for', function ($row) {
                    $contact = Contact::find($row->payment_for);
                    return $contact ? $contact->name : '--';
                })
                ->editColumn('note', function ($row) {
                    return !empty($row->note) ? $row->note : '--';
                })
                ->editColumn('amount', function ($row) {
                    $html = '<span class="display_currency total-money-expense" data-currency_symbol="true" data-orig-value="'. -1 * $row->amount.'">'. -1 * $row->amount.'</span>';
                    return $html;
                })
                ->removeColumn('id');

            return $expense->rawColumns(['amount'])
                ->make(true);*/

            //TODO: Get customer debts
            $customer_debts = Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->select(
                    '*',
                    DB::raw("SUM(final_total) as sum_final"),
                    DB::raw("SUM((SELECT SUM(tp.amount) FROM transaction_payments as tp WHERE tp.transaction_id=transactions.id AND approval_status='approved')) as total_paid")
                )
                ->groupBy('contact_id');


            if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $customer_debts->where('contact_id', $customer_id);
            }

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $customer_debts
                    ->whereDate('transaction_date', '>=', $start)
                    ->whereDate('transaction_date', '<=', $end);
            } else {
                $customer_debts->whereDate('transaction_date', '=', Carbon::today()->toDateString());
            }

            $customer_debts = $customer_debts->get();

            /*$transactions = DataTables::of($query)
                ->editColumn('contact_id', function ($row) {
                    $contact = Contact::find($row->contact_id);
                    return $contact ? $contact->name : '--';
                })
                ->addColumn('total_due_customer', function ($row) {
                    $remain = $row->sum_final - $row->total_paid;
                    if ($remain > 0) {
                        $html = '<span class="display_currency total_due_customer" data-currency_symbol="true" data-orig-value="'. abs($remain) .'">'. abs($remain) .'</span>';
                    } else {
                        $html = '<span class="display_currency total_due_customer" data-currency_symbol="true"></span>';
                    }

                    return $html;
                })
                ->addColumn('total_due_business', function ($row) {
                    $remain = $row->sum_final - $row->total_paid;
                    if ($remain < 0) {
                        $html = '<span class="display_currency total_due_business" data-currency_symbol="true" data-orig-value="'. abs($remain) .'">'. abs($remain) .'</span>';
                    } else {
                        $html = '<span class="display_currency total_due_business" data-currency_symbol="true"></span>';
                    }

                    return $html;
                })
                ->removeColumn('id');

            return $transactions->rawColumns(['total_due_customer', 'total_due_business'])
                ->make(true);*/

            /*$merge = [];

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
            $sum_final_total = 0;
            $sum_total_paid = 0;
            $sum_total_due_customer = $total_debt >= 0 ? $total_debt : 0;
            $sum_total_due_business = $total_debt < 0 ? abs($total_debt) : 0;

            foreach ($transactions as $transaction) {
                // Ngày giao dịch
                $transaction_date = Carbon::parse($transaction->transaction_date)->format('d-m-Y');

                // Tổng tiền đã trả
                $amount = 0;
                foreach ($transaction->payment_lines as $payment) {
                    if ($transaction->status == 'final' && $payment->approval_status == 'approved') {
                        $amount += $payment->amount;
                    }
                }

                if ($amount > 0) {
                    $total_paid = $amount;
                } else {
                    $total_paid = 0;
                }

                if ($transaction->type != 'expense') {
                    $sum_total_paid += $amount;
                }

                // Tổng tiền còn lại của khách
                $remain_customer = $transaction->final_total - $amount;

                // Hóa đơn số
                $invoice_no = in_array($transaction->type, ['sell', 'sell_return']) ? $transaction->invoice_no : $transaction->ref_no;

                $note = '';
                $final_total = 0;
                $total_due_customer = 0;
                $total_due_business = 0;
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

                    $sum_final_total += $transaction->final_total;
                    $final_total = $transaction->final_total;

                    // Số dư (Nợ)
                    if ($remain_customer > 0) {
                        $total_due_customer = abs($remain_customer);
                    } else {
                        $total_due_customer = 0;
                    }

                    // Số dư (Có)
                    $remain_business = $remain_customer;
                    if ($remain_business < 0) {
                        $total_due_business = abs($remain_business);
                    } else {
                        $total_due_business = 0;
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

                    $final_total = $amount;
                    $sum_final_total += $final_total;
                    $total_paid = (float)$sell_return->final_total;
                    $sum_total_paid = $sum_total_paid + $total_paid - $final_total;
                    $remain_customer = $final_total - $total_paid;

                    if ($remain_customer < 0) {
                        $total_due_customer = 0;
                        if ($remain_customer == 0) {
                            $total_due_customer = 0;
                        }
                    } else {
                        $total_due_customer = abs($remain_customer);
                    }

                    // Số dư (Có)
                    $remain_business = $remain_customer;
                    if ($remain_business < 0) {
                        $total_due_business = abs($remain_business);
                    } else {
                        $total_due_business = 0;
                    }
                } elseif ($transaction->type == 'receipt') {
                    $products[] = [
                        'sku' => '',
                        'detail' => $transaction->receipt_note,
                    ];
                    $final_total = 0;
                    $total_due_customer = 0;
                    $total_due_business = (float)$transaction->final_total;
                } elseif ($transaction->type == 'expense') {
                    $products[] = [
                        'sku' => '',
                        'detail' => $transaction->expense_note,
                    ];
                    $final_total = $transaction->final_total;
                    $total_due_customer = (float)$transaction->final_total;
                    $total_due_business = 0;
                    $sum_final_total += $transaction->final_total;
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

                    // Số tiền (Có)
                    $total_paid = (float) $transaction->final_total;

                    // Số dư (Có)
                    $total_due_business = (float) $transaction->final_total;
                }

                $sum_total_due_customer += (float)$total_due_customer;
                $sum_total_due_business += (float)$total_due_business;

                $export[$row_index]['A'] = $invoice_no;
                $export[$row_index]['B'] = $transaction_date;
                $export[$row_index]['C'] = !empty($products) ? $products[0]['sku'] : '';
                $export[$row_index]['D'] = !empty($products) ? $products[0]['detail'] : '';
                $export[$row_index]['E'] = $note;
                $export[$row_index]['F'] = (in_array($transaction->type, ['sell', 'expense', 'sell_return']) && $final_total != 0) ? $final_total : '';
                $export[$row_index]['G'] = $total_paid != 0 ? $total_paid : '';
                $export[$row_index]['H'] = $total_due_customer != 0 ? $total_due_customer : '';
                $export[$row_index]['I'] = $total_due_business != 0 ? $total_due_business : '';
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
            $export[$row_index]['F'] = $sum_final_total;
            $export[$row_index]['G'] = $sum_total_paid;
            $export[$row_index]['H'] = $sum_total_due_customer;
            $export[$row_index]['I'] = $sum_total_due_business;*/
//            $file_name = Str::slug($contact->name);

            $file_name = 'bao-cao-doanh-thu-ngay';
            if(isset($end_date)){
                $file_name .= '_'. $end_date;
            }
            $file_name .= '.xls';

            return Excel::download(new RevenueByDayReportExport($export), $file_name);
//            return Excel::download(new RevenueByDayReportExport($export, $row_index, $merge), $file_name);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                'msg' => $e->getMessage()
            ];
            return redirect(url()->previous())->with('status', $output);
        }
    }

    public function printRevenueByDayReport()
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            if(!empty(request()->date)){
                $date = request()->date;
            }else{
                $date = Carbon::today()->toDateString();
            }

            $total = $this->calculateTotalRevenueByDay($date);
            $receipt_and_expense_rows = [];

            //TODO: Get expenses
            $expenses = TransactionPayment::leftJoin('contacts', 'transaction_payments.payment_for', '=', 'contacts.id')
                ->where('transaction_payments.type', 'expense')
                ->whereDate('transaction_payments.paid_on', '=', $date)
                ->where('approval_status', 'approved')
                ->select(
                    'transaction_payments.method',
                    'transaction_payments.payment_for',
                    DB::raw("IF(transaction_payments.payment_for IS NOT NULL, IF(contacts.is_default = 0 , contacts.name, transaction_payments.note), '') as contact_name"),
                    'transaction_payments.amount',
                    'transaction_payments.note'
                );

            if (!empty(request()->expense_customer_id)) {
                $expense_customer_id = request()->expense_customer_id;
                $expenses->where('transaction_payments.payment_for', $expense_customer_id);
            }

            $expenses = $expenses->get();
            $num_expense = $expenses->count();
            $total_expense = 0;

            foreach ($expenses as $key => $expense){
                $expense_amount = $expense->amount * -1;
                $total_expense += $expense_amount;

                $receipt_and_expense_rows[$key]['expense'] = [
                    'note' => !empty($expense->contact_name) ? $expense->contact_name : $expense->note,
                    'amount' => $expense_amount,
                    'method' => $expense->method == 'cash' ? 'TM' : 'CK',
                ];
            }

            //TODO: Get receipts
            $receipts = TransactionPayment::leftJoin('contacts', 'transaction_payments.payment_for', '=', 'contacts.id')
                ->where('transaction_payments.type', 'receipt')
                ->whereDate('transaction_payments.paid_on', '=', $date)
                ->where('approval_status', 'approved')
                ->select(
                    'transaction_payments.method',
                    'transaction_payments.payment_for',
                    DB::raw("IF(transaction_payments.payment_for IS NOT NULL, IF(contacts.is_default = 0 , contacts.name, transaction_payments.note), '') as contact_name"),
                    'transaction_payments.amount',
                    'transaction_payments.note'
                );

            if (!empty(request()->receipt_customer_id)) {
                $receipt_customer_id = request()->receipt_customer_id;
                $receipts->where('transaction_payments.payment_for', $receipt_customer_id);
            }

            $receipts = $receipts->get();
            $num_receipt = $receipts->count();
            $total_receipt = 0;

            foreach ($receipts as $key => $receipt){
                $total_receipt += $receipt->amount;
                $receipt_and_expense_rows[$key]['receipt'] = [
                    'note' => !empty($receipt->contact_name) ? $receipt->contact_name : $receipt->note,
                    'amount' => $receipt->amount,
                    'method' => $receipt->method == 'cash' ? 'TM' : 'CK',
                ];
            }

            //TODO: Get customer debts
            $customer_debts = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->whereDate('transactions.transaction_date', '=', $date)
//                ->whereRaw("(SUM(transactions.final_total) - SUM((SELECT SUM(tp.amount) FROM transaction_payments as tp WHERE tp.transaction_id=transactions.id AND tp.approval_status='approved'))) <> 0")
                ->select(
                    'transactions.contact_id',
                    'contacts.name',
//                    DB::raw("(SUM(transactions.final_total) - SUM((SELECT SUM(tp.amount) FROM transaction_payments as tp WHERE tp.transaction_id=transactions.id AND tp.approval_status='approved'))) as total_debt")
                    DB::raw("SUM(final_total) as sum_final"),
                    DB::raw("SUM((SELECT SUM(tp.amount) FROM transaction_payments as tp WHERE tp.transaction_id=transactions.id AND approval_status='approved')) as total_paid")
                )
                ->groupBy('contact_id');


            if (!empty(request()->debt_customer_id)) {
                $debt_customer_id = request()->debt_customer_id;
                $customer_debts->where('contact_id', $debt_customer_id);
            }

            $customer_debts = $customer_debts->get()->toArray();

            $new_customer_debts = $customer_debts;
            foreach ($new_customer_debts as $key => $new_customer_debt){
                $customer_debts[$key]['total_debt'] = $new_customer_debt['sum_final'] - $new_customer_debt['total_paid'];
                if ($customer_debts[$key]['total_debt'] == 0){
                    unset($customer_debts[$key]);
                }
            }

            $debt_rows = [];
            $num_debt_column_1 = intdiv(count($customer_debts), 2) + (count($customer_debts) % 2);
            $num_debt_column_2 = count($customer_debts) - $num_debt_column_1;

            /*if(count($customer_debts) % 2 == 1){
                $num_debt = $num_debt_column_1;
            }else{
                $num_debt = $num_debt_column_2;
            }*/

            $debt_column_1_rows = array_slice($customer_debts, 0, $num_debt_column_1);
            $debt_column_2_rows = array_slice($customer_debts, $num_debt_column_1, $num_debt_column_2);

            foreach ($debt_column_1_rows as $key => $debt_column_1_row){
                $debt_rows[$key]['column_1'] = [
                    'name' => $debt_column_1_row['name'],
                    'debt' => $debt_column_1_row['total_debt'],
                ];
            }

            foreach ($debt_column_2_rows as $key => $debt_column_2_row){
                $debt_rows[$key]['column_2'] = [
                    'name' => $debt_column_2_row['name'],
                    'debt' => $debt_column_2_row['total_debt'],
                ];
            }

            //Get sell invoice
            $sell = Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->whereIn('status', ['final', 'cancel'])
                ->whereDate('transaction_date', '=', $date)
                ->select(
                    DB::raw('CONVERT(invoice_no, SIGNED) as invoice_no_int')
                )
                ->pluck('invoice_no_int')
                ->toArray();
            sort($sell);

            $total_sell = count($sell);

            if(!empty($sell)){
                $sell_invoice_from = $sell[0];
                $sell_invoice_to = $sell[count($sell) - 1];
            }else{
                $sell_invoice_from = '';
                $sell_invoice_to = '';
            }

            //Get total canceled sell
            $total_cancel_sell = Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'cancel')
                ->whereDate('transaction_date', '=', $date)
                ->count();

            // Get user close end of day
            $user_closed_end_of_day = $this->moduleUtil->getUserClosedEndOfDay($date);

            $output = [
                'success' => 1,
                'receipt' => []
            ];
            $output['receipt']['html_content'] = view('report.partials.revenue_by_day_receipt', compact(
                    'receipt_and_expense_rows',
                    'total_receipt',
                    'num_receipt',
                    'total_expense',
                    'num_expense',
                    'debt_rows',
                    'num_debt_column_1',
                    'num_debt_column_2',
                    'total',
                    'total_sell',
                    'sell_invoice_from',
                    'sell_invoice_to',
                    'total_cancel_sell',
                    'user_closed_end_of_day',
                    'date'
                ))->render();
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }

    public function expenseRevenueReport() {
        if (!auth()->user()->can('report.revenue_date')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            if(!empty(request()->date)){
                $date = request()->date;
            }else{
                $date = Carbon::today()->toDateString();
            }

            $query = TransactionPayment::where('type', 'expense')
                ->whereDate('paid_on', '=', $date)
                ->where('approval_status', 'approved')
                ->select(
                    'id',
                    'transaction_id',
                    'payment_for',
                    'amount',
                    'note'
                );

            if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $query->where('payment_for', $customer_id);
            }

            $expense = DataTables::of($query)
                ->editColumn('payment_for', function ($row) {
                    $contact = Contact::find($row->payment_for);
                    return $contact ? '<a href="' . action("ContactController@show", [$contact->id]) . '" target="_blank">' . $contact->name . '</a>' : '--';
                })
                ->editColumn('note', function ($row) {
                    return !empty($row->note) ? $row->note : '--';
                })
                ->editColumn('amount', function ($row) {
                    $html = '<span class="display_currency total-money-expense" data-currency_symbol="true" data-orig-value="'. -1 * $row->amount.'">'. -1 * $row->amount.'</span>';
                    return $html;
                })
                ->removeColumn('id');

            return $expense->rawColumns(['amount', 'payment_for'])
                ->make(true);
        }

        return view('report.revenue_report');
    }

    public function debtRevenueReport() {
        if (!auth()->user()->can('report.revenue_date')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            if(!empty(request()->date)){
                $date = request()->date;
            }else{
                $date = Carbon::today()->toDateString();
            }

            $query = Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->whereDate('transaction_date', '=', $date)
                ->select(
                    '*',
                    DB::raw("SUM(final_total) as sum_final"),
                    DB::raw("SUM((SELECT SUM(tp.amount) FROM transaction_payments as tp WHERE tp.transaction_id=transactions.id AND approval_status='approved')) as total_paid")
                )
                ->groupBy('contact_id');


            if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $query->where('contact_id', $customer_id);
            }

            $transactions = DataTables::of($query)
                ->editColumn('contact_id', function ($row) {
                    $contact = Contact::find($row->contact_id);
                    return $contact ? '<a href="' . action("ContactController@show", [$contact->id]) . '" target="_blank">' . $contact->name . '</a>' : '--';
//                    $contact = Contact::find($row->contact_id);
//                    return $contact ? $contact->name : '--';
                })
                ->addColumn('total_due_customer', function ($row) {
                    $remain = $row->sum_final - $row->total_paid;
                    if ($remain > 0) {
                        $html = '<span class="display_currency total_due_customer" data-currency_symbol="true" data-orig-value="'. abs($remain) .'">'. abs($remain) .'</span>';
                    } else {
                        $html = '<span class="display_currency total_due_customer" data-currency_symbol="true"></span>';
                    }

                    return $html;
                })
                ->addColumn('total_due_business', function ($row) {
                    $remain = $row->sum_final - $row->total_paid;
                    if ($remain < 0) {
                        $html = '<span class="display_currency total_due_business" data-currency_symbol="true" data-orig-value="'. abs($remain) .'">'. abs($remain) .'</span>';
                    } else {
                        $html = '<span class="display_currency total_due_business" data-currency_symbol="true"></span>';
                    }

                    return $html;
                })
                /*->orderColumn('total_due_business', function ($query, $order) {
                    $query->orderByRaw("(sum_final - total_paid) ".$order);
                })*/
                ->removeColumn('id');

            return $transactions->rawColumns(['total_due_customer', 'total_due_business', 'contact_id'])
                ->make(true);
        }

        return view('report.revenue_report');
    }

    public function reportingDate() {
        if (!auth()->user()->can('report.reporting_date')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $is_woocommerce = $this->moduleUtil->isModuleInstalled('Woocommerce');
        $is_tables_enabled = $this->transactionUtil->isModuleEnabled('tables');
        $is_service_staff_enabled = $this->transactionUtil->isModuleEnabled('service_staff');
        $is_types_service_enabled = $this->moduleUtil->isModuleEnabled('types_of_service');

        if (request()->ajax()) {
            $with = [];
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

            $sells = $sells->orderByDesc('transactions.created_at');

            $datatable = DataTables::of($sells)
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

                        if (!$only_shipments) {
                            if ($row->payment_status != "paid" && (auth()->user()->can("sell.create") || auth()->user()->can("direct_sell.access")) && auth()->user()->can("sell.payments")) {
                                $html .= '<li><a href="' . action('TransactionPaymentController@addPayment', [$row->id]) . '" class="add_payment_modal"><i class="fas fa-money-bill-alt"></i> ' . __("purchase.add_payment") . '</a></li>';
                            }

                            $html .= '<li><a href="' . action('TransactionPaymentController@show', [$row->id]) . '" class="view_payment_modal"><i class="fas fa-money-bill-alt"></i> ' . __("purchase.view_payments") . '</a></li>';
                            $html .= '<li class="divider"></li>';
                        }

                        if (auth()->user()->can("sell.view") || auth()->user()->can("direct_sell.access") || auth()->user()->can("view_own_sell_only")) {
                            $html .= '<li><a href="#" data-href="' . action("SellController@show", [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fas fa-eye" aria-hidden="true"></i> ' . __("messages.view") . '</a></li>';
                        }
                        if (auth()->user()->can("sell.view") || auth()->user()->can("direct_sell.access")) {
                            $html .= '<li><a href="#" class="print-invoice" data-href="' . route('sell.printInvoice', [$row->id]) . '"><i class="fas fa-print" aria-hidden="true"></i> ' . __("messages.print") . '</a></li>';
                        }
                        if (!$only_shipments) {
                            if (auth()->user()->can("sell.create")) {
                                $html .= '<li><a href="' . action('SellPosController@showInvoiceUrl', [$row->id]) . '" class="view_invoice_url"><i class="fas fa-eye"></i> ' . __("lang_v1.view_invoice_url") . '</a></li>';
                            }

                            $html .= '<li><a href="#" data-href="' . action('NotificationController@getTemplate', ["transaction_id" => $row->id,"template_for" => "new_sale"]) . '" class="btn-modal" data-container=".view_modal"><i class="fa fa-envelope" aria-hidden="true"></i>' . __("lang_v1.new_sale_notification") . '</a></li>';
                        }

                        $html .= '</ul></div>';

                        return $html;
                    }
                )
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
                    'deposit',
                    function ($row) {
                        if($row->deposit > 0){
                            $icon_class = $row->deposit_approved ? 'fas fa-check' : 'fas fa-times';
                            $tag_open = $row->deposit_approved ? '' : '<button type="button" class="approve_payment btn btn-xs btn-danger"
                                data-href="javascript:void(0)"';
                            $tag_close = $row->deposit_approved ? '' : '</button>';
                            $html = $tag_open .'<i class="'.$icon_class.'"></i> <span class="display_currency total_deposit total_before_tax" data-currency_symbol="true" data-orig-value="'. $row->deposit .'">'. $row->deposit .'</span>'. $tag_close;
                        }else{
                            $html = '<span class="display_currency total_before_tax" data-currency_symbol="true">0</span>';
                        }
                        return $html;
                    }
                )
                ->editColumn(
                    'cod',
                    function ($row) {
                        if($row->cod > 0){
                            $icon_class = $row->cod_approved ? 'fas fa-check' : 'fas fa-times';
                            $tag_open = $row->cod_approved ? '' : '<button type="button" class="approve_payment btn btn-xs btn-danger"
                                data-href="javascript:void(0)">';
                            $tag_close = $row->cod_approved ? '' : '</button>';
                            $html = $tag_open .'<i class="'.$icon_class.'"></i> <span class="display_currency total_cod total_before_tax" data-currency_symbol="true" data-orig-value="'. $row->cod .'">'. $row->cod .'</span>'. $tag_close;
                        }else{
                            $html = '<span class="display_currency total_before_tax" data-currency_symbol="true">0</span>';
                        }
                        return $html;
                    }
                )
                ->addColumn('total_remaining', function ($row) {
                    $total_remaining =  $row->final_total - $row->total_paid;
                    $icon_class = $total_remaining <= 0 ? 'fas fa-check' : 'fas fa-times';
                    $tag_open = $total_remaining <= 0 ? '' : '<a type="button" class="add_remaining_payment btn btn-xs btn-danger"
                                href="javascript:void(0)">';
                    $tag_close = $total_remaining <= 0 ? '' : '</a>';
                    $tag_content = '<i class="'.$icon_class.'"></i> <span class="display_currency total_remaining total_before_tax" data-currency_symbol="true" data-orig-value="'. $total_remaining .'">'. $total_remaining .'</span>';

                    if($total_remaining > 0){
                        $html = $tag_open . $tag_content . $tag_close;
                    }else{
                        $html = $tag_content;
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

                    return $invoice_no;
                });

            $rawColumns = ['approved_by_cashier', 'final_total', 'cod', 'deposit', 'total_paid', 'total_remaining', 'payment_status', 'invoice_no', 'discount_amount', 'tax_amount', 'total_before_tax', 'shipping_status', 'types_of_service_name', 'payment_methods', 'return_due'];

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

        $payment_types = $this->transactionUtil->payment_types();

        $total_money_payment = TransactionPayment::whereDate('paid_on', '=', Carbon::today()->toDateString())
            ->where('is_approved', 1)
            ->select('amount', 'method', 'bank_account_number')
            ->get()
            ->toArray();

        $total_money_cod = TransactionPayment::where('type', 'cod')
            ->whereDate('paid_on', '=', Carbon::today()->toDateString())
            ->where('is_approved', 1)
            ->select('amount', 'method', 'bank_account_number')
            ->get()
            ->toArray();

        return view('report.reporting_date')
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
                'payment_types',
                'total_money_payment',
                'total_money_cod'
            ));
    }

    public function transferReport() {
        if (!auth()->user()->can('report.transfer')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $is_service_staff_enabled = $this->transactionUtil->isModuleEnabled('service_staff');
        $shipping_statuses = $this->transactionUtil->shipping_statuses();

        if (request()->ajax()) {
            $sells = $this->transactionUtil->queryTransfer($business_id);
            $sells->whereNotNull('transactions.shipping_status');

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $sells->whereIn('transactions.location_id', $permitted_locations);
            }

            //Add condition for location,used in sales representative expense report
            if (request()->has('location_id')) {
                $location_id = request()->get('location_id');
                if (!empty($location_id)) {
                    $sells->where('transactions.location_id', $location_id);
                }
            }

            if (!empty(request()->input('service_staffs'))) {
                $transactionIds = TransactionShip::query()->where('ship_id', request()->input('service_staffs'))->pluck('transaction_id')->toArray();
                $sells->whereIn('transactions.id', $transactionIds);
//                $sells->where('ss.id', request()->input('service_staffs'));
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

            if (!empty(request()->input('shipping_status'))) {
                $sells->where('transactions.shipping_status', request()->input('shipping_status'));
            }

            $sells->groupBy('transactions.id');

            $datatable = Datatables::of($sells)
                ->removeColumn('id')
                ->editColumn('name', function ($row) {
                    return '<a href="' . action("ContactController@show", [$row->customer_id]) . '" target="_blank">' . $row->name . '</a>';
                })
                ->editColumn(
                    'final_total',
                    '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="{{$final_total}}">{{$final_total}}</span>'
                )
                ->editColumn(
                    'shipping_charges',
                    '<span class="display_currency total-shipping-charge" data-currency_symbol="true" data-orig-value="{{$shipping_charges}}">{{$shipping_charges}}</span>'
                )
                ->editColumn('shipping_status', function ($row) {
                    $shipping_statuses = $this->transactionUtil->shipping_statuses();
                    $status_color = !empty($this->shipping_status_colors[$row->shipping_status]) ? $this->shipping_status_colors[$row->shipping_status] : 'bg-gray';
                    $status = !empty($row->shipping_status) ? '<a href="javascript:void(0)"><span class="label ' . $status_color .'">' . $shipping_statuses[$row->shipping_status] . '</span></a>' : '';

                    return $status;
                })
                ->editColumn(
                    'cod',
                    function ($row) {
                        if($row->cod > 0){
                            $icon_class = $row->cod_approved ? 'fas fa-check' : 'fas fa-times';
                            $tag_open = $row->cod_approved ? '' : '<button type="button" class="approve_payment btn btn-xs btn-danger"
                                data-href="javascript:void(0)">';
                            $tag_close = $row->cod_approved ? '' : '</button>';
                            $html = $tag_open .'<i class="'.$icon_class.'"></i> <span class="display_currency total_cod total_before_tax" data-currency_symbol="true" data-orig-value="'. $row->cod .'">'. $row->cod .'</span>'. $tag_close;
                        }else{
                            $html = '<span class="display_currency total_before_tax" data-currency_symbol="true">0</span>';
                        }
                        return $html;
                    }
                );

            $rawColumns = ['final_total', 'cod', 'shipping_charges', 'shipping_status', 'name'];

            return $datatable->rawColumns($rawColumns)
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id, false);
        /*$customers = Contact::customersDropdown($business_id, false);*/

        //Service staff filter
        $service_staffs = null;
        if ($this->productUtil->isModuleEnabled('service_staff')) {
            $service_staffs = $this->productUtil->serviceStaffDropdown($business_id);
        }

        $customers = Contact::customersDropdown($business_id, false);

        return view('report.transfer_report')
            ->with(compact('business_locations',
                'customers',
                'service_staffs',
                'is_service_staff_enabled',
                'customers',
                'shipping_statuses'
            ));
    }

    public function reportOwnerTarget(){
        $business_id = request()->session()->get('user.business_id');
        $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);

        $categories = Category::forDropdown($business_id);
        $types = $this->util->target_types();
        $types = array_merge(['' => __('lang_v1.none')], $types);
        $type_default = 'revenue';

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

            if (!empty(request()->type)) {
                $type = request()->type;
                $targets->where('targets.type', $type);
            }

            return \Yajra\DataTables\Facades\DataTables::of($targets)
                ->removeColumn('id')
                ->editColumn('start_date', '{{@format_date($start_date)}}')
                ->editColumn('end_date', '{{@format_date($end_date)}}')
                ->editColumn('type', '{{__(\'target.type_\' . $type)}}')
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
                                    $variable = " ({$target_sale_line->variation->product_variation->name} : {$target_sale_line->variation->name})";
                                }

                                $target_total_html .= '<b>'. ($target_sale_line->product->unit->type == Unit::PCS ? number_format($target_sale_line->quantity) : number_format($target_sale_line->quantity, 2)) .' ' . $unit . ' </b>' . $target_sale_line->product->name . $variable . '<br>';
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
                ->addColumn('percent_complete', function ($row) use ($business_id) {
                    $target_total_html = '';
                    switch ($row->type){
                        case 'amount':
                            $revenue = Transaction::query()
                                ->whereBetween('transaction_date', [$row->start_date, $row->end_date . " 23:59:59"])
                                ->where('type', 'sell')
                                ->where('business_id', $business_id)
                                ->where('status', 'final')
                                ->select(\Illuminate\Support\Facades\DB::raw('SUM(final_total) AS total_revenue'))
                                ->first();

                            if (!empty($revenue) && !empty($row->amount)){
                                return number_format($revenue->total_revenue / $row->amount * 100, 2) . '%';
                            } else {
                                return 0;
                            }
                            break;
                        case 'product':
                            $variationIds = $row->target_sale_lines->map(function ($item) {
                                return $item->variation_id;
                            });

                            $product = Transaction::query()
                                ->leftJoin('transaction_plate_lines AS TPL', 'TPL.transaction_id', '=', 'transactions.id')
                                ->whereBetween('TPL.created_at', [$row->start_date, $row->end_date  . " 23:59:59"])
                                ->where('type', 'sell')
                                ->where('business_id', $business_id)
                                ->where('status', 'final')
                                ->whereIn('TPL.variation_id', $variationIds)
                                ->select(DB::raw('SUM(TPL.total_quantity) AS total_quantity, variation_id'))
                                ->groupBy('TPL.variation_id')
                                ->pluck('total_quantity', 'variation_id')
                                ->toArray();

                            foreach ($row->target_sale_lines as $target_sale_line){
                                $variable = '';
                                $quantitySellProduct = empty($product[$target_sale_line->variation_id]) ? 0 : $product[$target_sale_line->variation_id];

                                if ( $target_sale_line->product->type == 'variable' ) {
                                    $variable = " ({$target_sale_line->variation->product_variation->name} - {$target_sale_line->variation->name})";
                                }
                                if (!empty($target_sale_line->quantity)) {
                                    $percent = number_format($quantitySellProduct / $target_sale_line->quantity * 100, 2);
                                } else {
                                    $percent = 0;
                                }

                                $target_total_html .= $percent . ' % ' . $target_sale_line->product->name . $variable . '<br>';
                            }
                            return $target_total_html;
                            break;
                        case 'category':
                            $listCate = $row->target_category_lines->map(function ($item) {
                                return $item->category_id;
                            })->toArray();

                            $productCate = Product::query()->whereIn('category_id', $listCate)->pluck( 'id')->toArray();

                            $listSubCate = $row->target_category_lines->map(function ($item) {
                                return $item->sub_category_id;
                            })->toArray();

                            $productSubCate = Product::query()->whereIn('sub_category_id', $listSubCate)->pluck( 'id')->toArray();
                            $quantitySubCate = Transaction::query()
                                ->leftJoin('transaction_plate_lines AS TPL', 'TPL.transaction_id', '=', 'transactions.id')
                                ->leftJoin('products', 'products.id', '=', 'TPL.product_id')
                                ->whereBetween('TPL.created_at', [$row->start_date, $row->end_date  . " 23:59:59"])
                                ->where('transactions.type', 'sell')
                                ->where('transactions.business_id', $business_id)
                                ->where('transactions.status', 'final')
                                ->whereIn('TPL.product_id', $productSubCate)
                                ->select(DB::raw('SUM(TPL.total_quantity) AS total_quantity, products.sub_category_id'))
                                ->groupBy('products.sub_category_id')
                                ->pluck('total_quantity', 'products.sub_category_id')
                                ->toArray();

//                            DB::connection()->enableQueryLog();
                            $quantityCate = Transaction::query()
                                ->leftJoin('transaction_plate_lines AS TPL', 'TPL.transaction_id', '=', 'transactions.id')
                                ->leftJoin('products', 'products.id', '=', 'TPL.product_id')
                                ->whereBetween('TPL.created_at', [$row->start_date, $row->end_date  . " 23:59:59"])
                                ->where('transactions.type', 'sell')
                                ->where('transactions.business_id', $business_id)
                                ->where('transactions.status', 'final')
                                ->whereIn('TPL.product_id', $productCate)
                                ->whereRaw("NOT EXISTS (SELECT 1 FROM units WHERE units.id = products.unit_id AND units.type = 'pcs')")
                                ->select(DB::raw('SUM(TPL.total_quantity) AS total_quantity, products.category_id'))
                                ->groupBy('products.category_id')
                                ->pluck('total_quantity', 'products.category_id')
                                ->toArray();
//                            var_dump(DB::connection()->getQueryLog());
//                            die();

                            foreach ($row->target_category_lines as $target_category_line){
                                /*if ($target_category_line->sub_category_id && !empty($target_category_line->quantity)){
                                    $realQuantity = empty($quantitySubCate) ? 0 : $quantitySubCate[$target_category_line->sub_category_id];
                                    $target_total_html .= number_format($realQuantity / $target_category_line->quantity * 100, 2) . ' % ' . $target_category_line->category->name.' <b>>></b> '.$target_category_line->sub_category->name.'<br>';
                                }elseif (!empty($target_category_line->quantity)){
                                    $realQuantity = empty($quantityCate) ? 0 : $quantityCate[$target_category_line->category_id];
                                    $target_total_html .= number_format($realQuantity / $target_category_line->quantity * 100, 2) . ' % ' . $target_category_line->category->name.'<br>';
                                }*/
                                if (!empty($target_category_line->quantity)){
                                    $realQuantity = empty($quantityCate) ? 0 : $quantityCate[$target_category_line->category_id];
                                    $target_total_html .= number_format($realQuantity / $target_category_line->quantity * 100, 2) . ' % ' . $target_category_line->category->name.'<br>';
                                }
                            }
                            break;
                        case 'profit':
                            $sell = Transaction::query()
                                ->whereBetween('transaction_date', [$row->start_date, $row->end_date  . " 23:59:59"])
                                ->where('type', 'sell')
                                ->where('business_id', $business_id)
                                ->where('status', 'final')
                                ->select(DB::raw('SUM(final_total) AS total_sell'))
                                ->first();

                            $purchase = Transaction::query()
                                ->whereBetween('transaction_date', [$row->start_date, $row->end_date  . " 23:59:59"])
                                ->where('type', 'purchase')
                                ->where('business_id', $business_id)
                                ->where('status', 'received')
//                                ->where('shipping_status', 'delivered')
                                ->select(DB::raw('SUM(final_total) AS total_purchase'))
                                ->first();

                            $importStock = Transaction::query()
                                ->whereBetween('transaction_date', [$row->start_date, $row->end_date  . " 23:59:59"])
                                ->where('type', 'opening_stock')
                                ->where('status', 'received')
                                ->where('business_id', $business_id)
                                ->select(DB::raw('SUM(final_total) AS total_import'))
                                ->first();

                            $receipt = TransactionPayment::query()
                                ->whereBetween('created_at', [$row->start_date, $row->end_date . " 23:59:59"])
                                ->whereIn('type', ['receipt'])
                                ->where('approval_status', '<>', 'reject')
                                ->where('business_id', $business_id)
                                ->select(DB::raw('SUM(amount) AS total_receipt'))
                                ->first();

                            $expense = TransactionPayment::query()
                                ->whereBetween('created_at', [$row->start_date, $row->end_date . " 23:59:59"])
                                ->whereIn('type', ['return', 'expense'])
                                ->where('approval_status', '<>', 'reject')
                                ->where('business_id', $business_id)
                                ->select(DB::raw('SUM(amount) AS total_sell_expense'))
                                ->first();

                            $expenseAdjustment = Transaction::query()
                                ->whereBetween('transaction_date', [$row->start_date, $row->end_date  . " 23:59:59"])
                                ->where('type', 'stock_adjustment')
                                ->where('status', 'received')
                                ->where('business_id', $business_id)
                                ->select(DB::raw('SUM(total_amount_recovered) AS total_adjust'))
                                ->first();

                            $expenseTransfer = Transaction::query()
                                ->whereBetween('transaction_date', [$row->start_date, $row->end_date  . " 23:59:59"])
                                ->where('type', 'sell_transfer')
                                ->where('status', 'final')
                                ->where('business_id', $business_id)
                                ->select(DB::raw('SUM(shipping_charges) AS total_transfer'))
                                ->first();

                            $sellRevenue     = empty($sell) ? 0 : $sell->total_sell;
                            $purchaseExpense = empty($purchase) ? 0 : $purchase->total_purchase;
                            $importExpense   = empty($importStock) ? 0 : $importStock->total_import;
                            $sellExpense     = empty($expense) ? 0 : $expense->total_sell_expense;
                            $totalReceipt    = empty($receipt) ? 0 : $receipt->total_receipt;
                            $totalAdjust     = empty($expenseAdjustment) ? 0 : $expenseAdjustment->total_adjust;
                            $totalTransfer   = empty($expenseTransfer) ? 0 : $expenseTransfer->total_transfer;
                            // Plus expense because amount of expense save by negative digit in DB
                            $profit = $sellRevenue + $totalReceipt + $totalAdjust - $purchaseExpense - $importExpense + $sellExpense - $totalTransfer;

                            if ($profit > 0 && !empty($row->profit)) {
                                return number_format($profit / $row->profit * 100, 2) . '%';
                            } else {
                                return 0;
                            }
                            break;
                    }
                    return $target_total_html;
                })
                ->rawColumns(['action', 'target_total', 'percent_complete'])
                ->make(true);
        }

        return view('report.report_owner_target')
            ->with(compact('currency_details', 'categories', 'types', 'type_default'));
    }

    public function revenueByMonthReport() {
        if (!auth()->user()->can('report.revenue_by_month')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $query = Contact::join('transactions as t', 'contacts.id', '=', 't.contact_id')
                ->with('customer_group')
                ->where('contacts.business_id', $business_id)
                ->whereIn('t.type', ['sell', 'sell_return'])
                ->where('t.status', 'final')
                ->select(
                    'contacts.id as customer_id',
                    'contacts.name',
                    'contacts.contact_id',
                    DB::raw("IF(contacts.customer_group_id IS NOT NULL, (SELECT customer_groups.name FROM customer_groups WHERE customer_groups.id = contacts.customer_group_id LIMIT 1), '') AS customer_group_name"),
                    'contacts.customer_group_id',
                    DB::raw("SUM(IF(t.type = 'sell_return', final_total * -1, final_total)) as total_sell")
                );


            if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $query->where('contacts.id', $customer_id);
            }

            if (!empty(request()->customer_group_id)) {
                $customer_group_id = request()->customer_group_id;
                $query->where('contacts.customer_group_id', $customer_group_id);
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $query->whereDate('t.transaction_date', '>=', $start)
                    ->whereDate('t.transaction_date', '<=', $end);
            } else {
                $query->whereMonth('t.transaction_date', Carbon::now()->month);
            }

            $query->groupBy('t.contact_id');

            $datatable = Datatables::of($query)
                ->removeColumn('id')
                ->editColumn('name', function ($row) {
                    return '<a href="' . action("ContactController@show", [$row->customer_id]) . '" target="_blank">' . $row->name . '</a>';
                })
                ->editColumn(
                    'total_sell',
                    '<span class="display_currency total_sell" data-currency_symbol="true" data-orig-value="{{$total_sell}}">{{$total_sell}}</span>'
                )
                ->filterColumn('customer_group_name', function ($query, $keyword) {
                    $query->whereRaw("IF(contacts.customer_group_id IS NOT NULL, (SELECT customer_groups.name FROM customer_groups WHERE customer_groups.id = contacts.customer_group_id LIMIT 1), '') like ?", ["%{$keyword}%"]);
                });

            $rawColumns = ['total_sell', 'name'];

            return $datatable->rawColumns($rawColumns)
                ->make(true);
        }

        $customers = Contact::customersDropdown($business_id, false);
        $customer_groups = CustomerGroup::forDropdown($business_id, false, true);

        return view('report.revenue_by_month_report')
            ->with(compact('customers', 'customer_groups'));
    }

    public function reportExportImport(Request $request){
        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {

            $startDate = empty(request()->get('start_date')) ? date('Y-01-01') : request()->get('start_date');
            $endDate   = empty(request()->get('end_date')) ? date('Y-m-d') : request()->get('end_date');

            $dateTimeQuery          = " transactions.transaction_date BETWEEN '$startDate' AND '$endDate 23:59:59'";
            $compareStartTime       = " transactions.transaction_date <= '$startDate'";
            $comparePlateStartTime  = " tpl.created_at <= '$startDate'";
            $compareEndTime         = " transactions.transaction_date <= '$endDate 23:59:59'";
            $comparePlateEndTime    = " tpl.created_at <= '$endDate 23:59:59'";
            $plate_time_filter      = " tpl.created_at BETWEEN '$startDate' AND '$endDate 23:59:59'";

            $query = $this->transactionUtil->queryReportExportImport($compareStartTime, $comparePlateStartTime, $dateTimeQuery, $comparePlateEndTime, $plate_time_filter, $compareEndTime,$request->input('category_id'));

            $datatable = Datatables::of($query)
                ->editColumn('product', function ($row) {
                    return empty($row->purchase_product) ? $row->product : $row->purchase_product;
                })
                ->editColumn('sku', function ($row) {
                    return empty($row->sku) ? $row->purchase_sku : $row->sku;
                })
                ->editColumn('begin_quantity', function ($row) {
                    $beginStock = 0;
                    if (!empty($row->begin_quantity)) {
                        $beginStock =  (float)$row->begin_quantity - (float)$row->export_begin_quantity;
                    }
                    $unit = empty($row->unit) ? 'm2' : $row->unit;
                    $beginStock = empty($beginStock) ? 0 : number_format($beginStock, 2, '.', ',');

                    return '<span data-orig-value="' . $beginStock . '" data-unit="' . $unit . '" >' . $beginStock . '</span> ' . $unit;
                })
                ->editColumn('import_quantity', function ($row) {
                    $importStock = 0;
                    if (!empty($row->import_quantity)) {
                        $importStock =  (float)$row->import_quantity;
                    }
                    $unit = empty($row->unit) ? 'm2' : $row->unit;
                    $importStock = empty($importStock) ? 0 : number_format($importStock, 2, '.', ',');

                    return '<span data-orig-value="' . $importStock . '" data-unit="' . $unit . '" >' . $importStock . '</span> ' . $unit;
                })
                ->editColumn('export_quantity', function ($row) {
                    $exportStock = 0;
                    if (!empty($row->export_quantity)) {
                        $exportStock =  (float)$row->export_quantity;
                    }
                    $unit = empty($row->unit) ? 'm2' : $row->unit;
                    $exportStock = empty($exportStock) ? 0 : number_format($exportStock, 2, '.', ',');

                    return '<span data-orig-value="' . $exportStock . '" data-unit="' . $unit . '" >' . $exportStock . '</span> ' . $unit;
                })
                ->addColumn('end_stock_quantity', function ($row) {
                    $beginStock = 0;
                    if (!empty($row->begin_quantity)) {
                        $beginStock =  (float)$row->begin_quantity - (float)$row->export_begin_quantity;
                    }
                    $beginStock = empty($beginStock) ? 0 : $beginStock;

                    $importStock = 0;
                    if (!empty($row->import_quantity)) {
                        $importStock =  (float)$row->import_quantity;
                    }
                    $importStock = empty($importStock) ? 0 : $importStock;

                    $exportStock = 0;
                    if (!empty($row->export_quantity)) {
                        $exportStock =  (float)$row->export_quantity;
                    }
                    $exportStock = empty($exportStock) ? 0 : $exportStock;

                    $endStockQuantity = number_format($beginStock + $importStock - $exportStock,2, '.', ',');
                    $unit = empty($row->unit) ? 'm2' : $row->unit;

                    return '<span data-orig-value="' . $endStockQuantity . '" data-unit="' . $unit . '" >' . $endStockQuantity . '</span> ' . $unit;
                })
                /*->editColumn('end_stock_quantity', function ($row) {
                    $endStockQuantity = 0;
                    if (!empty($row->end_stock_quantity)) {
                        $endStockQuantity =  (float)$row->end_stock_quantity  - (float)$row->end_export_quantity;
                    }
                    $unit = empty($row->unit) ? 'm2' : $row->unit;
                    $endStockQuantity = empty($exportStock) ? 0 : number_format($endStockQuantity, 2, '.', ',');

                    return '<span data-orig-value="' . $endStockQuantity . '" data-unit="' . $unit . '" >' . $endStockQuantity . '</span> ' . $unit;
                })*/
                ->editColumn('width', function ($row) {
                    $html = '';
                    if($row->unit_type != 'pcs'){
                        $html = empty($row->width) ? $row->purchase_width : $row->width;
                        $html .= ' m';
                    }
                    return $html;
                })
                ->editColumn('height', function ($row) {
                    $html = '';
                    if($row->unit_type != 'pcs'){
                        $html = empty($row->height) ? $row->purchase_height : $row->height;
                        $html .= ' m';
                    }
                    return $html;
                })
                ->removeColumn('enable_stock')
                ->removeColumn('unit')
                ->removeColumn('id');

            $raw_columns  = ['unit_price', 'total_transfered', 'total_sold', 'total_adjusted', 'stock', 'stock_price',
                'begin_quantity', 'begin_price', 'import_quantity', 'import_price', 'export_quantity', 'export_price',
                'end_stock_quantity', 'end_stock_price'];

            return $datatable->rawColumns($raw_columns)->make(true);
        }

        $categories = Category::forDropdown($business_id, 'product');
        $brands = Brands::where('business_id', $business_id)
            ->pluck('name', 'id');
        $units = Unit::where('business_id', $business_id)
            ->pluck('short_name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('report.import_export_report')
            ->with(compact('categories', 'brands', 'units', 'business_locations'));
    }

    public function totalSales(){
        if (!auth()->user()->can('report.revenue_by_month')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $query = Transaction::query()
                ->join('users as u', 'u.id', '=', 'transactions.created_by')
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->select(
                    DB::raw("CONCAT(u.first_name, ' ', u.last_name) AS full_name"),
//                    'contacts.customer_group_id',
                    DB::raw("SUM(final_total) as total_sell")
                );

            if (!empty(request()->user_id)) {
                $userId = request()->user_id;
                $query->where('u.id', $userId);
            }

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $query->whereDate('transactions.transaction_date', '>=', $start)
                    ->whereDate('transactions.transaction_date', '<=', $end);
            } else {
                $query->whereMonth('transactions.transaction_date', Carbon::now()->month);
            }

            $query->groupBy('transactions.created_by');

            $datatable = Datatables::of($query)
                ->removeColumn('id')
                ->editColumn(
                    'total_sell',
                    '<span class="display_currency total_sell" data-currency_symbol="true" data-orig-value="{{$total_sell}}">{{number_format($total_sell)}}</span>'
                )
                ->filterColumn('full_name', function ($query, $keyword) {
                    $query->whereRaw("CONCAT(u.first_name, ' ', u.last_name) like ?", ["%{$keyword}%"]);
                });

            $rawColumns = ['total_sell'];

            return $datatable->rawColumns($rawColumns)
                ->make(true);
        }

        $users = User::forDropdownSell($business_id, true);
        return view('report.total_sale')->with(compact('users'));
    }

    public function getStockHistory($plate_stock_id, Request $request)
    {
        if (!$request->ajax()) {
            return [
                'success' => false,
                'message' => __('messages.permission_denied')
            ];
        }

        $plate_stock = PlateStock::with([
                'product',
                'product.unit',
                'variation',
                'warehouse',
            ])
            ->find($plate_stock_id);

        $purchase_transactions = Transaction::leftjoin('purchase_lines', 'transactions.id', '=', 'purchase_lines.transaction_id')
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->where('transactions.type', 'purchase')
            ->where('purchase_lines.variation_id', $plate_stock->variation_id)
            ->where('purchase_lines.warehouse_id', $plate_stock->warehouse_id)
            ->where('purchase_lines.width', $plate_stock->width)
            ->where('purchase_lines.height', $plate_stock->height)
            ->where('purchase_lines.is_origin', $plate_stock->is_origin)
            ->select([
                'transactions.id',
                'transactions.transaction_date',
                'transactions.ref_no',
                'contacts.name as contact_name',
                'purchase_lines.width',
                'purchase_lines.height',
                'purchase_lines.quantity_line',
            ])
            ->get()
            ->toArray();

        $import_stocks = Transaction::leftjoin('purchase_lines', 'transactions.id', '=', 'purchase_lines.transaction_id')
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->where('transactions.type', 'opening_stock')
            ->where('purchase_lines.variation_id', $plate_stock->variation_id)
            ->where('purchase_lines.warehouse_id', $plate_stock->warehouse_id)
            ->where('purchase_lines.width', $plate_stock->width)
            ->where('purchase_lines.height', $plate_stock->height)
            ->where('purchase_lines.is_origin', $plate_stock->is_origin)
            ->select([
                'transactions.id',
                'purchase_lines.width',
                'purchase_lines.height',
                'purchase_lines.quantity_line',
                'transactions.transaction_date',
            ])
            ->get()
            ->toArray();

        $sell_transactions = $this->getSellForStockHistory($plate_stock->variation_id, $plate_stock->warehouse_id, $plate_stock->width, $plate_stock->height, $plate_stock->is_origin);
        $sell_transactions = array_reverse($sell_transactions);

        $transfer_transactions = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', '=', 'transaction_sell_lines.transaction_id')
            ->where('transactions.type', 'sell_transfer')
            ->where('transaction_sell_lines.variation_id', $plate_stock->variation_id)
            ->where('transaction_sell_lines.warehouse_id_transfer_to', $plate_stock->warehouse_id)
            ->where('transaction_sell_lines.width', $plate_stock->width)
            ->where('transaction_sell_lines.height', $plate_stock->height)
            ->select([
                'transactions.id',
                'transactions.transaction_date',
                'transactions.ref_no',
                'transaction_sell_lines.quantity_line',
                DB::raw('(SELECT warehouses.name FROM warehouses WHERE warehouses.id = transaction_sell_lines.warehouse_id_transfer_from) as warehouse_transfer_from'),
            ])
            ->get()
            ->toArray();

        $adjustment_transactions = Transaction::leftjoin('stock_adjustment_lines', 'transactions.id', '=', 'stock_adjustment_lines.transaction_id')
            ->where('transactions.type', 'stock_adjustment')
            ->where('stock_adjustment_lines.variation_id', $plate_stock->variation_id)
            ->where('stock_adjustment_lines.warehouse_id', $plate_stock->warehouse_id)
            ->where('stock_adjustment_lines.width', $plate_stock->width)
            ->where('stock_adjustment_lines.height', $plate_stock->height)
            ->select([
                'transactions.id',
                'transactions.transaction_date',
                'transactions.ref_no',
                'stock_adjustment_lines.quantity_line',
                DB::raw('(SELECT warehouses.name FROM warehouses WHERE warehouses.id = stock_adjustment_lines.warehouse_id) as warehouse'),
                DB::raw('(SELECT plate_stocks.width FROM plate_stocks WHERE plate_stocks.id = stock_adjustment_lines.plate_stock_id) as before_width'),
                DB::raw('(SELECT plate_stocks.height FROM plate_stocks WHERE plate_stocks.id = stock_adjustment_lines.plate_stock_id) as before_height'),
            ])
            ->get()
            ->toArray();

        $return_transactions = Transaction::join(
                'transactions as T1',
                'transactions.return_parent_id',
                '=',
                'T1.id'
            )
            ->leftjoin('transaction_plate_lines_return', 'T1.id', '=', 'transaction_plate_lines_return.transaction_id')
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->where('transactions.type', 'sell_return')
            ->where('transaction_plate_lines_return.variation_id', $plate_stock->variation_id)
            ->where('transaction_plate_lines_return.warehouse_id', $plate_stock->warehouse_id)
            ->where('transaction_plate_lines_return.width', $plate_stock->width)
            ->where('transaction_plate_lines_return.height', $plate_stock->height)
            ->select([
                'transactions.id',
                'transactions.transaction_date',
                'transactions.invoice_no',
                'transactions.return_parent_id',
                'contacts.name as contact_name',
                'transaction_plate_lines_return.width',
                'transaction_plate_lines_return.height',
                'transaction_plate_lines_return.quantity',
                DB::raw('(SELECT warehouses.name FROM warehouses WHERE warehouses.id = transaction_plate_lines_return.warehouse_id) as warehouse'),
                DB::raw('(SELECT transaction_plate_lines.width FROM transaction_plate_lines WHERE transaction_plate_lines.id = transaction_plate_lines_return.transaction_plate_line_id LIMIT 1) as width_order'),
                DB::raw('(SELECT transaction_plate_lines.quantity FROM transaction_plate_lines WHERE transaction_plate_lines.id = transaction_plate_lines_return.transaction_plate_line_id LIMIT 1) as quantity_order'),
            ])
            ->get()
            ->toArray();

        $view = view('report.partials.stock_history_modal')
            ->with(compact(
                'plate_stock',
                'purchase_transactions',
                    'import_stocks',
                    'sell_transactions',
                'transfer_transactions',
                'adjustment_transactions',
                'return_transactions'
            ))->render();

        $output = [
            'success' => true,
            'data' => $view,
        ];

        return $output;
    }

    private function getSellForStockHistory($variation_id, $warehouse_id, $width, $height, $is_origin)
    {
        if ($is_origin){
            return [];
        }

        $sell_transactions = Transaction::leftjoin('remaining_plate_lines', 'transactions.id', '=', 'remaining_plate_lines.transaction_id')
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->where('transactions.type', 'sell')
            ->where('remaining_plate_lines.variation_id', $variation_id)
            ->where('remaining_plate_lines.warehouse_id', $warehouse_id)
            ->where('remaining_plate_lines.width', $width)
            ->where('remaining_plate_lines.height', $height)
            ->select([
                'transactions.id',
                'transactions.transaction_date',
                'transactions.invoice_no',
                'contacts.name as contact_name',
                DB::raw('(SELECT transaction_plate_lines.width FROM transaction_plate_lines WHERE transaction_plate_lines.id = remaining_plate_lines.transaction_plate_line_id LIMIT 1) as deliver_width'),
                DB::raw('(SELECT transaction_plate_lines.quantity FROM transaction_plate_lines WHERE transaction_plate_lines.id = remaining_plate_lines.transaction_plate_line_id LIMIT 1) as deliver_quantity'),
                DB::raw('(
                    SELECT 
                        plate_stocks.width 
                    FROM 
                        plate_stocks
                    WHERE 
                        plate_stocks.id = (SELECT transaction_plate_lines.selected_plate_stock_id FROM transaction_plate_lines WHERE transaction_plate_lines.id = remaining_plate_lines.transaction_plate_line_id LIMIT 1) 
                    LIMIT 1
                ) as selected_width'),
                DB::raw('(
                    SELECT 
                        plate_stocks.height 
                    FROM 
                        plate_stocks
                    WHERE 
                        plate_stocks.id = (SELECT transaction_plate_lines.selected_plate_stock_id FROM transaction_plate_lines WHERE transaction_plate_lines.id = remaining_plate_lines.transaction_plate_line_id LIMIT 1) 
                    LIMIT 1
                ) as selected_height'),
                DB::raw('(
                    SELECT 
                        plate_stocks.is_origin 
                    FROM 
                        plate_stocks
                    WHERE 
                        plate_stocks.id = (SELECT transaction_plate_lines.selected_plate_stock_id FROM transaction_plate_lines WHERE transaction_plate_lines.id = remaining_plate_lines.transaction_plate_line_id LIMIT 1) 
                    LIMIT 1
                ) as is_origin'),
                'remaining_plate_lines.width as remaining_width'
            ])
            ->get()
            ->toArray();

        if(empty($sell_transactions)){
            return $sell_transactions;
        }else{
            foreach ($sell_transactions as $sell_transaction){
                return array_merge($sell_transactions, $this->getSellForStockHistory($variation_id, $warehouse_id, $sell_transaction['selected_width'], $sell_transaction['selected_height'], $sell_transaction['is_origin']));
            }
        }
    }
}
