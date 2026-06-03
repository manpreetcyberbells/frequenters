<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessImage extends Model
{
    use SoftDeletes;

    protected $table = 'business_images';
    protected $primaryKey = 'id';
    protected $fillable = ['business_id', 'photo'];
    protected $dates = ['deleted_at'];

    // public function business()
    // {
    //     return $this->belongsTo(Business::class);
    // }
}
