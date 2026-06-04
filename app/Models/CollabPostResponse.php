<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BusinessProfile;

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

    public function responderProfile()
    {
        return $this->hasOneThrough(
            BusinessProfile::class,
            User::class,
            'id',                    // users.id
            'business_id',           // business_profiles.business_id
            'responder_business_id', // collab_post_responses.responder_business_id
            'id'                     // users.id
        );
    }

    public function conversation()
    {
        return $this->hasOne(B2BConversation::class, 'collab_post_id', 'collab_post_id')
            ->where('business_b_id', $this->responder_business_id);
    }
}