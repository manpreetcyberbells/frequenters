<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard.index', [
            'totalCustomers'  => User::where('role', 'customer')->count(),
            'activeCustomer'  => User::where('role', 'customer')->where('status', 'active')->count(),
            'pendingCustomer' => User::where('role', 'customer')->where('status', 'pending')->count(),
            'totalBusinesses' => User::where('role', 'business')->count(),
            'activeBusiness'  => User::where('role', 'business')->where('status', 'active')->count(),
            'pendingBusiness' => User::where('role', 'business')->where('status', 'pending')->count(),
            'totalContests'   => DB::table('contests')->count(),
            'totalEvents'     => DB::table('events')->count(),
            'totalOffers'     => DB::table('offers')->count(),
            'totalRewards'    => DB::table('rewards')->count(),
        ]);
    }

    public function edit()
    {
        return view('admin.profile.edit', ['admin' => Auth::user()]);
    }

    public function update(Request $request)
    {
        $admin = Auth::user();

        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $admin->id,
            'phone'         => 'nullable|string|max:15|unique:users,phone,' . $admin->id,
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('profile_image')) {
            $admin->profile_image = 'storage/' . $request->file('profile_image')->store('profile_images', 'public');
        }

        $admin->update([
            'name'  => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'Profile updated successfully.');
    }

    public function destroy(int $id)
    {
        User::findOrFail($id)->delete();
        return redirect()->route('admin.dashboard')->with('success', 'User deleted successfully.');
    }

    public function grantPointsToAllActiveBusinesses(Request $request)
    {
        $validated = $request->validate(['points' => 'required|integer|min:1']);

        DB::table('business_profiles')
            ->where('status', 'active')
            ->increment('monthly_points_available', $validated['points']);

        return redirect()->back()->with('success', 'Points successfully added to all active businesses.');
    }

    public function businesspoints()
    {
        return view('admin.businesspoints', ['businessList' => DB::table('business_profiles')->get()]);
    }
}
