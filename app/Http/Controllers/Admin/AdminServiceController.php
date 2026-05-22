<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\Admin\Category;
use Illuminate\Validation\Rule;
use App\Models\ServiceAvailabilitySchedule;

class AdminServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $services = Service::with('businessInfo')->orderBy('id', 'desc')->get() ;
        return view('admin.businesses.service.index', compact('services'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::whereNotNull('parent_id')->get();
        return view('admin.businesses.service.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try{
            $businessId = $request->business_id;
            $validated = $request->validate([
                'business_id' => [
                'required',
                'string',
                'max:255',
                    Rule::exists('users', 'id')->where(function ($query) {
                        $query->where('role', 'business');
                    }),
                ],
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
            $validated['photo'] = $serviceImage ? asset('storage/' . $serviceImage) : null;
            $service = Service::create($validated);

            // Availability schedules
            if (!empty($request->availability)) {
                foreach ($request->availability as $slot) {
                    $slot['service_id'] = $service->id;
                    ServiceAvailabilitySchedule::create($slot);
                }
            }

            return redirect()->route('services.index')->with('success', 'Service created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage() ?? 'Error creating Menu');
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
        $service = Service::with(['businessInfo','schedule'])->findOrFail($id);
        $categories = Category::whereNotNull('parent_id')->get();
        $availability = $service->schedule->keyBy('day');
        return view('admin.businesses.service.edit', compact('service','categories', 'availability'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $service = Service::findOrFail($id);
            $businessId = $request->business_id;

            $validated = $request->validate([
                'business_id' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::exists('users', 'id')->where(function ($query) {
                        $query->where('role', 'business');
                    }),
                ],
                'name' => 'required|string',
                'category_id' => 'required|exists:categories,id',
                'description' => 'nullable|string',
                'duration' => 'nullable',
                'price' => 'nullable|string',
                'photo' => 'nullable|image',
                'availability' => 'nullable|array',
                'availability.*.day' => 'required|string',
                'availability.*.start_time' => 'required',
                'availability.*.end_time' => 'required',
                'availability.*.is_open' => 'required|boolean',
            ]);

            if (isset($validated['availability'])) {
                unset($validated['availability']);
            }

            // Handle photo update
            if ($request->hasFile('photo')) {
                // Optional: delete old image
                if ($service->photo && file_exists(public_path('storage/' . str_replace(asset('storage/'), '', $service->photo)))) {
                    @unlink(public_path('storage/' . str_replace(asset('storage/'), '', $service->photo)));
                }

                $serviceImage = $request->file('photo')->store('services', 'public');
                $validated['photo'] = asset('storage/' . $serviceImage);
            }

            $validated['business_id'] = $businessId;

            // Update the service
            $service->update($validated);

            // Update availability
            if (!empty($request->availability)) {
                // Delete existing schedule
                $service->schedule()->delete();

                // Recreate schedule
                foreach ($request->availability as $slot) {
                    $slot['service_id'] = $service->id;
                    ServiceAvailabilitySchedule::create($slot);
                }
            }

            return redirect()->route('services.index')->with('success', 'Service updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage() ?? 'Error updating Service');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
