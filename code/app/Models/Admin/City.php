<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class City extends Model
{
    use HasFactory;
    protected $table = 'cities';
    protected $fillable = ['city_name', 'state_code', 'state', 'country', 'latitude', 'longitude'];

}
