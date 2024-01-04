<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TransactionSellLinesChange extends Model
{

    protected $guarded = ['id'];
    protected $table = 'transaction_sell_lines_change';

    public function transaction()
    {
        return $this->belongsTo(\App\Transaction::class);
    }

    public function product()
    {
        return $this->belongsTo(\App\Product::class, 'product_id');
    }

    public function variations()
    {
        return $this->belongsTo(\App\Variation::class, 'variation_id');
    }

    public function modifiers()
    {
        return $this->hasMany(\App\TransactionSellLine::class, 'parent_sell_line_id')
            ->where('children_type', 'modifier');
    }

    /**
     * Get the quantity column.
     *
     * @param  string  $value
     * @return float $value
     */
    public function getQuantityAttribute($value)
    {
        return (float)$value;
    }

    public function lot_details()
    {
        return $this->belongsTo(\App\PurchaseLine::class, 'lot_no_line_id');
    }

    public function get_discount_amount($arrs = null)
    {
        if($arrs == null){
            $arrs = $this;
        }
        $discount_amount = 0;
        if (!empty($arrs->line_discount_type) && !empty($arrs->line_discount_amount)) {
            if ($arrs->line_discount_type == 'fixed') {
                $discount_amount = $arrs->line_discount_amount;
            } elseif ($arrs->line_discount_type == 'percentage') {
                $discount_amount = ($arrs->unit_price_before_discount * $arrs->line_discount_amount) / 100;
            }
        }
        return $discount_amount;
    }

    /**
     * Get the unit associated with the purchase line.
     */
    public function sub_unit()
    {
        return $this->belongsTo(\App\Unit::class, 'sub_unit_id');
    }

    public function order_statuses()
    {
        $statuses = [
            'received',
            'cooked',
            'served'
        ];
    }

    public function service_staff()
    {
        return $this->belongsTo(\App\User::class, 'res_service_staff_id');
    }

    /**
     * The warranties that belong to the sell lines.
     */
    public function warranties()
    {
        return $this->belongsToMany('App\Warranty', 'sell_line_warranties', 'sell_line_id', 'warranty_id');
    }

    public static function _save($arrs){
        $productChange = new TransactionSellLinesChange();
        foreach ($arrs as $key => $val){
            $productChange->{$key} = $val;
        }
        return $productChange->save();
    }
}
