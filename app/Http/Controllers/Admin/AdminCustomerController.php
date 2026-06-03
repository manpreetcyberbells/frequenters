<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\City;
use App\Models\CustomerAddress;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdminCustomerController extends Controller
{
    public function index()
    {
        return view('admin.customers.index', [
            'customers' => User::where('role', 'customer')->orderByDesc('id')->get(),
        ]);
    }

    public function create() { return view('admin.customers.create'); }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required',
            'email'     => 'required|email|unique:users,email',
            'mobile'    => 'nullable|string|max:15|unique:users,phone',
            'address1'  => 'required',
            'zip_code'  => 'required',
            'city'      => 'required',
            'state'     => 'required',
            'country'   => 'required',
            'latitude'  => 'required',
            'longitude' => 'required',
        ]);

        $imageUrl = null;
        if ($request->hasFile('profile_image')) {
            $image    = $request->file('profile_image');
            $name     = 'profile_' . time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('profile_images', $name, 'public');
            $imageUrl = asset('storage/profile_images/' . $name);
        }

        $user = User::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'password'      => Hash::make($request->password),
            'phone'         => $request->mobile,
            'role'          => 'customer',
            'status'        => $request->status,
            'gender'        => $request->gender,
            'profile_image' => $imageUrl,
        ]);

        CustomerProfile::create(['customer_id' => $user->id, 'birthday' => $request->birthday, 'anniversary' => $request->anniversary]);

        CustomerAddress::create([
            'customer_id'   => $user->id,
            'address_line1' => $request->address1,
            'address_line2' => $request->address2,
            'zip_code'      => $request->zip_code,
            'city'          => $request->city,
            'state'         => $request->state,
            'country'       => $request->country,
            'latitude'      => $request->latitude,
            'longitude'     => $request->longitude,
        ]);

        Log::info('Customer created:', ['user_id' => $user->id]);
        return redirect()->route('customer.index')->with('success', 'Customer created successfully.');
    }

    public function show(User $customer)
    {
        $customer->load([
            'customerProfile', 'customerAddress',
            'customerCheckIn.business:id,name', 'customerCheckIn.customer:id,name',
            'customerRewardRedemptions', 'customerRewardRedemptions.business:id,name', 'customerRewardRedemptions.customer:id,name',
            'customerHistory', 'customerHistory.customer:id,name',
            'customerWishlist', 'customerWishlist.business:id,name', 'customerWishlist.customer:id,name',
            'customerComplaints', 'customerComplaints.business:id,name', 'customerComplaints.customer:id,name',
            'friendsInvited', 'friendsInvited.customer:id,name',
            'cusomerSubmitReviews', 'cusomerSubmitReviews.business:id,name', 'cusomerSubmitReviews.customer:id,name',
            'customerParticipant.contest', 'customerParticipant.contest.businessDetail:id,name',
            'customerEvents.event',
            'customerBusinessInteractions.customer:id,name', 'customerBusinessInteractions.business:id,name',
        ]);

        $cityName = City::where('id', $customer->customerAddress->city)->value('city_name');

        return view('admin.customers.show', [
            'customer'           => $customer,
            'cityName'           => $cityName,
            'rewardRedemptions'  => $customer->customerRewardRedemptions,
            'history'            => $customer->customerHistory,
            'wishlists'          => $customer->customerWishlist,
            'complaints'         => $customer->customerComplaints,
            'invitedFriends'     => $customer->friendsInvited,
            'reviews'            => $customer->cusomerSubmitReviews,
            'contests'           => $customer->customerParticipant,
            'events'             => $customer->customerEvents,
            'interactionHistory' => $customer->customerBusinessInteractions,
        ]);
    }

    public function edit(User $customer)
    {
        $customer->load(['customerProfile', 'customerAddress']);
        $cityName = City::where('id', $customer->customerAddress->city)->value('city_name');
        return view('admin.customers.edit', compact('customer', 'cityName'));
    }

    public function update(Request $request, User $customer)
    {
        try {
            $request->validate([
                'name'          => 'required|string|max:255',
                'email'         => 'required|email|unique:users,email,' . $customer->id,
                'mobile'        => 'nullable|string|max:15|unique:users,phone,' . $customer->id,
                'gender'        => 'nullable|in:male,female,other',
                'birthday'      => 'nullable|date|before:today',
                'anniversary'   => 'nullable|date|after:birthday',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
                'address1'      => 'required',
                'zip_code'      => 'required',
                'city'          => 'required',
                'state'         => 'required',
                'country'       => 'required',
                'latitude'      => 'required',
                'longitude'     => 'required',
            ]);

            if ($request->hasFile('profile_image')) {
                $image     = $request->file('profile_image');
                $imageName = 'profile_' . $customer->id . '_' . time() . '.' . $image->getClientOriginalExtension();
                $image->storeAs('profile_images', $imageName, 'public');

                if ($customer->profile_image) {
                    Storage::disk('public')->delete(str_replace(asset('storage/'), '', $customer->profile_image));
                }
                $customer->profile_image = asset('storage/profile_images/' . $imageName);
            }

            $customer->update(['name' => $request->name, 'email' => $request->email, 'phone' => $request->mobile, 'gender' => $request->gender, 'status' => $request->status]);

            CustomerProfile::updateOrCreate(
                ['customer_id' => $customer->id],
                ['birthday' => $request->birthday, 'anniversary' => $request->anniversary]
            );

            CustomerAddress::updateOrCreate(
                ['customer_id' => $customer->id],
                ['address_line1' => $request->address1, 'address_line2' => $request->address2, 'zip_code' => $request->zip_code, 'city' => $request->city, 'state' => $request->state, 'country' => $request->country, 'latitude' => $request->latitude, 'longitude' => $request->longitude]
            );

            return redirect()->route('customer.index')->with('success', 'Customer updated successfully.');

        } catch (\Exception $e) { // ✅ Fixed: was unqualified Exception — never caught
            Log::error('Customer update failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Something went wrong while updating the customer.');
        }
    }

    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        CustomerProfile::where('customer_id', $id)->delete();
        CustomerAddress::where('customer_id', $id)->delete();
        return redirect()->route('customer.index')->with('success', 'Customer deleted successfully.');
    }
}
