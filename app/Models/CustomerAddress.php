<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerAddress extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customer_addresses';
    protected $primaryKey = 'id';
    protected $fillable = ['customer_id','address_line1','address_line2','zip_code','city','state','country','radius','latitude','longitude','is_current'];
    protected $dates = ['deleted_at'];

    // public function customer_profile()
    // {
    //     return $this->belongsTo(CustomerProfile::class, 'customer_id', 'user_id');
    // }

    public function city()
    {
        return $this->belongsTo(City::class, 'id');
    }
}
