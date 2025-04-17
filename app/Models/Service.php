<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['business_id', 'name', 'category_id', 'description', 'duration', 'price', 'photo', 'availability'];

    public function business() {
        return $this->belongsTo(Business::class);
    }
    
    public function schedule() {
        return $this->hasMany(ServiceAvailabilitySchedule::class);
    }
}
