<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    // Get Parent Categories
    public function getCategories(): JsonResponse
    {
        $categories = Category::whereNull('parent_id')
            ->with('children')
            ->get();
    
        return response()->json($categories);
    }

    // Get Parent Categories
    public function getParentCategories(): JsonResponse
    {
        $categories = Category::whereNull('parent_id')->get();
        return response()->json($categories);
    }

    // Get Child Categories by Parent ID
    public function getChildCategories($parentId): JsonResponse
    {
        $categories = Category::where('parent_id', $parentId)->get();
        return response()->json($categories);
    }

    /**
     * Display a listing of the resource.
    */
    public function index()
    {
        $categories = Category::whereNull('parent_id')->with(['children','getChildrenRecursive'])->get();
        // echo"<pre>"; print_r($categories);  die;
        return view('admin.categories.index', compact('categories'));
    }
    
    /**
     * Show the form for creating a new resource.
    */
    public function create()
    {
        $categories = Category::whereNull('parent_id')->get(); // Fetch parent categories
        return view('admin.categories.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
    */
    public function store(Request $request)
    {
        // echo"<pre>"; print_r($request->all()); die;
        $request->validate([
            // 'name' => 'required|string|max:255',
            'subGrand' => 'required|string|max:255',
            // 'parent_id' => 'nullable|exists:categories,id',
            'active_icon' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'inactive_icon' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'darkmode_active' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'darkmode_inactive' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
        ]);

        // Step 1: Handle parent
        $parentId = $request->parent_id;
        if ($parentId === "other" && !empty($request->new_parent_name)) {
            $parent = Category::create([
                'name' => $request->new_parent_name,
            ]);
            $parentId = $parent->id;
        }

        // Step 2: Handle child (subcategory)
        $childId = $request->child_id;
        if ($childId === "other" || empty($childId) && !empty($request->new_sub_name)) {
            $child = Category::create([
                'name' => $request->new_sub_name,
                'parent_id' => $parentId
            ]);
            $childId = $child->id;
        } elseif ($childId !== "other" && is_numeric($childId)) {
            $childId = intval($childId);
        } else {
            $childId = null;
        }

        // Step 3: Prepare data for grandchild (final category to be stored)
        $data = [
            'name' => $request->subGrand,
            'parent_id' => $childId ?? $parentId
        ];

        // Step 4: Handle icon uploads
        if ($request->hasFile('active_icon')) {
            $data['active_icon'] = $request->file('active_icon')->store('category_icons', 'public');
        }
        if ($request->hasFile('inactive_icon')) {
            $data['inactive_icon'] = $request->file('inactive_icon')->store('category_icons', 'public');
        }
        if ($request->hasFile('darkmode_active')) {
            $data['darkmode_active'] = $request->file('darkmode_active')->store('category_icons', 'public');
        }
        if ($request->hasFile('darkmode_inactive')) {
            $data['darkmode_inactive'] = $request->file('darkmode_inactive')->store('category_icons', 'public');
        }

        // Step 5: Create the grandchild category
        Category::create($data);

        return redirect()->route('categories.index')->with('success', 'Category Added Successfully');
    }

    /**
     * Show the form for editing the specified resource.
    */
    public function edit($id)
    {
        $category = Category::findOrFail($id);
        $categories = Category::whereNull('parent_id')->get();
        $sub_categories = Category::where('parent_id', $id)->get();
        $children = $category->parent_id ? Category::where('parent_id', $category->parent->parent_id)->get() : collect();
        // echo"<pre>"; print_r($children);  die;
        return view('admin.categories.edit', compact('category', 'categories', 'children', 'sub_categories'));
    }

    /**
     * Update the specified resource in storage.
    */
    public function update(Request $request, $id)
    {
        // echo"<pre>"; print_r($request->all()); die;
        $category = Category::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'active_icon' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'inactive_icon' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'darkmode_active' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'darkmode_inactive' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
        ]);

        $data = $request->only(['name', 'parent_id']);

        if ($request->hasFile('active_icon')) {
            $data['active_icon'] = $request->file('active_icon')->store('category_icons', 'public');
        }
        if ($request->hasFile('inactive_icon')) {
            $data['inactive_icon'] = $request->file('inactive_icon')->store('category_icons', 'public');
        }
        if ($request->hasFile('darkmode_active')) {
            $data['darkmode_active'] = $request->file('darkmode_active')->store('category_icons', 'public');
        }
        if ($request->hasFile('darkmode_inactive')) {
            $data['darkmode_inactive'] = $request->file('darkmode_inactive')->store('category_icons', 'public');
        }

        $category->update($data);

        return redirect()->route('categories.index')->with('success', 'Category Updated Successfully');
    }

     // Delete Category
    public function destroy(Category $category)
    {
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Category deleted successfully.');
    }

    public function getSubcategories($id)
    {
        $children = Category::where('parent_id', $id)->get(['id', 'name']);
        return response()->json($children);
    }

}
