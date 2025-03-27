<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\BusinessProfile;
use Illuminate\Support\Facades\Crypt;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB; // Import DB Facade
use Illuminate\Support\Facades\Log;

class BusinessController extends Controller
{
    public function showRegistrationForm()
    {
        return view('business.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'business_name' => 'required|string|max:255',
            'industry_type' => 'required|string|max:255',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'zip_code' => 'required|string|max:10',
        ]);

        // Create the user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'business',
        ]);

        // Create the business profile
        BusinessProfile::create([
            'business_id' => $user->user_id,
            'business_name' => $validated['business_name'],
            'industry_type' => $validated['industry_type'],
            'address_line1' => $validated['address_line1'],
            'address_line2' => $validated['address_line2'],
            'city' => $validated['city'],
            'state' => $validated['state'],
            'country' => $validated['country'],
            'zip_code' => $validated['zip_code'],
        ]);

        return redirect()->route('business.register')->with('success', 'Business registration successful!');
    }
    /**
     * Display the business dashboard (for web users).
     */
    public function dashboard()
    {
        $user = Auth::user();

        // Ensure the user has the correct role
        if ($user->role !== 'business') {
            return redirect()->route('customer.dashboard')->with('error', 'Unauthorized access');
        }

       // Fetch business profile and rewards
    $business = DB::table('business_profiles')->where('business_id', $user->user_id)->first();
    $rewards = DB::table('rewards')->where('business_id', $user->user_id)->get();

    return view('business.dashboard', compact('user', 'business', 'rewards'));
    }


    public function handleLogin(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Redirect based on the role
            if ($user->role === 'business') {
                return redirect()->route('business.dashboard');
            } elseif ($user->role === 'customer') {
                return redirect()->route('customer.dashboard');
            }

            // Default redirection if role is missing
            return redirect()->route('login')->with('error', 'Invalid user role.');
        }

        return back()->with('error', 'Invalid login credentials.');
    }
    
    /**
     * Get all business profiles (API functionality).
     */
    public function index()
    {
        return response()->json(BusinessProfile::all(), 200);
    }

    /**
     * Show details for a single business profile (API functionality).
     */
    public function show($id)
    {
        $business = BusinessProfile::find($id);

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        return response()->json($business, 200);
    }

    // Generate Encrypted QR Code for Business
    public function generateEncryptedQrCode()
    {
        // Get the authenticated user's ID (customer ID)
        $userId = Auth::id(); // This retrieves the logged-in user's user_id
        // Create payload
        $payload = [
            "type" => "business_checkin",
            "business_id" => $userId
        ];

        // Encrypt payload
        $encryptedPayload = Crypt::encryptString(json_encode($payload));

        // Generate QR code
        $qrCode = QrCode::size(300)->generate($encryptedPayload);

        // Generate text code (Base64-encoded payload)
        $textCode = base64_encode($encryptedPayload);

        // Return view with QR code and text code
        return view('business.qr-code', compact('qrCode', 'textCode'));
    }

    public function generateEncryptedQrCodeApi()
    {
        // Get the authenticated user's ID (business admin ID)
        $userId = Auth::id();

        // Create payload
        $payload = [
            "type" => "business_checkin",
            "business_id" => $userId
        ];

        // Encrypt payload
        $encryptedPayload = Crypt::encryptString(json_encode($payload));

        // Generate QR Code as PNG
        $qrCode = QrCode::format('png')->size(300)->generate($encryptedPayload);

        // Generate text code (Base64-encoded payload)
        $textCode = base64_encode($encryptedPayload);

        // Return JSON response
        return response()->json([
            'message' => 'Business QR code generated successfully.',
            'qr_code' => 'data:image/png;base64,' . base64_encode($qrCode),
            'text_code' => $textCode
        ], 200);
    }

    public function grantCheckinPoints(Request $request, $businessId)
    {
        $validated = $request->validate([
            'points' => 'required|integer|min:1'
        ]);

        $business = DB::table('business_profiles')->where('business_id', $businessId)->first();

        if (!$business) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Business not found'], 404);
            }
            return redirect()->back()->with('error', 'Business not found.');
        }

        if ($business->status !== 'active') {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Business is not active'], 403);
            }
            return redirect()->back()->with('error', 'Business is not active.');
        }

        // Increment the points in the business_profiles table
        DB::table('business_profiles')
            ->where('business_id', $businessId)
            ->increment('monthly_points_available', $validated['points']);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Points successfully added to the business.']);
        }

        return redirect()->back()->with('success', 'Points successfully added to the business.');
    }

    public function createRewardForm()
    {
        return view('business.rewards.create');
    }

    public function storeReward(Request $request)
    {
        // Validate reward inputs
        $validated = $request->validate([
            'reward_name' => 'required|string|max:255',
            'points_required' => 'required|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'usage_limit' => 'nullable|integer|min:0',
        ]);

        $businessId = Auth::user()->businessProfile->business_id;

        try {
            // Insert reward into the `rewards` table
            $rewardId = DB::table('rewards')->insertGetId([
                'business_id' => $businessId,
                'reward_name' => $validated['reward_name'],
                'points_required' => $validated['points_required'],
                'valid_from' => $validated['valid_from'],
                'valid_until' => $validated['valid_until'],
                'usage_limit' => $validated['usage_limit'],
                'created_at' => now(),
            ]);

            // Insert reward conditions into the `reward_conditions` table
            if (!empty($validated['valid_until'])) {
                DB::table('reward_conditions')->insert([
                    'reward_id' => $rewardId,
                    'condition_type' => 'expiry',
                    'value' => $validated['valid_until'],
                ]);
            }

            if (!empty($validated['usage_limit'])) {
                DB::table('reward_conditions')->insert([
                    'reward_id' => $rewardId,
                    'condition_type' => 'usage_limit',
                    'value' => $validated['usage_limit'],
                ]);
            }

            return redirect()->route('business.dashboard')->with('success', 'Reward and conditions created successfully.');
        } catch (\Exception $e) {
            // Log error and redirect with failure message
            Log::error('Error creating reward: ' . $e->getMessage());
            return redirect()->route('business.dashboard')->with('error', 'Failed to create reward.');
        }
    }

    public function pointsSummaryApi()
    {
        $businessId = Auth::user()->businessProfile->business_id;

        try {
            // Fetch business details
            $business = DB::table('business_profiles')->where('business_id', $businessId)->first();

            if (!$business) {
                return response()->json(['error' => 'Business profile not found.'], 404);
            }

            return response()->json([
                'business_id' => $businessId,
                'monthly_points_available' => $business->monthly_points_available,
                'points_given' => $business->points_given,
                'points_redeemed' => $business->points_redeemed,
                'points_per_checkin' => $business->points_per_checkin,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching points summary: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch points summary.'], 500);
        }
    }

    public function manageCheckinPoints()
    {
        $business = Auth::user()->businessProfile;

        if (!$business) {
            return redirect()->route('business.dashboard')->with('error', 'Business profile not found.');
        }

        return view('business.manage-checkin-points', compact('business'));
    }

    public function updateCheckinPoints(Request $request)
    {
        $validated = $request->validate([
            'points_per_checkin' => 'required|integer|min:10', // Minimum 10 points
        ]);

        $businessId = Auth::user()->businessProfile->business_id;

        try {
            DB::table('business_profiles')
                ->where('business_id', $businessId)
                ->update(['points_per_checkin' => $validated['points_per_checkin']]);

            return redirect()->route('business.manageCheckinPoints')->with('success', 'Check-in points updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating check-in points: ' . $e->getMessage());
            return redirect()->route('business.manageCheckinPoints')->with('error', 'Failed to update check-in points.');
        }
    }

    public function getCheckinPointsApi()
    {
        $business = Auth::user()->businessProfile;

        if (!$business) {
            return response()->json(['error' => 'Business profile not found.'], 404);
        }

        return response()->json([
            'business_id' => $business->business_id,
            'points_per_checkin' => $business->points_per_checkin,
        ], 200);
    }

    public function updateCheckinPointsApi(Request $request)
    {
        $validated = $request->validate([
            'points_per_checkin' => 'required|integer|min:10', // Minimum 10 points
        ]);

        $businessId = Auth::user()->businessProfile->business_id;

        try {
            DB::table('business_profiles')
                ->where('business_id', $businessId)
                ->update(['points_per_checkin' => $validated['points_per_checkin']]);

            return response()->json(['message' => 'Check-in points updated successfully.'], 200);
        } catch (\Exception $e) {
            Log::error('Error updating check-in points: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update check-in points.'], 500);
        }
    }


    /**
     *  API Function getting Business Detail
    */
    public function getBusinessDetails($id){
        try {
            $business = User::with(['businessProfile', 'businessAddress', 'businessImage', 'businessHours'])->where('id', $id)->first();
            
            if (!$business) {
                return response()->json(['error' => 'Business not found'], 404);
            }
            
            return response()->json(['business' => $business], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong', 'details' => $e->getMessage()], 500);
        }
    }

}
