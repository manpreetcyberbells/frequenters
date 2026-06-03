<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CollabPostResponse extends Model
{
    use HasFactory;
    protected $table = 'collab_post_responses';
    protected $fillable = [
        'collab_post_id',
        'responder_business_id',
        'message',
        'status',
    ];

    public function collabPost()
    {
        return $this->belongsTo(CollabPost::class, 'collab_post_id');
    }

    public function responderBusiness()
    {
        return $this->belongsTo(User::class, 'responder_business_id');
    }
}