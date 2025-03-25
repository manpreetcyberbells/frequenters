<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SearchHistory;
use App\Models\BusinessProfile;
use App\Models\CustomerAddress;
use App\Models\BusinessAddress;
use Illuminate\Support\Facades\Auth;

class SearchHistoryController extends Controller
{
    /**
     *  API Function for Store Seach History of Customer 
    */
    public function store(Request $request)
    {
        $request->validate([
            'search_term' => 'required|string|max:255',
        ]);

        SearchHistory::create([
            'customer_id' => Auth::id(),
            'search_term' => $request->search_term,
        ]);

        return response()->json(['message' => 'Search saved successfully']);
    }

    /**
     *  API Function for Get Seach History of Customer 
    */
    public function getUserSearchHistory()
    {
        $searchHistory = SearchHistory::where('customer_id', Auth::id())->orderBy('created_at', 'desc')->get();
        return response()->json($searchHistory);
    }

    /**
     *  API Function for Delete Seach History of Customer 
    */
    public function deleteSingleSearchHistory($id)
    {
        $customerId = auth()->id();
        $searchHistory = SearchHistory::where('id', $id)->where('customer_id', $customerId)->first();
        if (!$searchHistory) {
            return response()->json(['error' => 'Search history not found'], 404);
        }
        $searchHistory->delete();
        return response()->json(['message' => 'Search history deleted successfully.']);
    }

    /**
     *  API Function for Seaching Like Business // Items
    */
    public function searchLocal(Request $request)
    {
        $customerId = auth()->id();
        $categoryId = $request->input('category_id');
        $maxRewardPoints = $request->input('reward_point_range');
        $maxDistance = $request->input('distance');
        $frequenters = $request->input('customer_tier');
        $customerReview = $request->input('customer_review');

        // Fetch customer address
        $customerAddress = CustomerAddress::where('customer_id', $customerId)->first();
        $customerLat = $customerAddress->latitude ?? 0;
        $customerLong = $customerAddress->longitude ?? 0;

        $query = BusinessAddress::selectRaw(
            "business_addresses.id AS business_address_id, 
            business_addresses.business_id,
            business_addresses.address_line1, 
            business_addresses.address_line2, 
            business_addresses.city, 
            business_addresses.state, 
            business_addresses.country, 
            business_addresses.zip_code, 
            business_addresses.latitude, 
            business_addresses.longitude,
            users.id AS user_id,
            users.status AS business_status,
            business_profiles.id AS business_profile_id,
            business_profiles.business_name,
            business_profiles.description,
            business_profiles.website,
            business_profiles.qr_code_hash,
            business_profiles.points_given,
            business_profiles.monthly_points_available,
            business_profiles.points_redeemed,
            business_profiles.points_per_checkin,
            business_profiles.conversion_rate,
            business_profiles.total_checkins,
            business_profiles.total_rewards_redeemed,
            business_profiles.logo,
            business_profiles.owner_name,
            business_profiles.owner_email,
            business_profiles.owner_phone,
            business_profiles.category_id,
            business_profiles.child_category_id,
            parent_category.name AS category_name,
            child_category.name AS child_category_name,
            COALESCE(cbr.customer_tier, 'new') AS customer_tier,
            (SELECT GROUP_CONCAT(business_images.photo) 
                FROM business_images 
                WHERE business_images.business_id = users.id) AS images,
            (SELECT GROUP_CONCAT(
                CONCAT(day_of_week, '|', open_time, '|', close_time, '|', is_closed)
            ) FROM business_hours 
            WHERE business_hours.business_id = users.id) AS business_hours,
            EXISTS (
                SELECT 1 FROM wishlists 
                WHERE wishlists.business_id = users.id 
                AND wishlists.customer_id = ?
            ) AS is_wishlist,
            (SELECT COUNT(*) FROM reviews WHERE reviews.business_id = users.id) AS total_reviews,
            (SELECT COALESCE(AVG(reviews.rating), 0) FROM reviews WHERE reviews.business_id = users.id) AS average_rating,
            (6371 * acos(least(1, 
                cos(radians(?)) * cos(radians(business_addresses.latitude)) * 
                cos(radians(business_addresses.longitude) - radians(?)) + 
                sin(radians(?)) * sin(radians(business_addresses.latitude))
            ))) AS distance",
            [$customerId, $customerLat, $customerLong, $customerLat]
        )
        ->join('users', 'users.id', '=', 'business_addresses.business_id')
        ->leftJoin('business_profiles', 'business_profiles.business_id', '=', 'users.id')
        ->leftJoin('categories AS parent_category', 'parent_category.id', '=', 'business_profiles.category_id')
        ->leftJoin('categories AS child_category', 'child_category.id', '=', 'business_profiles.child_category_id')
        ->leftJoin('customer_business_relationships AS cbr', 'cbr.business_id', '=', 'users.id');

        // Apply category filter
        if ($categoryId) {
            $query->where('business_profiles.category_id', $categoryId);
        }

        // Apply Checkin filter
        if ($maxRewardPoints) {
            $query->where('business_profiles.points_per_checkin', '<=', (int) $maxRewardPoints);
        }

        // Apply customer_tier (frequenters) filter
        if ($frequenters) {
            $query->whereRaw("COALESCE(cbr.customer_tier, 'new') = ?", [$frequenters]);
        }

        // Apply customer_review (rating) filter
        if ($customerReview) {
            if (is_array($customerReview)) {
                $query->havingRaw('average_rating IN (' . implode(',', $customerReview) . ')');
            } else {
                $query->having('average_rating', '<=', $customerReview);
            }
        }

        // Apply distance filter
        if ($maxDistance) {
            $query->havingRaw("distance <= ?", [$maxDistance]);
        }

        // Order results by distance (nearest first)
        $query->orderBy('distance', 'ASC');
        $searchResults = $query->paginate(10);

        return response()->json($searchResults);
    }


}