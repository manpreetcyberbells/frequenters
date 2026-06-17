<?php

namespace App\Models\Connect;

use Illuminate\Database\Eloquent\Model;

class ConnectCallLog extends Model
{
    protected $table = 'connect_call_logs';

    protected $guarded = ['id'];

    protected $casts = [
        'auto_text_sent' => 'boolean',
    ];
}
