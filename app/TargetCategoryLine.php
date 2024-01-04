<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TargetCategoryLine extends Model
{
    protected $guarded = ['id'];

    public function target()
    {
        return $this->belongsTo(\App\Target::class);
    }

    public function category()
    {
        return $this->belongsTo(\App\Category::class);
    }

    public function sub_category()
    {
        return $this->belongsTo(\App\Category::class, 'sub_category_id', 'id');
    }
}
