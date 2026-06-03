<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerSuggestion extends Model
{
    protected $table = 'customer_suggestions';

    protected $fillable = ['customer_id', 'suggestion'];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

}
