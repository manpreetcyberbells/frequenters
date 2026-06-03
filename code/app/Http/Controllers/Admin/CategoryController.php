<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    // Get Categories
    public function getCategories(): JsonResponse
    {
        $categories = Category::whereNull('parent_id')->with('children')->get();
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
        $request->validate([
            'active_icon' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'inactive_icon' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'darkmode_active' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'darkmode_inactive' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
        ]);

        $parentId = null;
        if ($request->parent_id === 'other' && !empty($request->new_parent_name)) {
            $parent = Category::create([
                'name' => $request->new_parent_name
            ]);
            $parentId = $parent->id;
        } elseif (is_numeric($request->parent_id)) {
            $parentId = (int) $request->parent_id;
        }

        $childId = null;
        if (($request->child_id === 'other' || empty($request->child_id)) && !empty($request->new_sub_name)) {
            $child = Category::create([
                'name' => $request->new_sub_name,
                'parent_id' => $parentId
            ]);
            $childId = $child->id;
        } elseif (is_numeric($request->child_id)) {
            $childId = (int) $request->child_id;
        }

        $finalCategoryName = null;
        if (!empty($request->new_grand_name)) {
            $finalCategoryName = $request->new_grand_name;
        } elseif (!empty($request->subGrand) && $request->subGrand !== 'other') {
            $finalCategoryName = $request->subGrand;
        }

        if ($finalCategoryName) {
            $data = [
                'name' => $finalCategoryName,
                'parent_id' => $childId ?? $parentId
            ];

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

            Category::create($data); // Save final sub-grand category
        }

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
        $grand_parent_id = [];
        foreach ($sub_categories as $key => $value) {
            $grand_parent_id[] = $value->id;

            $subGrandChildren = Category::where('parent_id', $value->id)->get();
            $structure[] = [
                'sub' => $value,
                'grand' => $subGrandChildren
            ];

        }
        $children = Category::whereIn('parent_id', $grand_parent_id)->get();
        return view('admin.categories.edit', compact('category', 'categories', 'children', 'sub_categories', 'structure'));
    }

    /**
     * Update the specified resource in storage.
    */
    // public function update(Request $request, $id)
    // {
    //     echo"<pre>"; print_r($request->all()); die;
    //     $category = Category::findOrFail($id);
    //     $request->validate([
    //         'name' => 'required|string|max:255',
    //         'parent_id' => 'nullable|exists:categories,id',
    //         'active_icon' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
    //         'inactive_icon' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
    //         'darkmode_active' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
    //         'darkmode_inactive' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
    //     ]);

    //     $data = $request->only(['name', 'parent_id']);

    //     if ($request->hasFile('active_icon')) {
    //         $data['active_icon'] = $request->file('active_icon')->store('category_icons', 'public');
    //     }
    //     if ($request->hasFile('inactive_icon')) {
    //         $data['inactive_icon'] = $request->file('inactive_icon')->store('category_icons', 'public');
    //     }
    //     if ($request->hasFile('darkmode_active')) {
    //         $data['darkmode_active'] = $request->file('darkmode_active')->store('category_icons', 'public');
    //     }
    //     if ($request->hasFile('darkmode_inactive')) {
    //         $data['darkmode_inactive'] = $request->file('darkmode_inactive')->store('category_icons', 'public');
    //     }

    //     $childIds = $request->input('child_id', []);
    //     $subGrandIds = $request->input('subGrand', []);


    //     $category->update($data);

    //     return redirect()->route('categories.index')->with('success', 'Category Updated Successfully');
    // }

    // public function update(Request $request, $id)
    // {
    //     $category = Category::findOrFail($id);

    //     $request->validate([
    //         'active_icon' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
    //         'inactive_icon' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
    //         'darkmode_active' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
    //         'darkmode_inactive' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
    //     ]);

    //     $data = $request->only(['name', 'parent_id']);

    //     //If new parent category is entered
    //     if ($request->filled('new_parent_name')) {
    //         $newParent = Category::create([
    //             'name' => $request->input('new_parent_name'),
    //             'parent_id' => null
    //         ]);
    //         $data['parent_id'] = $newParent->id;
    //     }

    //     // Upload icons
    //     if ($request->hasFile('active_icon')) {
    //         $data['active_icon'] = $request->file('active_icon')->store('category_icons', 'public');
    //     }
    //     if ($request->hasFile('inactive_icon')) {
    //         $data['inactive_icon'] = $request->file('inactive_icon')->store('category_icons', 'public');
    //     }
    //     if ($request->hasFile('darkmode_active')) {
    //         $data['darkmode_active'] = $request->file('darkmode_active')->store('category_icons', 'public');
    //     }
    //     if ($request->hasFile('darkmode_inactive')) {
    //         $data['darkmode_inactive'] = $request->file('darkmode_inactive')->store('category_icons', 'public');
    //     }

    //     $category->update($data);

    //     //Detach all current children
    //     Category::where('parent_id', $category->id)->update(['parent_id' => null]);

    //     //Handle new sub-categories (child_id[])
    //     $childIds = $request->input('child_id', []);

    //     // If a new sub-category was added
    //     if ($request->filled('new_sub_name')) {
    //         $newSub = Category::create([
    //             'name' => $request->input('new_sub_name'),
    //             'parent_id' => $category->id,
    //         ]);
    //         $childIds[] = $newSub->id;
    //     }

    //     // Set selected children to current category
    //     Category::whereIn('id', $childIds)->update(['parent_id' => $category->id]);

    //     //Detach all current sub-grandchildren
    //     Category::whereIn('parent_id', $childIds)->update(['parent_id' => null]);

    //     //Handle new sub-grand-categories
    //     $subGrandIds = $request->input('subGrand', []);

    //     if ($request->filled('new_grand_name') && !empty($childIds)) {
    //         $newGrand = Category::create([
    //             'name' => $request->input('new_grand_name'),
    //             'parent_id' => $childIds[0], // Attach to first child
    //         ]);
    //         $subGrandIds[] = $newGrand->id;
    //     }

    //     if (!empty($childIds)) {
    //         $firstChildId = $childIds[0];
    //         Category::whereIn('id', $subGrandIds)->update(['parent_id' => $firstChildId]);
    //     }

    //     return redirect()->route('categories.index')->with('success', 'Category Updated Successfully');
    // }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        if (is_null($category->parent_id) && !empty($request->parent_id)) {
            return redirect()->back()->withErrors([
                'parent_id' => 'Main categories like "' . $category->name . '" cannot be assigned under another category.',
            ]);
        }

        $request->validate([
            'active_icon' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'inactive_icon' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'darkmode_active' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'darkmode_inactive' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
        ]);

        // Handle Parent Category
        $parentId = null;
        if ($request->parent_id === 'other' && $request->filled('new_parent_name')) {
            $newParent = Category::create([
                'name' => $request->new_parent_name,
                'parent_id' => null
            ]);
            $parentId = $newParent->id;
        } elseif (is_numeric($request->parent_id)) {
            $parentId = (int) $request->parent_id;
        }

        // Handle Sub Category (Child)
        $childId = null;
        if (($request->child_id === 'other' || empty($request->child_id)) && $request->filled('new_sub_name')) {
            $newChild = Category::create([
                'name' => $request->new_sub_name,
                'parent_id' => $parentId
            ]);
            $childId = $newChild->id;
        } elseif (is_numeric($request->child_id)) {
            $childId = (int) $request->child_id;
        }

        // Handle Sub-Grand Category
        $grandId = null;
        if (($request->subGrand === 'other' || empty($request->subGrand)) && $request->filled('new_grand_name')) {
            $newGrand = Category::create([
                'name' => $request->new_grand_name,
                'parent_id' => $childId ?? $parentId
            ]);
            $grandId = $newGrand->id;
        } elseif (is_numeric($request->subGrand)) {
            $grandId = (int) $request->subGrand;
        }

        // Prepare update data
        $updateData = [];

        // Only set parent_id if the current category is NOT a main category
        if (!is_null($category->parent_id)) {
            $finalParentId = $grandId ?? $childId ?? $parentId;
            if (!is_null($finalParentId)) {
                $updateData['parent_id'] = $finalParentId;
            }
        }

        // Handle icon uploads
        if ($request->hasFile('active_icon')) {
            $updateData['active_icon'] = $request->file('active_icon')->store('category_icons', 'public');
        }
        if ($request->hasFile('inactive_icon')) {
            $updateData['inactive_icon'] = $request->file('inactive_icon')->store('category_icons', 'public');
        }
        if ($request->hasFile('darkmode_active')) {
            $updateData['darkmode_active'] = $request->file('darkmode_active')->store('category_icons', 'public');
        }
        if ($request->hasFile('darkmode_inactive')) {
            $updateData['darkmode_inactive'] = $request->file('darkmode_inactive')->store('category_icons', 'public');
        }

        // Update current category
        $category->update($updateData);

        return redirect()->route('categories.index')->with('success', 'Category Updated Successfully');
    }

     // Delete Category
    public function destroy(Category $category)
    {
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Category deleted successfully.');
    }

    public function destroySubGrand($id)
    {
        $category = Category::findOrFail($id);
        if ($category->parent_id === null) {
            return redirect()->back()->with('error', 'Cannot delete a root category here.');
        }

        $category->delete();
        return redirect()->back()->with('success', 'Sub-Grand Category deleted successfully.');
    }

    public function getSubcategories($id)
    {
        $children = Category::where('parent_id', $id)->get(['id', 'name']);
        return response()->json($children);
    }

}
