<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CustomerProfile;
use App\Models\CustomerAddress;
use App\Models\Admin\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminCustomerController extends Controller
{
    /**
     * Display a listing of the resource.
    */
    public function index()
    {
        $customers = User::where('role', 'customer')->orderBy('id', 'desc')->get();
        return view('admin.customers.index', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
    */
    public function create()
    {
        return view('admin.customers.create');
    }

    /**
     * Store a newly created resource in storage.
    */
    public function store(Request $request)
    {
        // Validations
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'mobile' => 'nullable|string|max:15|unique:users,phone',
            'profilei_mage' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // Max size 2MB
            'address1' => 'required',
            'zip_code' => 'required',
            'city' => 'required',
            'state' => 'required',
            'country' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        // Handle profile image upload
        $imageUrl = null;
        if ($request->hasFile('profile_image')) {
            $image = $request->file('profile_image');
            $imageName = 'profile_' . time() . '.' . $image->getClientOriginalExtension();
            $profileImagePath = $image->storeAs('profile_images', $imageName, 'public');
            $imageUrl = asset('storage/profile_images/' . $imageName);
            \Log::info('Profile Image Path: '. $imageUrl);
        }

        // Create Customer
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->mobile,
            'role' => 'customer',
            'status' => $request->status,
            'gender' => $request->gender,
            'profile_image' => $imageUrl, 
        ]);

        // Customer Profile Created
        CustomerProfile::create([
            'customer_id' => $user->id,
            'birthday' => $request->birthday,
            'anniversary' => $request->anniversary,
        ]);

        // Create the Customer address
        CustomerAddress::create([
            'customer_id' => $user->id,
            'address_line1' => $request->address1,
            'address_line2' => $request->address2,
            'zip_code' => $request->zip_code,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        Log::info('Customer created with ID:', ['user_id' => $user->id]);

        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
    }

    /**
     * Display the specified resource.
    */
    public function show(User $customer)
    {
        die('Show PAGE!!! PLEASE WAIT!!!');
        return view('admin.customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified resource.
    */
    public function edit(User $customer)
    {
        $customer->load(['customerProfile', 'customerAddress']);
        $cityName = City::where('id', $customer->customerAddress->city)->value('city_name');
        return view('admin.customers.edit', compact('customer', 'cityName'));
    }

    /**
     * Update the specified resource in storage.
    */
    public function update(Request $request, User $customer)
    {
        // echo"<pre>"; print_r($request->all()); die; 
        try {
            // Validations
            $request->validate([
                'name'          => 'required|string|max:255',
                'email'         => 'required|email|unique:users,email,' . $customer->id,
                'mobile'  => 'nullable|string|max:15|unique:users,phone,' . $customer->id,
                'gender'        => 'nullable|in:male,female,other',
                'birthday'      => 'nullable|date|before:today',
                'anniversary'   => 'nullable|date|after:birthday',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // Max size 2MB
                'address1' => 'required',
                'zip_code' => 'required',
                'city' => 'required',
                'state' => 'required',
                'country' => 'required',
                'latitude' => 'required',
                'longitude' => 'required',
            ]);

            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                $image = $request->file('profile_image');
                $imageName = 'profile_' . $customer->id . '_' . time() . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('profile_images', $imageName, 'public');
                $imageUrl = asset('storage/profile_images/' . $imageName);
                
                // Delete old image if exists
                if ($customer->profile_image) {
                    $oldImagePath = str_replace(asset('storage/'), '', $customer->profile_image);
                    Storage::disk('public')->delete($oldImagePath);
                }

                $customer->profile_image = $imageUrl;
            }

            // Update Customer
            $customer->update([
                'name'  => $request->name,
                'email' => $request->email,
                'mobile' => $request->phone_number,
                'gender' => $request->gender,
                'status' => $request->status,
            ]);

            // Update or Create Customer Profile
            CustomerProfile::updateOrCreate(
                ['customer_id' => $customer->id],
                [
                    'birthday'    => $request->birthday,
                    'anniversary' => $request->anniversary,
                ]
            );

            // Update or Create Customer Address
            CustomerAddress::updateOrCreate(
                ['customer_id' => $customer->id],
                [
                    'address_line1' => $request->address1,
                    'address_line2' => $request->address2,
                    'zip_code'      => $request->zip_code,
                    'city'          => $request->city,
                    'state'         => $request->state,
                    'country'       => $request->country,
                    'latitude'      => $request->latitude,
                    'longitude'     => $request->longitude,
                ]
            );

            Log::info('Customer updated successfully', ['user_id' => $customer->id]);

            return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
        } catch (Exception $e) {
            Log::error('Customer update failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Something went wrong while updating the customer.');
        }
    }


    /**
     * Remove the specified resource from storage.
    */
    public function destroy(string $id)
    {
        // die('Please wait...!!!');
        $user = User::findOrFail($id);
        // Soft delete user and related data
        $user->delete();
        CustomerProfile::where('customer_id', $user->id)->delete();
        CustomerAddress::where('customer_id', $user->id)->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }
}
