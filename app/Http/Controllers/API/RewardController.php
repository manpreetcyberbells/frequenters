<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB; // Import DB Facade
use Illuminate\Support\Facades\Crypt;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\CustomerProfile;
use App\Models\User;
use Carbon\Carbon;
use App\Services\FCMService;
use App\Services\NearbyCustomerService;

class RewardController extends Controller
{
    /**
     * Private Function for handle Rewad Redemptions
     * 
    */
    private function processRewardRedemption($businessId, $rewardId, $customerTiers = [])
    {
        $reward = DB::table('rewards')->where('id', $rewardId)->first();
        if (!$reward) {
            return response()->json(['error' => 'Reward not found'], 404);
        }

        // Store customer tiers in the QR code if provided
        $qrPayload = [
            'type' => 'reward_redemption',
            'business_id' => $businessId,
            'reward_id' => $rewardId,
            'redemption_code' => Str::random(10),
        ];

        if (!empty($customerTiers)) {
            $qrPayload['customer_tiers'] = $customerTiers;
        }

        return $this->generateQrResponse($businessId, $rewardId, $qrPayload, 'Reward created successfully.');
    }


    /**
     * Private Function to Gererate QR for Reward
     * 
    */
    private function generateQrResponse($businessId, $rewardId, $qrPayload, $message)
    {
        $encryptedPayload = Crypt::encryptString(json_encode($qrPayload));
        $rewardCheck = DB::table('rewards')->where('id', $rewardId)->first();
        // // Generate QR Code if not exists
        // if (!$rewardCheck->qr_code || !Storage::disk('public')->exists($rewardCheck->qr_code)) {
        //     $safeFilename = hash('sha256', $encryptedPayload);
        //     $path = 'qrcodes/rewards/' . $safeFilename . '.svg';
        //     Storage::disk('public')->put($path, QrCode::format('svg')->size(300)->generate($encryptedPayload));
            
        //     $updated = DB::table('rewards')->where('id', $rewardId)
        //                 ->update([
        //                     'qr_code' => asset('storage/' . $path),
        //                     'updated_at' => now(),
        //                 ]);
            
        //     Log::info('Reward QR created for Business:', ['Business' => $rewardId]);
        // }
        
        // Insert value of QR payload // We will using on Customer app.
        $updatePayload = DB::table('rewards')->where('id', $rewardId)->update([
            'qr_code_payload' => json_encode($qrPayload)
        ]);

        // Get QR
        // $qrCode = DB::table('rewards')->where('id', $rewardId)->pluck('qr_code')->first();
        // Notification (Testing for all Users // Later allow for special user also)
        // createNotification($businessId, $rewardCheck->reward_name, "push", null, true);

        return response()->json([
            // 'qr_code' => $qrCode,
            'message' => $message,
        ], 200);
    }

    /**
     * API Function to hanle // create Reward and QR
     * 
    */
    public function generateRedemptionQrCodeApi(Request $request)
    {
        $validated = $request->validate([
            'reward_id' => 'required|integer',
        ]);

        $businessId = Auth::id();
        $rewardId = $validated['reward_id'];

        return $this->processRewardRedemption($businessId, $rewardId);
    }

