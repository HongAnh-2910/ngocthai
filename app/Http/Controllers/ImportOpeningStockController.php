<?php

namespace App\Http\Controllers;

use App\Business;

use App\BusinessLocation;
use App\PlateStock;
use App\Product;
use App\PurchaseLine;
use App\Transaction;
use App\Unit;
use App\Utils\ProductUtil;
use App\Variation;
use App\Warehouse;
use DB;
use Excel;
use Illuminate\Http\Request;
use App\Imports\OpeningStockImport;
use App\Http\Requests\OpeningStockImportRequest;
use App\Imports\ValidateOpeningStockFile;
use Illuminate\Support\Facades\Log;

class ImportOpeningStockController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $productUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil)
    {
        $this->productUtil = $productUtil;
    }

    /**
     * Display import product screen.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('product.opening_stock')) {
            abort(403, 'Unauthorized action.');
        }

        $zip_loaded = extension_loaded('zip') ? true : false;

        $date_formats = Business::date_formats();
        $date_format = session('business.date_format');
        $date_format = isset($date_formats[$date_format]) ? $date_formats[$date_format] : $date_format;

        //Check if zip extension it loaded or not.
        if ($zip_loaded === false) {
            $notification = ['success' => 0,
                'msg' => 'Please install/enable PHP Zip archive for import'
            ];

            return view('import_opening_stock.index')
                ->with(compact('notification', 'date_format'));
        } else {
            return view('import_opening_stock.index')
                ->with(compact('date_format'));
        }
    }

    /**
     * Imports the uploaded file to database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(OpeningStockImportRequest $request)
    {
        if (!auth()->user()->can('product.opening_stock')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $notAllowed = $this->productUtil->notAllowedInDemo();
            if (!empty($notAllowed)) {
                return $notAllowed;
            }

            //Set maximum php execution time
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);

            if ($request->hasFile('products_csv')) {
                $file = $request->file('products_csv');

                $parsed_array = Excel::toArray([], $file);

                //Remove header row
                $imported_data = array_splice($parsed_array[0], 1);

                $business_id = $request->session()->get('user.business_id');
                $user_id = $request->session()->get('user.id');
                $imported_data = array_filter($imported_data, 'array_filter');

                $is_valid = true;
                $error_msg = '';

                DB::beginTransaction();
                foreach ($imported_data as $key => $row) {
                    $product_info = Variation::where('sub_sku', trim($row[0]))
                        ->join('products AS P', 'variations.product_id', '=', 'P.id')
                        ->join('units', 'P.unit_id', '=', 'units.id')
                        ->leftjoin('tax_rates AS TR', 'P.tax', 'TR.id')
                        ->where('P.business_id', $business_id)
                        ->select(['P.id',
                            'variations.id as variation_id',
                            'P.enable_stock',
                            'TR.amount as tax_percent',
                            'TR.id as tax_id',
                            'units.actual_name as unit_name',
                            'units.type as unit_type'
                        ])->first();

                    if (empty($product_info)) {
                        $error_msg = 'Không tồn tại mã sản phẩm ở dòng ' . ($key + 2);
                        throw new \Exception($error_msg);
                    }

                    $location = BusinessLocation::where('name', trim($row[4]))
                        ->where('business_id', $business_id)
                        ->first();

                    if (empty($location)) {
                        $error_msg = 'Không tồn tại chi nhánh ở dòng ' . ($key + 2);
                        throw new \Exception($error_msg);
                    }

                    $warehouse = Warehouse::where('name', trim($row[5]))
                        ->where('business_id', $business_id)
                        ->where('location_id', $location->id)
                        ->first();

                    if (empty($warehouse)) {
                        $error_msg = 'Không tồn tại kho chứa hàng ở dòng ' . ($key + 2);
                        throw new \Exception($error_msg);
                    }

                    $opening_stock = [
                        'width' => $product_info->unit_type == Unit::PCS ? 1 : (float) trim($row[1]),
                        'height' => $product_info->unit_type == Unit::PCS ? 1 : (float) trim($row[2]),
                        'quantity' => (int) trim($row[3]),
                        'location_id' => $location->id,
                        'warehouse_id' => $warehouse->id,
                        'is_origin' => (int) trim($row[6]),
                    ];

                    $productUtil = new ProductUtil;
                    $productUtil->addOpeningStock($opening_stock, $product_info, $business_id);
                }

                if (!$is_valid) {
                    throw new \Exception($error_msg);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                'msg' => "Line:" . $e->getLine(). "Message:" . $e->getMessage()
            ];
            return redirect('import-opening-stock')->with('notification', $output);
        }

        $output = ['success' => 1,
            'msg' => __('product.file_imported_successfully')
        ];
        return redirect('import-opening-stock')->with('status', $output);
    }

    /*public function store(OpeningStockImportRequest $request)
    {
//        if (!auth()->user()->can('product.opening_stock')) {
//            abort(403, 'Unauthorized action.');
//        }

        try {
            $notAllowed = $this->productUtil->notAllowedInDemo();
            if (!empty($notAllowed)) {
                return $notAllowed;
            }

            //Set maximum php execution time
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);

            $validator = new ValidateOpeningStockFile();
            Excel::import($validator, $request->file('products_csv'));
            if (count($validator->errors)) {
                $msg = '';

                foreach ($validator->errors as $key => $error) {
                    $msg .= $error."\n";
                }

                $output = [
                    'success' => 0,
                    'msg' => $msg
                ];

                return redirect()->back()->with('notification', $output);
            } elseif (!$validator->isValidFile) {
                return redirect()->back();
            }

            (new OpeningStockImport())->queue($request->file('products_csv'));

            $output = ['success' => 1,
                'msg' => __('product.file_imported_successfully')
            ];
            dd($output);

            return redirect('import-opening-stock')->with('status', $output);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                'msg' => "Line:" . $e->getLine(). "Message:" . $e->getMessage()
            ];
            return redirect('import-opening-stock')->with('notification', $output);
        }
    }*/
}
