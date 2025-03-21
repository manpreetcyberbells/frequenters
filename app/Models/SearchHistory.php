<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchHistory extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'search_term'];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
