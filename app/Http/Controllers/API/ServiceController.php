<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceAvailabilitySchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    /**
     *  API Function : Create Services
    */
    public function createServiceMenu(Request $request)
    {
        $businessId = auth()->id();
        $user = auth()->user();
        if ($user->role !== 'business') {
            return response()->json(['message' => 'Please login with a valid Business account'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'duration' => 'nullable',
            'price' => 'nullable|string',
            'photo' => 'nullable|image',
            'availability' => 'nullable|array',
            'availability.*.day' => 'required|string',
            'availability.*.start_time' => 'required|date_format:H:i',
            'availability.*.end_time' => 'required|date_format:H:i',
            'availability.*.is_open' => 'required|boolean',
        ]);

        if (isset($validated['availability'])) {
            unset($validated['availability']);
        }

        $serviceImage = '';
        if ($request->hasFile('photo')) {
            $serviceImage = $request->file('photo')->store('services', 'public');
        }
        
        $validated['business_id'] = $businessId;
        $validated['photo'] = asset('storage/' . $serviceImage);
        $service = Service::create($validated);

        // Availability schedules
        if (!empty($request->availability)) {
            foreach ($request->availability as $slot) {
                $slot['service_id'] = $service->id;
                ServiceAvailabilitySchedule::create($slot);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Service created successfully', 
            'data' => $service->load('schedule')
        ]);
    }

    /**
     *  API Function : Service Listing
    */
    public function getServiceList(Request $request)
    {
        $businessId = $request->business_id;
        if (!$businessId) {
            return response()->json(['status' => false, 'message' => 'Business ID is required'], 400);
        }

        $services = Service::where('business_id', $businessId)->orderBy('created_at', 'desc')->paginate(10);
        if ($services->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'No services found for this business'], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $services
        ]);
    }

}
