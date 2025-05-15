<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    protected $table = 'rewards';
    protected $fillable = [
        'business_id',
        'reward_name',
        'points_required',
        'valid_from',
        'valid_until',
        'usage_limit',
    ];

    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    public function reedemtionHistory()
    {
        return $this->hasMany(RewardRedemption::class);
    }

}

