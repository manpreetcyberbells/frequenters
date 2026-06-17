<?php

namespace App\Models\Connect;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConnectConversation extends Model
{
    protected $table = 'connect_conversations';

    protected $guarded = ['id'];

    protected $casts = [
        'last_customer_message_at' => 'datetime',
        'last_owner_message_at'    => 'datetime',
        'last_message_at'          => 'datetime',
        'closed_at'                => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(ConnectCustomer::class, 'connect_customer_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConnectSmsMessage::class, 'connect_conversation_id');
    }

    /** The human-facing lead number shown to the owner (e.g. #1047). */
    public function leadNumber(): string
    {
        return $this->conversation_code ?: (string) $this->id;
    }
}
