<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\CustomerProfile;
use App\Models\CustomerAddress;
use App\Models\BusinessAddress;
use App\Models\Review;
use Illuminate\Support\Facades\Crypt;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\DB;


class CustomerController extends Controller
{
    // Show the login form (for web)
    public function showLoginForm()
    {
        return view('customer.login');
    }

    public function showRegistrationForm()
    {
        return view('customer.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'zip_code' => 'required|string|max:10',
            'radius' => 'nullable|integer',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
        ]);

        // Create the user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'customer',
        ]);

        // Create the customer profile
        CustomerProfile::create([
            'customer_id' => $user->user_id,
            'zip_code' => $validated['zip_code'],
            'radius' => $validated['radius'] ?? 5,
            'address_line1' => $validated['address_line1'],
            'address_line2' => $validated['address_line2'],
            'city' => $validated['city'],
            'state' => $validated['state'],
            'country' => $validated['country'],
        ]);

        return redirect()->route('customer.register')->with('success', 'Registration successful!');
    }
    // Handle login (supports both web and API)
    public function handleLogin(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            // Update last_login_at
            $user->last_login_at = now();
            $user->save();

            if ($request->expectsJson()) {
                // For API Login: Return Sanctum token
                $token = $user->createToken($user->role . '-token')->plainTextToken;

                return response()->json([
                    'message' => 'Login successful',
                    'token' => $token,
                    'user' => $user,
                    'role' => $user->role, // Include role in API response
                ], 200);
            }

            // For Web Login: Role-based redirection
            $request->session()->regenerate();

            if ($user->role === 'business') {
                return redirect()->route('business.dashboard');
            } elseif ($user->role === 'customer') {
                return redirect()->route('customer.dashboard');
            }

            // Default: If role is not set properly
            Auth::logout();
            return back()->with('error', 'User role is invalid.');
        }

        if ($request->expectsJson()) {
            // For API: Unauthorized Response
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // For Web: Return to login form with error
        return back()->with('error', 'Invalid login credentials.');
    }

    // Customer Dashboard View (web only)
    public function dashboard(Request $request)
    {
        return view('customer.dashboard', ['customer' => Auth::user()]);
    }

    // Handle Logout (supports both web and API)
    public function logout(Request $request)
    {
        if ($request->expectsJson()) {
            // For API: Revoke Sanctum token
            $request->user()->tokens()->delete();

            return response()->json(['message' => 'Logged out successfully'], 200);
        }

        // For Web: Logout and invalidate session
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function generateEncryptedQrCode()
    {
        // Get the authenticated user's ID (customer ID)
        $userId = Auth::id(); // This retrieves the logged-in user's user_id
        // Create payload
        $payload = [
            "type" => "customer_checkin",
            "customer_id" => $userId
        ];

        // Encrypt payload
        $encryptedPayload = Crypt::encryptString(json_encode($payload));

        // Generate QR code
        $qrCode = QrCode::size(300)->generate($encryptedPayload);

        // Generate text code (Base64-encoded payload)
        $textCode = base64_encode($encryptedPayload);

        // Return view with QR code and text code
        return view('customer.qr-code', compact('qrCode', 'textCode'));
    }

    public function generateEncryptedQrCodeApi()
    {
        // Get the authenticated user's ID (customer ID)
        $userId = Auth::id();

        // Create payload
        $payload = [
            "type" => "customer_checkin",
            "customer_id" => $userId
        ];

        // Encrypt payload
        $encryptedPayload = Crypt::encryptString(json_encode($payload));

        // Generate QR Code as PNG
        $qrCode = QrCode::format('png')->size(300)->generate($encryptedPayload);

        // Generate text code (Base64-encoded payload)
        $textCode = base64_encode($encryptedPayload);

        // Return JSON response
        return response()->json([
            'message' => 'Customer QR code generated successfully.',
            'qr_code' => 'data:image/png;base64,' . base64_encode($qrCode),
            'text_code' => $textCode
        ], 200);
    }

    public function searchBusinesses(Request $request)
    {
        $query = $request->get('query', '');

        // Search businesses by name or category
        $businesses = DB::table('business_profiles')
            ->where('business_name', 'LIKE', "%{$query}%")
            ->orWhere('industry_type', 'LIKE', "%{$query}%")
            ->paginate(10);

        return view('customer.search', compact('businesses', 'query'));
    }

    public function viewBusinessProfile($businessId)
    {
        $business = DB::table('business_profiles')->where('business_id', $businessId)->first();

        $rewards = DB::table('rewards')->where('business_id', $businessId)->get();
        $contests = DB::table('contests')->where('business_id', $businessId)->get(); // If contests exist

        return view('customer.business-profile', compact('business', 'rewards', 'contests'));
    }

    public function apiDashboard(Request $request)
    {
        try {
            $user = $request->user();
            $profile = $user->profile; // Assuming `profile` is a relationship in the User model.

            // 1. Fetch City and State
            $city = $profile->city ?? 'Unknown';
            $state = $profile->state ?? 'Unknown'; // Assuming state exists in the profile

            // 2. Fetch Total Check-Ins
            $checkIns = DB::table('customer_business_interactions')
                ->where('customer_id', $user->user_id)
                ->where('interaction_type', 'check_in')
                ->count();

            // 3. Fetch Total Businesses
            $businessesCount = DB::table('customer_business_interactions')
                ->where('customer_id', $user->user_id)
                ->distinct('business_id')
                ->count('business_id');

            // 4. Fetch Frequent Businesses
            $frequentBusinesses = DB::table('customer_business_interactions')
                ->where('customer_id', $user->user_id)
                ->join('business_profiles', 'customer_business_interactions.business_id', '=', 'business_profiles.business_id')
                ->select('business_profiles.business_name')
                ->groupBy('business_profiles.business_name')
                ->orderByRaw('COUNT(*) DESC')
                ->limit(4)
                ->pluck('business_name')
                ->toArray();

            return response()->json([
                'name' => $user->name,
                'city' => "$city, $state",
                'reward_points' => $profile->points_balance ?? 0,
                'businesses_count' => $businessesCount,
                'check_ins' => $checkIns,
                'offers' => 5, // Placeholder for now
                'frequent_businesses' => $frequentBusinesses,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch dashboard data', 'error' => $e->getMessage()], 500);
        }
    }

    public function getHomeBusinesses(Request $request)
    {
        $customerId = auth()->id();

        // Fetch customer address
        $customerAddress = CustomerAddress::where('customer_id', $customerId)->first();
        if (!$customerAddress) {
            return response()->json(['error' => 'Customer address not found'], 404);
        }

        $customerLat = $customerAddress->latitude;
        $customerLong = $customerAddress->longitude;

        // Fetch nearby businesses
        // $nearbyBusinesses = BusinessAddress::selectRaw(
        //     "business_addresses.*, 
        //     users.name AS business_name,
        //     business_profiles.*,  
        //     parent_category.name AS category_name,
        //     child_category.name AS child_category_name,
        //     (SELECT GROUP_CONCAT(business_images.photo) 
        //         FROM business_images 
        //         WHERE business_images.business_id = users.id) AS images,
        //     (SELECT GROUP_CONCAT(
        //         CONCAT(day_of_week, '|', open_time, '|', close_time, '|', is_closed)
        //     ) FROM business_hours 
        //     WHERE business_hours.business_id = users.id) AS business_hours,
        //     ROUND((6371 * acos(
        //         cos(radians(?)) * cos(radians(latitude)) 
        //         * cos(radians(longitude) - radians(?)) 
        //         + sin(radians(?)) * sin(radians(latitude))
        //     )), 2) AS distance",
        //     [$customerLat, $customerLong, $customerLat]
        // )
        // ->join('users', 'users.id', '=', 'business_addresses.business_id')
        // ->leftJoin('business_profiles', 'business_profiles.business_id', '=', 'users.id') 
        // ->leftJoin('categories AS parent_category', 'parent_category.id', '=', 'business_profiles.category_id')
        // ->leftJoin('categories AS child_category', 'child_category.id', '=', 'business_profiles.child_category_id')
        // ->having('distance', '<=', 10000)
        // ->orderBy('distance', 'asc')
        // ->get();

        $nearbyBusinesses = BusinessAddress::selectRaw(
            "business_addresses.*, 
            users.name AS business_name,
            business_profiles.*,  
            parent_category.name AS category_name,
            child_category.name AS child_category_name,
            (SELECT GROUP_CONCAT(business_images.photo) 
                FROM business_images 
                WHERE business_images.business_id = users.id) AS images,
            (SELECT GROUP_CONCAT(
                CONCAT(day_of_week, '|', open_time, '|', close_time, '|', is_closed)
            ) FROM business_hours 
            WHERE business_hours.business_id = users.id) AS business_hours,
            EXISTS (
                SELECT 1 FROM wishlists 
                WHERE wishlists.business_id = users.id 
                AND wishlists.customer_id = ?
            ) AS is_wishlist,
            (SELECT COUNT(*) FROM reviews WHERE reviews.business_id = users.id) AS total_reviews,
            (SELECT COALESCE(AVG(reviews.rating), 0) FROM reviews WHERE reviews.business_id = users.id) AS average_rating,
            ROUND((6371 * acos(
                cos(radians(?)) * cos(radians(latitude)) 
                * cos(radians(longitude) - radians(?)) 
                + sin(radians(?)) * sin(radians(latitude))
            )), 2) AS distance",
            [$customerId, $customerLat, $customerLong, $customerLat]
        )
        ->join('users', 'users.id', '=', 'business_addresses.business_id')
        ->leftJoin('business_profiles', 'business_profiles.business_id', '=', 'users.id')
        ->leftJoin('categories AS parent_category', 'parent_category.id', '=', 'business_profiles.category_id')
        ->leftJoin('categories AS child_category', 'child_category.id', '=', 'business_profiles.child_category_id')
        ->having('distance', '<=', 10000) // Filter businesses within 10,000 km
        ->orderBy('distance', 'asc') // Sort by nearest first
        ->get();
        
        // Get Reviews Based on Business_Ids
        $businessIds = $nearbyBusinesses->pluck('business_id')->toArray();
        $reviews = Review::whereIn('business_id', $businessIds)->with(['customer:id,name,profile_image'])->orderBy('created_at', 'desc')->get();

        
        // Fetch frequently visited businesses
        $frequentBusinesses = array();

        // Fetch businesses with highest reward points
        $highestRewardBusinesses = array();

        return response()->json([
            'nearby' => $nearbyBusinesses,
            'frequent' => $frequentBusinesses,
            'highest_rewards' => $highestRewardBusinesses,
            'reviews' => $reviews
        ]);
    }


}
