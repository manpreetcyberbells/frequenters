<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerBusinessInteraction extends Model
{
    protected $table = 'customer_business_interactions';

    protected $fillable = [
        'customer_id',
        'business_id',
        'interaction_type',
        'points_allocated',
        'last_interaction_date',
        'interaction_subtype',
        'interaction_count'
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }
}