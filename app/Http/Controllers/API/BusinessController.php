<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\BusinessProfile;
use App\Models\BusinessAddress;
use App\Models\BusinessImage;
use App\Models\BusinessHour;
use Illuminate\Support\Facades\Crypt;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Admin\Category;

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

    /**
     *  API Function Update CheckIn Points By Business
    */
    public function updateCheckinPointsApi(Request $request)
    {
        // echo"<pre>"; print_r($request->all()); die;
        $validated = $request->validate([
            'points_per_checkin' => 'required|integer|min:10', // Minimum 10 points
        ]);
        
        $businessId = auth()->id();

        try {
            DB::table('business_profiles')->where('business_id', $businessId)->update(['points_per_checkin' => $validated['points_per_checkin']]);

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

    /**
     *  API Function getting Business Detail
    */
    public function dashboardBusinessData(Request $request)
    {
        $businessId = auth()->id();
        $user = auth()->user();

        if ($user->role !== 'business') {
            return response()->json(['message' => 'Please login with a valid Business account'], 403);
        }

        try {
            $today = Carbon::today();
            $now = Carbon::now();
            $todayCheckin = DB::table('checkins')->where('business_id', $businessId)->whereDate('check_in_time', $today)->count();
            $todayRewardClaims = DB::table('reward_redemptions')->where('business_id', $businessId)->where('status', 'success')->whereDate('redeemed_at', $today)->count();

            // Active Customers - Weekly & Monthly Visits
            $startOfWeek = Carbon::now()->startOfWeek();
            $startOfMonth = Carbon::now()->startOfMonth();
            $weeklyVisits = DB::table('checkins')->where('business_id', $businessId)->whereBetween('check_in_time', [$startOfWeek, $now])->distinct('customer_id')->count('customer_id');
            $monthlyVisits = DB::table('checkins')->where('business_id', $businessId)->whereBetween('check_in_time', [$startOfMonth, $now])->distinct('customer_id')->count('customer_id');

            // Recent Active Customers (limit 5)
            $recentCheckins = DB::table('checkins as c')
                ->join('users as u', 'u.id', '=', 'c.customer_id')
                ->select('u.id as customer_id', 'u.name', 'u.profile_image', 'c.check_in_time')
                ->where('c.business_id', $businessId)
                ->orderBy('c.check_in_time', 'desc')
                ->limit(5)
                ->get();

            // Customer Complaints in last 30 days with status pending or in_progress
            $startDate = Carbon::now()->subDays(30);
            $openComplaints = DB::table('complaints')
                ->where('business_id', $businessId)
                ->whereIn('status', ['pending', 'in_progress'])
                ->whereDate('created_at', '>=', $startDate)
                ->count();

            return response()->json([
                'today_checkins' => $todayCheckin,
                'today_reward_claims' => $todayRewardClaims,
                'active_customers' => [
                    'weekly' => $weeklyVisits,
                    'monthly' => $monthlyVisits
                ],
                'recent_active_customers' => $recentCheckins,
                'open_complaints_last_30_days' => $openComplaints,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     *  API Function : Update Business Profile
    */
    public function updateBusinessProfile(Request $request)
    {
        $id = auth()->id();
        $checkRole = auth()->user();
        if ($checkRole->role !== 'business') {
            return response()->json(['message' => 'Please login with a valid Business account'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'address1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:55',
            'state' => 'nullable|string|max:55',
            'country'    => 'nullable|string|max:55',
            'zip_code'   => 'nullable|string|max:20',
            'latitude'   => 'nullable|numeric|between:-90,90',
            'longitude'  => 'nullable|numeric|between:-180,180',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            // 'website' => ['required', 'url'],
            // 'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            // 'business_hours' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {

            $imageUrl = null;
            if ($request->hasFile('profile_image')) {
                $image = $request->file('profile_image');
                $imageName = 'profile_' . time() . '.' . $image->getClientOriginalExtension();
                $profileImagePath = $image->storeAs('profile_images', $imageName, 'public');
                $imageUrl = asset('storage/profile_images/' . $imageName);
                \Log::info('Profile Image Path: '. $imageUrl);
            }

            $socialLinksArray = array_map('trim', explode(',', $request->social_links));
            $user = User::findOrFail($id);
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->mobile,
                'profile_image' => $imageUrl,
            ]);

            // Update BusinessProfile
            BusinessProfile::updateOrCreate(
                ['business_id' => $user->id],
                [
                    'business_name' => $request->name,
                    'website' => $request->website,
                    'social_links' => $socialLinksJson = json_encode($socialLinksArray)
                ]
            );

            // Update BusinessAddress
            BusinessAddress::updateOrCreate(
                ['business_id' => $user->id],
                [
                    'address_line1' => $request->address1,
                    'address_line2' => $request->address1,
                    'city' => $request->city,
                    'state' => $request->state,
                    'country' => $request->country,
                    'zip_code' => $request->zip_code,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                ]
            );

            // Handle Image Upload
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $imageName = time() . '_' . $image->getClientOriginalName();

                    $profileImagePath = $image->storeAs('businesses', $imageName, 'public');
                    $imageUrl = asset('storage/businesses/' . $imageName);

                    BusinessImage::create([
                        'business_id' => $user->id,
                        'photo' => $imageUrl,
                    ]);
                }
            }

            // Handle Business Hours
            /*foreach ($request->business_hours as $hours) {
                BusinessHour::updateOrCreate(
                    ['business_id' => $user->id, 'day_of_week' => $hours['day_of_week']],
                    [
                        'open_time' => isset($hours['is_closed']) ? null : $hours['open_time'],
                        'close_time' => isset($hours['is_closed']) ? null : $hours['close_time'],
                        'is_closed' => isset($hours['is_closed']) ? 1 : 0,
                    ]
                );
            }*/

            Log::info('Business profile updated via API:', ['user_id' => $user->id]);

            return response()->json([
                'status' => true,
                'message' => 'Business profile updated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Business update failed:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }
    
    /**
     *  API Function : Update Business Profile
    */
    public function updateBusinessHours(Request $request)
    {
        // echo"<pre>"; print_r($request->all()); die;
        $user = auth()->user();
        if ($user->role !== 'business') {
            return response()->json(['message' => 'Unauthorized. Only businesses can update hours.'], 403);
        }

        $request->validate([
            'business_hours' => 'required|array|min:1',
            'business_hours.*.day_of_week' => 'required|string',
            'business_hours.*.open_time' => 'nullable|date_format:H:i:s',
            'business_hours.*.close_time' => 'nullable|date_format:H:i:s',
            'business_hours.*.is_closed' => 'required|boolean',
        ]);

        foreach ($request->business_hours as $hours) {
            BusinessHour::updateOrCreate(
                ['business_id' => $user->id, 'day_of_week' => $hours['day_of_week']],
                [
                    'open_time' => $hours['is_closed'] ? null : $hours['open_time'],
                    'close_time' => $hours['is_closed'] ? null : $hours['close_time'],
                    'is_closed' => $hours['is_closed'],
                ]
            );
        }

        return response()->json([
            'status' => true,
            'message' => 'Business hours updated successfully.',
        ]);
    }

    /**
     *  API Function : Change Business Password
    */
    public function changePassword(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'business') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => ['required','min:8','regex:/[a-z]/','regex:/[A-Z]/','regex:/[0-9]/','regex:/[@$!%*#?&]/'],
            'confirm_password' => 'required|same:new_password'
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['message' => 'Current password is incorrect.'], 400);
            }
        
            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json(['message' => 'Password changed successfully.']);

        } catch (\Exception $e) {

            Log::error('Business Change Password failed:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);

        }
    }

    /**
     *  API Function : Update Business Owner Detail
    */
    public function updateOwnerDetail(Request $request)
    {
        $id = auth()->id();
        $checkRole = auth()->user();
        if ($checkRole->role !== 'business') {
            return response()->json(['message' => 'Please login with a valid Business account'], 403);
        }

        $validator = Validator::make($request->all(), [
            'owner_name' => 'required|string|max:255',
            'owner_email' => 'required|email|unique:users,email',
            'owner_phone' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Update BusinessProfile
            BusinessProfile::updateOrCreate(
                ['business_id' => $id],
                [
                    'owner_name' => $request->owner_name,
                    'owner_email' => $request->owner_email,
                    'owner_phone' => $request->owner_phone
                ]
            );

            Log::info('Owner Detail updated via API:', ['BID' => $id]);

            return response()->json([
                'status' => true,
                'message' => 'Owner details updated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Business Owner Detail failed:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     *  API Function : Create Staff
    */
    // public function createStaff(Request $request)
    // {
    //     $business = auth()->user();
    //     if ($business->role !== 'business') {
    //         return response()->json(['message' => 'Only businesses can add staff'], 403);
    //     }

    //     $validated = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|email|unique:users,email',
    //         'password' => 'required|string|min:8|confirmed',
    //         'role' => 'required|in:manager,employee'
    //     ]);

    //     // Count existing staff under the business
    //     $staffCount = User::where('parent_business_id', $business->id)
    //     ->whereNull('deleted_at')
    //     ->selectRaw('SUM(role = "manager") as managers, SUM(role = "employee") as employees')
    //     ->first();

    //     if ($validated['role'] === 'manager' && $staffCount->managers >= 1) {
    //         return response()->json(['message' => 'You can only add 1 manager.'], 400);
    //     }

    //     if ($validated['role'] === 'employee' && $staffCount->employees >= 5) {
    //         return response()->json(['message' => 'You can only add up to 5 employees.'], 400);
    //     }

    //     try{
    //         $user = User::create([
    //             'name' => $validated['name'],
    //             'email' => $validated['email'],
    //             'password' => Hash::make($validated['password']),
    //             'role' => $validated['role'],
    //             'parent_business_id' => $business->id,
    //             'providers' => 'email',
    //             "status" => "active"
    //         ]);

    //         return response()->json(['message' => 'Staff created successfully', 'user' => $user]);
    //     } catch (\Exception $e) {

    //         Log::error('Staff failed:', ['error' => $e->getMessage()]);
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Something went wrong. Please try again.',
    //         ], 500);
    //     }
    // }

    public function createStaff(Request $request)
    {
        $business = auth()->user();

        if ($business->role !== 'business') {
            return response()->json(['message' => 'Only businesses can add staff'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:manager,employee'
        ]);

        // Count total staff under this business (excluding soft-deleted)
        $totalStaff = User::where('parent_business_id', $business->id)->whereIn('role', ['manager', 'employee'])->whereNull('deleted_at')->count();
        if ($totalStaff >= 5) {
            return response()->json(['message' => 'You can only add up to 5 staff members.'], 400);
        }

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'parent_business_id' => $business->id,
                'providers' => 'email',
                'status' => 'active'
            ]);

            return response()->json(['message' => 'Staff created successfully', 'user' => $user]);
        } catch (\Exception $e) {
            Log::error('Staff creation failed:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     *  API Function : Business Staff Listing
    */
    public function listStaff()
    {
        try{

            $business = auth()->user();
            $staff = User::select('id', 'name', 'email', 'role')->where('parent_business_id', $business->id)->whereIn('role', ['manager', 'employee'])->whereNull('deleted_at')->get();
            return response()->json($staff);
        
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    public function deactivateStaff($id)
    {
        try{

            $business = auth()->user();
            $staff = User::where('parent_business_id', $business->id)->whereIn('role', ['manager', 'employee'])->where('id', $id)->first();
            if (!$staff) {
                return response()->json(['message' => 'Staff member not found or unauthorized.'], 404);
            }

            $staff->delete(); // Soft delete

            return response()->json(['message' => 'Staff member deactivated successfully.']);

        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     *  API Function for update Language 
    */
    public function updateBusinessLanguage(Request $request)
    {
        $request->validate([
            'language' => 'required|in:en,es' //English is "en" and for Spanish is "es"
        ]);

        try {
            $user = Auth::user();
            $businessProfile = BusinessProfile::where('business_id', $user->id)->first();
            if (!$businessProfile) {
                return response()->json([
                    'error' => 'business profile not found',
                ], 404);
            }
            $businessProfile->preferred_language = $request->language;
            $businessProfile->save();

            return response()->json([
                'message' => 'Language updated successfully',
                'language' => $businessProfile->preferred_language,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update business location',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     *  API Function for update Push Notification status of business
    */
    public function pushNotificationUpdate(Request $request)
    {
        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        try {
            $user = Auth::user();
            $businessProfile = BusinessProfile::where('business_id', $user->id)->first();
            if (!$businessProfile) {
                return response()->json([
                    'error' => 'business profile not found',
                ], 404);
            }
            $businessProfile->push_notification = $request->enabled ? '1' : '0';
            $businessProfile->save();

            return response()->json([
                'message' => 'Push notification setting updated successfully.',
                'push_notification' => $businessProfile->push_notification,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update push notification setting',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     *  API Function Create GrandChild
    */
    public function createGrandChildCategory(Request $request)
    {
        // echo"<pre>"; print_r($request->all());  die;
        $validated = $request->validate([
            'parent_id' => 'required|exists:categories,id',
            'child_id'  => 'required|exists:categories,id',
            'name'      => 'required|string|max:255',
        ]);

        $childCategory = Category::find($validated['child_id']);
        if ($childCategory->parent_id != $validated['parent_id']) {
            return response()->json([
                'status' => false,
                'message' => 'The selected child category is not under the specified parent.'
            ], 422);
        }

        $existing = Category::where('name', $validated['name'])->where('parent_id', $validated['child_id'])->first();
        if ($existing) {
            return response()->json([
                'status' => false,
                'message' => 'This grandchild category already exists under the selected child category.'
            ], 409);
        }

        $grandchild = Category::create([
            'name' => $validated['name'],
            'parent_id' => $validated['child_id']
        ]);

        return response()->json([
            'status' => true,
            'id' => $grandchild->id
        ], 200);
    }

}
