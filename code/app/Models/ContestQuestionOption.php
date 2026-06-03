<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContestQuestionOption extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'contest_question_options';

    protected $fillable = [
        'contest_question_id',
        'option_text',
    ];

    /**
     * Get the question that owns the option.
     */
    public function question()
    {
        return $this->belongsTo(ContestQuestion::class, 'contest_question_id');
    }
}
