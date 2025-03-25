<?php

namespace App\Http\Controllers\API;

use App\Models\Offer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreOfferRequest;

class OfferController extends Controller 
{
    /**
     *  API Function to create Offer. 
    */
    public function businessCreateOffer(StoreOfferRequest $request) 
    {
        try {
            $bannerPath = null;
            if ($request->hasFile('banner')) {
                $bannerPath = $request->file('banner')->store('offers/banners', 'public');
            }

            // Create Offer with validated data + banner path
            $offer = Offer::create(array_merge($request->validated(), [
                'banner_image' => asset('storage/' . $bannerPath)
            ]));

            return response()->json(['message' => 'Offer created successfully', 'offer' => $offer], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Something went wrong', 'error' => $e->getMessage()], 500);
        }
    }

}
