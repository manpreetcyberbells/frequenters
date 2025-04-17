<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = [
        'business_id',
        'category_id',
        'name',
        'description',
        'price',
        'photo',
        'availability_status',
    ];

    public function business()
    {
        return $this->belongsTo(BusinessProfile::class, 'business_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}

