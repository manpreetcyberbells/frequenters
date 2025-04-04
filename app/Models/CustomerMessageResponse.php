<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerMessageResponse extends Model
{
    use HasFactory;

    protected $table = 'customer_message_responses';
    protected $fillable = ['customer_id', 'business_id', 'message_id', 'status'];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    public function message()
    {
        return $this->belongsTo(Message::class, 'message_id');
    }
}
