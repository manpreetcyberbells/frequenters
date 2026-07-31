<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateEventRequest;
use App\Models\City;
use App\Models\Event;
use App\Models\EventReservation;
use App\Models\Zip;
use App\Services\FCMService;
use App\Services\NearbyCustomerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    public function __construct(
        private readonly FCMService $fcmService,
        private readonly NearbyCustomerService $nearbyCustomerService,
    ) {}

    // Business: Event Listing
    public function eventListing(Request $request)
    {
        try {
            $perPage = $request->integer('per_page', 10);
            $events  = Event::where('business_id', Auth::id())->where('status', 'published')->paginate($perPage);
            $eventIds = $events->pluck('id')->toArray();
            $confirmedCounts = EventReservation::where('status', 'confirmed')->whereIn('event_id', $eventIds)->selectRaw('event_id, COUNT(*) as count')->groupBy('event_id')->pluck('count', 'event_id');
            $transformed = $events->map(fn($e) => [
                'id' => $e->id,
                'business_id' => $e->business_id,
                'event_name' => $e->event_name,
                'event_description' => $e->event_description,
                'venue_name' => $e->venue_name,
                'venue_location'    => $e->venue_location,
                'capacity' => $e->capacity,
                'remaining_seats' => max(0, $e->capacity - ($confirmedCounts[$e->id] ?? 0)),
                'event_banner' => $e->event_banner,
                'start_date' => $e->start_date,
                'end_date' => $e->end_date,
                'start_time' => $e->start_time,
                'end_time' => $e->end_time,
                'status' => $e->status,
                'created_at' => $e->created_at,
                'updated_at' => $e->updated_at,
                'date_time' => Carbon::parse($e->start_date . ' ' . $e->start_time)->format('D, M d • h:i A'),
                'createdAt' => Carbon::parse($e->created_at)->diffForHumans(),
            ]);

            return response()->json([
                'message' => 'Events retrieved successfully',
                'data'    => [
                    'current_page' => $events->currentPage(),
                    'last_page'    => $events->lastPage(),
                    'per_page'     => $events->perPage(),
                    'total'        => $events->total(),
                    'events'       => $transformed,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    //  Create Event
    public function createBusinessEvent(CreateEventRequest $request)
    {
        if (Auth::user()->role !== 'business') {
            return response()->json(['message' => 'Please login with a valid Business account'], 403);
        }

        try {
            $businessId = Auth::id();
            $venueParts = explode(',', $request->venue_location);
            $zipCode    = trim(end($venueParts));
            $zipData  = Zip::where('zip_code', $zipCode)->first();
            if (!$zipData) {
                return response()->json(['error' => 'Invalid ZIP code. City not found.'], 422);
            }

            $cityData = City::find($zipData->city_id);
            if (!$cityData) {
                return response()->json(['error' => 'City data not found for the given ZIP code.'], 422);
            }

            $bannerPath = $request->hasFile('event_banner') ? asset('storage/' . $request->file('event_banner')->store('events/banners', 'public')) : null;
            $event = Event::create([
                'business_id' => $businessId,
                'event_name' => $request->event_name,
                'event_description' => $request->event_description,
                'venue_name' => $request->venue_name,
                'venue_location' => $request->venue_location,
                'capacity' => $request->capacity,
                'event_banner' => $bannerPath,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'status' => $request->status,
                'target_city_id' => $zipData->city_id,
                'latitude' => $cityData->latitude,
                'longitude' => $cityData->longitude,
            ]);

            $this->sendNearbyNotifications($event->latitude, $event->longitude);

            return response()->json(['message' => 'Event created successfully', 'event' => $event], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong', 'details' => $e->getMessage()], 500);
        }
    }

    // Customer: Fetch Events
    // public function getEventsDetail(Request $request)
    // {
    //     try {
    //         $customerId = Auth::id();
    //         $search = $request->query('search');

    //         $eventsQuery = Event::where('status', 'published')->with('business');

    //         if (!empty($search)) {
    //             $eventsQuery->where(fn($q) => $q
    //                 ->where('event_name', 'like', "%$search%")
    //                 ->orWhere('event_description', 'like', "%$search%")
    //                 ->orWhere('venue_name', 'like', "%$search%")
    //                 ->orWhere('venue_location', 'like', "%$search%")
    //             );
    //         }

    //         $events = $eventsQuery->orderBy('start_date', 'asc')->paginate(10);
    //         $eventIds = $events->pluck('id')->toArray();

    //         $confirmedCounts = EventReservation::where('status', 'confirmed')
    //             ->whereIn('event_id', $eventIds)
    //             ->selectRaw('event_id, COUNT(*) as count')
    //             ->groupBy('event_id')
    //             ->pluck('count', 'event_id');

    //         $joinedEventIds = $customerId
    //             ? EventReservation::where('customer_id', $customerId)
    //                 ->where('status', 'confirmed')
    //                 ->whereIn('event_id', $eventIds)
    //                 ->pluck('event_id')
    //                 ->flip()
    //             : collect();

    //         $events->through(function ($event) use ($confirmedCounts, $joinedEventIds) {
    //             $confirmed      = $confirmedCounts[$event->id] ?? 0;
    //             $remainingSeats = max(0, $event->capacity - $confirmed);

    //             return [
    //                 'id'              => $event->id,
    //                 'business_id'     => $event->business_id,
    //                 'event_name'      => $event->event_name,
    //                 'event_description'=> $event->event_description,
    //                 'venue_name'      => $event->venue_name,
    //                 'venue_location'  => $event->venue_location,
    //                 'capacity'        => $event->capacity,
    //                 'remaining_seats' => $remainingSeats,
    //                 'event_banner'    => $event->event_banner,
    //                 'start_date'      => $event->start_date,
    //                 'end_date'        => $event->end_date,
    //                 'start_time'      => $event->start_time,
    //                 'end_time'        => $event->end_time,
    //                 'status'          => $event->status,
    //                 'created_at'      => $event->created_at,
    //                 'updated_at'      => $event->updated_at,
    //                 'date_time'       => Carbon::parse($event->start_date . ' ' . $event->start_time)->format('D, M d • h:i A'),
    //                 'is_joined'       => isset($joinedEventIds[$event->id]),
    //             ];
    //         });

    //         return response()->json(['message' => 'Events fetched successfully', 'events' => $events]);

    //     } catch (\Exception $e) {
    //         return response()->json(['status' => false, 'message' => 'Something went wrong', 'error' => $e->getMessage()], 500);
    //     }
    // }

    public function getEventsDetail(Request $request)
    {
        try {
            $customerId = Auth::id();
            $search     = $request->query('search');
            $today      = now()->toDateString();

            $eventsQuery = Event::where('status', 'published')
                ->where('end_date', '>=', $today) 
                ->with('business')
                ->orderByRaw("
                    CASE 
                        WHEN start_date = CURDATE() THEN 0
                        WHEN start_date > CURDATE() THEN 1
                    END ASC
                ")
                ->orderBy('start_date', 'asc');

            if (!empty($search)) {
                $eventsQuery->where(fn($q) => $q
                    ->where('event_name', 'like', "%$search%")
                    ->orWhere('event_description', 'like', "%$search%")
                    ->orWhere('venue_name', 'like', "%$search%")
                    ->orWhere('venue_location', 'like', "%$search%")
                );
            }

            $events = $eventsQuery->paginate(10);
            $eventIds = $events->pluck('id')->toArray();
            $confirmedCounts = EventReservation::where('status', 'confirmed')->whereIn('event_id', $eventIds)->selectRaw('event_id, COUNT(*) as count')->groupBy('event_id')->pluck('count', 'event_id');
            $joinedEventIds = $customerId
                ? EventReservation::where('customer_id', $customerId)
                    ->where('status', 'confirmed')
                    ->whereIn('event_id', $eventIds)
                    ->pluck('event_id')
                    ->flip()
                : collect();

            $events->through(function ($event) use ($confirmedCounts, $joinedEventIds) {
                $confirmed      = $confirmedCounts[$event->id] ?? 0;
                $remainingSeats = max(0, $event->capacity - $confirmed);

                return [
                    'id' => $event->id,
                    'business_id'=> $event->business_id,
                    'event_name' => $event->event_name,
                    'event_description'=> $event->event_description,
                    'venue_name' => $event->venue_name,
                    'venue_location'   => $event->venue_location,
                    'capacity' => $event->capacity,
                    'remaining_seats'  => $remainingSeats,
                    'event_banner' => $event->event_banner,
                    'start_date' => $event->start_date,
                    'end_date' => $event->end_date,
                    'start_time' => $event->start_time,
                    'end_time' => $event->end_time,
                    'status' => $event->status,
                    'created_at' => $event->created_at,
                    'updated_at' => $event->updated_at,
                    'date_time' => Carbon::parse($event->start_date . ' ' . $event->start_time)->format('D, M d • h:i A'),
                    'is_joined' => isset($joinedEventIds[$event->id]),
                ];
            });

            return response()->json(['message' => 'Events fetched successfully', 'events' => $events]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Something went wrong', 'error' => $e->getMessage()], 500);
        }
    }

    // Customer: Join / Cancel Event
    public function eventReservationStatus(Request $request)
    {
        try {
            $validated  = $request->validate([
                'event_id' => 'required|exists:events,id',
                'status'   => 'required|in:confirmed,cancelled',
            ]);

            $userId   = Auth::id();
            $userName = Auth::user()->name;
            $event    = Event::findOrFail($validated['event_id']);
            $status   = $validated['status'];

            if ($status === 'confirmed') {
                $confirmedCount = EventReservation::where('event_id', $event->id)->where('status', 'confirmed')->count();
                if ($confirmedCount >= $event->capacity) {
                    return response()->json(['error' => 'Event is already at full capacity.'], 400);
                }
            }

            $reservation = EventReservation::where('customer_id', $userId)->where('event_id', $event->id)->first();

            if (!$reservation && $status === 'confirmed') {
                EventReservation::create([
                    'event_id'    => $event->id,
                    'customer_id' => $userId,
                    'status'      => 'confirmed',
                    'reserved_at' => now(),
                ]);
                createNotification($event->business_id, "$userName successfully joined the event", 'push', null, true);
                return response()->json(['message' => 'You have successfully joined the event.']);
            }

            if ($reservation?->status === 'cancelled' && $status === 'confirmed') {
                $reservation->update(['status' => 'confirmed']);
                createNotification($event->business_id, "$userName successfully joined the event again!", 'push', null, true);
                return response()->json(['message' => 'You have successfully joined the event.']);
            }

            if ($reservation?->status === 'confirmed' && $status === 'cancelled') {
                $reservation->update(['status' => 'cancelled']);
                createNotification($event->business_id, "$userName cancelled their event participation", 'push', null, true);
                return response()->json(['message' => 'You have successfully cancelled your event participation.']);
            }

            return response()->json(['error' => 'Invalid request.'], 400);

        } catch (ValidationException $e) {
            return response()->json(['status' => false, 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Something went wrong', 'error' => $e->getMessage()], 500);
        }
    }

    private function sendNearbyNotifications(?float $lat, ?float $lng): void
    {
        if (!$lat || !$lng) return;

        $nearbyIds = $this->nearbyCustomerService->getNearbyCustomerIds($lat, $lng, 10);
        if (empty($nearbyIds)) return;

        $tokens = DB::table('customer_profiles')->whereIn('customer_id', $nearbyIds)->whereNotNull('fcm_token')->pluck('fcm_token', 'customer_id');
        $image = url('storage/notifications/notification.png');
        $body  = "There's an event close by you might love. Check it out!";

        foreach ($tokens as $customerId => $token) {
            $this->fcmService->sendToCustomerApp($token, "Something's Happening Near You!", $body, $image);
            customerNotification($customerId, $body, 'push', null, true);
        }
    }
}
