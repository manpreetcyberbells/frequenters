<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TermsAndCondition extends Model
{
    protected $table = 'terms_and_conditions';
    protected $fillable = ['description', 'audience', 'is_active'];
}
