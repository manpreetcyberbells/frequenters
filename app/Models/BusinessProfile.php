<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessProfile extends Model
{
    use SoftDeletes;
    protected $table = 'business_profiles';
    protected $primaryKey = 'id';
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'business_id',
        'business_name',
        'industry_type',
        'qr_code_hash',
        'points_given',
        'points_redeemed',
        'points_per_checkin',
        'conversion_rate',
        'total_checkins',
        'total_rewards_redeemed',
        'category_id',
        'child_category_id',
        'description',
        'website',
        'points_per_checkin',
        'qr_code_url',
        'owner_name',
        'owner_email',
        'owner_phone',
        'logo',
    ];
    
    protected $casts = [
        'location' => 'array',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function customers()
    {
        return $this->hasManyThrough(Customer::class, Transaction::class);
    }

}