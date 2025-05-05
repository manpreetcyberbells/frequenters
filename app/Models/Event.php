<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'target_city_id',
        'event_name',
        'event_description',
        'venue_name',
        'venue_location',
        'capacity',
        'start_time',
        'end_time',
        'start_date',
        'end_date',
        'latitude',
        'longitude',
        'status',
        'event_banner',
    ];

    
    public function business()
    {
        return $this->belongsTo(BusinessProfile::class, 'business_id', 'business_id');
    }

    public function businessDetail()
    {
        return $this->belongsTo(User::class, 'business_id');
    }
}
