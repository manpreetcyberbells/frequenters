<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\BusinessProfile;
use App\Models\BusinessAddress;
use App\Models\BusinessImage;
use App\Models\BusinessHour;
use App\Models\Admin\City;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AdminBusinessController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $businesses = User::where('role', 'business')->orderBy('id', 'desc')->get();
        return view('admin.businesses.index', compact('businesses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.businesses.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // echo"<pre>"; print_r($request->all()); die;
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            // 'website' => ['required', 'url'],
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'business_hours' => 'required|array',
            'owner_name' => 'required|string',
            'owner_email' => 'required|email|unique:users,email',
            'owner_phone' => ['required', 'regex:/^\+?[0-9]{10,15}$/'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->mobile,
            'role' => 'business',
            'status' => $request->status,
        ]);

        Log::info('Business created with ID:', ['user_id' => $user->id]);
            
        // Bisiness Profile Created
        BusinessProfile::create([
            'business_id' => $user->id,
            'business_name' => $request->name,
            'industry_type' => $request->category,
            'description' => $request->description,
            'website' => $request->website,
            'category_id' => $request->category,
            'child_category_id' => $request->child_category,
            'points_per_checkin' => $request->points_per_checkin,
            'owner_name' => $request->owner_name,
            'owner_email' => $request->owner_email,
            'owner_phone' => $request->owner_phone,
        ]);

        // Create the Business address
        BusinessAddress::create([
            'business_id' => $user->id,
            'zip_code' => $request->zip_code,
            'address_line1' => $request->address1,
            'address_line2' => $request->address2,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        // Handle multiple image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/businesses'), $imageName);

                BusinessImage::create([
                    'business_id' => $user->id,
                    'photo' => 'uploads/businesses/' . $imageName,
                ]);
            }
        }

        // Insert Business Hours
        foreach ($request->business_hours as $hours) {
            BusinessHour::create([
                'business_id' => $user->id,
                'day_of_week' => $hours['day_of_week'],
                'open_time' => isset($hours['is_closed']) ? null : $hours['open_time'],
                'close_time' => isset($hours['is_closed']) ? null : $hours['close_time'],
                'is_closed' => isset($hours['is_closed']) ? 1 : 0,
            ]);
        }
        Log::info('Business profile created for user_id:', ['user_id' => $user->id]);

        return redirect()->route('businesses.index')->with('success', 'Business created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $business = User::with(['businessProfile', 'businessAddress', 'businessImage', 'businessHours'])->findOrFail($id);
        $getContest = DB::table('contests')->where('business_id',$id)->get()->count();
        $getEvents = DB::table('events')->where('business_id',$id)->get()->count();
        $getOffers = DB::table('offers')->where('business_id',$id)->get()->count();
        $getRewards = DB::table('rewards')->where('business_id',$id)->get()->count();
        return view('admin.businesses.show', compact('business', 'getContest', 'getEvents', 'getOffers', 'getRewards'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $business)
    {
        $business->load(['businessProfile', 'businessAddress', 'businessImage', 'businessHours']);
        $cityName = City::where('id', $business->businessAddress->city)->value('city_name');
        return view('admin.businesses.edit', compact('business', 'cityName'));
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // echo"<pre>"; print_r($request->all()); die;
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $id,
            'website' => ['required', 'url'],
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'business_hours' => 'required|array',
            'owner_name' => 'required|string',
            'owner_email' => 'required|email|unique:users,email',
            'owner_phone' => ['required', 'regex:/^\+?[0-9]{10,15}$/'],
        ]);

        // Find existing user
        $user = User::findOrFail($id);

        // Update user details
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->mobile,
            'status' => $request->status,
        ]);

        Log::info('Business updated with ID:', ['user_id' => $user->id]);

        // Update or create BusinessProfile
        BusinessProfile::updateOrCreate(
            ['business_id' => $user->id], // Search condition
            [
                'business_name' => $request->name,
                'industry_type' => $request->category,
                'description' => $request->description,
                'website' => $request->website,
                'category_id' => $request->category,
                'child_category_id' => $request->child_category,
                'points_per_checkin' => $request->points_per_checkin,
                'owner_name' => $request->owner_name,
                'owner_email' => $request->owner_email,
                'owner_phone' => $request->owner_phone,
            ]
        );

        // Update or create BusinessAddress
        BusinessAddress::updateOrCreate(
            ['business_id' => $user->id], // Search condition
            [
                'zip_code' => $request->zip_code,
                'address_line1' => $request->address1,
                'address_line2' => $request->address2,
                'city' => $request->city,
                'state' => $request->state,
                'country' => $request->country,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]
        );

        // Handle multiple image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/businesses'), $imageName);
                // Store new images
                BusinessImage::create([
                    'business_id' => $user->id,
                    'photo' => 'uploads/businesses/' . $imageName,
                ]);
            }
        }

        // Update or Insert Business Hours
        foreach ($request->business_hours as $hours) {
            BusinessHour::updateOrCreate(
                ['business_id' => $user->id, 'day_of_week' => $hours['day_of_week']],
                [
                    'open_time' => isset($hours['is_closed']) ? null : $hours['open_time'],
                    'close_time' => isset($hours['is_closed']) ? null : $hours['close_time'],
                    'is_closed' => isset($hours['is_closed']) ? 1 : 0,
                ]
            );
        }

        Log::info('Business profile updated for user_id:', ['user_id' => $user->id]);

        return redirect()->route('businesses.index')->with('success', 'Business updated successfully.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // dd($id);
        $user = User::findOrFail($id);
        // Soft delete user and related data
        $user->delete();
        BusinessProfile::where('business_id', $user->id)->delete();
        BusinessAddress::where('business_id', $user->id)->delete();
        BusinessImage::where('business_id', $user->id)->delete();
        return redirect()->route('businesses.index')->with('success', 'Business deleted successfully.');
    }
}
