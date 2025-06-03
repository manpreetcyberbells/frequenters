<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\User;
use App\Services\FCMService;
use App\Services\NearbyCustomerService;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\CreateEventRequest;
use App\Models\EventReservation;
use App\Models\City;
use App\Models\Zip;

class AdminEventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $events = Event::with('businessDetail')->orderBy('id', 'desc')->get();
        return view('admin.businesses.event.index', compact('events'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.businesses.event.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // echo"<pre>"; print_r($request->all());  die;
        $request->validate([
            'business_id' => 'required|exists:business_profiles,business_id',
            'event_name' => 'required|string|max:255',
            'event_description' => 'nullable|string',
            'venue_name' => 'required|string|max:255',
            'venue_location' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:draft,published',
            'event_banner' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        try{   
            $businessId = $request->business_id;
            // Extract ZIP code from venue_location (assuming last part is ZIP)
            $venueParts = explode(',', $request->venue_location);
            $zipCode = trim(end($venueParts));

            // Get city_id
            $zipData = Zip::where('zip_code', $zipCode)->first();
            if (!$zipData) {
                return redirect()->back()->with('error', 'Invalid ZIP code. City not found.');
            }

            // Get latitude and longitude
            $cityData = City::where('id', $zipData->city_id)->first();
            if (!$cityData) {
                return redirect()->back()->with('error', 'City data not found for the given ZIP code.');
            }

            // Handle event banner upload
            $bannerPath = null;
            if ($request->hasFile('event_banner')) {
                $bannerPath = $request->file('event_banner')->store('events/banners', 'public');
            }

            // Create the event
            $event = Event::create([
                'business_id' => $businessId,
                'event_name' => $request->event_name,
                'event_description' => $request->event_description,
                'venue_name' => $request->venue_name,
                'venue_location' => $request->venue_location,
                'capacity' => $request->capacity,
                'event_banner' => asset('storage/' . $bannerPath),
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'start_time' => $request->start_time.':00',
                'end_time' => $request->end_time.':00',
                'status' => $request->status,
                'target_city_id' => $zipData->city_id,
                'latitude' => $cityData->latitude,
                'longitude' => $cityData->longitude
            ]);

            $radius = 10; // kilometers
            $latitude = $event->latitude;
            $longitude = $event->longitude;
            $nearbyService = new NearbyCustomerService();
            $nearbyCustomerIds = $nearbyService->getNearbyCustomerIds($latitude, $longitude, $radius);

            // FCM => Push Notifications
            $fcmService = new FCMService();
            $title = "Something’s Happening Near You!";
            $body = "There's an event close by you might love. Check it out and make your day a little less boring";
            $image = asset('storage/notifications/notification.png');

            foreach ($nearbyCustomerIds as $customerId) {
                $fcmToken = DB::table('customer_profiles')->where('customer_id',$customerId)->pluck('fcm_token')->first();
                // Sending Notification on Device
                $fcmService->sendNotificationToDevice($fcmToken, $title, $body, $image);

                // Store into DB
                customerNotification($customerId, $body, "push", null, true);
            }

            return redirect()->route('events.index')->with('success', 'Event created successfully!');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage() ?? 'Error creating event');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $event = Event::with('businessDetail')->findOrFail($id);
        echo"<pre>"; print_r($event);  die;
        return view('admin.businesses.event.edit', compact('event'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
