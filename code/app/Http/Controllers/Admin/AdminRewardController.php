<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reward;
use App\Services\FCMService;
use App\Services\NearbyCustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminRewardController extends Controller
{
    public function __construct(
        private readonly FCMService $fcmService,
        private readonly NearbyCustomerService $nearbyCustomerService,
    ) {}

    public function index()
    {
        return view('admin.businesses.reward.index', [
            'rewards' => Reward::with('business')->orderByDesc('id')->get(),
        ]);
    }

    public function create() { return view('admin.businesses.reward.create'); }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_id'      => 'required|exists:users,id',
            'reward_name'      => 'required|string|max:255',
            'points_required'  => 'required|integer|min:1',
            'valid_from'       => 'nullable|date',
            'valid_until'      => 'nullable|date|after_or_equal:valid_from',
            'customer_tiers'   => 'nullable|array',
            // 'customer_tiers.*' => 'in:new,regular,frequenter',
            'customer_tiers.*' => 'in:new,regular,daily',
        ]);

        $businessId = $validated['business_id'];

        try {
            $reward = Reward::create([
                'business_id'     => $businessId,
                'reward_name'     => $validated['reward_name'],
                'points_required' => $validated['points_required'],
                'valid_from'      => $validated['valid_from'] ?? null,
                'valid_until'     => $validated['valid_until'] ?? null,
            ]);

            $qrPayload = [
                'type'            => 'reward_redemption',
                'business_id'     => $businessId,
                'reward_id'       => $reward->id,
                'redemption_code' => bin2hex(random_bytes(6)),
            ];
            if (!empty($validated['customer_tiers'])) {
                $qrPayload['customer_tiers'] = $validated['customer_tiers'];
            }

            $reward->update(['qr_code_payload' => json_encode($qrPayload)]);
            $address = DB::table('business_addresses')->where('business_id', $businessId)->first();
            if ($address?->latitude && $address?->longitude) {
                $nearbyIds = $this->nearbyCustomerService->getNearbyCustomerIds($address->latitude, $address->longitude, 10);
                if (!empty($nearbyIds)) {
                    $tokens = DB::table('customer_profiles')->whereIn('customer_id', $nearbyIds)->whereNotNull('fcm_token')->pluck('fcm_token', 'customer_id');
                    $body   = 'New reward near you, please redeem it';
                    $image  = asset('storage/notifications/notification.png');
                    foreach ($tokens as $customerId => $token) {
                        $this->fcmService->sendNotificationToDevice($token, 'Reward', $body, $image);
                        customerNotification($customerId, $body, 'push', null, true);
                    }
                }
            }

            return redirect()->route('rewards.index')->with('success', 'Reward created successfully.');

        } catch (\Exception $e) {
            Log::error('Error creating reward: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create reward.');
        }
    }

    public function edit(string $id)
    {
        return view('admin.businesses.reward.edit', ['reward' => Reward::findOrFail($id)]);
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'business_id'      => 'required|exists:users,id',
            'reward_name'      => 'required|string|max:255',
            'points_required'  => 'required|integer|min:1',
            'valid_from'       => 'nullable|date',
            'valid_until'      => 'nullable|date|after_or_equal:valid_from',
        ]);

        try {
            Reward::findOrFail($id)->update($validated);
            return redirect()->route('rewards.index')->with('success', 'Reward updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating reward: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update reward.');
        }
    }

    public function rewardReedemHistory(string $id)
    {
        $reward = Reward::with('redemptionHistory.customer')->findOrFail($id);
        return view('admin.businesses.reward.history', compact('reward'));
    }
}