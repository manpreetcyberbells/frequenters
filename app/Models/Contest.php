<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contest extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id', 'name', 'description', 'banner_image', 'status', 'start_date', 'end_date', 'start_time', 'end_time', 'max_participants', 'reward_type', 
        'close_description', 'close_start_date', 'close_end_date', 'close_start_time', 'close_end_time',
        'winner_points', 'participant_points', 'voter_points',
        'winner_discount', 'participant_discount', 'voter_discount',
        'winner_free_item', 'participant_free_item', 'voter_free_item',
    ];

    public function questions()
    {
        return $this->hasMany(ContestQuestion::class);
    }
}

