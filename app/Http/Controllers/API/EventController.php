<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateEventRequest;
use App\Models\Event;
use App\Models\EventReservation;
use App\Models\City;
use App\Models\Zip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    /**
     *  API Function to Create Business Event. 
    */
    public function createBusinessEvent(CreateEventRequest $request)
    {
        try {

            $businessId = auth()->id();
            $user = auth()->user();

            if ($user->role !== 'business') {
                return response()->json(['message' => 'Please login with a valid Business account'], 403);
            }

            // Extract ZIP code from venue_location (assuming last part is ZIP)
            $venueParts = explode(',', $request->venue_location);
            $zipCode = trim(end($venueParts));

            // Get city_id
            $zipData = Zip::where('zip_code', $zipCode)->first();
            if (!$zipData) {
                return response()->json([
                    'error' => 'Invalid ZIP code. City not found.'
                ], 422);
            }

            // Get latitude and longitude
            $cityData = City::where('id', $zipData->city_id)->first();
            if (!$cityData) {
                return response()->json([
                    'error' => 'City data not found for the given ZIP code.'
                ], 422);
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
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'status' => $request->status,
                'target_city_id' => $zipData->city_id,
                'latitude' => $cityData->latitude,
                'longitude' => $cityData->longitude
            ]);

            // Notification
            createNotification($businessId, $event->event_name, "push", null, true);

            return response()->json([
                'message' => 'Event created successfully',
                'event' => $event
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     *  API Function to Create Business Event. 
    */
    public function getEventsDetail(Request $request)
    {
        try {
            $search = $request->query('search');
            $customerId = auth()->id();

            $eventsQuery = Event::where('status', 'published')->with('business');

            if (!empty($search)) {
                $eventsQuery->where(function ($query) use ($search) {
                    $query->where('event_name', 'like', "%$search%")
                        ->orWhere('event_description', 'like', "%$search%")
                        ->orWhere('venue_name', 'like', "%$search%")
                        ->orWhere('venue_location', 'like', "%$search%");
                });
            }

            $events = $eventsQuery
                ->orderBy('start_date', 'asc')
                ->paginate(10)
                ->through(function ($event) use ($customerId) {
                    $confirmedCount = EventReservation::where('event_id', $event->id)
                        ->where('status', 'confirmed')
                        ->count();
                    $remainingSeats = max(0, $event->capacity - $confirmedCount);

                    $isJoined = false;
                    if ($customerId) {
                        $isJoined = EventReservation::where('event_id', $event->id)
                            ->where('customer_id', $customerId)
                            ->where('status', 'confirmed')
                            ->exists();
                    }

                    return [
                        'id' => $event->id,
                        'business_id' => $event->business_id,
                        'event_name' => $event->event_name,
                        'event_description' => $event->event_description,
                        'venue_name' => $event->venue_name,
                        'venue_location' => $event->venue_location,
                        'capacity' => $event->capacity,
                        'remaining_seats' => $remainingSeats,
                        'event_banner' => $event->event_banner ?? null,
                        'start_date' => $event->start_date,
                        'end_date' => $event->end_date,
                        'start_time' => $event->start_time,
                        'end_time' => $event->end_time,
                        'status' => $event->status,
                        'created_at' => $event->created_at,
                        'updated_at' => $event->updated_at,
                        'date_time' => Carbon::parse($event->start_date . ' ' . $event->start_time)->format('D, M d • h:i A'),
                        'is_joined' => $isJoined,
                    ];
                });

            return response()->json([
                'message' => 'Events fetched successfully',
                'events' => $events
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     *  API Function to Confirmed // Cancelled Event. 
    */
    public function eventReservationStatus(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'event_id' => 'required|exists:events,id',
                'status' => 'required|in:confirmed,cancelled',
            ]);

            $userId = Auth::id();
            $eventId = $validatedData['event_id'];
            $status = $validatedData['status'];

            // Fetch event details
            $event = Event::findOrFail($eventId);
            $confirmedCount = EventReservation::where('event_id', $eventId)->where('status', 'confirmed')->count();
            // Check capacity
            if ($status === 'confirmed' && $confirmedCount >= $event->capacity) {
                return response()->json(['error' => 'Event is already at full capacity.'], 400);
            }

            // Check if reservation exists
            $reservation = EventReservation::where('customer_id', $userId)->where('event_id', $eventId)->first();
            if (!$reservation && $status === 'confirmed') {
                EventReservation::create([
                    'event_id' => $eventId,
                    'customer_id' => $userId,
                    'status' => 'confirmed',
                    'reserved_at' => now(),
                ]);

                // Notification
                createNotification($event->business_id, "You have successfully joined the event.", "push", $userId, true);

                return response()->json(['message' => 'You have successfully joined the event.'], 200);
            }

            if ($reservation && $reservation->status !== 'confirmed' && $status === 'cancelled') {
                return response()->json(['error' => 'You cannot cancel without confirming the event.'], 400);
            }

            if ($reservation && $reservation->status === 'confirmed' && $status === 'cancelled') {
                $reservation->update(['status' => 'cancelled']);

                // Notification
                createNotification($event->business_id, "You have successfully cancelled your event participation.", "push", $userId, true);

                return response()->json(['message' => 'You have successfully cancelled your event participation.'], 200);
            }

            return response()->json(['error' => 'Invalid request.'], 400);

        } catch (ValidationException $e) {

            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
