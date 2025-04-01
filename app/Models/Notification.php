<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'business_id', 'type', 'status', 'message', 
        'campaign_id', 'retry_count', 'priority', 'scheduled_time', 'is_broadcast'
    ];
}
