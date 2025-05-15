<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;


class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory, SoftDeletes;

    protected $fillable = ['name', 'email', 'password', 'role','last_login_at', 'phone', 'profile_image', 'gender', 'status', 'parent_business_id', 'providers', 'provider_id', 'timezone'];
    protected $primaryKey = 'id';
    protected $hidden = ['password', 'remember_token'];
    protected $dates = ['deleted_at'];

    public function getAuthPassword()
    {
        return $this->password;
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    // OLD FUNCATION NOT IN USE
    // public function businessProfile()
    // {
    //     return $this->hasOne(BusinessProfile::class, 'business_id', 'user_id');
    // }

    /**
     * Define the relationship to the CustomerProfile model.
     *
     * @return HasOne
     */
    public function profile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class, 'customer_id', 'user_id'); // Ensure 'customer_id' is the foreign key

    }

    // Relationship with BusinessProfile
    public function businessProfile()
    {
        return $this->hasOne(BusinessProfile::class, 'business_id')->withDefault();
    }

    // Relationship with BusinessAddress
    public function businessAddress()
    {
        return $this->hasOne(BusinessAddress::class, 'business_id')->withDefault();
    }

    // Relationship with BusinessImages
    public function businessImage()
    {
        return $this->hasMany(BusinessImage::class, 'business_id');
    }

    // Relationship with BusinessHour
    public function businessHours()
    {
        return $this->hasMany(BusinessHour::class, 'business_id');
    }

    // Relationship with CustomerProfile
    public function customerProfile()
    {
        return $this->hasOne(CustomerProfile::class, 'customer_id')->withDefault();
    }

    // Relationship with CustomerAddress
    public function customerAddress()
    {
        return $this->hasOne(CustomerAddress::class, 'customer_id')->withDefault();
    }

    // Relationship with CustomerBadges
    public function customerBadges()
    {
        return $this->hasOne(CustomerBadge::class, 'customer_id');
    }

}
