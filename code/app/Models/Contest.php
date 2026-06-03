<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id', 'name', 'description', 'banner_image', 'status', 'start_date', 'end_date', 'start_time', 'end_time', 'max_participants', 'reward_type', 
        'close_description', 'close_start_date', 'close_end_date', 'close_start_time', 'close_end_time',
        'winner_points', 'participant_points', 'voter_points',
        'winner_discount', 'participant_discount', 'voter_discount',
        'winner_free_item', 'participant_free_item', 'voter_free_item',
    ];

    // Add soft delete boot logic
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($contest) {
            if ($contest->isForceDeleting()) {
                $contest->answers()->forceDelete();
                $contest->participants()->forceDelete();
                $contest->questions()->forceDelete();
                // $contest->options()->forceDelete();
                $contest->votingHistory()->forceDelete();
            } else {
                $contest->answers()->delete();
                $contest->participants()->delete();
                $contest->questions()->delete();
                // $contest->options()->delete();
                $contest->votingHistory()->delete();
            }
        });
    }

    public function questions()
    {
        return $this->hasMany(ContestQuestion::class);
    }

    public function businessDetail()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    public function answers()
    {
        return $this->hasMany(ContestAnswer::class);
    }

    public function participants()
    {
        return $this->hasMany(ContestParticipant::class);
    }

    // public function options()
    // {
    //     return $this->hasMany(ContestQuestionOption::class);
    // }

    public function votingHistory()
    {
        return $this->hasMany(ContestVotingHistory::class);
    }

}

