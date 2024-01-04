<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Utils\Util;
use DB;

class Account extends Model
{
    use SoftDeletes;
    
    protected $guarded = ['id'];

    public static function forDropdown($business_id, $prepend_none, $closed = false, $show_balance = false, $type = '')
    {
        $query = Account::where('accounts.business_id', $business_id)
            ->select('accounts.name','accounts.id');

        if ($show_balance) {
            $query->leftjoin('account_transactions as AT', function ($join) {
                $join->on('AT.account_id', '=', 'accounts.id');
                $join->whereNull('AT.deleted_at');
            })
                ->leftjoin(
                    'transaction_payments as tp',
                    'AT.transaction_payment_id',
                    '=',
                    'tp.id'
                )
                ->addSelect(DB::raw("SUM(IF(tp.approval_status = 'approved', tp.amount, 0)) as balance"))
//            ->addSelect(DB::raw("SUM( IF(AT.type='credit', amount, -1*amount) ) as balance"))
                ->groupBy('accounts.id');
        }

        if (!$closed) {
            $query->where('accounts.is_closed', 0);
        }

        if(!empty($type)){
            $query->leftJoin('account_types', 'accounts.account_type_id', '=', 'account_types.id')
                ->where('account_types.type', $type);
        }

        $accounts = $query->get();

        $dropdown = [];
        if ($prepend_none) {
            $dropdown[''] = __('lang_v1.none');
        }

        $commonUtil = new Util;
        foreach ($accounts as $account) {
            $name = $account->name;

            if ($show_balance) {
                $name .= ' (' . __('lang_v1.balance') . ': ' . $commonUtil->num_f($account->balance) . ' đ)';
            }

            $dropdown[$account->id] = $name;
        }

        return $dropdown;
    }

    /**
     * Scope a query to only include not closed accounts.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotClosed($query)
    {
        return $query->where('is_closed', 0);
    }

    /**
     * Scope a query to only include non capital accounts.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    // public function scopeNotCapital($query)
    // {
    //     return $query->where(function ($q) {
    //         $q->where('account_type', '!=', 'capital');
    //         $q->orWhereNull('account_type');
    //     });
    // }

    public static function accountTypes()
    {
        return [
            '' => __('account.not_applicable'),
            'saving_current' => __('account.saving_current'),
            'capital' => __('account.capital')
        ];
    }

    public function account_type()
    {
        return $this->belongsTo(\App\AccountType::class, 'account_type_id');
    }
}
