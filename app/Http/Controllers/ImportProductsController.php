<?php

namespace App\Http\Controllers;

use App\Brands;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\CustomerGroup;
use App\Product;
use App\ProductVariation;
use App\SellingPriceGroup;
use App\TaxRate;
use App\Transaction;
use App\Unit;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Variation;
use App\VariationGroupPrice;
use App\VariationTemplate;
use App\VariationValueTemplate;

use DB;

use Excel;
use Illuminate\Http\Request;

class ImportProductsController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $productUtil;
    protected $moduleUtil;

    private $barcode_types;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, ModuleUtil $moduleUtil)
    {
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;

        //barcode types
        $this->barcode_types = $this->productUtil->barcode_types();
    }

    /**
     * Display import product screen.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('product.create')) {
            abort(403, 'Unauthorized action.');
        }

        $zip_loaded = extension_loaded('zip') ? true : false;

        //Check if zip extension it loaded or not.
        if ($zip_loaded === false) {
            $output = ['success' => 0,
                            'msg' => 'Please install/enable PHP Zip archive for import'
                        ];

            return view('import_products.index')
                ->with('notification', $output);
        } else {
            return view('import_products.index');
        }
    }

    /**
     * Imports the uploaded file to database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('product.create')) {
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

                $formated_data = [];

                $is_valid = true;
                $error_msg = '';

                $total_rows = count($imported_data);

                //Check if subscribed or not, then check for products quota
                if (!$this->moduleUtil->isSubscribed($business_id)) {
                    return $this->moduleUtil->expiredResponse();
                } elseif (!$this->moduleUtil->isQuotaAvailable('products', $business_id, $total_rows)) {
                    return $this->moduleUtil->quotaExpiredResponse('products', $business_id, action('ImportProductsController@index'));
                }

                $business_locations = BusinessLocation::where('business_id', $business_id)->get();
                DB::beginTransaction();
                foreach ($imported_data as $key => $value) {
                    //Check if any column is missing
                    if (count($value) < 16) {
                        $is_valid =  false;
                        $error_msg = "Một số cột bị thiếu. Vui lòng sử dụng mẫu tệp CSV mới nhất.";
                        break;
                    }

                    $row_no = $key + 2;
                    $product_array = [];
                    $product_array['business_id'] = $business_id;
                    $product_array['created_by'] = $user_id;

                    //Add name
                    $product_name = trim($value[0]);
                    if (!empty($product_name)) {
                        $product_array['name'] = $product_name;
                    } else {
                        $is_valid =  false;
                        $error_msg = "Tên sản phẩm không được trống ở hàng số $row_no";
                        break;
                    }

                    //Add unit
                    $unit_name = trim($value[1]);
                    if (!empty($unit_name)) {
                        $unit = Unit::whereRaw("actual_name LIKE CONCAT(CONVERT(?, BINARY))", [$unit_name])
                            ->first();
                        if ($unit) {
                            $product_array['sub_unit_ids'] = [strval($unit->id)];

                            if ($unit->base_unit_id){
                                $product_array['unit_id'] = $unit->base_unit_id;
                                $product_array['sub_unit_ids'][] = strval($unit->base_unit_id);
                                $product_array['default_sub_unit_id'] = $unit->id;
                            }else{
                                $product_array['unit_id'] = $unit->id;
                                $product_array['default_sub_unit_id'] = null;
                            }
                        } else {
                            $is_valid = false;
                            $error_msg = "Đơn vị tính không được tìm thấy ở hàng số $row_no";
                            break;
                        }
                    } else {
                        $is_valid = false;
                        $error_msg = "Đơn vị tính không được trống ở hàng số $row_no";
                        break;
                    }

                    $brand_name = trim($value[2]);
                    $product_array['brand_id'] = null;
                    if (!empty($value[2])) {
                        $brand = Brands::whereRaw("name LIKE CONCAT(CONVERT(?, BINARY))", [$brand_name])->first();
                        if ($brand) {
                            $product_array['brand_id'] = $brand->id;
                        } else {
                            $is_valid = false;
                            $error_msg = "Thương hiệu không hợp lệ ở hàng số $row_no";
                            break;
                        }
                    }

                    //Add SKU
                    $sku = trim($value[3]);
                    if (!empty($sku)) {
                        $product_array['sku'] = $sku;
                        //Check if product with same SKU already exist
                        $is_exist = Product::where('sku', $product_array['sku'])
                            ->where('business_id', $business_id)
                            ->exists();
                        if ($is_exist) {
                            $is_valid = false;
                            $error_msg = "SKU $sku đã tồn tại ở hàng số $row_no";
                            break;
                        }
                    } else {
                        $product_array['sku'] = '';
                    }

                    //Add barcode type
                    $barcode_type = strtoupper(trim($value[5]));
                    if (empty($barcode_type)) {
                        $product_array['barcode_type'] = 'C128';
                    } elseif (array_key_exists($barcode_type, $this->barcode_types)) {
                        $product_array['barcode_type'] = $barcode_type;
                    } else {
                        $is_valid = false;
                        $error_msg = "Loại mã vạch không hợp lệ ở hàng số $row_no";
                        break;
                    }

                    // Category
                    //Check if category exists else create new
                    /*if (!empty($category_name)) {
                        $category = Category::firstOrCreate(
                            ['business_id' => $business_id, 'name' => $category_name, 'category_type' => 'product'],
                            ['created_by' => $user_id, 'parent_id' => 0]
                        );
                        $product_array['category_id'] = $category->id;
                    }*/
                    $category_name = trim($value[4]);
                    $product_array['category_id'] = null;
                    if (!empty($category_name)) {
                        $category = Category::whereRaw("name LIKE CONCAT(CONVERT(?, BINARY))", [$category_name])->first();
                        if ($category) {
                            $product_array['category_id'] = $category->id;
                        } else {
                            $is_valid = false;
                            $error_msg = "Danh mục không hợp lệ ở hàng số $row_no";
                            break;
                        }
                    }

                    //Add enable stock
                    /*$enable_stock = trim($value[6]);
                    if (in_array($enable_stock, [0,1])) {
                        $product_array['enable_stock'] = $enable_stock;
                    } else {
                        $is_valid =  false;
                        $error_msg = "Quản lý tồn kho không hợp lệ ở hàng số $row_no";
                        break;
                    }*/

                    //Add stock and alert quantity
                    $enable_stock = $value[6];
                    if ($enable_stock == 1) {
                        $product_array['enable_stock'] = 1;
                        $product_array['alert_quantity'] = trim($value[7]);
                    } elseif ($enable_stock == 0) {
                        $product_array['enable_stock'] = 0;
                        $product_array['alert_quantity'] = null;
                    } elseif ($enable_stock != 0 || $enable_stock != 1) {
                        $is_valid = false;
                        $error_msg = "Quản lý tồn kho chỉ có giá trị là 0 hoặc 1 ở hàng số $row_no";
                        break;
                    }

                    //Add product type
                    $product_array['type'] = 'single';
                    /*$product_type = strtolower(trim($value[8]));
                    if (in_array($product_type, ['single','variable'])) {
                        $product_array['type'] = $product_type;
                    } else {
                        $is_valid =  false;
                        $error_msg = "Loại sản phẩm không hợp lệ ở hàng số $row_no";
                        break;
                    }*/

                    // Add default price

                    //Thickness
                    $thickness = floatval(trim($value[8]));
                    if (!empty($thickness)) {
                        $product_array['thickness'] = $thickness;
                    } else {
                        $product_array['thickness'] = null;
                    }

                    //Weight
                    if (!empty($value[9])) {
                        if(is_numeric(trim($value[9]))) {
                            $product_array['weight'] = trim($value[9]);
                        }else{
                            $is_valid = false;
                            $error_msg = '"Quy đổi 1m2 ra kg" phải là số. Lỗi ở hàng số '. $row_no .'.';
                            break;
                        }
                    } else {
                        $product_array['weight'] = null;
                    }

                    //image name
                    /*$image_name = trim($value[13]);
                    if (!empty($image_name)) {
                        $product_array['image'] = $image_name;
                    } else {
                        $product_array['image'] = '';
                    }*/

                    $product_array['product_description'] = isset($value[10]) ? $value[10] : null;

                    //Add not for selling
                    $product_array['not_for_selling'] = !empty($value[11]) && $value[11] == 1 ? 1 : 0;

                    if (!empty(trim($value[12]))) {
                        $default_sell_price = trim($value[12]);
                    } else {
                        $default_sell_price = 0;
                    }
                    /*if (!empty(trim($value[12]))) {
                        $default_sell_price = trim($value[12]);
                    } else {
                        $is_valid = false;
                        $error_msg = "Giá bán không được trống ở hàng số $row_no";
                        break;
                    }*/

                    $default_sell_price_by_plate = trim($value[14]);

                    /*if (!empty(trim($value[17]))) {
                        $default_sell_price_by_plate = trim($value[17]);
                    } else {
                        $is_valid = false;
                        $error_msg = "Giá bán không được trống ở hàng số $row_no";
                        break;
                    }*/

                    $selling_price_group = SellingPriceGroup::get()->toArray();

                    $product = Product::create([
                        'name' => $product_array['name'],
                        'business_id' => $business_id,
                        'type' => $product_array['type'],
                        'unit_id' => $product_array['unit_id'],
                        'sub_unit_ids' => $product_array['sub_unit_ids'],
                        'brand_id' => $product_array['brand_id'],
                        'category_id' => $product_array['category_id'],
                        'tax_type' => 'inclusive',
                        'enable_stock' => $product_array['enable_stock'],
                        'alert_quantity' => $product_array['alert_quantity'],
                        'sku' => $product_array['sku'],
                        'barcode_type' => $product_array['barcode_type'],
                        'weight' => $product_array['weight'],
                        'thickness' => $product_array['thickness'],
                        'image' => '',
                        'product_description' => $product_array['product_description'],
                        'not_for_selling' => $product_array['not_for_selling'],
                        'created_by' => $user_id,
                        'default_sub_unit_id' => $product_array['default_sub_unit_id']
                    ]);

                    if (empty($sku)) {
                        $sku_generate = $this->productUtil->generateProductSku($product->id);
                        Product::find($product->id)->update(['sku' => $sku_generate]);
                    }

                    if ($product_array['type'] == 'single') {
                        // Add variation
                        $product_variation = ProductVariation::create([
                            'variation_template_id' => null,
                            'name' => 'DUMMY',
                            'product_id' => $product->id,
                            'is_dummy' => 1
                        ]);

                        $sku_product = Product::find($product->id);
                        // Add variation
                        $variation = Variation::create([
                            'name' => 'DUMMY',
                            'sub_sku' => !empty($sku) ? $product_array['sku'] : $sku_product->sku,
                            'product_id' => $product->id,
                            'product_variation_id' => $product_variation->id,
                            'default_purchase_price' => empty($default_purchase_price) ? 0 : $default_purchase_price,
                            'dpp_inc_tax' => empty($default_purchase_price) ? 0 : $default_purchase_price,
                            'default_sell_price' => $default_sell_price,
                            'sell_price_inc_tax' => $default_sell_price,
                            'combo_variations' => [],
                            'default_sell_price_by_plate' => !empty($default_sell_price_by_plate) ? $default_sell_price_by_plate : 0,
                        ]);

                        // Add price group
                        $price_group = explode('|', trim($value[13]));
                        $price_group_by_plate = explode('|', trim($value[15]));
                        if (!empty($selling_price_group)) {
                            foreach ($selling_price_group as $idx => $item) {
//                                if ($idx <= (count($price_group) - 1)) {
                                    VariationGroupPrice::create([
                                        'variation_id' => $variation->id,
                                        'price_group_id' => $item['id'],
                                        'price_inc_tax' => isset($price_group[$idx]) ? str_replace(',', '', $price_group[$idx]) : 0,
                                        'price_by_plate' => isset($price_group_by_plate[$idx]) ? str_replace(',', '', $price_group_by_plate[$idx]) : 0
                                    ]);
//                                } else {
//                                    VariationGroupPrice::create([
//                                        'variation_id' => $variation->id,
//                                        'price_group_id' => $item['id'],
//                                        'price_inc_tax' => 0,
//                                        'price_by_plate' => 0,
//                                    ]);
//                                }
                            }
                        }
                    }
                    /*elseif ($product_array['type'] == 'variable') {
                        $variation_name = trim($value[9]);
                        $variation_values_string = trim($value[10]);
                        $variation_prices = array_map('trim', explode('|', $default_sell_price));
                        $variation_purchase_prices = empty($default_purchase_price) ? [] : array_map('trim', explode('|', $default_purchase_price));
                        $variation_price_group = empty($value[17]) ? [] : array_map('trim', explode(',', trim($value[17])));
                        if (!empty($variation_name) && !empty($variation_values_string)) {
                            $variation_template = VariationTemplate::where('name', $variation_name)
                                ->where('business_id', $business_id)
                                ->first();

                            if ($variation_template) {
                                if (!empty($variation_values_string)) {
                                    $variation_values = array_map('trim', explode('|', $variation_values_string));
                                    $product_variation = ProductVariation::create([
                                        'variation_template_id' => $variation_template->id,
                                        'name' => $variation_template->name,
                                        'product_id' => $product->id,
                                        'is_dummy' => 0
                                    ]);
                                    foreach ($variation_values as $k => $variation_value) {
                                        $variation_value_template = VariationValueTemplate::where('name', $variation_value)
                                            ->where('variation_template_id', $variation_template->id)
                                            ->first();
                                        if ($variation_value_template) {
                                            $variation = Variation::create([
                                                'name' => $variation_value_template->name,
                                                'product_id' => $product->id,
                                                'sub_sku' => $product_array['sku'] . '-' . $variation_value_template->id,
                                                'product_variation_id' => $product_variation->id,
                                                'variation_value_id' => $variation_value_template->id,
                                                'default_purchase_price' => empty($variation_purchase_prices) ? 0 : $variation_purchase_prices[$k],
                                                'dpp_inc_tax' => empty($variation_purchase_prices) ? 0 : $variation_purchase_prices[$k],
                                                'default_sell_price' => $variation_prices[$k],
                                                'sell_price_inc_tax' => $variation_prices[$k],
                                                'combo_variations' => null
                                            ]);

                                            if (!empty($selling_price_group) && !empty($variation_price_group)) {
                                                foreach ($selling_price_group as $idx => $item) {
                                                    $arr_price_group = array_map('trim', explode('|', trim($variation_price_group[$idx])));
                                                    if ($idx <= (count($variation_price_group) - 1)) {
                                                        VariationGroupPrice::create([
                                                            'variation_id' => $variation->id,
                                                            'price_group_id' => $item['id'],
                                                            'price_inc_tax' => empty($arr_price_group[$k]) ? 0 : $this->productUtil->num_uf($arr_price_group[$k])
                                                        ]);
                                                    } else {
                                                        VariationGroupPrice::create([
                                                            'variation_id' => $variation->id,
                                                            'price_group_id' => $item['id'],
                                                            'price_inc_tax' => 0
                                                        ]);
                                                    }
                                                }
                                            }
                                        } else {
                                            $is_valid = false;
                                            $error_msg = "Giá trị thuộc tính không tồn tại ở hàng số $row_no";
                                            break;
                                        }
                                    }
                                }
                            } else {
                                $is_valid = false;
                                $error_msg = "Thuộc tính không tồn tại ở hàng số $row_no";
                                break;
                            }
                        } else {
                            $is_valid = false;
                            $error_msg = "Tên thuộc tính và giá trị không được trống ở hàng số $row_no";
                            break;
                        }
                    }*/

                    // Add location
                    if (!empty(trim($value[16]))) {
                        $product_location_ids = [];
                        $product_locations = explode('|', trim($value[16]));
                        foreach ($product_locations as $product_location) {
                            $location = BusinessLocation::whereRaw("name LIKE CONCAT(CONVERT(?, BINARY))", [trim($product_location)])->first();
                            if ($location) {
                                $product_location_ids[] = $location->id;
                            } else {
                                $is_valid =  false;
                                $error_msg = "Chi nhánh không hợp lệ ở hàng số $row_no";
                                break;
                            }
                        }
                        $product->product_locations()->sync($product_location_ids);
                    } else {
                        $is_valid =  false;
                        $error_msg = "Chi nhánh không được trống ở hàng số $row_no";
                        break;
                    }
                }

                if (!$is_valid) {
                    throw new \Exception($error_msg);
                }
            }

            $output = ['success' => 1,
                            'msg' => __('product.file_imported_successfully')
                        ];

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = ['success' => 0,
                            'msg' => $e->getMessage()
                        ];
            return redirect('import-products')->with('notification', $output);
        }

        return redirect('import-products')->with('status', $output);
    }

    private function calculateVariationPrices($dpp_exc_tax, $dpp_inc_tax, $selling_price, $tax_amount, $tax_type, $margin)
    {

        //Calculate purchase prices
        if ($dpp_inc_tax == 0) {
            $dpp_inc_tax = $this->productUtil->calc_percentage(
                $dpp_exc_tax,
                $tax_amount,
                $dpp_exc_tax
            );
        }

        if ($dpp_exc_tax == 0) {
            $dpp_exc_tax = $this->productUtil->calc_percentage_base($dpp_inc_tax, $tax_amount);
        }

        if ($selling_price != 0) {
            if ($tax_type == 'inclusive') {
                $dsp_inc_tax = $selling_price;
                $dsp_exc_tax = $this->productUtil->calc_percentage_base(
                    $dsp_inc_tax,
                    $tax_amount
                );
            } elseif ($tax_type == 'exclusive') {
                $dsp_exc_tax = $selling_price;
                $dsp_inc_tax = $this->productUtil->calc_percentage(
                    $selling_price,
                    $tax_amount,
                    $selling_price
                );
            }
        } else {
            $dsp_exc_tax = $this->productUtil->calc_percentage(
                $dpp_exc_tax,
                $margin,
                $dpp_exc_tax
            );
            $dsp_inc_tax = $this->productUtil->calc_percentage(
                $dsp_exc_tax,
                $tax_amount,
                $dsp_exc_tax
            );
        }

        return [
            'dpp_exc_tax' => $this->productUtil->num_f($dpp_exc_tax),
            'dpp_inc_tax' => $this->productUtil->num_f($dpp_inc_tax),
            'dsp_exc_tax' => $this->productUtil->num_f($dsp_exc_tax),
            'dsp_inc_tax' => $this->productUtil->num_f($dsp_inc_tax)
        ];
    }

    /**
     * Adds opening stock of a single product
     *
     * @param array $opening_stock
     * @param obj $product
     * @param int $business_id
     * @return void
     */
    private function addOpeningStock($opening_stock, $product, $business_id)
    {
        $user_id = request()->session()->get('user.id');

        $variation = Variation::where('product_id', $product->id)
            ->first();

        $total_before_tax = $opening_stock['quantity'] * $variation->dpp_inc_tax;

        $transaction_date = request()->session()->get("financial_year.start");
        $transaction_date = \Carbon::createFromFormat('Y-m-d', $transaction_date)->toDateTimeString();
        //Add opening stock transaction
        $transaction = Transaction::create(
            [
                                'type' => 'opening_stock',
                                'opening_stock_product_id' => $product->id,
                                'status' => 'received',
                                'business_id' => $business_id,
                                'transaction_date' => $transaction_date,
                                'total_before_tax' => $total_before_tax,
                                'location_id' => $opening_stock['location_id'],
                                'final_total' => $total_before_tax,
                                'payment_status' => 'paid',
                                'created_by' => $user_id
                            ]
        );
        //Get product tax
        $tax_percent = !empty($product->product_tax->amount) ? $product->product_tax->amount : 0;
        $tax_id = !empty($product->product_tax->id) ? $product->product_tax->id : null;

        $item_tax = $this->productUtil->calc_percentage($variation->default_purchase_price, $tax_percent);

        //Create purchase line
        $transaction->purchase_lines()->create([
                        'product_id' => $product->id,
                        'variation_id' => $variation->id,
                        'quantity' => $opening_stock['quantity'],
                        'item_tax' => $item_tax,
                        'tax_id' => $tax_id,
                        'pp_without_discount' => $variation->default_purchase_price,
                        'purchase_price' => $variation->default_purchase_price,
                        'purchase_price_inc_tax' => $variation->dpp_inc_tax,
                        'exp_date' => !empty($opening_stock['exp_date']) ? $opening_stock['exp_date'] : null
                    ]);
        //Update variation location details
        $this->productUtil->updateProductQuantity($opening_stock['location_id'], $product->id, $variation->id, $opening_stock['quantity']);
    }


    private function addOpeningStockForVariable($variations, $product, $business_id)
    {
        $user_id = request()->session()->get('user.id');

        $transaction_date = request()->session()->get("financial_year.start");
        $transaction_date = \Carbon::createFromFormat('Y-m-d', $transaction_date)->toDateTimeString();

        $total_before_tax = 0;
        $location_id = $variations['opening_stock_location'];
        if (isset($variations['variations'][0]['opening_stock'])) {
            //Add opening stock transaction
            $transaction = Transaction::create(
                [
                                'type' => 'opening_stock',
                                'opening_stock_product_id' => $product->id,
                                'status' => 'received',
                                'business_id' => $business_id,
                                'transaction_date' => $transaction_date,
                                'total_before_tax' => $total_before_tax,
                                'location_id' => $location_id,
                                'final_total' => $total_before_tax,
                                'payment_status' => 'paid',
                                'created_by' => $user_id
                            ]
            );

            foreach ($variations['variations'] as $variation_os) {
                if (!empty($variation_os['opening_stock'])) {
                    $variation = Variation::where('product_id', $product->id)
                                    ->where('name', $variation_os['value'])
                                    ->first();
                    if (!empty($variation)) {
                        $opening_stock = [
                            'quantity' => $variation_os['opening_stock'],
                            'exp_date' => $variation_os['opening_stock_exp_date'],
                        ];

                        $total_before_tax = $total_before_tax + ($variation_os['opening_stock'] * $variation->dpp_inc_tax);
                    }

                    //Get product tax
                    $tax_percent = !empty($product->product_tax->amount) ? $product->product_tax->amount : 0;
                    $tax_id = !empty($product->product_tax->id) ? $product->product_tax->id : null;

                    $item_tax = $this->productUtil->calc_percentage($variation->default_purchase_price, $tax_percent);

                    //Create purchase line
                    $transaction->purchase_lines()->create([
                                    'product_id' => $product->id,
                                    'variation_id' => $variation->id,
                                    'quantity' => $opening_stock['quantity'],
                                    'item_tax' => $item_tax,
                                    'tax_id' => $tax_id,
                                    'purchase_price' => $variation->default_purchase_price,
                                    'purchase_price_inc_tax' => $variation->dpp_inc_tax,
                                    'exp_date' => !empty($opening_stock['exp_date']) ? $opening_stock['exp_date'] : null
                                ]);
                    //Update variation location details
                    $this->productUtil->updateProductQuantity($location_id, $product->id, $variation->id, $opening_stock['quantity']);
                }
            }

            $transaction->total_before_tax = $total_before_tax;
            $transaction->final_total = $total_before_tax;
            $transaction->save();
        }
    }

    private function rackDetails($rack_value, $row_value, $position_value, $business_id, $product_id, $row_no)
    {
        if (!empty($rack_value) || !empty($row_value) || !empty($position_value)) {
            $locations = BusinessLocation::forDropdown($business_id);
            $loc_count = count($locations);

            $racks = explode('|', $rack_value);
            $rows = explode('|', $row_value);
            $position = explode('|', $position_value);

            if (count($racks) > $loc_count) {
                $error_msg = "Invalid value for RACK in row no. $row_no";
                throw new \Exception($error_msg);
            }

            if (count($rows) > $loc_count) {
                $error_msg = "Invalid value for ROW in row no. $row_no";
                throw new \Exception($error_msg);
            }

            if (count($position) > $loc_count) {
                $error_msg = "Invalid value for POSITION in row no. $row_no";
                throw new \Exception($error_msg);
            }

            $rack_details = [];
            $counter = 0;
            foreach ($locations as $key => $value) {
                $rack_details[$key]['rack'] = isset($racks[$counter]) ? $racks[$counter] : '';
                $rack_details[$key]['row'] = isset($rows[$counter]) ? $rows[$counter] : '';
                $rack_details[$key]['position'] = isset($position[$counter]) ? $position[$counter] : '';
                $counter += 1;
            }

            if (!empty($rack_details)) {
                $this->productUtil->addRackDetails($business_id, $product_id, $rack_details);
            }
        }
    }
}
