<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use App\Unit;
use App\Variation;
use App\BusinessLocation;
use App\Warehouse;
use App\Utils\ProductUtil;

class OpeningStockImport implements ToModel, ShouldQueue, WithChunkReading, WithStartRow, WithCalculatedFormulas
{
    use Importable;

    /**
     * @var errors
     */
    private $errors;

    /**
     * @var row
     */
    private $row = 1;

    /**
     * UsersImport constructor.
     * @param StoreEntity $store
     */
    public function __construct($errors = [])
    {
        $this->errors = $errors;
    }

    public function model(array $row)
    {
        if (array_key_exists(++$this->row, $this->errors)) {
            return null;
        }

        try {
            $business_id = request()->session()->get('user.business_id');

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

            $location = BusinessLocation::where('name', trim($row[4]))
                ->where('business_id', $business_id)
                ->first();

            $warehouse = Warehouse::where('name', trim($row[5]))
                ->where('business_id', $business_id)
                ->where('location_id', $location->id)
                ->first();

            DB::beginTransaction();
            $opening_stock = [
                'width' => $product_info->unit_type == Unit::PCS ? 1 : trim($row[1]),
                'height' => $product_info->unit_type == Unit::PCS ? 1 : trim($row[2]),
                'quantity' => trim($row[3]),
                'location_id' => $location->id,
                'warehouse_id' => $warehouse->id,
            ];

            $productUtil = new ProductUtil;
            $productUtil->addOpeningStock($opening_stock, $product_info, $business_id);
            DB::commit();
        } catch (Exceptions $e) {
            DB::rollBack();
            Log::debug($e);
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function startRow(): int
    {
        return 2;
    }
}