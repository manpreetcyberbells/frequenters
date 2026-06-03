<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminStaffController extends Controller
{
    public function index()
    {
        $staffMembers = User::whereIn('role', ['manager', 'employee'])->orderByDesc('id')->get();
        $parentIds = $staffMembers->pluck('parent_business_id')->unique()->filter()->toArray();
        $parents   = User::whereIn('id', $parentIds)->pluck('name', 'id');

        foreach ($staffMembers as $staff) {
            $staff->parent_business = $parents[$staff->parent_business_id] ?? null;
        }

        return view('admin.businesses.staff.index', compact('staffMembers'));
    }

    public function create() { return view('admin.businesses.staff.create'); }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_id' => ['required', 'string', 'max:255', Rule::exists('users', 'id')->where(fn($q) => $q->where('role', 'business'))],
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:8|confirmed',
            'role'        => 'required|in:manager,employee',
        ]);

        $totalStaff = User::where('parent_business_id', $request->business_id)->whereIn('role', ['manager', 'employee'])->whereNull('deleted_at')->count();
        if ($totalStaff >= 5) {
            return redirect()->back()->with('error', 'You can only add up to 5 staff members.');
        }

        User::create([
            'name'               => $validated['name'],
            'email'              => $validated['email'],
            'password'           => Hash::make($validated['password']),
            'role'               => $validated['role'],
            'parent_business_id' => $request->business_id,
            'providers'          => 'email',
            'status'             => $request->status,
        ]);

        return redirect()->route('staff.index')->with('success', 'Staff created successfully!');
    }

    public function edit(string $id)
    {
        return view('admin.businesses.staff.edit', ['staff' => User::findOrFail($id)]);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'business_id' => ['required', 'string', 'max:255', Rule::exists('users', 'id')->where(fn($q) => $q->where('role', 'business'))],
            'name'        => 'required|string|max:255',
            'email'       => ['required', 'email', Rule::unique('users', 'email')->ignore($id)],
            'password'    => 'nullable|string|min:8|confirmed', // ✅ Fixed: was commented out
            'role'        => 'required|in:manager,employee',
        ]);

        $user = User::findOrFail($id);
        $user->fill([
            'name'               => $validated['name'],
            'email'              => $validated['email'],
            'role'               => $validated['role'],
            'parent_business_id' => $validated['business_id'],
            'status'             => $request->status ?? $user->status,
        ]);

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();
        return redirect()->route('staff.index')->with('success', 'Staff updated successfully!');
    }

    public function destroy(string $id)
    {
        User::findOrFail($id)->delete();
        return redirect()->route('staff.index')->with('success', 'Staff deleted successfully.');
    }
}
