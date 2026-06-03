<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessHour extends Model
{
    use SoftDeletes;

    protected $table = 'business_hours';
    protected $primaryKey = 'id';
    protected $fillable = ['business_id', 'day_of_week', 'open_time', 'close_time', 'is_closed'];
    protected $dates = ['deleted_at'];

}
