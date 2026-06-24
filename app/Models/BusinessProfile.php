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
        'social_links',
        'monthly_points_available',
        'qr_code_url',
        'owner_name',
        'owner_email',
        'owner_phone',
        'logo',
        'business_uid',
        'tier_regular_min',
        'tier_daily_min',
        'fcm_token',
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

    public function services() {
        return $this->hasMany(Service::class);
    }
    
    public function menus() {
        return $this->hasMany(Menu::class);
    }

    public function tierFor(int $checkinCount): string
    {
        $regularMin = (int) ($this->tier_regular_min ?: 3);
        $dailyMin   = (int) ($this->tier_daily_min   ?: 10);

        return match (true) {
            $checkinCount >= $dailyMin   => 'daily',
            $checkinCount >= $regularMin => 'regular',
            default                      => 'new',
        };
    }
}