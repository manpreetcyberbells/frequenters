<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invite extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'business_id', 'email', 'token', 'status', 'role'];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}

