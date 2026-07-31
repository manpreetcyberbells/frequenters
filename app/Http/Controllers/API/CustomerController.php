<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Models\Offer;
use App\Models\Review;
use App\Models\BusinessProfile;
use App\Services\BusinessQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function __construct(
        private readonly BusinessQueryService $businessQuery
    ) {}


    public function showLoginForm()       { return view('customer.login'); }
    public function showRegistrationForm(){ return view('customer.register'); }
    public function dashboard()           { return view('customer.dashboard', ['customer' => Auth::user()]); }

    public function handleLogin(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return $request->expectsJson()
                ? response()->json(['error' => 'Unauthorized'], 401)
                : back()->with('error', 'Invalid login credentials.');
        }

        $user = Auth::user();
        $user->update(['last_login_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Login successful',
                'token'   => $user->createToken($user->role . '-token')->plainTextToken,
                'user'    => $user,
                'role'    => $user->role,
            ]);
        }

        $request->session()->regenerate();

        return match ($user->role) {
            'business' => redirect()->route('business.dashboard'),
            'customer' => redirect()->route('customer.dashboard'),
            default    => back()->with('error', 'User role is invalid.'),
        };
    }

    public function logout(Request $request)
    {
        if ($request->expectsJson()) {
            $request->user()->tokens()->delete();
            return response()->json(['message' => 'Logged out successfully']);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    // Customer Home (API)
    public function getHomeBusinesses(Request $request)
    {
        $customerId = Auth::id();
        $profile    = Auth::user()->customerProfile;
        $listType   = $request->input('listType');
        $categoryId = $request->input('category_id');

        // Live GPS from device
        $lat = $request->input('latitude');
        $lng = $request->input('longitude');

        // Fallback
        if (!$lat || !$lng) {
            $customerAddress = CustomerAddress::where('customer_id', $customerId)->where('is_current', '1')->first() ?? CustomerAddress::where('customer_id', $customerId)->first();
            $lat = $customerAddress->latitude ?? null;
            $lng = $customerAddress->longitude ?? null;
        }

        $hasLocation = $lat && $lng;
        $nearby = $hasLocation ? $this->businessQuery->nearbyBusinesses($customerId, $lat, $lng, $categoryId) : [];
        $highestRewards = $hasLocation ? $this->businessQuery->highestRewardBusinesses($customerId, $lat, $lng, $categoryId) : [];
        $frequent = $hasLocation ? $this->businessQuery->frequentlyVisited($customerId, $lat, $lng, $categoryId) : [];
        $offers = $this->getNearbyOffers($lat, $lng);

        $businessIds = BusinessProfile::pluck('business_id')->toArray();
        $reviews = Review::whereIn('business_id', $businessIds)->with(['customer:id,name,profile_image'])->orderByDesc('created_at')->get();
        $addresses   = CustomerAddress::where('customer_id', $customerId)->get();
        $data = [
            'name' => Auth::user()->name,
            'title' => 'Good Morning Bestie',
            'subtitle' => "Yo, you just snagged {$profile->points_balance} exclusive points!",
            'reward_points' => $profile->points_balance ?? 0,
            'needs_location' => !$hasLocation,
            'frequent' => $frequent,
            'highest_rewards' => $highestRewards,
            'nearby' => $nearby,
            'reviews' => $reviews,
            'offers' => $offers,
            'address' => $addresses,
        ];

        if ($listType) {
            if (!array_key_exists($listType, $data)) {
                return response()->json(['error' => 'Invalid listType parameter'], 400);
            }
            return response()->json([$listType => $data[$listType]]);
        }

        return response()->json($data);
    }

    /**
     * Get active offers ordered by distance from customer location.
     * Falls back to discount_percentage order if no location provided.
     */
    private function getNearbyOffers(?float $lat, ?float $lng, int $limit = 5)
    {
        $query = Offer::query()
            ->where('offers.is_active', true)
            ->join('business_addresses', 'business_addresses.business_id', '=', 'offers.business_id')
            ->whereNull('business_addresses.deleted_at')
            ->whereNotNull('business_addresses.latitude')
            ->whereNotNull('business_addresses.longitude')
            ->select('offers.*')
            ->selectRaw("
                ROUND(
                    6371 * acos(
                        cos(radians(?)) * cos(radians(business_addresses.latitude))
                        * cos(radians(business_addresses.longitude) - radians(?))
                        + sin(radians(?)) * sin(radians(business_addresses.latitude))
                    ), 2
                ) AS distance
            ", [$lat, $lng, $lat]);

        if ($lat && $lng) {
            $query->orderByRaw("
                (
                    (6371 * acos(
                        cos(radians(?)) * cos(radians(business_addresses.latitude))
                        * cos(radians(business_addresses.longitude) - radians(?))
                        + sin(radians(?)) * sin(radians(business_addresses.latitude))
                    )) * 0.5
                    + (DATEDIFF(NOW(), offers.created_at) * 0.3)
                ) ASC
            ", [$lat, $lng, $lat]);
        } else {
            $query->orderByDesc('offers.discount_percentage');
        }

        return $query->limit($limit)->get();
    }

    // Web helpers
    public function searchBusinesses(Request $request)
    {
        $query = $request->get('query', '');
        $businesses = DB::table('business_profiles')
            ->where('business_name', 'LIKE', "%{$query}%")
            ->orWhere('industry_type', 'LIKE', "%{$query}%")
            ->paginate(10);
        return view('customer.search', compact('businesses', 'query'));
    }

    public function viewBusinessProfile($businessId)
    {
        $business = DB::table('business_profiles')->where('business_id', $businessId)->first();
        $rewards  = DB::table('rewards')->where('business_id', $businessId)->get();
        $contests = DB::table('contests')->where('business_id', $businessId)->get();
        return view('customer.business-profile', compact('business', 'rewards', 'contests'));
    }
}
