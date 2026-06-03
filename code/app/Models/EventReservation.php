<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventReservation extends Model
{
    use HasFactory;

    protected $table = 'event_reservations';
    protected $fillable = ['event_id', 'customer_id', 'status', 'reserved_at'];

    protected $casts = [
        'reserved_at' => 'datetime',
    ];

    // Relationship: An event reservation belongs to an event
    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    // Relationship: An event reservation belongs to a customer (User)
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
