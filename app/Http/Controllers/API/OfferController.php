<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOfferRequest;
use App\Models\Offer;
use App\Services\OfferService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OfferController extends Controller
{
    public function __construct(
        private readonly OfferService $offerService
    ) {}

    // Business: Offer Listing
    public function offerListingOLD(Request $request)
    {
        try {
            $perPage = $request->integer('per_page', 10);
            $offers = Offer::where('business_id', Auth::id())->orderByDesc('created_at')->paginate($perPage);
            $transformed = $offers->map(fn($offer) => [
                'id'               => $offer->id,
                'business_id'      => $offer->business_id,
                'title'            => $offer->title,
                'applicable_users' => $offer->applicable_users,
                'createdAt'        => Carbon::parse($offer->created_at)->diffForHumans(),
            ]);

            return response()->json([
                'message' => 'Offers retrieved successfully',
                'data'    => [
                    'current_page' => $offers->currentPage(),
                    'last_page'    => $offers->lastPage(),
                    'per_page'     => $offers->perPage(),
                    'total'        => $offers->total(),
                    'offers'       => $transformed,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    public function offerListing(Request $request)
    {
        try {
            $businessId = Auth::user()->effectiveBusinessId();

            $perPage = $request->integer('per_page', 10);
            $offers = Offer::where('business_id', $businessId)
                ->orderByDesc('created_at')
                ->paginate($perPage);

            $transformed = $offers->getCollection()->map(fn($offer) => [
                'id'               => $offer->id,
                'business_id'      => $offer->business_id,
                'title'            => $offer->title,
                'applicable_users' => $offer->applicable_users,
                'createdAt'        => Carbon::parse($offer->created_at)->diffForHumans(),
            ]);

            return response()->json([
                'message' => 'Offers retrieved successfully',
                'data'    => [
                    'current_page' => $offers->currentPage(),
                    'last_page'    => $offers->lastPage(),
                    'per_page'     => $offers->perPage(),
                    'total'        => $offers->total(),
                    'offers'       => $transformed,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    // Business: Create Offer
    public function businessCreateOffer(StoreOfferRequest $request)
    {
        if (Auth::user()->role !== 'business') {
            return response()->json(['message' => 'Please login with a valid Business account'], 403);
        }

        try {
            $businessId = Auth::id();
            $bannerPath = $request->hasFile('banner')? asset('storage/' . $request->file('banner')->store('offers/banners', 'public')): null;

            $offer = Offer::create(array_merge($request->validated(), [
                'business_id' => Auth::id(),
                'banner_image' => $bannerPath,
            ]));

            // Nearby notifications
            $address = DB::table('business_addresses')->where('business_id', $businessId)->first();
            $this->offerService->sendNearbyNotifications(
                $businessId,
                $address?->latitude,
                $address?->longitude,
                $offer
            );

            return response()->json([
                'message' => 'Offer created successfully',
                'offer'   => $offer,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating offer: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    // Customer: Active Offers
    public function getOffersDetail(Request $request)
    {
        try {
            $today = now()->toDateString();
            $offers = Offer::where('is_active', true)
                ->where('campaign_end_date', '>=', $today)
                ->orderByRaw("
                    CASE 
                        WHEN campaign_start_date = CURDATE() THEN 0
                        WHEN campaign_start_date > CURDATE() THEN 1
                    END ASC
                ")
                ->orderByDesc('campaign_start_date')
                ->paginate(10)
                ->through(function ($offer) {
                    $end   = \Carbon\Carbon::parse($offer->campaign_end_date);
                    $today = now()->toDateString();
                    $validityStatus = ($offer->campaign_start_date === $today && $offer->campaign_end_date === $today) ? 'Valid Today Only' : 'Valid until ' . $end->format('d M y');
                    return array_merge($offer->toArray(), [
                        'validity_status' => $validityStatus,
                    ]);
                });

            return response()->json(['status' => true, 'offers' => $offers]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong.'], 500);
        }
    }

    // Business: Recommended Offers
    public function getRecommendedOffers(Request $request)
    {
        if (Auth::user()->role !== 'business') {
            return response()->json(['error' => 'Unauthorized. Only businesses can view this.'], 403);
        }

        try {
            $businessId = Auth::id();
            $businessProfile = DB::table('business_profiles')->where('business_id', $businessId)->first();
            $businessAddress = DB::table('business_addresses')->where('business_id', $businessId)->first();

            if (!$businessAddress || !$businessProfile?->child_category_id) {
                return response()->json(['error' => 'Business address or category not found.'], 404);
            }

            $nearbyBusinessIds = $this->offerService->getNearbyBusinessIds(
                $businessId,
                $businessProfile->child_category_id,
                $businessAddress->latitude,
                $businessAddress->longitude
            );

            if ($nearbyBusinessIds->isEmpty()) {
                return response()->json(['recommended_offers' => []]);
            }

            $offers = Offer::whereIn('business_id', $nearbyBusinessIds)->where('is_active', true)->orderByDesc('discount_percentage')->limit(5)->with('business:id,name')->get()
                ->map(fn($offer) => array_merge($offer->toArray(), [
                    'validity_status' => $offer->validity_status,
                    'business_name'   => $offer->business?->name,
                ]));

            return response()->json(['recommended_offers' => $offers]);

        } catch (\Exception $e) {
            Log::error('Error in getRecommendedOffers: ' . $e->getMessage());
            return response()->json(['error' => 'Something went wrong.'], 500);
        }
    }
}