<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $table = 'message';
    protected $fillable = ['business_id', 'message_text', 'group_target', 'chat_enabled', 'enable_buttons', 'sender_id', 'receiver_id', 'is_group_message', 'read_by'];

    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }
}
