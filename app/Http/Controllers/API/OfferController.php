<?php

namespace App\Http\Controllers\API;

use App\Models\Offer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreOfferRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\FCMService;
use App\Services\NearbyCustomerService;

class OfferController extends Controller 
{
    /**
     *  Common Function. 
    */
    private function haversineDistanceQuery($latitude, $longitude, $radiusInKm = 10)
    {
        return DB::raw("(
            6371 * acos(
                cos(radians($latitude)) * 
                cos(radians(latitude)) * 
                cos(radians(longitude) - radians($longitude)) + 
                sin(radians($latitude)) * sin(radians(latitude))
            )
        )");
    }

    /**
     *  API Function Offer Listing
    */
    public function offerListing(Request $request)
    {
        try{
            $business = auth()->user();
            $perPage = $request->get('per_page', 10);
            $offers = Offer::where('business_id', $business->id)->paginate($perPage);

            $transformed = $offers->map(function ($offer) {
                return [
                    'id' => $offer->id,
                    'business_id' => $offer->business_id,
                    'title' => $offer->title,
                    'applicable_users' => $offer->applicable_users,
                    'createdAt' => Carbon::parse($offer->created_at)->diffForHumans(),
                ];
            });


            return response()->json([
                'message' => 'Offers retrieved successfully',
                'data' => [
                    'current_page' => $offers->currentPage(),
                    'last_page' => $offers->lastPage(),
                    'per_page' => $offers->perPage(),
                    'total' => $offers->total(),
                    'offers' => $transformed,
                ]
            ], 200);

            
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     *  API Function to create Offer. 
    */
    public function businessCreateOffer(StoreOfferRequest $request) 
    {
        try {
            $businessId = auth()->id();
            $user = auth()->user();

            if ($user->role !== 'business') {
                return response()->json(['message' => 'Please login with a valid Business account'], 403);
            }

            $bannerPath = null;
            if ($request->hasFile('banner')) {
                $bannerPath = $request->file('banner')->store('offers/banners', 'public');
            }

            // Create Offer with validated data + banner path
            $offer = Offer::create(array_merge($request->validated(), [
                'banner_image' => asset('storage/' . $bannerPath)
            ]));

            $businessAddress = DB::table('business_addresses')->where('business_id',$businessId)->first();
            $radius = 10; // kilometers
            $latitude = @$businessAddress->latitude;
            $longitude = @$businessAddress->longitude;

            $nearbyService = new NearbyCustomerService();
            $nearbyCustomerIds = $nearbyService->getNearbyCustomerIds($latitude, $longitude, $radius);

            $fcmService = new FCMService();
            $title = "New Offer";
            $body = "Offer available for you";
            $image = asset('storage/notifications/notification.png');

            foreach ($nearbyCustomerIds as $customerId) {
                $fcmToken = DB::table('customer_profiles')->where('customer_id',$customerId)->pluck('fcm_token')->first();
                // Sending Notification on Device
                $fcmService->sendNotificationToDevice($fcmToken, $title, $body, $image);

                // Store into DB
                customerNotification($customerId, $body, "push", null, true);
            }
            
            return response()->json(['message' => 'Offer created successfully', 'offer' => $offer], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Something went wrong', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     *  API Function to Fetch Offers. 
    */
    public function getOffersDetail(Request $request){
        try {
            $businessOffers = Offer::where('is_active', true)
                ->orderBy('discount_percentage', 'desc')
                ->paginate(10)
                ->through(function ($offer) {
                    $startDateTime = Carbon::parse($offer->campaign_start_date . ' ' . $offer->campaign_start_time);
                    $endDateTime = Carbon::parse($offer->campaign_end_date . ' ' . $offer->campaign_end_time);
                    $now = Carbon::now();

                    // Calculate the offer duration
                    $diffInHours = $now->diffInHours($endDateTime, false);
                    $diffInDays = $now->diffInDays($endDateTime, false);
                    $diffInMonths = $now->diffInMonths($endDateTime, false);

                    // Determine the validity status
                    if ($offer->campaign_start_date === $offer->campaign_end_date) {
                        $validity = "Valid today only";
                    } elseif ($diffInHours <= 24 && $diffInDays == 0) {
                        $validity = "Valid for a few hours";
                    } elseif ($diffInDays == 1) {
                        $validity = "Valid today only";
                    } elseif ($diffInDays <= 7) {
                        $validity = "Valid this week";
                    } elseif ($diffInMonths == 0) {
                        $validity = "Valid this month";
                    } else {
                        $validity = "Valid until " . $endDateTime->format('M d, Y');
                    }

                    $offer->validity_status = $validity;
                    return $offer;
                });

            return response()->json(['status' => true, 'offers' => $businessOffers], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     *  API Function : Recommended Offers. 
    */
    public function getRecommendedOffers(Request $request)
    {
        try {
            $user = auth()->user();
            $businessId = $user->id;
            if ($user->role !== 'business') {
                return response()->json(['error' => 'Unauthorized. Only businesses can view this.'], 403);
            }

            $businessProfile = DB::table('business_profiles')->where('business_id', $businessId)->first();
            $categoryId = $businessProfile->child_category_id;
            // echo"<pre>"; print_r($categoryId);  die;
            // 1. Get logged-in business's location & category
            $businessAddress = DB::table('business_addresses')->where('business_id', $businessId)->first();
            if (!$businessAddress || !$categoryId) {
                return response()->json(['error' => 'Business address or category not found.'], 404);
            }
            
            $latitude = $businessAddress->latitude;
            $longitude = $businessAddress->longitude;
            $radius = 10; // in KM

            // 2. Find nearby businesses of same category (excluding current)
            $nearbyBusinessIds = DB::table('users')
                ->join('business_addresses', 'business_addresses.business_id', '=', 'users.id')
                ->join('business_profiles', 'business_profiles.business_id', '=', 'users.id')
                ->where('business_profiles.child_category_id', $categoryId)
                ->where('users.role', 'business')
                ->where('users.id', '!=', $businessId)
                ->whereRaw($this->haversineDistanceQuery($latitude, $longitude, $radius))
                ->select('users.id')
                ->pluck('id');
            
            if ($nearbyBusinessIds->isEmpty()) {
                return response()->json(['recommended_offers' => []], 200);
            }

            // 3. Fetch active offers from those businesses
            $offers = Offer::whereIn('business_id', $nearbyBusinessIds)
                ->where('is_active', true)
                ->orderBy('discount_percentage', 'desc')
                ->limit(5)
                ->get()
                // ->paginate(10)
                ->map(function ($offer) {
                    $startDateTime = Carbon::parse($offer->campaign_start_date . ' ' . $offer->campaign_start_time);
                    $endDateTime = Carbon::parse($offer->campaign_end_date . ' ' . $offer->campaign_end_time);
                    $now = Carbon::now();
                    if ($offer->campaign_start_date === $offer->campaign_end_date) {
                        $validity = "Valid today only";
                    } elseif ($now->diffInHours($endDateTime, false) <= 24) {
                        $validity = "Valid for a few hours";
                    } elseif ($now->diffInDays($endDateTime, false) <= 7) {
                        $validity = "Valid this week";
                    } elseif ($now->diffInMonths($endDateTime, false) == 0) {
                        $validity = "Valid this month";
                    } else {
                        $validity = "Valid until " . $endDateTime->format('M d, Y');
                    }
                    $offer->validity_status = $validity;
                    $offer->business_name = $offer->business->name ?? null;
                    return $offer;
                });

            return response()->json(['recommended_offers' => $offers], 200);

        } catch (\Exception $e) {
            Log::error('Error in getRecommendedOffers: ' . $e->getMessage());
            return response()->json(['error' => 'Something went wrong.'], 500);
        }
    }
}
