<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Traits\ResolvesGrandCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MenuController extends Controller
{
    use ResolvesGrandCategory;

    public function createMenu(Request $request)
    {
        if (Auth::user()->role !== 'business') {
            return response()->json(['message' => 'Please login with a valid Business account'], 403);
        }

        $validated = $request->validate([
            'name'                => 'required|string',
            'grandcategory_id'    => 'nullable|exists:categories,id',
            'grandcategory'       => 'nullable|string|max:255',
            'child_id'            => 'nullable|exists:categories,id',
            'parent_id'           => 'nullable|exists:categories,id',
            'description'         => 'nullable|string',
            'price'               => 'nullable|string',
            'photo'               => 'nullable|image',
            'availability_status' => 'nullable|boolean',
        ]);

        $categoryId = $this->resolveGrandCategoryId($validated);
        if ($categoryId instanceof \Illuminate\Http\JsonResponse) return $categoryId;

        $menuImage = $request->hasFile('photo')
            ? asset('storage/' . $request->file('photo')->store('menus', 'public'))
            : null;

        $menu = Menu::create([
            'business_id'         => Auth::id(),
            'name'                => $validated['name'],
            'category_id'         => $categoryId ?: null,
            'description'         => $validated['description'] ?? null,
            'price'               => $validated['price'] ?? null,
            'availability_status' => $validated['availability_status'] ?? 0,
            'photo'               => $menuImage,
        ]);

        return response()->json(['status' => true, 'message' => 'Menu Created Successfully', 'data' => $menu]);
    }

    public function getMenuList(Request $request)
    {
        $businessId = $request->business_id;
        if (!$businessId) {
            return response()->json(['status' => false, 'message' => 'Business ID is required'], 400);
        }

        $menus = Menu::where('business_id', $businessId)->orderByDesc('created_at')->paginate(10);
        if ($menus->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'No menus found for this business'], 404);
        }

        return response()->json(['status' => true, 'data' => $menus]);
    }
}