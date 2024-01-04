<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Target extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    public function target_sale_lines()
    {
        return $this->hasMany(\App\TargetSaleLine::class);
    }

    public function target_category_lines()
    {
        return $this->hasMany(\App\TargetCategoryLine::class);
    }
}
