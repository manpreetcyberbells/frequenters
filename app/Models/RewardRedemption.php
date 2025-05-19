<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RewardRedemption extends Model
{
    protected $table = 'reward_redemptions';
    protected $fillable = [
        'customer_id',
        'reward_id',
        'business_id',
        'points_redeemed',
        'redeemed_at',
        'status',
        'redemption_code',
        'failure_reason',
        'redemption_method',
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

