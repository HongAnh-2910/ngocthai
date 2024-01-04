<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TargetSaleLine extends Model
{
    protected $guarded = ['id'];

    public function target()
    {
        return $this->belongsTo(\App\Target::class);
    }

    public function product()
    {
        return $this->belongsTo(\App\Product::class);
    }

    public function variation()
    {
        return $this->belongsTo(\App\Variation::class);
    }
}
