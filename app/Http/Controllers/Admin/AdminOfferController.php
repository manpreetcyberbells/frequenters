<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\API\OfferController;
use App\Http\Requests\StoreOfferRequest;
use App\Models\User;
use App\Models\Offer;
use App\Services\FCMService;
use App\Services\NearbyCustomerService;
use Illuminate\Support\Facades\DB;

class AdminOfferController extends Controller
{
    public function index()
    {
        $offers = Offer::with('business')->orderBy('id', 'desc')->get();
        return view('admin.businesses.offer.index', compact('offers'));
    }

    public function create()
    {
        return view('admin.businesses.offer.create');
    }

    public function store(StoreOfferRequest $request)
    {
        try {
            $businessId = $request->business_id;
            $bannerPath = null;
            if ($request->hasFile('banner')) {
                $bannerPath = $request->file('banner')->store('offers/banners', 'public');
            }

            // Create Offer with validated data + banner path
            $offer = Offer::create(array_merge($request->validated(), [
                'banner_image' => asset('storage/' . $bannerPath)
            ]));

            $businessAddress = DB::table('business_addresses')->where('business_id',$businessId)->first();
            $radius = 10;
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
            
            return redirect()->route('admin.businesses.offer.index')->with('success', 'Offer created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage() ?? 'Error creating offer');
        }
    }
}
