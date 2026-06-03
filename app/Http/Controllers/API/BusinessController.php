<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Admin\Category;
use App\Models\BusinessAddress;
use App\Models\BusinessHour;
use App\Models\BusinessImage;
use App\Models\BusinessProfile;
use App\Models\User;
use App\Services\QrService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BusinessController extends Controller
{
    public function __construct(
        private readonly QrService $qrService
    ) {}

    public function showRegistrationForm() { return view('business.register'); }
    public function createRewardForm()     { return view('business.rewards.create'); }

    public function dashboard()
    {
        $user     = Auth::user();
        $business = DB::table('business_profiles')->where('business_id', $user->id)->first();
        $rewards  = DB::table('rewards')->where('business_id', $user->id)->get();
        return view('business.dashboard', compact('user', 'business', 'rewards'));
    }

    public function handleLogin(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return back()->with('error', 'Invalid login credentials.');
        }

        $request->session()->regenerate();
        $user = Auth::user();
        if ($user->canActAsBusiness()) {
            return redirect()->route('business.dashboard');
        }

        return match ($user->role) {
            'customer' => redirect()->route('customer.dashboard'),
            default    => redirect()->route('login')->with('error', 'Invalid user role.'),
        };
    }

    // QR Code 
    public function generateEncryptedQrCode()
    {
        $businessId = Auth::user()->effectiveBusinessId();
        $payload   = ['type' => 'business_checkin', 'business_id' => $businessId];
        $encrypted = $this->qrService->encryptPayload($payload);
        $qrCode    = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(300)->generate($encrypted);
        return view('business.qr-code', ['qrCode' => $qrCode, 'textCode' => base64_encode($encrypted)]);
    }

    public function generateEncryptedQrCodeApi()
    {
        $businessId = Auth::user()->effectiveBusinessId();
        $payload   = ['type' => 'business_checkin', 'business_id' => $businessId];
        $encrypted = $this->qrService->encryptPayload($payload);
        $qrCode    = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(300)->generate($encrypted);

        return response()->json([
            'message'   => 'Business QR code generated successfully.',
            'qr_code'   => 'data:image/png;base64,' . base64_encode($qrCode),
            'text_code' => base64_encode($encrypted),
        ]);
    }

    // Points
    public function pointsSummaryApi()
    {
        $businessId = Auth::user()->effectiveBusinessId();

        $business = DB::table('business_profiles')->where('business_id', $businessId)->first();
        if (!$business) {
            return response()->json(['error' => 'Business profile not found.'], 404);
        }

        return response()->json([
            'business_id'              => $businessId,
            'monthly_points_available' => $business->monthly_points_available,
            'points_given'             => $business->points_given,
            'points_redeemed'          => $business->points_redeemed,
            'points_per_checkin'       => $business->points_per_checkin,
        ]);
    }

    public function getCheckinPointsApi()
    {
        $businessId = Auth::user()->effectiveBusinessId();

        $business = BusinessProfile::where('business_id', $businessId)->first();
        if (!$business) {
            return response()->json(['error' => 'Business profile not found.'], 404);
        }

        return response()->json([
            'business_id'        => $businessId,
            'points_per_checkin' => $business->points_per_checkin,
        ]);
    }

    public function updateCheckinPointsApi(Request $request)
    {
        $validated = $request->validate([
            'points_per_checkin' => 'required|integer|min:10',
        ]);

        try {
            DB::table('business_profiles')->where('business_id', Auth::id())->update(['points_per_checkin' => $validated['points_per_checkin']]);
            return response()->json(['message' => 'Check-in points updated successfully.']);
        } catch (\Exception $e) {
            Log::error('Error updating check-in points: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update check-in points.'], 500);
        }
    }

    // Business Profile
    public function index()
    {
        return response()->json(BusinessProfile::all());
    }

    public function show(int $id)
    {
        $business = BusinessProfile::find($id);
        return $business ? response()->json($business) : response()->json(['error' => 'Business not found'], 404);
    }

    public function getBusinessDetailsOLDV1(int $id)
    {
        try {
            $business = User::with(['businessProfile', 'businessAddress', 'businessImage', 'businessHours'])->findOrFail($id);
            return response()->json(['business' => $business]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong', 'details' => $e->getMessage()], 500);
        }
    }

    public function getBusinessDetails(?int $id = null)
    {
        try {
            $userId = $id ?? Auth::id();

            if (!$userId) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $user = User::findOrFail($userId);
            $businessId = $user->effectiveBusinessId();
            if (!$businessId) {
                return response()->json(['error' => 'No business linked to this account'], 404);
            }

            $business = User::with(['businessProfile', 'businessAddress', 'businessImage', 'businessHours'])->findOrFail($businessId);
            return response()->json(['business' => $business]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong', 'details' => $e->getMessage()], 500);
        }
    }

    public function dashboardBusinessData()
    {
        $user = Auth::user();
        if (!$user->canActAsBusiness()) {
            return response()->json(['message' => 'Business access required.'], 403);
        }

        $businessId = $user->effectiveBusinessId();

        try {
            $today        = Carbon::today();
            $now          = Carbon::now();
            $startOfWeek  = Carbon::now()->startOfWeek();
            $startOfMonth = Carbon::now()->startOfMonth();

            return response()->json([
                'today_checkins'      => DB::table('checkins')->where('business_id', $businessId)->whereDate('check_in_time', $today)->count(),
                'today_reward_claims' => DB::table('reward_redemptions')->where('business_id', $businessId)->where('status', 'success')->whereDate('redeemed_at', $today)->count(),
                'active_customers'    => [
                    'weekly'  => DB::table('checkins')->where('business_id', $businessId)->whereBetween('check_in_time', [$startOfWeek, $now])->distinct('customer_id')->count('customer_id'),
                    'monthly' => DB::table('checkins')->where('business_id', $businessId)->whereBetween('check_in_time', [$startOfMonth, $now])->distinct('customer_id')->count('customer_id'),
                ],
                'recent_active_customers' => DB::table('checkins as c')
                    ->join('users as u', 'u.id', '=', 'c.customer_id')
                    ->select('u.id as customer_id', 'u.name', 'u.profile_image', 'c.check_in_time')
                    ->where('c.business_id', $businessId)
                    ->orderByDesc('c.check_in_time')
                    ->limit(5)
                    ->get(),
                'open_complaints_last_30_days' => DB::table('complaints')
                    ->where('business_id', $businessId)
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->whereDate('created_at', '>=', Carbon::now()->subDays(30))
                    ->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong', 'details' => $e->getMessage()], 500);
        }
    }

    public function updateBusinessProfile(Request $request)
    {
        if (Auth::user()->role !== 'business') {
            return response()->json(['message' => 'Please login with a valid Business account'], 403);
        }

        $id = Auth::id();
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $id,
            'address1'      => 'nullable|string|max:255',
            'address2'      => 'nullable|string|max:255',
            'city'          => 'nullable|string|max:55',
            'state'         => 'nullable|string|max:55',
            'country'       => 'nullable|string|max:55',
            'zip_code'      => 'nullable|string|max:20',
            'latitude'      => 'nullable|numeric|between:-90,90',
            'longitude'     => 'nullable|numeric|between:-180,180',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $user = User::findOrFail($id);

            $imageUrl = null;
            if ($request->hasFile('profile_image')) {
                $image     = $request->file('profile_image');
                $imageName = 'profile_' . time() . '.' . $image->getClientOriginalExtension();
                $image->storeAs('profile_images', $imageName, 'public');
                $imageUrl  = asset('storage/profile_images/' . $imageName);
            }

            $user->update([
                'name'          => $request->name,
                'email'         => $request->email,
                'phone'         => $request->phone,
                'profile_image' => $imageUrl ?? $user->profile_image,
            ]);

            $socialLinks = array_map('trim', explode(',', $request->social_links ?? ''));

            BusinessProfile::updateOrCreate(
                ['business_id' => $id],
                ['business_name' => $request->name, 'website' => $request->website, 'social_links' => json_encode($socialLinks)]
            );

            BusinessAddress::updateOrCreate(
                ['business_id' => $id],
                [
                    'address_line1' => $request->address1,
                    'address_line2' => $request->address2,
                    'city'          => $request->city,
                    'state'         => $request->state,
                    'country'       => $request->country,
                    'zip_code'      => $request->zip_code,
                    'latitude'      => $request->latitude,
                    'longitude'     => $request->longitude,
                ]
            );

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $imageName = time() . '_' . $image->getClientOriginalName();
                    $image->storeAs('businesses', $imageName, 'public');
                    BusinessImage::create(['business_id' => $id, 'photo' => asset('storage/businesses/' . $imageName)]);
                }
            }

            Log::info('Business profile updated:', ['user_id' => $id]);
            return response()->json(['status' => true, 'message' => 'Business profile updated successfully']);

        } catch (\Exception $e) {
            Log::error('Business update failed:', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    // Business Hours
    public function updateBusinessHours(Request $request)
    {
        if (Auth::user()->role !== 'business') {
            return response()->json(['message' => 'Unauthorized. Only businesses can update hours.'], 403);
        }

        $request->validate([
            'business_hours'                 => 'required|array|min:1',
            'business_hours.*.day_of_week'   => 'required|string',
            'business_hours.*.open_time'     => 'nullable|date_format:H:i:s',
            'business_hours.*.close_time'    => 'nullable|date_format:H:i:s',
            'business_hours.*.is_closed'     => 'required|boolean',
        ]);

        foreach ($request->business_hours as $hours) {
            BusinessHour::updateOrCreate(
                ['business_id' => Auth::id(), 'day_of_week' => $hours['day_of_week']],
                [
                    'open_time'  => $hours['is_closed'] ? null : $hours['open_time'],
                    'close_time' => $hours['is_closed'] ? null : $hours['close_time'],
                    'is_closed'  => $hours['is_closed'],
                ]
            );
        }

        return response()->json(['status' => true, 'message' => 'Business hours updated successfully.']);
    }

    // Password
    public function changePassword(Request $request)
    {
        if (Auth::user()->role !== 'business') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password'     => ['required','min:8','regex:/[a-z]/','regex:/[A-Z]/','regex:/[0-9]/','regex:/[@$!%*#?&]/'],
            'confirm_password' => 'required|same:new_password',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!Hash::check($request->current_password, Auth::user()->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 400);
        }

        Auth::user()->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['message' => 'Password changed successfully.']);
    }

    public function updateOwnerDetail(Request $request)
    {
        if (Auth::user()->role !== 'business') {
            return response()->json(['message' => 'Please login with a valid Business account'], 403);
        }

        $validator = Validator::make($request->all(), [
            'owner_name'  => 'required|string|max:255',
            'owner_email' => 'required|email|unique:users,email',
            'owner_phone' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        BusinessProfile::updateOrCreate(
            ['business_id' => Auth::id()],
            ['owner_name' => $request->owner_name, 'owner_email' => $request->owner_email, 'owner_phone' => $request->owner_phone]
        );

        return response()->json(['status' => true, 'message' => 'Owner details updated successfully']);
    }

    public function updateBusinessLanguage(Request $request)
    {
        $request->validate(['language' => 'required|in:en,es']);

        $profile = BusinessProfile::where('business_id', Auth::id())->firstOrFail();
        $profile->update(['preferred_language' => $request->language]);

        return response()->json(['message' => 'Language updated successfully', 'language' => $profile->preferred_language]);
    }

    public function pushNotificationUpdate(Request $request)
    {
        $request->validate(['enabled' => 'required|boolean']);
        $profile = BusinessProfile::where('business_id', Auth::id())->firstOrFail();
        $profile->update(['push_notification' => $request->boolean('enabled') ? '1' : '0']);

        return response()->json(['message' => 'Push notification setting updated successfully.', 'push_notification' => $profile->push_notification]);
    }

    // ─── Staff ──
    public function createStaff(Request $request)
    {
        $business = Auth::user();
        if ($business->role !== 'business') {
            return response()->json(['message' => 'Only businesses can add staff'], 403);
        }

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|in:manager,employee',
        ]);

        // $totalStaff = User::where('parent_business_id', $business->id)->whereIn('role', ['manager', 'employee'])->whereNull('deleted_at')->count();
        // if ($totalStaff >= 5) {
        //     return response()->json(['message' => 'You can only add up to 5 staff members.'], 400);
        // }

        try {
            $user = User::create([
                'name'               => $validated['name'],
                'email'              => $validated['email'],
                'password'           => Hash::make($validated['password']),
                'role'               => $validated['role'],
                'parent_business_id' => $business->id,
                'providers'          => 'email',
                'status'             => 'active',
            ]);

            return response()->json(['message' => 'Staff created successfully', 'user' => $user]);
        } catch (\Exception $e) {
            Log::error('Staff creation failed:', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function listStaff()
    {
        $staff = User::select('id', 'name', 'email', 'role')->where('parent_business_id', Auth::id())->whereIn('role', ['manager', 'employee'])->whereNull('deleted_at')->get();
        return response()->json($staff);
    }

    public function deactivateStaff(int $id)
    {
        try {
            $staff = User::where('parent_business_id', Auth::id())->whereIn('role', ['manager', 'employee'])->findOrFail($id);
            $staff->delete();
            return response()->json(['message' => 'Staff member deactivated successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    // Category
    public function createGrandChildCategory(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'required|exists:categories,id',
            'child_id'  => 'required|exists:categories,id',
            'name'      => 'required|string|max:255',
        ]);

        $childCategory = Category::findOrFail($validated['child_id']);
        if ($childCategory->parent_id != $validated['parent_id']) {
            return response()->json(['status' => false, 'message' => 'The selected child category is not under the specified parent.'], 422);
        }

        $existing = Category::where('name', $validated['name'])->where('parent_id', $validated['child_id'])->first();
        if ($existing) {
            return response()->json(['status' => false, 'message' => 'This grandchild category already exists.'], 409);
        }

        $grandchild = Category::create(['name' => $validated['name'], 'parent_id' => $validated['child_id']]);

        return response()->json(['status' => true, 'id' => $grandchild->id]);
    }

    public function customerListing(Request $request)
    {
        $user = Auth::user();
        if (!$user->canActAsBusiness()) {
            return response()->json(['message' => 'Business access required.'], 403);
        }

        $businessId = $user->effectiveBusinessId();
        $request->validate([
            'tier'   => 'nullable|in:new,regular,daily',
            'search' => 'nullable|string|max:255',
        ]);

        // Load business-specific tier thresholds (fallback to defaults if not set)
        $business   = BusinessProfile::where('business_id', $businessId)->first();
        $regularMin = (int) ($business->tier_regular_min ?: 3);
        $dailyMin   = (int) ($business->tier_daily_min   ?: 10);

        $query = DB::table('checkins as c')
            ->join('users as u', 'u.id', '=', 'c.customer_id')
            ->leftJoin('customer_profiles as cp', 'cp.customer_id', '=', 'u.id')
            ->where('c.business_id', $businessId)
            ->where('u.role', 'customer')
            ->groupBy('u.id', 'u.name', 'u.profile_image', 'cp.anonymous_name', 'cp.anonymous_image')
            ->select(
                'u.id as customer_id',
                'u.name',
                'u.profile_image',
                'cp.anonymous_name',
                'cp.anonymous_image',
                DB::raw('COUNT(c.id) as checkin_count'),
                DB::raw('MAX(c.check_in_time) as last_visit')
            );

        if ($request->filled('search')) {
            $query->where('u.name', 'LIKE', '%' . $request->search . '%');
        }

        // Tier filter
        match ($request->tier) {
            'daily'   => $query->having('checkin_count', '>=', $dailyMin),
            'regular' => $query->having('checkin_count', '>=', $regularMin)->having('checkin_count', '<', $dailyMin),
            'new'     => $query->having('checkin_count', '<', $regularMin),
            default   => null,
        };

        $customers = $query->orderByDesc('last_visit')->paginate(20);
        $customers->getCollection()->transform(function ($c) use ($regularMin, $dailyMin) {
            $c->tier = $business->tierFor($c->checkin_count);
            return $c;
        });

        return response()->json($customers);
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

    public function getCustomerTierThresholdsApi()
    {
        $businessId = Auth::user()->effectiveBusinessId();

        $business = BusinessProfile::where('business_id', $businessId)->first();
        if (!$business) {
            return response()->json(['error' => 'Business profile not found.'], 404);
        }

        $regularMin = (int) $business->tier_regular_min;
        $dailyMin   = (int) $business->tier_daily_min;

        return response()->json([
            'business_id'      => $businessId,
            'tier_regular_min' => $regularMin,
            'tier_daily_min'   => $dailyMin,
            'tiers' => [
                'new'     => "< {$regularMin} check-ins",
                'regular' => "{$regularMin} - " . ($dailyMin - 1) . " check-ins",
                'daily'   => ">= {$dailyMin} check-ins",
            ],
        ]);
    }

    public function updateCustomerTierThresholdsApi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tier_regular_min' => 'required|integer|min:1',
            'tier_daily_min'   => 'required|integer|min:2|gt:tier_regular_min',
        ], [
            'tier_daily_min.gt' => 'Daily tier minimum must be greater than regular tier minimum.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $businessId = Auth::user()->effectiveBusinessId();
            $regularMin = (int) $request->tier_regular_min;
            $dailyMin   = (int) $request->tier_daily_min;

            DB::transaction(function () use ($businessId, $regularMin, $dailyMin) {
                // Update thresholds on business profile
                BusinessProfile::where('business_id', $businessId)->update([
                    'tier_regular_min' => $regularMin,
                    'tier_daily_min'   => $dailyMin,
                ]);

                // Recalc all existing relationships for this business
                DB::table('customer_business_relationships')
                    ->where('business_id', $businessId)
                    ->update([
                        'customer_tier' => DB::raw("
                            CASE
                                WHEN interaction_count >= {$dailyMin}   THEN 'daily'
                                WHEN interaction_count >= {$regularMin} THEN 'regular'
                                ELSE 'new'
                            END
                        "),
                        'updated_at' => now(),
                    ]);
            });

            return response()->json([
                'status'  => true,
                'message' => 'Customer tier thresholds updated and existing customers re-tiered successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating tier thresholds: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Failed to update thresholds.'], 500);
        }
    }
}
