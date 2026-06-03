<?php

namespace App\Http\Controllers;

use App\Services\QrService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerDashboardController extends Controller
{
    public function __construct(
        private readonly QrService $qrService
    ) {}

    public function redemptionQrCode(int $rewardId)
    {
        $customerId = Auth::id();
        $reward = DB::table('rewards')->where('id', $rewardId)->first();
        if (!$reward) {
            abort(404, 'Reward not found');
        }

        $redemption = DB::table('reward_redemptions')
            ->where('customer_id', $customerId)
            ->where('reward_id', $rewardId)
            ->where('status', 'pending')
            ->first();

        if (!$redemption) {
            abort(404, 'Redemption not initiated');
        }

        $payload = [
            'type'            => 'reward_redemption',
            'customer_id'     => $customerId,
            'reward_id'       => $rewardId,
            'business_id'     => $reward->business_id,
            'redemption_code' => $redemption->redemption_code,
            'points_redeemed' => $reward->points_required,
            'reward_name'     => $reward->reward_name,
        ];

        $encryptedPayload = $this->qrService->encryptPayload($payload);
        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(300)->generate($encryptedPayload);

        return view('customer.reward-qr', compact('qrCode', 'reward'));
    }
}
