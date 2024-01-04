<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionPlateLinesReturn extends Model
{
    use SoftDeletes;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $table = 'transaction_plate_lines_return';

    public function transaction()
    {
        return $this->belongsTo(\App\Transaction::class);
    }

    public function sell_line()
    {
        return $this->belongsTo(\App\TransactionSellLine::class);
    }

    public function plate_line()
    {
        return $this->belongsTo(\App\TransactionPlateLine::class);
    }

    public function plate_stock()
    {
        return $this->belongsTo(\App\PlateStock::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(\App\Warehouse::class);
    }

    public function variation()
    {
        return $this->belongsTo(\App\Variation::class, 'variation_id');
    }
}
