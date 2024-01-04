<?php

namespace App\Imports;

use App\SellingPriceGroup;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;


class SellingPriceGroupImport implements ToCollection, WithCalculatedFormulas
{
    public function collection(Collection $rows)
    {
        return $rows;
    }
}