<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model {
    use HasFactory;

    protected $fillable = [
        'business_id', 'title', 'discount_percentage', 'discount_cap', 'applicable_users',
        'offer_time', 'campaign_start_date', 'campaign_start_time', 'campaign_end_date',
        'campaign_end_time', 'applicable_days', 'min_order_value', 'terms_and_conditions',
        'tagline', 'description', 'banner_image', 'is_active'
    ];

    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

}