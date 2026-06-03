<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = ['city_name', 'state', 'latitude', 'longitude'];

    public function zips()
    {
        return $this->hasMany(Zip::class);
    }

    public function scopeNearLocation($query, $lat, $lon, $radius = 50)
    {
        return $query->whereRaw(
            "ST_Distance_Sphere(point(latitude, longitude), point(?, ?)) <= ?",
            [$lat, $lon, $radius * 1609.34]
        );
    }
}