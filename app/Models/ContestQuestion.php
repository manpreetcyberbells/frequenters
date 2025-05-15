<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContestQuestion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['contest_id', 'question', 'question_type'];

    public function contest()
    {
        return $this->belongsTo(Contest::class);
    }

    public function answers()
    {
        return $this->hasMany(ContestAnswer::class);
    }

    public function options()
    {
        return $this->hasMany(\App\Models\ContestQuestionOption::class);
    }

}
