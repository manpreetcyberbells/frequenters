<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessAddress;
use App\Models\BusinessHour;
use App\Models\BusinessImage;
use App\Models\BusinessProfile;
use App\Models\Admin\City;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminBusinessController extends Controller
{
    public function index()
    {
        return view('admin.businesses.index', [
            'businesses' => User::where('role', 'business')->orderByDesc('id')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.businesses.create');
    }

    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'name'          => 'required',
            'email'         => 'required|email|unique:users,email',
            'images.*'      => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'business_hours' => 'required|array',
            'owner_name'    => 'required|string',
            'owner_email'   => 'required|email|unique:business_profiles,owner_email',
            'owner_phone'   => ['required', 'regex:/^\+?[0-9]{10,15}$/'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'phone'    => $request->mobile,
            'role'     => 'business',
            'status'   => $request->status,
        ]);

        BusinessProfile::create([
            'business_id'       => $user->id,
            'business_name'     => $request->name,
            'description'       => $request->description,
            'website'           => $request->website,
            'category_id'       => $request->category,
            'child_category_id' => $request->child_category,
            'points_per_checkin'=> $request->points_per_checkin,
            'owner_name'        => $request->owner_name,
            'owner_email'       => $request->owner_email,
            'owner_phone'       => $request->owner_phone,
        ]);

        BusinessAddress::create([
            'business_id'  => $user->id,
            'zip_code'     => $request->zip_code,
            'address_line1'=> $request->address1,
            'address_line2'=> $request->address2,
            'city'         => $request->city,
            'state'        => $request->state,
            'country'      => $request->country,
            'latitude'     => $request->latitude,
            'longitude'    => $request->longitude,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/businesses'), $imageName);
                BusinessImage::create(['business_id' => $user->id, 'photo' => 'uploads/businesses/' . $imageName]);
            }
        }

        foreach ($request->business_hours as $hours) {
            BusinessHour::create([
                'business_id' => $user->id,
                'day_of_week' => $hours['day_of_week'],
                'open_time'   => isset($hours['is_closed']) ? null : $hours['open_time'],
                'close_time'  => isset($hours['is_closed']) ? null : $hours['close_time'],
                'is_closed'   => isset($hours['is_closed']) ? 1 : 0,
            ]);
        }

        Log::info('Business created:', ['user_id' => $user->id]);
        return redirect()->route('business.index')->with('success', 'Business created successfully.');
    }

    public function show(string $id)
    {
        $business = User::with(['businessProfile', 'businessAddress', 'businessImage', 'businessHours'])->findOrFail($id);
        $getContest = DB::table('contests')->where('business_id', $id)->count();
        $getEvents  = DB::table('events')->where('business_id', $id)->count();
        $getOffers  = DB::table('offers')->where('business_id', $id)->count();
        $getRewards = DB::table('rewards')->where('business_id', $id)->count();
        $staffMembers = User::where('parent_business_id', $id)->orderByDesc('id')->get();
        $parentIds      = $staffMembers->pluck('parent_business_id')->unique()->filter()->toArray();
        $parentBusiness = User::whereIn('id', $parentIds)->pluck('name', 'id');
        foreach ($staffMembers as $staff) {
            $staff->parent_business = $parentBusiness[$staff->parent_business_id] ?? null;
        }

        return view('admin.businesses.show', compact('business', 'getContest', 'getEvents', 'getOffers', 'getRewards', 'staffMembers'));
    }

    public function edit(User $business)
    {
        $business->load(['businessProfile', 'businessAddress', 'businessImage', 'businessHours']);
        $cityValue = optional($business->businessAddress)->city;
        $cityName = City::where('id', $cityValue)->value('city_name');
        if (!$cityName) {
            $cityName = $cityValue;
        }

        return view('admin.businesses.edit', compact('business', 'cityName'));
    }

    public function update(Request $request, string $id)
    {
        $profileId = BusinessProfile::where('business_id', $id)->value('id');
        $validator = \Validator::make($request->all(), [
            'name'           => 'required',
            'email'          => 'required|email|unique:users,email,' . $id,
            'website'        => ['required', 'url'],
            'images.*'       => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'business_hours' => 'required|array',
            'owner_name'     => 'required|string',
            'owner_phone'    => ['required', 'regex:/^\+?[0-9]{10,15}$/'],
            'owner_email'    => [
                'required',
                'email',
                Rule::unique('business_profiles', 'owner_email')->ignore($profileId)
            ],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = User::findOrFail($id);
        $user->update(['name' => $request->name, 'email' => $request->email, 'phone' => $request->mobile, 'status' => $request->status]);

        BusinessProfile::updateOrCreate(['business_id' => $user->id], [
            'business_name'     => $request->name,
            'description'       => $request->description,
            'website'           => $request->website,
            'category_id'       => $request->category,
            'child_category_id' => $request->child_category,
            'points_per_checkin'=> $request->points_per_checkin,
            'owner_name'        => $request->owner_name,
            'owner_email'       => $request->owner_email,
            'owner_phone'       => $request->owner_phone,
        ]);

        BusinessAddress::updateOrCreate(['business_id' => $user->id], [
            'zip_code'     => $request->zip_code,
            'address_line1'=> $request->address1,
            'address_line2'=> $request->address2,
            'city'         => $request->city,
            'state'        => $request->state,
            'country'      => $request->country,
            'latitude'     => $request->latitude,
            'longitude'    => $request->longitude,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/businesses'), $imageName);
                BusinessImage::create(['business_id' => $user->id, 'photo' => 'uploads/businesses/' . $imageName]);
            }
        }

        foreach ($request->business_hours as $hours) {
            BusinessHour::updateOrCreate(
                ['business_id' => $user->id, 'day_of_week' => $hours['day_of_week']],
                ['open_time' => isset($hours['is_closed']) ? null : $hours['open_time'], 'close_time' => isset($hours['is_closed']) ? null : $hours['close_time'], 'is_closed' => isset($hours['is_closed']) ? 1 : 0]
            );
        }

        return redirect()->route('business.index')->with('success', 'Business updated successfully.');
    }

    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        BusinessProfile::where('business_id', $id)->delete();
        BusinessAddress::where('business_id', $id)->delete();
        BusinessImage::where('business_id', $id)->delete();
        BusinessHour::where('business_id', $id)->delete();
        return redirect()->route('business.index')->with('success', 'Business deleted successfully.');
    }
}
