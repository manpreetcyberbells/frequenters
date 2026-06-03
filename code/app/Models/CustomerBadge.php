<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerBadge extends Model
{
    protected $table = 'customer_badges';

    protected $fillable = ['customer_id', 'badge_id', 'earned_at', 'expires_at'];

    public function badge()
    {
        return $this->belongsTo(Badge::class);
    }
}
