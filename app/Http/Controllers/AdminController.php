<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Import DB Facade
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    // Show Admin Dashboard
    public function dashboard()
    {
        // Count total customers
        $totalCustomers = User::where('role', 'customer')->get()->count();
        $activeCustomer = User::where('role', 'customer')->where('status', 'active')->count();
        $pendingCustomer = User::where('role', 'customer')->where('status', 'pending')->count();
        
        // Count total businesses
        $totalBusinesses = User::where('role', 'business')->get()->count();
        $activeBusiness = User::where('role', 'business')->where('status', 'active')->count();
        $pendingBusiness = User::where('role', 'business')->where('status', 'pending')->count();

        // Other Stuff for Dashbord
        $totalContests = DB::table('contests')->get()->count();
        $totalEvents = DB::table('events')->get()->count();
        $totalOffers = DB::table('offers')->get()->count();
        $totalRewards = DB::table('rewards')->get()->count();

        return view('admin.dashboard.index', compact('totalCustomers', 'activeCustomer', 'pendingCustomer', 'totalBusinesses', 'activeBusiness', 'pendingBusiness','totalContests', 'totalEvents', 'totalOffers', 'totalRewards'));
    }

    // Edit a Admin
    public function edit()
    {
        $admin = Auth::user();
        return view('admin.profile.edit', compact('admin'));
    }

    // Update a Admin
    public function update(Request $request)
    {
        $admin = Auth::user();

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $admin->id,
            'phone' => 'nullable|string|max:15|unique:users,phone,' . $admin->id,
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            $imagePath = $request->file('profile_image')->store('profile_images', 'public');
            $admin->profile_image = 'storage/' . $imagePath;
        }

        // Update admin details
        $admin->name  = $request->name;
        $admin->email = $request->email;
        $admin->phone = $request->phone;
        $admin->save();

        return redirect()->route('admin.dashboard')->with('success', 'User updated successfully.');
    }

    // Delete a User
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return redirect()->route('admin.dashboard')->with('success', 'User deleted successfully.');
    }

    public function grantPointsToAllActiveBusinesses(Request $request)
    {
        $validated = $request->validate([
            'points' => 'required|integer|min:1'
        ]);

        // Update points for all active businesses
        DB::table('business_profiles')
            ->where('status', 'active')
            ->increment('monthly_points_available', $validated['points']);

        return redirect()->back()->with('success', 'Points successfully added to all active businesses.');
    }

    public function businesspoints()
    {
        // Fetch all businesses
        $businessList = DB::table('business_profiles')->get();

        return view('admin.businesspoints', compact('businessList'));
    }
}
