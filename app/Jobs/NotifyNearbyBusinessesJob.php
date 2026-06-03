<?php

namespace App\Jobs;

use App\Models\BusinessAddress;
use App\Models\BusinessProfile;
use App\Models\CollabPost;
use App\Models\User;
use App\Services\FCMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotifyNearbyBusinessesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // retry after 60s

    public function __construct(
        private readonly int $collabPostId,
        private readonly float $radiusMiles = 5.0
    ) {}

    public function handle(FCMService $fcmService): void
    {
        $post = CollabPost::with('business')->find($this->collabPostId);

        if (!$post || !$post->latitude || !$post->longitude) {
            Log::warning("NotifyNearbyBusinessesJob: Post {$this->collabPostId} not found or missing location.");
            return;
        }

        // Find businesses within radius using Haversine
        $nearbyBusinessIds = DB::table('business_addresses as ba')
            ->join('users as u', 'u.id', '=', 'ba.business_id')
            ->join('business_profiles as bp', 'bp.business_id', '=', 'u.id')
            ->where('u.role', 'business')
            ->where('u.id', '!=', $post->business_id) // Exclude self
            ->whereNotNull('ba.latitude')
            ->whereNotNull('ba.longitude')
            ->selectRaw("
                u.id,
                bp.fcm_token,
                (3959 * ACOS(
                    COS(RADIANS(?)) * COS(RADIANS(ba.latitude)) *
                    COS(RADIANS(ba.longitude) - RADIANS(?)) +
                    SIN(RADIANS(?)) * SIN(RADIANS(ba.latitude))
                )) AS distance_miles
            ", [$post->latitude, $post->longitude, $post->latitude])
            ->having('distance_miles', '<=', $this->radiusMiles)
            ->get();

        $image = asset('storage/notifications/notification.png');

        foreach ($nearbyBusinessIds as $business) {
            // In-DB notification
            createNotification(
                $business->id,
                "New B2B {$post->post_type}: {$post->headline}",
                'push',
                null,
                true
            );

            // FCM push notification
            if (!empty($business->fcm_token)) {
                try {
                    $fcmService->sendNotificationToDevice(
                        $business->fcm_token,
                        "New B2B Collaboration — {$post->post_type}",
                        $post->headline,
                        $image
                    );
                } catch (\Throwable $e) {
                    Log::error("FCM failed for business {$business->id}: " . $e->getMessage());
                }
            }
        }

        Log::info("NotifyNearbyBusinessesJob: Notified " . count($nearbyBusinessIds) . " businesses for post {$this->collabPostId}");
    }
}