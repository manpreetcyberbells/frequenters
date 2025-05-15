<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reward;
use App\Models\RewardRedemption;
use Carbon\Carbon;
use App\Services\FCMService;
use App\Services\NearbyCustomerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminRewardController extends Controller
{
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

    private function generateQrResponse($businessId, $rewardId, $qrPayload, $message)
    {
        $encryptedPayload = Crypt::encryptString(json_encode($qrPayload));
        $rewardCheck = DB::table('rewards')->where('id', $rewardId)->first();
        // Insert value of QR payload // We will using on Customer app.
        $updatePayload = DB::table('rewards')->where('id', $rewardId)->update([
            'qr_code_payload' => json_encode($qrPayload)
        ]);

        return redirect()->route('rewards.index')->with('success', $message);
    }

    /**
     * Display a listing of the resource.
    */
    public function index()
    {
        $rewards = Reward::with('business')->orderBy('id', 'desc')->get();
        // echo"<pre>"; print_r($rewards);  die;
        return view('admin.businesses.reward.index', compact('rewards'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.businesses.reward.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // echo"<pre>"; print_r($request->all());  die;
        $validated = $request->validate([
            'business_id' => 'required|exists:users,id',
            'reward_name' => 'required|string|max:255',
            'points_required' => 'required|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'customer_tiers' => 'nullable|array',
            'customer_tiers.*' => 'in:new,regular,frequenter'
        ]);

        $businessId = $request->business_id;
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
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $reward = Reward::findOrFail($id);
        return view('admin.businesses.reward.edit', compact('reward'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'business_id' => 'required|exists:users,id',
            'reward_name' => 'required|string|max:255',
            'points_required' => 'required|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'customer_tiers' => 'nullable|array',
            'customer_tiers.*' => 'in:new,regular,frequenter'
        ]);

        try {
            DB::table('rewards')
                ->where('id', $id)
                ->update([
                    'business_id' => $validated['business_id'],
                    'reward_name' => $validated['reward_name'],
                    'points_required' => $validated['points_required'],
                    'valid_from' => $validated['valid_from'],
                    'valid_until' => $validated['valid_until'],
                    'updated_at' => now(),
                ]);

            return redirect()->route('rewards.index')->with('success', 'Reward updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating reward: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update reward.'], 500);
        }
    }

    /**
     * Reward History.
    */    
    public function rewardReedemHistory($id)
    {
        $reward = Reward::with('reedemtionHistory.customer')->findOrFail($id);
        // echo"<pre>"; print_r($reward);  die;
        return view('admin.businesses.reward.history', compact('reward'));
    }
}
