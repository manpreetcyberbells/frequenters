<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;


class AdminCheckInController extends Controller
{
    /**
     * Show the Manual Check-In Form.
     */
    public function showManualCheckInForm()
    {
        return view('admin.manual-checkin');
    }

    /**
     * Process Manual Check-In Submission.
     */
    public function manualCheckIn(Request $request)
    {
        $validated = $request->validate([
            'text_code' => 'required|string',
            'business_id' => 'required|exists:users,id',
        ]);

        try {
            // Decrypt and decode the text code
            $encryptedPayload = base64_decode($validated['text_code']);
            $decryptedPayload = json_decode(Crypt::decryptString($encryptedPayload), true);

            if (!isset($decryptedPayload['type']) || $decryptedPayload['type'] !== 'customer_checkin') {
                throw new \Exception('Invalid or corrupted text code.');
            }

            $customerId = $decryptedPayload['customer_id'];
            $businessId = $validated['business_id'];

            // Update or insert interaction record
            DB::table('customer_business_interactions')->updateOrInsert(
                ['customer_id' => $customerId, 'business_id' => $businessId],
                [
                    'interaction_count' => DB::raw('interaction_count + 1'),
                    'last_interaction_date' => now(),
                    'updated_at' => now(),
                ]
            );

            // Session::flash('success', "Customer ID: $customerId successfully checked in!");
            return redirect()->back()->with('success', "Customer ID: $customerId successfully checked in!");

        } catch (\Exception $e) {
            // Session::flash('error', 'Failed to process check-in: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to process check-in: ' . $e->getMessage());
        }
    }

    public function getBusinesses(Request $request)
    {
        $search = $request->input('q');

        // Fetch businesses based on search term
        $businesses = DB::table('users')
            ->select('user_id as id', 'name', 'email') // Replace 'id' with 'user_id'
            ->where('role', 'business') // Add role filter
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get();

        return response()->json($businesses);
    }
}
