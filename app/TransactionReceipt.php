<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TransactionReceipt extends Model
{
    protected $guarded = ['id'];
    const INCOME  = 'recovery_dues';
    const OUTCOME = 'receipt';
    const DEPOSIT = 'deposit';

    public static $TYPES = [
        self::INCOME => 'Thu nợ khách',
        self::DEPOSIT => 'Đặt cọc',
        self::OUTCOME => 'Phiếu thu',
    ];

    public function transactions()
    {
        return $this->belongsTo(\App\Transaction::class);
    }
}
