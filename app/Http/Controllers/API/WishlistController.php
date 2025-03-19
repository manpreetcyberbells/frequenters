<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CustomerAddress;
use App\Models\BusinessProfile;

class WishlistController extends Controller
{
    // Add Business to Wishlist
    public function store(Request $request)
    {
        $request->validate([
            'business_id' => 'required|exists:users,id',
        ]);

        $businessExists = BusinessProfile::where('business_id', $request->business_id)->exists();
        if (!$businessExists) {
            return response()->json(['message' => 'Business not found'], 404);
        }

        $wishlist = Wishlist::firstOrCreate([
            'customer_id' => Auth::id(),
            'business_id' => $request->business_id,
        ]);

        return response()->json(['message' => 'Business added to wishlist successfully', 'wishlist' => $wishlist], 201);
    }

    // Get Wishlist for Authenticated Customer
    public function index()
    {
        $customerId = Auth::id();
        $customerAddress = CustomerAddress::where('customer_id', $customerId)->select('latitude', 'longitude')->first();
        $customerLat = $customerAddress->latitude ?? 0;
        $customerLong = $customerAddress->longitude ?? 0;

        $wishlist = Wishlist::where('wishlists.customer_id', $customerId)
            ->join('users as businesses', 'wishlists.business_id', '=', 'businesses.id')
            ->leftJoin('business_profiles', 'business_profiles.business_id', '=', 'businesses.id')
            ->leftJoin('business_addresses', 'business_addresses.business_id', '=', 'businesses.id')
            ->leftJoin('categories as parent_category', 'parent_category.id', '=', 'business_profiles.category_id')
            ->leftJoin('categories as child_category', 'child_category.id', '=', 'business_profiles.child_category_id')
            ->leftJoin('reviews', 'reviews.business_id', '=', 'businesses.id')
            ->selectRaw("
                business_addresses.*, 
                business_profiles.*, 
                businesses.name AS business_name,
                parent_category.id AS category_id,
                child_category.id AS child_category_id,
                parent_category.name AS category_name,
                child_category.name AS child_category_name,
                (SELECT GROUP_CONCAT(business_images.photo) 
                    FROM business_images 
                    WHERE business_images.business_id = businesses.id) AS images,
                (SELECT GROUP_CONCAT(
                    CONCAT(day_of_week, '|', open_time, '|', close_time, '|', is_closed)
                ) FROM business_hours 
                WHERE business_hours.business_id = businesses.id) AS business_hours,
                EXISTS (
                    SELECT 1 FROM wishlists 
                    WHERE wishlists.business_id = businesses.id 
                    AND wishlists.customer_id = ?
                ) AS is_wishlist,
                (SELECT COUNT(*) FROM reviews WHERE reviews.business_id = businesses.id) AS total_reviews,
                (SELECT COALESCE(AVG(reviews.rating), 0) FROM reviews WHERE reviews.business_id = businesses.id) AS average_rating,
                ROUND((6371 * acos(
                    cos(radians(?)) * cos(radians(business_addresses.latitude)) 
                    * cos(radians(business_addresses.longitude) - radians(?)) 
                    + sin(radians(?)) * sin(radians(business_addresses.latitude))
                )), 2) AS distance
            ", [$customerId, $customerLat, $customerLong, $customerLat])
            ->orderBy('distance', 'asc')
            ->get();

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

        return response()->json(['message' => 'Business removed from wishlist successfully'], 200);
    }
}