    /**
     * API Function Create Reward
     * As of now No Limit // No RewardConditions
    */
    public function storeRewardApi(Request $request)
    {
        $businessRole = auth()->user();
        if ($businessRole->role !== 'business') {
            return response()->json(['message' => 'Please login with a valid Business account'], 403);
        }

        $validated = $request->validate([
            'reward_name' => 'required|string|max:255',
            'points_required' => 'required|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'customer_tiers' => 'nullable|array',
            'customer_tiers.*' => 'in:new,regular,frequenter'
        ]);

        $businessId = Auth::user()->businessProfile->business_id;

        try {
            $rewardId = DB::table('rewards')->insertGetId([
                'business_id' => $businessId,
                'reward_name' => $validated['reward_name'],
                'points_required' => $validated['points_required'],
                'valid_from' => $validated['valid_from'],
                'valid_until' => $validated['valid_until'],
                'created_at' => now(),
            ]);


            $businessAddress = DB::table('business_addresses')->where('business_id',$businessId)->first();
            $radius = 10; // kilometers
            $latitude = @$businessAddress->latitude;
            $longitude = @$businessAddress->longitude;

            $nearbyService = new NearbyCustomerService();
            $nearbyCustomerIds = $nearbyService->getNearbyCustomerIds($latitude, $longitude, $radius);

            $fcmService = new FCMService();
            $title = "Reward";
            $body = "New reward near you, please redeem it";
            $image = asset('storage/notifications/notification.png');

            foreach ($nearbyCustomerIds as $customerId) {
                $fcmToken = DB::table('customer_profiles')->where('customer_id',$customerId)->pluck('fcm_token')->first();
                // Sending Notification on Device
                $fcmService->sendNotificationToDevice($fcmToken, $title, $body, $image);

                // Store into DB
                customerNotification($customerId, $body, "push", null, true);
            }

            return $this->processRewardRedemption($businessId, $rewardId, $validated['customer_tiers'] ?? []);

        } catch (\Exception $e) {
            Log::error('Error creating reward: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create reward.'], 500);
        }
    }

    /**
     * API Function for Reward Redemptions
    */
    public function rewardRedemptions(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'business') {
            return response()->json(['message' => 'Please login with a valid Business account'], 403);
        }

        $validated = $request->validate([
            'qr_code' => 'required',
            // 'customer_id' => 'required|exists:customer_profiles,customer_id'
        ]);

        try {
            $decryptedPayload = json_decode(Crypt::decryptString($validated['qr_code']), true);
            // echo"<pre>"; print_r($decryptedPayload); die;
            $customerId = $decryptedPayload['customer_id'];

            if ($decryptedPayload['business_id'] !== $user->id) {
                return response()->json(['message' => 'You can only redeem rewards created by your business'], 403);
            }

            // Get customer tier from database
            $customerTier = DB::table('customer_business_relationships')->where('business_id', $user->id)->where('customer_id', $customerId)->value('customer_tier');
            // Check if the QR code has tier restrictions
            if (!empty($decryptedPayload['customer_tiers']) && !in_array($customerTier, $decryptedPayload['customer_tiers'])) {
                return response()->json(['message' => 'This reward is not available for your customer tier'], 403);
            }

            // Check customer's points balance
            $customerBalance = DB::table('customer_profiles')->where('customer_id', $customerId)->value('points_balance');
            if (!$customerBalance) {
                return response()->json(['message' => 'Customer not found or invalid balance'], 404);
            }

            // Fetch reward details
            $reward = DB::table('rewards')->where('id', $decryptedPayload['reward_id'])->where('business_id', $user->id)->first();
            if (!$reward) {
                return response()->json(['message' => 'Invalid reward details'], 400);
            }

            if ($customerBalance < $reward->points_required) {
                return response()->json(['message' => 'Insufficient points balance'], 400);
            }

            // Process redemption - deduct points and insert redemption record
            DB::beginTransaction();

            try {
                DB::table('customer_profiles')->where('customer_id', $customerId)->decrement('points_balance', $reward->points_required);

                DB::table('reward_redemptions')->insert([
                    'customer_id' => $customerId,
                    'reward_id' => $reward->id,
                    'business_id' => $user->id,
                    'points_redeemed' => $reward->points_required,
                    'status' => 'success',
                    'redemption_code' => $decryptedPayload['redemption_code'],
                    'created_at' => now(),
                ]);

                DB::commit();

                // FCM Notifications
                $fcmService = new FCMService();
                $customerInfo = DB::table('customer_profiles')->where('customer_id', $customerId)->first();
                $businessInfo = DB::table('business_profiles')->where('business_id', $user->id)->first();
                $businessFcmToken = $customerInfo->fcm_token;
                $customerFcmToken = $businessInfo->fcm_token;
                $image = asset('storage/notifications/notification.png');
                
                if ($customerFcmToken) {
                    $customerTitle = "Reward Claimed";
                    $customerBody = "Show this at the store to redeem.";
                    $fcmService->sendNotificationToDevice($customerFcmToken, $customerTitle, $customerBody, $image);
                    // Save into DB
                    customerNotification($customerId, $customerBody, "push", null, true);
                }
            
                if ($businessFcmToken) {
                    $businessTitle = "Reward Redeemed";
                    $businessBody = "A customer has claimed their reward.";
                    $fcmService->sendNotificationToDevice($businessFcmToken, $businessTitle, $businessBody, $image);
                    // Save into DB
                    createNotification($user->id, $businessBody, "push", null, true);
                }

                return response()->json(['message' => 'Reward redeemed successfully'], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['message' => 'Something went wrong, please try again.'], 500);
            }

        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid or tampered QR Code'], 400);
        }
    }


    /**
     * API Function for Get Rewards
    */
    public function getBusinessRewards(Request $request)
    {
        $user = auth()->user();
        $customer = CustomerProfile::where('customer_id', $user->id)->first();
        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        try {
            $today = now();
            $customerId = $user->id;

            // Get total number of check-ins by the customer
            $checkInCount = DB::table('checkins')->where('customer_id', $customerId)->count();

            // Get unique businesses visited by the customer
            $businessVisitedCount = DB::table('checkins')
                ->where('customer_id', $customerId)
                ->distinct('business_id')
                ->count('business_id');

            // Fetch businesses that have active reward
            $activeBusinesses = DB::table('rewards')
                ->join('users', 'rewards.business_id', '=', 'users.id')
                ->whereDate('rewards.valid_from', '<=', $today)
                ->whereDate('rewards.valid_until', '>=', $today)
                ->whereNull('rewards.expiry_date')
                ->select('users.id as business_id', 'users.name', DB::raw('COUNT(rewards.id) as total_rewards'))
                ->groupBy('users.id', 'users.name')
                ->get();

            // Fetch redeemed rewards of Customer
            $redeemedRewards = DB::table('reward_redemptions')
                ->join('rewards', 'reward_redemptions.reward_id', '=', 'rewards.id')
                ->join('users', 'rewards.business_id', '=', 'users.id')
                ->where('reward_redemptions.customer_id', $customerId)
                ->where('reward_redemptions.status', 'success')
                ->select(
                    'users.name as business_name',
                    'users.profile_image',
                    'reward_redemptions.points_redeemed',
                    'reward_redemptions.redeemed_at'
                )
                ->orderBy('reward_redemptions.redeemed_at', 'desc')
                ->get();

            $formattedRewards = $redeemedRewards->map(function ($reward) {
                return [
                    'business_name' => $reward->business_name,
                    'business_logo' => $reward->profile_image ?? '',
                    'message' => "You have earned {$reward->points_redeemed} points",
                    'points' => "+{$reward->points_redeemed}"
                ];
            });

            return response()->json([
                'business_visited' => $businessVisitedCount,
                'check_ins' => $checkInCount,
                'active_businesses' => $activeBusinesses,
                'redeemed_rewards' => $formattedRewards,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching business rewards: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get rewards.'], 500);
        }
    }

    /**
     * API Function Fetch all Rewards by BusinessID
    */
    public function getRewardByBusiness($id)
    {
        try {
            $rewards = DB::table('rewards')->where('business_id', $id)->get();
            if ($rewards->isEmpty()) {
                return response()->json(['error' => 'No rewards found for this business'], 404);
            }

            $today = now()->startOfDay();
            foreach ($rewards as $reward) {
                $business = User::where('id', $reward->business_id)->first();
                $reward->business_name = optional($business)->name;
                if (!empty($reward->valid_until)) {
                    $validUntil = \Carbon\Carbon::parse($reward->valid_until)->startOfDay();
                    $daysLeft = max(0, $today->diffInDays($validUntil));
                } else {
                    $daysLeft = null;
                }
                $reward->days_left = $daysLeft;
            }

            return response()->json(['rewards' => $rewards], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching rewards: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch rewards.'], 500);
        }
    }

    /**
     * API Function Fetch Reward Detail
    */
    public function fetchSingleReward($id)
    {
        try {
            $reward = DB::table('rewards')->where('id', $id)->first();
            if (!$reward) {
                return response()->json(['error' => 'Invalid Reward or Reward not found'], 404);
            }

            $originalPayload = json_decode($reward->qr_code_payload, true);
            $originalPayload['customer_id'] = auth()->user()->id;
            $encryptedPayload = Crypt::encryptString(json_encode($originalPayload));
            // echo"<pre>"; print_r($originalPayload);  echo"</br>";
            // echo"<pre>"; print_r($encryptedPayload);  die;

            // Generate QR Code if not exists
            // if (!$reward->qr_code || !Storage::disk('public')->exists($reward->qr_code)) {
                $safeFilename = hash('sha256', $encryptedPayload);
                $path = 'qrcodes/rewards/' . $safeFilename . '.svg';
                Storage::disk('public')->put($path, QrCode::format('svg')->size(300)->generate($encryptedPayload));
                
                // $updated = DB::table('rewards')->where('id', $id)
                //             ->update([
                //                 'qr_code' => asset('storage/' . $path),
                //                 'updated_at' => now(),
                //             ]);
                
                // Log::info('Reward QR created for Business:', ['Business' => $id]);
            // }

            $customer_qr = [
                'customer_id' => auth()->user()->id,
                'reward_id' => $reward->id,
                'encrypted_payload' => $encryptedPayload,
                'qr_path' => $path,
            ];

            // QR code Log
            Log::build([
                'driver' => 'single',
                'path' => storage_path('logs/customer_qr.log'),
            ])->info('Customers QRCode:', ['CustomerQR' => $customer_qr]);

            $rewardUpdatedInfo = DB::table('rewards')->where('id', $id)->first();
            $business = User::where('id', $reward->business_id)->first();
            $rewardArray = (array) $rewardUpdatedInfo;
            $rewardArray['qr_code'] = asset('storage/' . @$path);
            $rewardArray['business_name'] = $business ? $business->name : null;
            return response()->json(['reward' => $rewardArray], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching reward: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get reward.'], 500);
        }
    }


    /**
     * Web
    */
    public function generateRedemptionQrCode($rewardId)
    {
        $customerId = Auth::id();

        // Fetch reward details
        $reward = DB::table('rewards')->where('reward_id', $rewardId)->first();
        if (!$reward) {
            abort(404, 'Reward not found');
        }

        // Check for existing pending redemption
        $existingRedemption = DB::table('reward_redemptions')
            ->where('customer_id', $customerId)
            ->where('reward_id', $rewardId)
            ->where('status', 'pending')
            ->first();

        if ($existingRedemption) {
            $encryptedPayload = Crypt::encryptString(json_encode([
                'type' => 'reward_redemption',
                'customer_id' => $customerId,
                'reward_id' => $rewardId,
                'redemption_code' => $existingRedemption->redemption_code,
            ]));
            $qrCode = QrCode::size(300)->generate($encryptedPayload);

            return view('customer.reward-qr', compact('qrCode'));
        }

        // Create new redemption entry
        $redemptionCode = Str::random(10);
        DB::table('reward_redemptions')->insert([
            'customer_id' => $customerId,
            'reward_id' => $rewardId,
            'business_id' => $reward->business_id,
            'points_redeemed' => $reward->points_required,
            'status' => 'pending',
            'redemption_code' => $redemptionCode,
            'created_at' => now(),
        ]);

        $encryptedPayload = Crypt::encryptString(json_encode([
            'type' => 'reward_redemption',
            'customer_id' => $customerId,
            'reward_id' => $rewardId,
            'redemption_code' => $redemptionCode,
        ]));
        $qrCode = QrCode::size(300)->generate($encryptedPayload);

        return view('customer.reward-qr', compact('qrCode'));
    }


    /*public function generateRedemptionQrCodeApi(Request $request)
    {
        $validated = $request->validate([
            'reward_id' => 'required|integer',
        ]);

        $customerId = Auth::id();
        $rewardId = $validated['reward_id'];

        // Fetch reward details
        $reward = DB::table('rewards')->where('reward_id', $rewardId)->first();
        if (!$reward) {
            return response()->json(['error' => 'Reward not found'], 404);
        }

        // Check for existing pending redemption
        $existingRedemption = DB::table('reward_redemptions')
            ->where('customer_id', $customerId)
            ->where('reward_id', $rewardId)
            ->where('status', 'pending')
            ->first();

        if ($existingRedemption) {
            $encryptedPayload = Crypt::encryptString(json_encode([
                'type' => 'reward_redemption',
                'customer_id' => $customerId,
                'reward_id' => $rewardId,
                'redemption_code' => $existingRedemption->redemption_code,
            ]));
            $qrCode = base64_encode(QrCode::size(300)->generate($encryptedPayload));

            return response()->json([
                'qr_code' => $qrCode,
                'message' => 'Existing redemption reused.',
            ], 200);
        }

        // Create new redemption entry
        $redemptionCode = Str::random(10);
        DB::table('reward_redemptions')->insert([
            'customer_id' => $customerId,
            'reward_id' => $rewardId,
            'business_id' => $reward->business_id,
            'points_redeemed' => $reward->points_required,
            'status' => 'pending',
            'redemption_code' => $redemptionCode,
            'created_at' => now(),
        ]);

        $encryptedPayload = Crypt::encryptString(json_encode([
            'type' => 'reward_redemption',
            'customer_id' => $customerId,
            'reward_id' => $rewardId,
            'redemption_code' => $redemptionCode,
        ]));
        $qrCode = base64_encode(QrCode::size(300)->generate($encryptedPayload));

        return response()->json([
            'qr_code' => $qrCode,
            'message' => 'New redemption created.',
        ], 201);
    }*/


}
