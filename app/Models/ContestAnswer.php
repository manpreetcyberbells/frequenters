<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContestAnswer extends Model
{
    use HasFactory;

    protected $fillable = ['contest_id', 'contest_question_id', 'participant_id', 'answer', 'contest_question_option_id', 'is_winner'];

    public function participant()
    {
        return $this->belongsTo(ContestParticipant::class);
    }

    public function question()
    {
        return $this->belongsTo(ContestQuestion::class, 'contest_question_id');
    }

    public function selectedOption()
    {
        return $this->belongsTo(ContestQuestionOption::class, 'contest_question_option_id');
    }

    /*public function question()
    {
        return $this->belongsTo(ContestQuestion::class);
    }*/

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
