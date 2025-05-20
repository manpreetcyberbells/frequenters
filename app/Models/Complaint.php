<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'business_id',
        'description',
        'status',
        'priority',
        'attachment',
        'resolved_at'
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }
}
