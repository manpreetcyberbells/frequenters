<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class AdminStaffController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $staffMembers = User::whereIn('role',['manager','employee'])
        // ->where('status','active')
        ->orderBy('id', 'desc')->get();

        foreach ($staffMembers as $key => $staff) {
            $staffDetail = User::where('id', $staff->parent_business_id)->first();
            $staff->parent_business = @$staffDetail->name;
        }

        // echo"<pre>"; print_r($staffMembers);  die;
        return view('admin.businesses.staff.index', compact('staffMembers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.businesses.staff.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // echo"<pre>"; print_r($request->all());  die;
        $validated = $request->validate([
            'business_id' => [
                'required',
                'string',
                'max:255',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', 'business');
                }),
            ],
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:manager,employee'
        ]);

        $totalStaff = User::where('parent_business_id', $request->business_id)->whereIn('role', ['manager', 'employee'])->whereNull('deleted_at')->count();
        if ($totalStaff >= 5) {
            return response()->json(['message' => 'You can only add up to 5 staff members.'], 400);
        }
        
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'parent_business_id' => $request->business_id,
            'providers' => 'email',
            'status' => $request->status
        ]);

        return redirect()->route('staff.index')->with('success', 'Staff created successfully!');
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
        $staff = User::findOrFail($id);
        return view('admin.businesses.staff.edit', compact('staff'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // echo"<pre>"; print_r($request->all());  die;
        $validated = $request->validate([
            'business_id' => [
                'required',
                'string',
                'max:255',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', 'business');
                }),
            ],
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:manager,employee'
        ]);
    
        $user = User::findOrFail($id);
    
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        $user->parent_business_id = $validated['business_id'];
        $user->providers = 'email';
        $user->status = $request->status ?? $user->status;
    
        // if (!empty($validated['password'])) {
        //     $user->password = Hash::make($validated['password']);
        // }
    
        $user->save();
    
        return redirect()->route('staff.index')->with('success', 'Staff updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
