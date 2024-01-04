<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RemainingPlateLine extends Model
{
    protected $guarded = ['id'];

    public function plate_stock()
    {
        return $this->belongsTo(\App\PlateStock::class);
    }

    public function plate_line()
    {
        return $this->belongsTo(\App\TransactionPlateLine::class);
    }
}

