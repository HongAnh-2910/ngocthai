<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PlateStock extends Model
{
    protected $guarded = ['id'];

    public function product()
    {
        return $this->belongsTo(\App\Product::class);
    }

    public function variation()
    {
        return $this->belongsTo(\App\Variation::class);
    }

    public function business_location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(\App\Warehouse::class);
    }

    public function product_locations()
    {
        return $this->belongsToMany(\App\BusinessLocation::class, 'product_locations', 'product_id', 'location_id');
    }
}
