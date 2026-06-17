<?php

namespace App\Models\Connect;

use Illuminate\Database\Eloquent\Model;

class ConnectMessageTemplate extends Model
{
    protected $table = 'connect_message_templates';

    protected $guarded = ['id'];

    protected $casts = [
        'active' => 'boolean',
    ];
}
