<?php

namespace App\Services;

use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class RewardService
{
    public function __construct(
        private readonly FCMService $fcmService,
        private readonly NearbyCustomerService $nearbyCustomerService,
    ) {}

    public function createReward(array $data, int $businessId): Reward
    {
        $reward = Reward::create([
            'business_id'     => $businessId,
            'reward_name'     => $data['reward_name'],
            'points_required' => $data['points_required'] ?? 0,
            'valid_from'      => $data['valid_from']  ?? null,
            'valid_until'     => $data['valid_until'] ?? null,
            'banner_image'    => $data['banner_image'] ?? null,
            'description'    => $data['description'] ?? null,
        ]);

        $qrPayload = [
            'type'            => 'reward_redemption',
            'business_id'     => $businessId,
            'reward_id'       => $reward->id,
            'redemption_code' => $this->generateUniqueCode(),
        ];

        if (!empty($data['customer_tiers'])) {
            $qrPayload['customer_tiers'] = $data['customer_tiers'];
        }

        $reward->update(['qr_code_payload' => json_encode($qrPayload)]);
        return $reward;
    }


    public function buildCustomerQr(Reward $reward, int $customerId): array
    {
        $originalPayload = json_decode($reward->qr_code_payload, true);
        $originalPayload['customer_id'] = $customerId;

        $encryptedPayload = Crypt::encryptString(json_encode($originalPayload));
        $safeFilename = hash('sha256', $encryptedPayload);
        $path = 'qrcodes/rewards/' . $safeFilename . '.svg';
        if (!Storage::disk('public')->exists($path)) {
            Storage::disk('public')->put(
                $path,
                QrCode::format('svg')->size(300)->generate($encryptedPayload)
            );
        }

        return [
            'path'              => $path,
            'encrypted_payload' => $encryptedPayload,
        ];
    }

    // Reward Redemption (Business scans QR)
    public function processRedemption(array $decryptedPayload, int $businessUserId): void
    {
        $customerId = $decryptedPayload['customer_id'];
        $reward = Reward::where('id', $decryptedPayload['reward_id'])->where('business_id', $businessUserId)->firstOrFail();

        // Tier check
        $customerTier = DB::table('customer_business_relationships')->where('business_id', $businessUserId)->where('customer_id', $customerId)->value('customer_tier');
        if (!empty($decryptedPayload['customer_tiers']) &&
            !in_array($customerTier, $decryptedPayload['customer_tiers'])) {
            abort(403, 'This reward is not available for your customer tier.');
        }

        // Points check is_null check
        $customerBalance = DB::table('customer_profiles')->where('customer_id', $customerId)->value('points_balance');
        if (is_null($customerBalance)) {
            abort(404, 'Customer not found.');
        }

        if ($customerBalance < $reward->points_required) {
            abort(400, 'Insufficient points balance.');
        }

        // Transaction
        DB::transaction(function () use ($customerId, $reward, $decryptedPayload, $businessUserId) {
            DB::table('customer_profiles')->where('customer_id', $customerId)->decrement('points_balance', $reward->points_required);

            RewardRedemption::create([
                'customer_id'      => $customerId,
                'reward_id'        => $reward->id,
                'business_id'      => $businessUserId,
                'points_redeemed'  => $reward->points_required,
                'status'           => 'success',
                'redemption_code'  => $decryptedPayload['redemption_code'],
                'redeemed_at'      => now(),
            ]);
        });

        // FCM
        $this->sendRedemptionNotifications($customerId, $businessUserId);
    }

    // Notifications
    // public function sendNearbyNotifications(int $businessId, float $lat, float $lng): void
    // {
    //     $nearbyCustomerIds = $this->nearbyCustomerService->getNearbyCustomerIds($lat, $lng, 10);
    //     if (empty($nearbyCustomerIds->toArray())) return;
    //     $fcmTokens = DB::table('customer_profiles')->whereIn('customer_id', $nearbyCustomerIds)->whereNotNull('fcm_token')->pluck('fcm_token', 'customer_id');
    //     $title = 'Reward Near You!';
    //     $body  = 'New reward near you, please redeem it';
    //     $image = url('storage/notifications/notification.png');
    //     foreach ($fcmTokens as $customerId => $token) {
    //         try {
    //             $this->fcmService->sendToCustomerApp($token, $title, $body, $image);
    //         } catch (\Throwable $e) {
    //             \Log::error("Nearby reward FCM failed for customer {$customerId}: " . $e->getMessage());
    //         }
    //         customerNotification($customerId, $body, 'push', null, true);
    //     }
    // }
    public function sendNearbyNotifications(int $businessId, float $lat, float $lng, $reward = null): void
    {
        $nearbyCustomerIds = $this->nearbyCustomerService->getNearbyCustomerIds($lat, $lng, 10);
        if (empty($nearbyCustomerIds->toArray())) return;
        $businessName = DB::table('users')->where('id', $businessId)->value('name');
        $title = $reward ? "New Reward from {$businessName}: {$reward->reward_name}" : 'Reward Near You!';
        $body  = $reward ? (strlen($reward->description) > 50 ? substr($reward->description, 0, 50) . '...' : ($reward->description ?? 'New reward near you, please redeem it')) : 'New reward near you, please redeem it';
        $image = isset($reward->banner_image) ? asset('storage/' . $reward->banner_image) : url('storage/notifications/notification.png');
        $fcmTokens = DB::table('customer_profiles')->whereIn('customer_id', $nearbyCustomerIds)->whereNotNull('fcm_token')->pluck('fcm_token', 'customer_id');
        foreach ($fcmTokens as $customerId => $token) {
            try {
                $this->fcmService->sendToCustomerApp($token, $title, $body, $image);
            } catch (\Throwable $e) {
                \Log::error("Nearby reward FCM failed for customer {$customerId}: " . $e->getMessage());
            }
            customerNotification($customerId, $body, 'push', null, true);
        }
    }

    private function sendRedemptionNotifications(int $customerId, int $businessUserId): void
    {
        $image = url('storage/notifications/notification.png');
        $customerInfo = DB::table('customer_profiles')->where('customer_id', $customerId)->first();
        $businessInfo = DB::table('business_profiles')->where('business_id', $businessUserId)->first();
        $customerFcmToken = $customerInfo?->fcm_token;
        $businessFcmToken = $businessInfo?->fcm_token;
        if (!empty($customerFcmToken)) {
            try {
                $this->fcmService->sendToCustomerApp(
                    $customerFcmToken,
                    'Reward Claimed',
                    'Show this at the store to redeem.',
                    $image
                );
            } catch (\Throwable $e) {
                \Log::error("Redemption FCM failed for customer {$customerId}: " . $e->getMessage());
            }
            customerNotification($customerId, 'Reward Claimed', 'push', null, true);
        }
        if (!empty($businessFcmToken)) {
            try {
                $this->fcmService->sendToBusinessApp(
                    $businessFcmToken,
                    'Reward Redeemed',
                    'A customer has claimed their reward.',
                    $image
                );
            } catch (\Throwable $e) {
                \Log::error("Redemption FCM failed for business {$businessUserId}: " . $e->getMessage());
            }
            createNotification($businessUserId, 'Reward Redeemed', 'push', null, true);
        }
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(bin2hex(random_bytes(6)));
        } while (RewardRedemption::where('redemption_code', $code)->exists());

        return $code;
    }
}