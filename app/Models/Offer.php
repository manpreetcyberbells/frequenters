<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id', 'title', 'discount_percentage', 'discount_cap', 'applicable_users',
        'offer_time', 'campaign_start_date', 'campaign_start_time', 'campaign_end_date',
        'campaign_end_time', 'applicable_days', 'min_order_value', 'terms_and_conditions',
        'tagline', 'description', 'banner_image', 'is_active',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'discount_percentage'=> 'float',
        'discount_cap'       => 'float',
        'min_order_value'    => 'float',
    ];

    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    public function getValidityStatusAttribute(): string
    {
        $now        = Carbon::now();
        $endDateTime = Carbon::parse($this->campaign_end_date . ' ' . $this->campaign_end_time);

        if ($this->campaign_start_date === $this->campaign_end_date) {
            return 'Valid today only';
        }

        $diffHours  = $now->diffInHours($endDateTime, false);
        $diffDays   = $now->diffInDays($endDateTime, false);
        $diffMonths = $now->diffInMonths($endDateTime, false);

        return match(true) {
            $diffHours <= 0                    => 'Expired',
            $diffHours <= 24 && $diffDays == 0 => 'Valid for a few hours',
            $diffDays == 1                     => 'Valid today only',
            $diffDays <= 7                     => 'Valid this week',
            $diffMonths == 0                   => 'Valid this month',
            default                            => 'Valid until ' . $endDateTime->format('M d, Y'),
        };
    }
}