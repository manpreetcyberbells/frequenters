<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessAddress extends Model
{
    use SoftDeletes;

    protected $table = 'business_addresses';
    protected $primaryKey = 'id';
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'business_id',
        'address_line1',
        'address_line2',
        'zip_code',
        'city',
        'state',
        'country',
        'location',
        'latitude',
        'longitude',
    ];

    // public function city()
    // {
    //     return $this->belongsTo(City::class, 'city');
    // }
}
