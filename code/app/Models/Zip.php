<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Zip extends Model
{
    protected $fillable = ['zip_code', 'city_id'];

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
