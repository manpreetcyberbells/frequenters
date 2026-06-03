<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class B2BConversation extends Model
{
    use HasFactory;
    protected $table = 'b2b_conversations';
    protected $fillable = [
        'collab_post_id',
        'business_a_id',
        'business_b_id',
    ];

    public function collabPost()
    {
        return $this->belongsTo(CollabPost::class, 'collab_post_id');
    }

    public function businessA()
    {
        return $this->belongsTo(User::class, 'business_a_id');
    }

    public function businessB()
    {
        return $this->belongsTo(User::class, 'business_b_id');
    }

    public function messages()
    {
        return $this->hasMany(B2BMessage::class, 'conversation_id')->orderBy('created_at');
    }

    public function latestMessage()
    {
        return $this->hasOne(B2BMessage::class, 'conversation_id')->latestOfMany();
    }

    public function otherParty(int $myBusinessId): User
    {
        return $this->business_a_id === $myBusinessId ? $this->businessB : $this->businessA;
    }
}