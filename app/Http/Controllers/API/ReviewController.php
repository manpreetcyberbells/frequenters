<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    // Submit a review
    public function store(Request $request)
    {
        $request->validate([
            'business_id' => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', 'business');
                }),
            ],
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:500',
            // 'complaint' => 'nullable|string|max:500',
        ]);

        $review = Review::updateOrCreate(
            [
                'customer_id' => Auth::id(),
                'business_id' => $request->business_id,
            ],
            [
                'rating' => $request->rating,
                'review' => $request->review,
                // 'complaint' => $request->complaint,
            ]
        );

        return response()->json(['message' => 'Review submitted successfully', 'review' => $review], 201);
    }

    // Get all reviews for a specific business
    public function getBusinessReviews($business_id)
    {
        $reviews = Review::where('business_id', $business_id)
            ->with('customer:id,name,profile_image')
            ->get();

        $totalReviews = $reviews->count();
        $averageRating = $totalReviews > 0 ? round($reviews->avg('rating'), 1) : 0;

        return response()->json([
            'total_reviews' => $totalReviews,
            'average_rating' => $averageRating,
            'reviews' => $reviews
        ]);
    }


    // Get reviews submitted by the authenticated customer
    public function getCustomerReviews()
    {
        $reviews = Review::where('customer_id', Auth::id())->with('business:id,name')->get();
        return response()->json($reviews);
    }

    // Delete a review
    public function destroy($id)
    {
        $review = Review::where('id', $id)->where('customer_id', Auth::id())->first();
        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }
        $review->delete();
        return response()->json(['message' => 'Review deleted successfully']);
    }
}

