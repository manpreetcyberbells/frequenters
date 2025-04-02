<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContestVotingHistory extends Model
{
    use HasFactory;

    protected $table = 'contest_voting_history';

    protected $fillable = [
        'contest_id',
        'contest_question_id',
        'contest_answer_id',
        'participant_id',
    ];

    /**
     * Relationships
     */
    public function contest()
    {
        return $this->belongsTo(Contest::class);
    }

    public function question()
    {
        return $this->belongsTo(ContestQuestion::class, 'contest_question_id');
    }

    public function answer()
    {
        return $this->belongsTo(ContestAnswer::class, 'contest_answer_id');
    }

    public function participant()
    {
        return $this->belongsTo(ContestParticipant::class, 'participant_id');
    }
}
