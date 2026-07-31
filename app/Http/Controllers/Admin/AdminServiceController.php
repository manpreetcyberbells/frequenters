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
        $validator = \Validator::make($request->all(), [
            'business_id' => [
                'required', 'string', 'max:255',
                Rule::exists('users', 'id')->where(fn($q) => $q->where('role', 'business')),
            ],
            'name'                       => 'required|string',
            'category_id'                => 'required|exists:categories,id',
            'description'                => 'nullable|string',
            'duration'                   => 'nullable',
            'price'                      => 'nullable|string',
            'photo'                      => 'nullable|image',
            'availability'               => 'nullable|array',
            'availability.*.day'         => 'required|string',
            'availability.*.start_time'  => 'required|date_format:H:i',
            'availability.*.end_time'    => 'required|date_format:H:i',
            'availability.*.is_open'     => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated  = $validator->validated();
        $businessId = $request->business_id;
        try {
            $serviceImage = '';
            if ($request->hasFile('photo')) {
                $serviceImage = $request->file('photo')->store('services', 'public');
            }

            $service = Service::create([
                'business_id' => $businessId,
                'name'        => $validated['name'],
                'category_id' => $validated['category_id'],
                'description' => $validated['description'] ?? null,
                'duration'    => $validated['duration']    ?? null,
                'price'       => $validated['price']       ?? null,
                'photo'       => $serviceImage ? asset('storage/' . $serviceImage) : null,
            ]);

            if (!empty($request->availability)) {
                foreach ($request->availability as $slot) {
                    $slot['service_id'] = $service->id;
                    ServiceAvailabilitySchedule::create($slot);
                }
            }

            return redirect()->route('services.index')->with('success', 'Service created successfully!');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage() ?? 'Error creating Service');
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
        $validator = \Validator::make($request->all(), [
            'business_id' => [
                'required', 'string', 'max:255',
                Rule::exists('users', 'id')->where(fn($q) => $q->where('role', 'business')),
            ],
            'name'                       => 'required|string',
            'category_id'                => 'required|exists:categories,id',
            'description'                => 'nullable|string',
            'duration'                   => 'nullable',
            'price'                      => 'nullable|string',
            'photo'                      => 'nullable|image',
            'availability'               => 'nullable|array',
            'availability.*.day'         => 'required|string',
            'availability.*.start_time'  => 'required',
            'availability.*.end_time'    => 'required',
            'availability.*.is_open'     => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated  = $validator->validated();
        $businessId = $request->business_id;
        
        try {
            $service = Service::findOrFail($id);

            $photoUrl = $service->photo; // keep existing by default
            if ($request->hasFile('photo')) {
                if ($service->photo && file_exists(public_path('storage/' . str_replace(asset('storage/'), '', $service->photo)))) {
                    @unlink(public_path('storage/' . str_replace(asset('storage/'), '', $service->photo)));
                }
                $serviceImage = $request->file('photo')->store('services', 'public');
                $photoUrl     = asset('storage/' . $serviceImage);
            }

            $service->update([
                'business_id' => $businessId,
                'name'        => $validated['name'],
                'category_id' => $validated['category_id'],
                'description' => $validated['description'] ?? null,
                'duration'    => $validated['duration']    ?? null,
                'price'       => $validated['price']       ?? null,
                'photo'       => $photoUrl,
            ]);

            if (!empty($request->availability)) {
                $service->schedule()->delete();
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
