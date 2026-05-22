<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Reward extends Model
{
    protected $table = 'rewards';

    protected $fillable = [
        'business_id',
        'reward_name',
        'points_required',
        'qr_code',
        'qr_code_payload',
        'is_exclusive',
        'valid_from',
        'valid_until',
        'usage_limit',
        'expiry_date',
    ];

    protected $casts = [
        'valid_from'   => 'datetime',
        'valid_until'  => 'datetime',
        'expiry_date'  => 'datetime',
        'is_exclusive' => 'boolean',
        'usage_limit'  => 'integer',
    ];

    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    public function redemptionHistory()
    {
        return $this->hasMany(RewardRedemption::class, 'reward_id', 'id'); // ✅ id se map
    }

    public function isExpired(): bool
    {
        $now = Carbon::now();

        if ($this->valid_from  && $now->lt($this->valid_from))  return true;
        if ($this->valid_until && $now->gt($this->valid_until)) return true;
        if ($this->expiry_date && $now->gt($this->expiry_date)) return true;

        return false;
    }

    public function isUsageLimitReached(): bool
    {
        if (is_null($this->usage_limit)) return false;

        return $this->redemptionHistory()
            ->whereIn('status', ['pending', 'redeemed'])
            ->count() >= $this->usage_limit;
    }
}