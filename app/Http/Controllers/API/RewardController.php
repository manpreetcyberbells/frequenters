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
use Carbon\Carbon;

class RewardController extends Controller
{
    /**
     * Private Function for handle Rewad Redemptions
     * 
    */
    private function processRewardRedemption($customerId, $rewardId)
    {
        // Fetch reward details
        $reward = DB::table('rewards')->where('id', $rewardId)->first();
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
            return $this->generateQrResponse($customerId, $rewardId, $existingRedemption->redemption_code, 'Existing redemption reused.');
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

        return $this->generateQrResponse($customerId, $rewardId, $redemptionCode, 'New redemption created.');
    }

    /**
     * Private Function to Gererate QR for Reward
     * 
    */
    private function generateQrResponse($customerId, $rewardId, $redemptionCode, $message)
    {
        $encryptedPayload = Crypt::encryptString(json_encode([
            'type' => 'reward_redemption',
            'customer_id' => $customerId,
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

        $customerId = Auth::id();
        $rewardId = $validated['reward_id'];

        return $this->processRewardRedemption($customerId, $rewardId);
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
        
        $activeReward = DB::table('rewards')
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
        }

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

            return $this->processRewardRedemption(Auth::id(), $rewardId);

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
