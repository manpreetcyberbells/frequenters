<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use App\Models\CheckIn;
use App\Models\CustomerBusinessRelationship;
use App\Models\CustomerProfile;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckInController extends Controller
{
    // Both naming variants accepted for backward compat
    private const CUSTOMER_QR_TYPES = ['customer_qr', 'customer_checkin'];
    private const BUSINESS_QR_TYPES = ['business_qr', 'business_checkin'];

    public function __construct(
        private readonly FCMService $fcmService
    ) {}

    /**
     * Scanner: Business (or its staff)
     * Scans:   Customer's QR
     * Route:   POST /checkin/scan-customer-qr   (alias: /checkin/customer)
     * Auth:    Must be business owner or staff
     */
    public function customerCheckIn(Request $request)
    {
        $validated = $request->validate([
            'qr_data' => 'required|string',
        ]);

        $user = Auth::user();
        if (!$user || !$user->canActAsBusiness()) {
            return response()->json(['message' => 'Business access required.'], 403);
        }

        $businessId = $user->effectiveBusinessId();

        try {
            $payload = json_decode(Crypt::decryptString($validated['qr_data']), true);

            if (!in_array($payload['type'] ?? '', self::CUSTOMER_QR_TYPES, true)) {
                throw new \Exception('Not a valid customer QR code.');
            }

            $customerProfile = CustomerProfile::where('customer_uid', $payload['customer_uid'] ?? null)->first();
            if (!$customerProfile) {
                throw new \Exception('Customer not found for this QR.');
            }

            return $this->finalizeCheckIn($customerProfile->customer_id, $businessId, $user->id);

        } catch (\Exception $e) {
            Log::error('customerCheckIn failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'effective_business_id' => $businessId,
            ]);
            return response()->json(['message' => 'Invalid or tampered QR Code'], 400);
        }
    }

    /**
     * Scanner: Customer
     * Scans:   Business's QR
     * Route:   POST /checkin/scan-business-qr   (alias: /checkin/business)
     * Auth:    Must be a logged-in customer
     */
    public function businessCheckIn(Request $request)
    {
        $validated = $request->validate([
            'qr_data' => 'required|string',
        ]);

        $user = Auth::user();
        if (!$user || $user->role !== 'customer') {
            return response()->json(['message' => 'Customer login required.'], 403);
        }

        $customerId = $user->id;

        try {
            $payload = json_decode(Crypt::decryptString($validated['qr_data']), true);

            if (!in_array($payload['type'] ?? '', self::BUSINESS_QR_TYPES, true)) {
                throw new \Exception('Not a valid business QR code.');
            }

            $businessInfo = BusinessProfile::where('business_uid', $payload['business_uid'] ?? null)->first();
            if (!$businessInfo) {
                throw new \Exception('Business not found for this QR.');
            }

            return $this->finalizeCheckIn($customerId, $businessInfo->business_id, $user->id);

        } catch (\Exception $e) {
            Log::error('businessCheckIn failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
            return response()->json(['message' => 'Invalid or tampered QR Code'], 400);
        }
    }

    /**
     * Fallback path — no QR scan, paste a UID
     * Route:   POST /checkin/by-uid
     * Auth:    Either customer OR business/staff (decided by `type`)
     *
     * type=scan_business → I am customer, here is business_uid
     * type=scan_customer → I am business/staff, here is customer_uid
     * (also accepts legacy type=customer_checkin / business_checkin)
     */
    public function checkInWithUID(Request $request)
    {
        $validated = $request->validate([
            'type'         => 'required|in:scan_business,scan_customer,customer_checkin,business_checkin',
            'customer_uid' => 'required_if:type,scan_customer,business_checkin|string',
            'business_uid' => 'required_if:type,scan_business,customer_checkin|string',
        ]);

        // Normalize type
        $intent = in_array($validated['type'], ['scan_business', 'customer_checkin'], true)
            ? 'customer_scans_business'
            : 'business_scans_customer';

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        try {
            if ($intent === 'customer_scans_business') {
                if ($user->role !== 'customer') {
                    return response()->json(['message' => 'Customer login required.'], 403);
                }
                $business = BusinessProfile::where('business_uid', $validated['business_uid'])->first();
                if (!$business) return response()->json(['message' => 'Invalid business UID'], 400);

                return $this->finalizeCheckIn($user->id, $business->business_id, $user->id);
            }

            // business_scans_customer
            if (!$user->canActAsBusiness()) {
                return response()->json(['message' => 'Business access required.'], 403);
            }
            $customer = CustomerProfile::where('customer_uid', $validated['customer_uid'])->first();
            if (!$customer) return response()->json(['message' => 'Invalid customer UID'], 400);

            return $this->finalizeCheckIn($customer->customer_id, $user->effectiveBusinessId(), $user->id);

        } catch (\Exception $e) {
            Log::error('checkInWithUID failed', ['error' => $e->getMessage(), 'user_id' => $user->id]);
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    /**
     * Core check-in logic + notifications.
     * Single source of truth — same flow for all 3 entry points.
     */
    private function finalizeCheckInV0(int $customerId, int $businessId)
    {
        try {
            $business = BusinessProfile::where('business_id', $businessId)->first();
            if (!$business) {
                return response()->json(['error' => 'Business not found.'], 404);
            }

            // $alreadyCheckedIn = CheckIn::where('customer_id', $customerId)->where('business_id', $businessId)->whereDate('check_in_time', today())->exists();
            // if ($alreadyCheckedIn) {
            //     return response()->json([
            //         'error' => 'You have already checked in today. Please come back tomorrow!',
            //     ], 429);
            // }

            $allowedUsers = [116, 89];
            $alreadyCheckedIn = CheckIn::where('customer_id', $customerId)->where('business_id', $businessId)->whereDate('check_in_time', today())->exists();
            if ($alreadyCheckedIn && !in_array($customerId, $allowedUsers)) {
                return response()->json([
                    'error' => 'You have already checked in today. Please come back tomorrow!',
                ], 429);
            }

            if ($business->points_per_checkin <= 0) {
                return response()->json(['error' => 'This business has not configured check-in points yet.'], 400);
            }

            if ($business->points_per_checkin > $business->monthly_points_available) {
                return response()->json(['error' => 'Business does not have enough points available.'], 400);
            }

            $points = $business->points_per_checkin;

            DB::transaction(function () use ($customerId, $businessId, $business, $points) {

                CheckIn::create([
                    'customer_id'   => $customerId,
                    'business_id'   => $businessId,
                    'check_in_time' => now(),
                ]);

                // Relationship
                $relationship = DB::table('customer_business_relationships')
                    ->where('customer_id', $customerId)
                    ->where('business_id', $businessId)
                    ->first();

                if (!$relationship) {
                    DB::table('customer_business_relationships')->insert([
                        'customer_id'           => $customerId,
                        'business_id'           => $businessId,
                        'interaction_count'     => 1,
                        'last_interaction_date' => now(),
                        'total_points_earned'   => $points,
                        'customer_tier' => $business->tierFor(1),
                        'created_at'            => now(),
                    ]);
                } else {
                    $newCount = $relationship->interaction_count + 1;
                    DB::table('customer_business_relationships')
                        ->where('id', $relationship->id)
                        ->update([
                            'interaction_count'     => $newCount,
                            'last_interaction_date' => now(),
                            'total_points_earned'   => $relationship->total_points_earned + $points,
                            'customer_tier' => $business->tierFor($newCount),
                        ]);
                }

                // Points log
                DB::table('points')->insert([
                    'customer_id'   => $customerId,
                    'business_id'   => $businessId,
                    'points_earned' => $points,
                    'description'   => 'Check-in reward',
                    'created_at'    => now(),
                ]);

                // Customer balance
                DB::table('customer_profiles')->where('customer_id', $customerId)
                    ->increment('points_balance', $points, ['total_rewards_earned' => DB::raw("total_rewards_earned + $points")]);

                // Business metrics
                DB::table('business_profiles')->where('business_id', $businessId)->update([
                    'monthly_points_available' => $business->monthly_points_available - $points,
                    'points_given'             => $business->points_given + $points,
                    'total_checkins'           => $business->total_checkins + 1,
                ]);

                // Interaction history
                DB::table('customer_business_interactions')->insert([
                    'customer_id'           => $customerId,
                    'business_id'           => $businessId,
                    'interaction_type'      => 'check_in',
                    'points_allocated'      => $points,
                    'interaction_count'     => 1,
                    'last_interaction_date' => now(),
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);
            });

            // Notifications — outside DB transaction
            $this->sendCheckInNotifications($customerId, $business, $points);

            return response()->json([
                'message'        => 'Check-in successful.',
                'points_awarded' => $points,
            ]);

        } catch (\Exception $e) {
            Log::error('finalizeCheckIn failed', [
                'error'       => $e->getMessage(),
                'customer_id' => $customerId,
                'business_id' => $businessId,
            ]);
            return response()->json(['error' => 'Failed to process check-in.'], 500);
        }
    }

    /**
     * Core check-in logic + notifications.
     * Single source of truth — same flow for all 3 entry points.
     *
     * @param int $customerId
     * @param int $businessId
     * @param int|null $scannedByUserId  — the user who performed the scan (for "Allotted By")
     */
    private function finalizeCheckIn(int $customerId, int $businessId, ?int $scannedByUserId = null)
    {
        try {
            $business = BusinessProfile::where('business_id', $businessId)->first();
            if (!$business) {
                return response()->json(['error' => 'Business not found.'], 404);
            }

            $allowedUsers = [116, 89];
            $alreadyCheckedIn = CheckIn::where('customer_id', $customerId)
                ->where('business_id', $businessId)
                ->whereDate('check_in_time', today())
                ->exists();

            if ($alreadyCheckedIn && !in_array($customerId, $allowedUsers)) {
                return response()->json([
                    'error' => 'You have already checked in today. Please come back tomorrow!',
                ], 429);
            }

            if ($business->points_per_checkin <= 0) {
                return response()->json(['error' => 'This business has not configured check-in points yet.'], 400);
            }

            if ($business->points_per_checkin > $business->monthly_points_available) {
                return response()->json(['error' => 'Business does not have enough points available.'], 400);
            }

            $points = $business->points_per_checkin;
            $checkInTime = now();

            DB::transaction(function () use ($customerId, $businessId, $business, $points, $checkInTime) {

                CheckIn::create([
                    'customer_id'   => $customerId,
                    'business_id'   => $businessId,
                    'check_in_time' => $checkInTime,
                ]);

                $relationship = DB::table('customer_business_relationships')
                    ->where('customer_id', $customerId)
                    ->where('business_id', $businessId)
                    ->first();

                if (!$relationship) {
                    DB::table('customer_business_relationships')->insert([
                        'customer_id'           => $customerId,
                        'business_id'           => $businessId,
                        'interaction_count'     => 1,
                        'last_interaction_date' => $checkInTime,
                        'total_points_earned'   => $points,
                        'customer_tier'         => $business->tierFor(1),
                        'created_at'            => $checkInTime,
                    ]);
                } else {
                    $newCount = $relationship->interaction_count + 1;
                    DB::table('customer_business_relationships')
                        ->where('id', $relationship->id)
                        ->update([
                            'interaction_count'     => $newCount,
                            'last_interaction_date' => $checkInTime,
                            'total_points_earned'   => $relationship->total_points_earned + $points,
                            'customer_tier'         => $business->tierFor($newCount),
                        ]);
                }

                DB::table('points')->insert([
                    'customer_id'   => $customerId,
                    'business_id'   => $businessId,
                    'points_earned' => $points,
                    'description'   => 'Check-in reward',
                    'created_at'    => $checkInTime,
                ]);

                DB::table('customer_profiles')->where('customer_id', $customerId)
                    ->increment('points_balance', $points, [
                        'total_rewards_earned' => DB::raw("total_rewards_earned + $points"),
                    ]);

                DB::table('business_profiles')->where('business_id', $businessId)->update([
                    'monthly_points_available' => $business->monthly_points_available - $points,
                    'points_given'             => $business->points_given + $points,
                    'total_checkins'           => $business->total_checkins + 1,
                ]);

                DB::table('customer_business_interactions')->insert([
                    'customer_id'           => $customerId,
                    'business_id'           => $businessId,
                    'interaction_type'      => 'check_in',
                    'points_allocated'      => $points,
                    'interaction_count'     => 1,
                    'last_interaction_date' => $checkInTime,
                    'created_at'            => $checkInTime,
                    'updated_at'            => $checkInTime,
                ]);
            });

            // Notifications — outside DB transaction
            $this->sendCheckInNotifications($customerId, $business, $points);

            // Display names
            $customerName = DB::table('users')->where('id', $customerId)->value('name');
            $allottedByName = $scannedByUserId ? DB::table('users')->where('id', $scannedByUserId)->value('name') ?? $business->business_name : $business->business_name;
            return response()->json([
                'message'         => 'Check-in successful.',
                'points_awarded'  => $points,
                'check_in_time'   => $checkInTime->format('d F Y, h:i A'), 
                'customer_name'   => $customerName,
                'business_name'   => $business->business_name,
                'allotted_by'     => $allottedByName,
            ]);

        } catch (\Exception $e) {
            Log::error('finalizeCheckIn failed', [
                'error'       => $e->getMessage(),
                'customer_id' => $customerId,
                'business_id' => $businessId,
            ]);
            return response()->json(['error' => 'Failed to process check-in.'], 500);
        }
    }

    private function sendCheckInNotifications(int $customerId, $business, int $points): void
    {
        $customerName = DB::table('users')->where('id', $customerId)->value('name');
        $image        = url('storage/notifications/notification.png');

        // Notify Business
        if (!empty($business->fcm_token)) {
            try {
                $this->fcmService->sendToBusinessApp(
                    $business->fcm_token,
                    'Check-in Successful',
                    "You allotted {$points} points to {$customerName}",
                    $image
                );
            } catch (\Throwable $e) {
                \Log::error("CheckIn FCM failed for business {$business->business_id}: " . $e->getMessage());
            }
        }
        createNotification(
            $business->business_id,
            "You allotted {$points} points to {$customerName}",
            'push',
            null,
            true
        );

        // Notify Customer
        $fcmToken = CustomerProfile::where('customer_id', $customerId)->value('fcm_token');
        if (!empty($fcmToken)) {
            try {
                $this->fcmService->sendToCustomerApp(
                    $fcmToken,
                    'Check-in Successful',
                    "You got {$points} points from {$business->business_name}",
                    $image
                );
            } catch (\Throwable $e) {
                \Log::error("CheckIn FCM failed for customer {$customerId}: " . $e->getMessage());
            }
        }
        customerNotification(
            $customerId,
            "You got {$points} points from {$business->business_name}",
            'push',
            null,
            true
        );
    }
}