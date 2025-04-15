<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Collaboration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CollaborationController extends Controller
{
    /**
     * API Function : BtoB Collaborations
    */
    public function bTobCollaboration(Request $request)
    {
        // echo"<pre>"; print_r($request->all()); die;
        $businessId = auth()->id();
        $user = auth()->user();
        if ($user->role !== 'business') {
            return response()->json(['message' => 'Please login with a valid Business account'], 403);
        }

        $validatedData = $request->validate([
            //'creator_business_id' => 'required|exists:businesses,id', // Business initiating the collab
            //'partner_business_id' => 'nullable|exists:users,id', // Partner business
            'partner_business_id' => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', 'business');
                }),
            ],
            'target_business_type' => 'required|string|max:255',
            'description' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
            'social_links' => 'nullable|string',
        ]);

        try{

            $validatedData['creator_business_id'] = $businessId; // Business initiating the collab
            $collab = Collaboration::create($validatedData);

            return response()->json([
                'message' => 'Collaboration request created successfully',
                'data' => $collab
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API Function : BtoB Collaboration Listing
    */
    public function getbTobListing($businessId)
    {
        $businessId = auth()->id();
        $user = auth()->user();
        if ($user->role !== 'business') {
            return response()->json(['message' => 'Please login with a valid Business account'], 403);
        }

        $collabs = Collaboration::where('creator_business_id', $businessId)
            ->orWhere('partner_business_id', $businessId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($collabs);
    }

}
