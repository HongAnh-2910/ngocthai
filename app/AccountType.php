<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AccountType extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function sub_types()
    {
        return $this->hasMany(\App\AccountType::class, 'parent_account_type_id');
    }

    public function parent_account()
    {
        return $this->belongsTo(\App\AccountType::class, 'parent_account_type_id');
    }

    public static function forDropdown($business_id, $show_none = false)
    {
        $account_types = AccountType::where('business_id', $business_id)
            ->whereNull('parent_account_type_id')
            ->pluck('name', 'id');

        if ($show_none) {
            $account_types->prepend(__('lang_v1.none'), '');
        }

        return $account_types;
    }
}
