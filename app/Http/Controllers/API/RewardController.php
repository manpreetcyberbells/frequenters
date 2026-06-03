<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CustomerProfile;
use App\Models\Reward;
use App\Models\User;
use App\Services\RewardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RewardController extends Controller
{
    public function __construct(
        private readonly RewardService $rewardService
    ) {}

    // Business: Reward List
    public function rewardListing(Request $request)
    {
        $perPage = $request->integer('per_page', 10);

        $rewards = Reward::where('business_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $transformed = $rewards->map(fn($r) => [
            'id'              => $r->id,
            'business_id'     => $r->business_id,
            'reward_name'     => $r->reward_name,
            'points_required' => $r->points_required,
            'valid_from'      => $r->valid_from,
            'valid_until'     => $r->valid_until,
            'banner_image'    => $r->banner_image,
            'description'     => $r->description,
            'customer_tiers'  => data_get($r->qr_code_payload, 'customer_tiers', []),
            'createdAt'       => Carbon::parse($r->created_at)->diffForHumans(),
        ]);

        return response()->json([
            'message' => 'Rewards retrieved successfully',
            'data'    => [
                'current_page' => $rewards->currentPage(),
                'last_page'    => $rewards->lastPage(),
                'per_page'     => $rewards->perPage(),
                'total'        => $rewards->total(),
                'rewards'      => $transformed,
            ],
        ]);
    }

    // Business: Create Reward
    public function storeRewardApi(Request $request)
    {
        if (Auth::user()->role !== 'business') {
            return response()->json(['message' => 'Please login with a valid Business account'], 403);
        }

        $validated = $request->validate([
            'reward_name'      => 'required|string|max:255',
            // 'points_required'  => 'required|integer|min:1',
            'valid_from'       => 'nullable|date',
            'valid_until'      => 'nullable|date|after_or_equal:valid_from',
            'customer_tiers'   => 'nullable|array',
            'customer_tiers.*' => 'in:new,regular,frequenter',
            'description'    => 'nullable|string|max:255',
            'banner_image'     => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ],
        [
            'banner_image.uploaded' => 'The banner image is too large or failed to upload. Max 2MB.',
            'banner_image.max'      => 'The banner image must not be larger than 2MB.',
        ]);

        $businessId = Auth::id();
        try {
            if ($request->hasFile('banner_image')) {
                $validated['banner_image'] = $request->file('banner_image')->store('rewards/banners', 'public');
            } else {
                unset($validated['banner_image']); 
            }

            $reward = $this->rewardService->createReward($validated, $businessId);
            // Nearby notifications
            $address = DB::table('business_addresses')->where('business_id', $businessId)->first();
            if ($address?->latitude && $address?->longitude) {
                $this->rewardService->sendNearbyNotifications(
                    $businessId,
                    $address->latitude,
                    $address->longitude
                );
            }

            return response()->json(['message' => 'Reward created successfully.'], 201);

        } catch (\Exception $e) {
            Log::error('Error creating reward: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create reward.'], 500);
        }
    }

    // Business: Scan QR & Redeem
    public function rewardRedemptions(Request $request)
    {
        if (Auth::user()->role !== 'business') {
            return response()->json(['message' => 'Please login with a valid Business account'], 403);
        }

        $validated = $request->validate([
            'qr_code' => 'required|string',
        ]);

        try {
            $decryptedPayload = json_decode(
                Crypt::decryptString($validated['qr_code']), true
            );
        } catch (\Exception) {
            return response()->json(['message' => 'Invalid or tampered QR Code'], 400);
        }

        if ($decryptedPayload['business_id'] !== Auth::id()) {
            return response()->json(['message' => 'You can only redeem rewards created by your business'], 403);
        }

        try {
            $this->rewardService->processRedemption($decryptedPayload, Auth::id());
            return response()->json(['message' => 'Reward redeemed successfully'], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['message' => 'Invalid reward details'], 400);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        } catch (\Exception $e) {
            Log::error('Redemption error: ' . $e->getMessage());
            return response()->json(['message' => 'Something went wrong, please try again.'], 500);
        }
    }

    // Business: Generate QR for existing reward
    public function generateRedemptionQrCodeApi(Request $request)
    {
        $validated = $request->validate([
            'reward_id' => 'required|integer|exists:rewards,id',
        ]);

        $reward = Reward::findOrFail($validated['reward_id']);
        // Refresh QR payload with new redemption code
        $qrPayload = [
            'type'            => 'reward_redemption',
            'business_id'     => Auth::id(),
            'reward_id'       => $reward->id,
            'redemption_code' => bin2hex(random_bytes(6)),
        ];

        $reward->update(['qr_code_payload' => json_encode($qrPayload)]);
        return response()->json(['message' => 'QR payload updated successfully.']);
    }

    // Customer: Get Rewards Dashboard
    public function getBusinessRewards(Request $request)
    {
        $customerId = Auth::id();
        if (!CustomerProfile::where('customer_id', $customerId)->exists()) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        try {
            $today = now();
            $checkInCount = DB::table('checkins')->where('customer_id', $customerId)->count();
            $businessVisitedCount = DB::table('checkins')->where('customer_id', $customerId)->distinct('business_id')->count('business_id');
            $activeBusinesses = DB::table('rewards')
                ->join('users', 'rewards.business_id', '=', 'users.id')
                ->whereDate('rewards.valid_from', '<=', $today)
                ->whereDate('rewards.valid_until', '>=', $today)
                ->whereNull('rewards.expiry_date')
                ->select(
                    'users.id as business_id',
                    'users.name',
                    DB::raw('COUNT(rewards.id) as total_rewards')
                )
                ->groupBy('users.id', 'users.name')
                ->get();

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
                ->orderByDesc('reward_redemptions.redeemed_at')
                ->get()
                ->map(fn($r) => [
                    'business_name' => $r->business_name,
                    'business_logo' => $r->profile_image ?? '',
                    'message'       => "You have earned {$r->points_redeemed} points",
                    'points'        => "+{$r->points_redeemed}",
                ]);

            return response()->json([
                'business_visited' => $businessVisitedCount,
                'check_ins'        => $checkInCount,
                'active_businesses'=> $activeBusinesses,
                'redeemed_rewards' => $redeemedRewards,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching business rewards: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get rewards.'], 500);
        }
    }

    // Customer: Rewards by Business
    public function getRewardByBusiness(int $id)
    {
        try {
            $rewards = Reward::where('business_id', $id)->get();

            if ($rewards->isEmpty()) {
                return response()->json(['error' => 'No rewards found for this business'], 404);
            }

            $business  = User::find($id);
            $today     = now()->startOfDay();
            $formatted = $rewards->map(function ($reward) use ($business, $today) {
                $daysLeft = $reward->valid_until
                    ? max(0, $today->diffInDays(Carbon::parse($reward->valid_until)->startOfDay()))
                    : null;

                return array_merge($reward->toArray(), [
                    'business_name' => $business?->name,
                    'days_left'     => $daysLeft,
                ]);
            });

            return response()->json(['rewards' => $formatted]);

        } catch (\Exception $e) {
            Log::error('Error fetching rewards: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch rewards.'], 500);
        }
    }

    // Customer: Single Reward + QR
    public function fetchSingleReward(int $id)
    {
        try {
            $reward = Reward::findOrFail($id);
            $qrData   = $this->rewardService->buildCustomerQr($reward, Auth::id());
            $business = User::find($reward->business_id);

            Log::build([
                'driver' => 'single',
                'path'   => storage_path('logs/customer_qr.log'),
            ])->info('Customers QRCode:', ['CustomerQR' => $qrData]);

            return response()->json([
                'reward' => array_merge($reward->toArray(), [
                    'qr_code'       => asset('storage/' . $qrData['path']),
                    'business_name' => $business?->name,
                ]),
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching reward: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get reward.'], 500);
        }
    }

    // Web: Blade View QR
    public function generateRedemptionQrCode(int $rewardId)
    {
        $reward = Reward::findOrFail($rewardId);
        $qrData = $this->rewardService->buildCustomerQr($reward, Auth::id());

        return view('customer.reward-qr', ['qrCode' => asset('storage/' . $qrData['path'])]);
    }
}