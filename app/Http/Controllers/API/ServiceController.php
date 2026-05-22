<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceAvailabilitySchedule;
use App\Traits\ResolvesGrandCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    use ResolvesGrandCategory;

    public function createServiceMenu(Request $request)
    {
        if (Auth::user()->role !== 'business') {
            return response()->json(['message' => 'Please login with a valid Business account'], 403);
        }

        $validated = $request->validate([
            'name'             => 'required|string',
            'category_id'      => 'nullable|exists:categories,id',
            'grandcategory'    => 'nullable|string|max:255',
            'child_id'         => 'nullable|exists:categories,id',
            'parent_id'        => 'nullable|exists:categories,id',
            'description'      => 'nullable|string',
            'duration'         => 'nullable',
            'price'            => 'nullable|string',
            'photo'            => 'nullable|image',
            'availability'     => 'nullable|array',
            'availability.*.day'        => 'required|string',
            'availability.*.start_time' => 'required|date_format:H:i',
            'availability.*.end_time'   => 'required|date_format:H:i',
            'availability.*.is_open'    => 'required|boolean',
        ]);

        if (empty($validated['category_id'])) {
            $categoryId = $this->resolveGrandCategoryId($validated);
            if ($categoryId instanceof \Illuminate\Http\JsonResponse) return $categoryId;
            $validated['category_id'] = $categoryId ?: null;
        }

        $serviceImage = $request->hasFile('photo')
            ? asset('storage/' . $request->file('photo')->store('services', 'public'))
            : null;

        $availabilityData = $request->input('availability', []);
        unset($validated['availability'], $validated['grandcategory'], $validated['child_id'], $validated['parent_id']);

        $validated['business_id'] = Auth::id();
        $validated['photo']       = $serviceImage;

        $service = Service::create($validated);

        foreach ($availabilityData as $slot) {
            $slot['service_id'] = $service->id;
            ServiceAvailabilitySchedule::create($slot);
        }

        return response()->json(['status' => true, 'message' => 'Service created successfully', 'data' => $service->load('schedule')]);
    }

    public function getServiceList(Request $request)
    {
        $businessId = $request->business_id;
        if (!$businessId) {
            return response()->json(['status' => false, 'message' => 'Business ID is required'], 400);
        }

        $services = Service::where('business_id', $businessId)->orderByDesc('created_at')->paginate(10);
        if ($services->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'No services found for this business'], 400);
        }

        return response()->json(['status' => true, 'data' => $services]);
    }
}
