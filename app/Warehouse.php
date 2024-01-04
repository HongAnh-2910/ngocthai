<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */


    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Return list of warehouses for a business
     *
     * @param int $business_id
     * @param boolean $show_none = false
     *
     * @return array
     */
    public static function forDropdown($business_id, $show_none = false, $except_warehouse_ids = [], $only_warehouse_ids = [])
    {
        $warehouses = Warehouse::where('business_id', $business_id);

        if(!empty($except_warehouse_ids)){
            $warehouses = $warehouses->whereNotIn('id', $except_warehouse_ids);
        }

        if(!empty($only_warehouse_ids)){
            $warehouses = $warehouses->whereIn('id', $only_warehouse_ids);
        }

        $warehouses = $warehouses->pluck('name', 'id');

        if ($show_none) {
            $warehouses->prepend(__('lang_v1.none'), '');
        }

        return $warehouses;
    }

    public function plate_stocks() {
        return $this->hasMany(PlateStock::class);
    }

    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class);
    }
}
