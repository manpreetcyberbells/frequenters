<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MenuController extends Controller
{
    /**
     *  API Function : Create Menu
    */
    public function createMenu(Request $request)
    {
        $businessId = auth()->id();
        $user = auth()->user();
    
        if ($user->role !== 'business') {
            return response()->json(['message' => 'Please login with a valid Business account'], 403);
        }
    
        $validated = $request->validate([
            'name' => 'required|string',
            'category_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'price' => 'nullable|string',
            'photo' => 'nullable|image',
            'availability_status' => 'nullable|boolean',
        ]);
    
        // Handle Image Upload
        $menuImage = '';
        if ($request->hasFile('photo')) {
            $menuImage = $request->file('photo')->store('menus', 'public');
        }
    
        $validated['business_id'] = $businessId;
        $validated['photo'] = asset('storage/' . $menuImage);
    
        $menu = Menu::create($validated);
    
        return response()->json([
            'status' => true,
            'message' => 'Menu Created Successfully',
            'data' => $menu
        ]);
    }
    
}
