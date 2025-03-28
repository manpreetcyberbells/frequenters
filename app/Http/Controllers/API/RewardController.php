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
use Carbon\Carbon;

class RewardController extends Controller
{
    /**
     * Private Function for handle Rewad Redemptions
     * 
    */
    private function processRewardRedemption($businessId, $rewardId)
    {
        // Fetch reward details
        $reward = DB::table('rewards')->where('id', $rewardId)->first();
        if (!$reward) {
            return response()->json(['error' => 'Reward not found'], 404);
        }

        // Check for existing pending redemption
        $existingRedemption = DB::table('reward_redemptions')
            ->where('business_id', $businessId)
            ->where('reward_id', $rewardId)
            ->where('status', 'pending')
            ->first();

        if ($existingRedemption) {
            return $this->generateQrResponse($businessId, $rewardId, $existingRedemption->redemption_code, 'Existing redemption reused.');
        }

        // Create new redemption entry
        $redemptionCode = Str::random(10);
        DB::table('reward_redemptions')->insert([
            // 'customer_id' => $customerId,
            'reward_id' => $rewardId,
            'business_id' => $businessId,
            'points_redeemed' => $reward->points_required,
            'status' => 'pending',
            'redemption_code' => $redemptionCode,
            'created_at' => now(),
        ]);

        return $this->generateQrResponse($businessId, $rewardId, $redemptionCode, 'New redemption created.');
    }

    /**
     * Private Function to Gererate QR for Reward
     * 
    */
    private function generateQrResponse($businessId, $rewardId, $redemptionCode, $message)
    {
        $encryptedPayload = Crypt::encryptString(json_encode([
            'type' => 'reward_redemption',
            'business_id' => $businessId,
            'reward_id' => $rewardId,
            'redemption_code' => $redemptionCode,
        ]));

        $qrCode = base64_encode(QrCode::size(300)->generate($encryptedPayload));
        $rewardCheck = DB::table('rewards')->where('id', $rewardId)->first();
        // Generate QR Code if not exists
        if (!$rewardCheck->qr_code || !Storage::disk('public')->exists($rewardCheck->qr_code)) {
            // Create a safe filename for the QR code
            $safeFilename = hash('sha256', $encryptedPayload);
            $path = 'qrcodes/rewards/' . $safeFilename . '.svg';
            Storage::disk('public')->put($path, QrCode::format('svg')->size(300)->generate($encryptedPayload));
            
            $updated = DB::table('rewards')->where('id', $rewardId)
                        ->update([
                            'qr_code' => asset('storage/' . $path),
                            'updated_at' => now(),
                        ]);
            
            Log::info('Reward QR created for Business:', ['Business' => $rewardId]);
        }

        // Get QR
        $qrCode = DB::table('rewards')->where('id', $rewardId)->pluck('qr_code')->first();

        return response()->json([
            'qr_code' => $qrCode,
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
        ]);

        $businessId = Auth::user()->businessProfile->business_id;
        
        /*$activeReward = DB::table('rewards')
        ->where('business_id', $businessId)
        ->where(function ($query) {
            $query->whereNull('valid_until')
                    ->orWhere(function ($subQuery) {
                        $subQuery->whereDate('valid_from', '<=', Carbon::today())->whereDate('valid_until', '>=', Carbon::today());
                    });

        })
        ->exists();

        if ($activeReward) {
            return response()->json([
                'message' => 'You already have an active reward. Please wait until it expires before creating a new one.',
                'active_reward' => $activeReward
            ], 400);
        }*/

        try {
            // Insert reward into the database
            $rewardId = DB::table('rewards')->insertGetId([
                'business_id' => $businessId,
                'reward_name' => $validated['reward_name'],
                'points_required' => $validated['points_required'],
                'valid_from' => $validated['valid_from'],
                'valid_until' => $validated['valid_until'],
                'created_at' => now(),
            ]);

            $businessId = Auth::id();

            return $this->processRewardRedemption($businessId, $rewardId);

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
            'customer_id' => 'required|exists:customer_profiles,customer_id'
        ]);

        try { 
            $decryptedPayload = json_decode(Crypt::decryptString($validated['qr_code']), true);
            $customer_id = $request->customer_id;
            // Ensure the reward belongs to the logged-in business
            if ($decryptedPayload['business_id'] !== $user->id) {
                return response()->json(['message' => 'You can only redeem rewards created by your business'], 403);
            }

            $customerBalance = CustomerProfile::where('customer_id',$customer_id)->pluck('points_balance')->first();
            if (!$customerBalance) {
                return response()->json(['message' => 'Customer not found or invalid balance'], 404);
            }

            $reward = DB::table('reward_redemptions')
                ->where('reward_id',$decryptedPayload['reward_id'])
                ->where('business_id',$user->id)
                ->where('redemption_code',$decryptedPayload['redemption_code'])
                ->where('status','pending')
                ->first();

            if (!$reward) {
                return response()->json(['message' => 'Invalid reward redemption details'], 400);
            }

            // Validate if customer has enough balance for redemption
            if ($customerBalance < $reward->points_redeemed) {
                return response()->json(['message' => 'Insufficient points balance'], 400);
            }

            // Process reward redemption
            DB::table('customer_profiles')->where('customer_id', $customer_id)->decrement('points_balance', $reward->points_redeemed);

            DB::table('reward_redemptions')->where('reward_id', $decryptedPayload['reward_id'])->update(['customer_id' => $customer_id, 'status' => 'success', 'updated_at' => now()]);

            return response()->json(['message' => 'Reward redeemed successfully'], 200);

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

        try{

            $today = now();
            $customerId = $user->id;
            // Get total number of check-ins by the customer
            $checkInCount = DB::table('checkins')->where('customer_id', $customerId)->count();
            // Get unique businesses visited by the customer
            $businessVisitedCount = DB::table('checkins')->where('customer_id', $customerId)->distinct('business_id')->count('business_id');

            // Fetch active rewards for the business (only with pending redemptions)
            $activeRewards = DB::table('rewards')
                ->leftJoin('reward_redemptions', function ($join) {
                    $join->on('rewards.id', '=', 'reward_redemptions.reward_id')
                        ->where('reward_redemptions.status', '=', 'pending');
                })
                ->whereDate('rewards.valid_from', '<=', $today)
                ->whereDate('rewards.valid_until', '>=', $today)
                ->select('rewards.*', DB::raw('COUNT(CASE WHEN reward_redemptions.status = "pending" THEN 1 END) as pending_redemptions'))
                ->groupBy('rewards.id')
                ->having('pending_redemptions', '>', 0)
                ->get();

            // Fetch redeemed rewards of the logged-in customer (where status is "success")
            $redeemedRewards = DB::table('rewards')
                ->join('reward_redemptions', 'rewards.id', '=', 'reward_redemptions.reward_id')
                ->where('reward_redemptions.customer_id', $customerId)
                ->where('reward_redemptions.status', 'success')
                ->select('rewards.*', 'reward_redemptions.redeemed_at')
                ->get();
            
            return response()->json([
                'business_visited' => $businessVisitedCount,
                'check_ins' => $checkInCount,
                'active_rewards' => $activeRewards,
                'redeemed_rewards' => $redeemedRewards,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error creating reward: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create reward.'], 500);
        }
    }

    /**
     * API Function Fetch Reward Detail
    */
    public function fetchSingleReward($id)
    {
        try{
            $rewards = DB::table('rewards')->where('id',$id)->get();

            return response()->json([ 'rewards' => $rewards ], 200);

        } catch (\Exception $e) {
            Log::error('Error creating reward: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create reward.'], 500);
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
