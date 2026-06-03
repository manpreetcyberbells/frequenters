<?php

namespace App\Services;

use App\Models\Offer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OfferService
{
    public function __construct(
        private readonly FCMService $fcmService,
        private readonly NearbyCustomerService $nearbyCustomerService,
    ) {}

    // Nearby Notification
    public function sendNearbyNotifications(int $businessId, ?float $lat, ?float $lng): void
    {
        if (!$lat || !$lng) return;

        $nearbyCustomerIds = $this->nearbyCustomerService->getNearbyCustomerIds($lat, $lng, 10);
        if (empty($nearbyCustomerIds->toArray())) return;

        $fcmTokens = DB::table('customer_profiles')->whereIn('customer_id', $nearbyCustomerIds)->whereNotNull('fcm_token')->pluck('fcm_token', 'customer_id');
        $image = asset('storage/notifications/notification.png');
        foreach ($fcmTokens as $customerId => $token) {
            $this->fcmService->sendNotificationToDevice($token, 'New Offer', 'Offer available for you', $image);
            customerNotification($customerId, 'Offer available for you', 'push', null, true);
        }
    }

    public function getNearbyBusinessIds(int $excludeBusinessId, int $categoryId, float $lat, float $lng, int $radius = 10)
    {
        return DB::table('users')
            ->join('business_addresses', 'business_addresses.business_id', '=', 'users.id')
            ->join('business_profiles', 'business_profiles.business_id', '=', 'users.id')
            ->where('business_profiles.child_category_id', $categoryId)
            ->where('users.role', 'business')
            ->where('users.id', '!=', $excludeBusinessId)
            ->whereRaw("
                (
                    6371 * acos(
                        cos(radians(?)) * cos(radians(business_addresses.latitude)) *
                        cos(radians(business_addresses.longitude) - radians(?)) +
                        sin(radians(?)) * sin(radians(business_addresses.latitude))
                    )
                ) < ?
            ", [$lat, $lng, $lat, $radius])
            ->pluck('users.id');
    }
}