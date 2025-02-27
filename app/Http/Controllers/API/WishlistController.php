<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    // Add Business to Wishlist
    public function store(Request $request)
    {
        $request->validate([
            'business_id' => 'required|exists:users,id',
        ]);

        $wishlist = Wishlist::firstOrCreate([
            'customer_id' => Auth::id(),
            'business_id' => $request->business_id,
        ]);

        return response()->json(['message' => 'Business added to wishlist successfully', 'wishlist' => $wishlist], 201);
    }

    // Get Wishlist for Authenticated Customer
    public function index()
    {
        $wishlist = Wishlist::where('customer_id', Auth::id())->with('business')->get();
        return response()->json($wishlist);
    }

    // Remove Business from Wishlist
    public function destroy($id)
    {
        $wishlist = Wishlist::where('customer_id', Auth::id())->where('business_id', $id)->first();
    
        if (!$wishlist) {
            return response()->json(['message' => 'Business not found in wishlist'], 404);
        }

        $wishlist->delete();

        return response()->json(['message' => 'Business removed from wishlist successfully']);
    }
}
