<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin\Category;

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
            'grandcategory_id' => 'nullable|exists:categories,id',
            'grandcategory' => 'nullable|string|max:255',
            'child_id' => 'nullable|exists:categories,id',   // For grandcategory creation
            'parent_id' => 'nullable|exists:categories,id',  // For grandcategory creation
            'description' => 'nullable|string',
            'price' => 'nullable|string',
            'photo' => 'nullable|image',
            'availability_status' => 'nullable|boolean',
        ]);

        if (empty($validated['grandcategory_id']) && !empty($validated['grandcategory'])) {
            // Validate that child_id and parent_id are provided
            if (empty($validated['child_id']) || empty($validated['parent_id'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Both parent_id and child_id are required when creating a new grandcategory.'
                ], 422);
            }

            // Verify relationship: child must belong to parent
            $child = Category::find($validated['child_id']);
            if ($child->parent_id != $validated['parent_id']) {
                return response()->json([
                    'status' => false,
                    'message' => 'The child category does not belong to the specified parent.'
                ], 422);
            }

            // Check if grandchild already exists
            $existing = Category::where('name', $validated['grandcategory'])->where('parent_id', $validated['child_id'])->first();
            if ($existing) {
                $validated['grandcategory_id'] = $existing->id;
            } else {
                // Create the grandchild category
                $newGrand = Category::create([
                    'name' => $validated['grandcategory'],
                    'parent_id' => $validated['child_id']
                ]);
                $validated['grandcategory_id'] = $newGrand->id;
            }
        }
    
        // Handle Image Upload
        $menuImage = '';
        if ($request->hasFile('photo')) {
            $menuImage = $request->file('photo')->store('menus', 'public');
        }
    
        $validated['business_id'] = $businessId;
        $validated['photo'] = asset('storage/' . $menuImage);
    
        $menu = Menu::create([
            'name' => $validated['name'],
            'category_id' => $validated['grandcategory_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'] ?? null,
            'availability_status' => $validated['availability_status'] ?? 0,
            'photo' => asset('storage/' . $menuImage),
            'business_id' => $businessId,
        ]);
    
        return response()->json([
            'status' => true,
            'message' => 'Menu Created Successfully',
            'data' => $menu
        ]);
    }

    /**
     *  API Function : MENU Listing
    */
    public function getMenuList(Request $request)
    {
        $businessId = $request->business_id;
        if (!$businessId) {
            return response()->json(['status' => false, 'message' => 'Business ID is required'], 400);
        }

        $menus = Menu::where('business_id', $businessId)->orderBy('created_at', 'desc')->paginate(10);
        if ($menus->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'No menus found for this business'], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $menus
        ]);
    }

    
}
