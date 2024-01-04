<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TransactionExpense extends Model
{
    protected $guarded = ['id'];
    const INCOME  = 'return_customer';
    const OUTCOME = 'expense';

    public static $TYPES = [
        self::OUTCOME => 'Chi phí',
        self::INCOME => 'Trả lại cho khách'
    ];

    public function transactions()
    {
        return $this->belongsTo(\App\Transaction::class);
    }
}
