<?php

namespace App\Models\Connect;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConnectSmsMessage extends Model
{
    protected $table = 'connect_sms_messages';

    protected $guarded = ['id'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ConnectConversation::class, 'connect_conversation_id');
    }
}
