<?php

namespace App\Jobs;

use App\Services\FCMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotifyNearbyCustomersForOfferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly int $businessId,
        private readonly float $latitude,
        private readonly float $longitude,
        private readonly int $radius = 150
    ) {}

    public function handle(FCMService $fcmService): void
    {
        Log::info("NotifyNearbyCustomersForOfferJob: STARTED for business {$this->businessId}");

        // Get nearby customer IDs using SQL Haversine (no full table scan)
        $nearbyCustomerIds = DB::table('customer_addresses')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereRaw("
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )) < ?
            ", [$this->latitude, $this->longitude, $this->latitude, $this->radius])
            ->distinct()
            ->pluck('customer_id');

        if ($nearbyCustomerIds->isEmpty()) {
            Log::info("NotifyNearbyCustomersForOfferJob: No nearby customers found.");
            return;
        }

        $fcmTokens = DB::table('customer_profiles')
            ->whereIn('customer_id', $nearbyCustomerIds)
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token', 'customer_id');

        $title = 'New Offer Near You!';
        $body  = 'Offer available for you';
        $image = url('storage/notifications/notification.png');

        foreach ($fcmTokens as $customerId => $token) {
            try {
                $fcmService->sendToCustomerApp($token, $title, $body, $image);
            } catch (\Throwable $e) {
                Log::error("Offer FCM failed for customer {$customerId}: " . $e->getMessage());
            }
            customerNotification($customerId, $body, 'push', null, true);
        }

        Log::info("NotifyNearbyCustomersForOfferJob: Notified {$fcmTokens->count()} customers for business {$this->businessId}");
    }
}