<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Variation;
use App\BusinessLocation;
use App\Warehouse;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class ValidateOpeningStockFile implements ToCollection, WithStartRow, WithCalculatedFormulas
{
    /**
     * @var errors
     */
    public $errors = [];

    /**
     * @var isValidFile
     */
    public $isValidFile = false;

    /**
     * ValidateCsvFile constructor.
     * @param StoreEntity $store
     */
    public function __construct()
    {
        //
    }

    public function collection(Collection $rows)
    {
        $errors = [];
        $business_id = request()->session()->get('user.business_id');

        if (count($rows) > 1) {
            $rows = $rows->slice(1);
            foreach ($rows as $key => $row) {
                $num_row = $key + 1;

                //Check for product SKU, get product id, variation id.
                if (!empty($row[0])) {
                    $product_info = Variation::where('sub_sku', $row[0])
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
                        $errors[$num_row] = "SKU không hợp lệ ở dòng số $num_row";
                        break;
                    } elseif ($product_info->enable_stock == 0) {
                        $errors[$num_row] = "Quản lý kho không được kích hoạt cho SKU ở hàng số $num_row";
                        break;
                    }
                } else {
                    $errors[$num_row] = "SKU không được trống ở hàng số $num_row";
                    break;
                }

                if (empty(trim($row[1])) || trim($row[1]) < 0) {
                    $errors[$num_row] = "Độ dài của tấm không được để trống hoặc âm ở hàng $num_row";
                    break;
                }

                if (empty(trim($row[2])) || trim($row[2]) < 0) {
                    $errors[$num_row] = "Độ rộng của tấm không được để trống hoặc âm ở hàng $num_row";
                    break;
                }

                if (empty(trim($row[3])) || trim($row[3]) <= 0) {
                    $errors[$num_row] = "Số lượng tấm phải là số nguyên dương ở hàng $num_row";
                    break;
                }

                //Get location details.
                if (!empty(trim($row[4]))) {
                    $location_name = trim($row[4]);
                    $location = BusinessLocation::where('name', $location_name)
                        ->where('business_id', $business_id)
                        ->first();
                    if (empty($location)) {
                        $errors[$num_row] = "Không có chi nhánh nào có tên '$location_name' ở hàng số $num_row";
                        break;
                    }
                } else {
                    $errors[$num_row] = "Chi nhánh không được trống ở hàng số $num_row";
                    break;
                }

                if (!empty(trim($row[5]))) {
                    $warehouse_name = trim($row[5]);
                    $warehouse = Warehouse::where('name', $warehouse_name)
                        ->where('business_id', $business_id)
                        ->where('location_id', $location->id)
                        ->first();
                    if (empty($warehouse)) {
                        $errors[$num_row] = "Không có kho hàng nào có tên '$warehouse_name' ở hàng số $num_row";
                        break;
                    }
                } else {
                    $errors[$num_row] = "Kho chứa hàng không được trống ở hàng số $num_row";
                    break;
                }
            }
            $this->errors = $errors;
            $this->isValidFile = true;
        }
    }

    public function startRow(): int
    {
        return 1;
    }
}
