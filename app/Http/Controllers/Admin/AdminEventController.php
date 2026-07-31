<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Event;
use App\Models\Zip;
use App\Services\FCMService;
use App\Services\NearbyCustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminEventController extends Controller
{
    public function __construct(
        private readonly FCMService $fcmService,
        private readonly NearbyCustomerService $nearbyCustomerService,
    ) {}

    public function index()
    {
        return view('admin.businesses.event.index', [
            'events' => Event::with('businessDetail')->orderByDesc('id')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.businesses.event.create');
    }

    public function store(Request $request)
    {
        // $request->validate([
        //     'business_id'       => 'required|exists:business_profiles,business_id',
        //     'event_name'        => 'required|string|max:255',
        //     'event_description' => 'nullable|string',
        //     'venue_name'        => 'required|string|max:255',
        //     'venue_location'    => 'required|string|max:255',
        //     'capacity'          => 'required|integer|min:1',
        //     'start_time'        => 'required|date_format:H:i',
        //     'end_time'          => 'required|date_format:H:i|after:start_time',
        //     'start_date'        => 'required|date|after_or_equal:today',
        //     'end_date'          => 'required|date|after_or_equal:start_date',
        //     'status'            => 'required|in:draft,published',
        //     'event_banner'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        // ]);

        // echo"<pre>"; print_r($request->all()); die;

        $validator = \Validator::make($request->all(), [
            'business_id'       => 'required|exists:business_profiles,business_id',
            'event_name'        => 'required|string|max:255',
            'event_description' => 'nullable|string',
            'venue_name'        => 'required|string|max:255',
            'venue_location'    => 'required|string|max:255',
            'capacity'          => 'required|integer|min:1',
            'start_time'        => 'required|date_format:H:i:s',
            'end_time'          => 'required|date_format:H:i:s|after:start_time',
            'start_date'        => 'required|date|after_or_equal:today',
            'end_date'          => 'required|date|after_or_equal:start_date',
            'status'            => 'required|in:draft,published',
            'event_banner'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $parts = explode(',', $request->venue_location);
            $zipCode = trim(end($parts));
            $zipData = Zip::where('zip_code', $zipCode)->first();
            if (!$zipData) return redirect()->back()->with('error', 'Invalid ZIP code.');

            $cityData = City::find($zipData->city_id);
            if (!$cityData) return redirect()->back()->with('error', 'City data not found.');

            $bannerPath = $request->hasFile('event_banner') ? asset('storage/' . $request->file('event_banner')->store('events/banners', 'public')) : null;
            $event = Event::create([
                'business_id'       => $request->business_id,
                'event_name'        => $request->event_name,
                'event_description' => $request->event_description,
                'venue_name'        => $request->venue_name,
                'venue_location'    => $request->venue_location,
                'capacity'          => $request->capacity,
                'event_banner'      => $bannerPath,
                'start_date'        => $request->start_date,
                'end_date'          => $request->end_date,
                'start_time'        => $request->start_time,
                'end_time'          => $request->end_time,
                'status'            => $request->status,
                'target_city_id'    => $zipData->city_id,
                'latitude'          => $cityData->latitude,
                'longitude'         => $cityData->longitude,
            ]);

            $nearbyIds = $this->nearbyCustomerService->getNearbyCustomerIds(
                $event->latitude,
                $event->longitude,
                10
            );

            if (!empty($nearbyIds)) {
                $tokens = DB::table('customer_profiles')->whereIn('customer_id', $nearbyIds)->whereNotNull('fcm_token')->pluck('fcm_token', 'customer_id');
                $title = "Something's Happening Near You!";
                $body  = "There's an event close by you might love. Check it out!";
                $image = url('storage/notifications/notification.png');
                foreach ($tokens as $customerId => $token) {
                    try {
                        $this->fcmService->sendToCustomerApp($token, $title, $body, $image);
                    } catch (\Throwable $e) {
                        \Log::error("Nearby event FCM failed for customer {$customerId}: " . $e->getMessage());
                    }
                    customerNotification($customerId, $body, 'push', null, true);
                }
            }

            return redirect()->route('events.index')->with('success', 'Event created successfully!');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage() ?? 'Error creating event');
        }
    }

    public function show(string $id)
    {
        $event = Event::with('businessDetail')->findOrFail($id);
        return view('admin.businesses.event.show', compact('event'));
    }

    public function edit(string $id)
    {
        $event = Event::with('businessDetail')->findOrFail($id);
        return view('admin.businesses.event.edit', compact('event'));
    }

    public function update(Request $request, string $id)
    {
        $validator = \Validator::make($request->all(), [
            'event_name'        => 'required|string|max:255',
            'venue_name'        => 'required|string|max:255',
            'venue_location'    => 'required|string|max:255',
            'capacity'          => 'required|integer|min:1',
            'start_date'        => 'required|date',
            'end_date'          => 'required|date|after_or_equal:start_date',
            'status'            => 'required|in:draft,published',
            'event_banner'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $event = Event::findOrFail($id);
        $data = $request->only(['event_name', 'event_description', 'venue_name', 'venue_location', 'capacity', 'start_date', 'end_date', 'start_time', 'end_time', 'status']);
        if ($request->hasFile('event_banner')) {
            $data['event_banner'] = asset('storage/' . $request->file('event_banner')->store('events/banners', 'public'));
        }

        $event->update($data);
        return redirect()->route('events.index')->with('success', 'Event updated successfully.');
    }

    public function destroy(string $id)
    {
        Event::findOrFail($id)->delete();
        return redirect()->route('events.index')->with('success', 'Event deleted successfully.');
    }
}
