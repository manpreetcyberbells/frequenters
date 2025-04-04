<?php

namespace App\Http\Controllers\API;

use App\Models\Offer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreOfferRequest;
use Carbon\Carbon;

class OfferController extends Controller 
{
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

            // Notification
            createNotification($businessId, $offer->title, "push", null, true);

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

}
