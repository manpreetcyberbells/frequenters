<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menu;
use App\Models\Admin\Category;
use Illuminate\Validation\Rule;

class AdminMenuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $menus = Menu::orderBy('id', 'desc')->get();
        return view('admin.businesses.menu.index', compact('menus'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::whereNotNull('parent_id')->get();
        return view('admin.businesses.menu.create', compact('categories'));
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
            'name'                => 'required|string',
            'category_id'         => 'nullable|exists:categories,id',
            'description'         => 'nullable|string',
            'price'               => 'nullable|string',
            'photo'               => 'nullable|image',
            'availability_status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();
        try{
            $businessId = $request->business_id;
            $validated['business_id'] = $businessId;
            // Handle Image Upload
            $menuImage = '';
            $validated['photo'] = null;
            if ($request->hasFile('photo')) {
                $menuImage = $request->file('photo')->store('menus', 'public');
                $validated['photo'] = asset('storage/' . $menuImage);
            }
            $menu = Menu::create($validated);
            return redirect()->route('menus.index')->with('success', 'Menu created successfully!');
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
        $menu = Menu::findOrFail($id);
        $categories = Category::whereNotNull('parent_id')->get();
        return view('admin.businesses.menu.edit', compact('menu','categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // echo"<pre>"; print_r($request->all());  die;
        $menu = Menu::findOrFail($id);
        $validator = \Validator::make($request->all(), [
            'business_id' => [
                'required', 'string', 'max:255',
                Rule::exists('users', 'id')->where(fn($q) => $q->where('role', 'business')),
            ],
            'name'                => 'required|string',
            'category_id'         => 'nullable|exists:categories,id',
            'description'         => 'nullable|string',
            'price'               => 'nullable|string',
            'photo'               => 'nullable|image',
            'availability_status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();
        try {
            if ($request->hasFile('photo')) {
                $menuImage = $request->file('photo')->store('menus', 'public');
                $validated['photo'] = asset('storage/' . $menuImage);
            } else {
                // Prevent overwriting with null
                unset($validated['photo']);
            }

            $menu->update($validated);

            return redirect()->route('menus.index')->with('success', 'Menu updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage() ?? 'Error updating menu');
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
