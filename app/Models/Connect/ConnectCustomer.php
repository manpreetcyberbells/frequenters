<?php

namespace App\Models\Connect;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConnectCustomer extends Model
{
    protected $table = 'connect_customers';

    protected $guarded = ['id'];

    protected $casts = [
        'first_seen_at'       => 'datetime',
        'last_seen_at'        => 'datetime',
        'opted_out'           => 'boolean',
        'opted_out_at'        => 'datetime',
        'total_conversations' => 'integer',
        'total_messages'      => 'integer',
    ];

    public function conversations(): HasMany
    {
        return $this->hasMany(ConnectConversation::class, 'connect_customer_id');
    }
}
