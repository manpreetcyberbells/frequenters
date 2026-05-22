<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class AdminCheckInController extends Controller
{
    public function showManualCheckInForm()
    {
        return view('admin.manual-checkin');
    }

    public function manualCheckIn(Request $request)
    {
        $validated = $request->validate([
            'text_code'   => 'required|string',
            'business_id' => 'required|exists:users,id',
        ]);

        try {
            $encryptedPayload = base64_decode($validated['text_code']);
            $payload          = json_decode(Crypt::decryptString($encryptedPayload), true);

            if (($payload['type'] ?? '') !== 'customer_checkin') {
                throw new \Exception('Invalid or corrupted text code.');
            }

            $customerId = $payload['customer_id'];
            $businessId = $validated['business_id'];

            DB::table('customer_business_interactions')->updateOrInsert(
                ['customer_id' => $customerId, 'business_id' => $businessId],
                ['interaction_count' => DB::raw('interaction_count + 1'), 'last_interaction_date' => now(), 'updated_at' => now()]
            );

            return redirect()->back()->with('success', "Customer ID: {$customerId} successfully checked in!");

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to process check-in: ' . $e->getMessage());
        }
    }

    public function getBusinesses(Request $request)
    {
        $search = $request->input('q');
        $businesses = DB::table('users')->select('id', 'name', 'email')->where('role', 'business')->where(fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))->limit(10)->get();
        return response()->json($businesses);
    }
}
