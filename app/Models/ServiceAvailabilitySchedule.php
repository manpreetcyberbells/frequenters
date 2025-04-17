<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceAvailabilitySchedule extends Model
{
    protected $fillable = ['service_id', 'day', 'start_time', 'end_time', 'is_open'];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

}
