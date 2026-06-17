<?php

namespace App\Models\Connect;

use Illuminate\Database\Eloquent\Model;

class ConnectBusinessHour extends Model
{
    protected $table = 'connect_business_hours';

    protected $guarded = ['id'];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_closed'   => 'boolean',
    ];
}
