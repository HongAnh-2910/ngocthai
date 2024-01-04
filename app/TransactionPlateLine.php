<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionPlateLine extends Model
{
    use SoftDeletes;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function transaction()
    {
        return $this->belongsTo(\App\Transaction::class);
    }

    public function sell_line()
    {
        return $this->belongsTo(\App\TransactionSellLine::class, 'transaction_sell_line_id');
    }

    public function selected_plate_stock()
    {
        return $this->belongsTo(\App\PlateStock::class, 'selected_plate_stock_id');
    }

    public function plate_line_return() {
        return $this->hasMany(\App\TransactionPlateLinesReturn::class, 'transaction_plate_line_id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Product::class, 'product_id');
    }

    public function variations()
    {
        return $this->belongsTo(\App\Variation::class, 'variation_id');
    }

    public function remaining_plate_lines() {
        return $this->hasMany(\App\RemainingPlateLine::class, 'transaction_plate_line_id');
    }
}
