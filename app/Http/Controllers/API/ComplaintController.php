<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ComplaintController extends Controller
{
    /**
     * Store a newly created complaint in the database.
     */
    public function createComplaint(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|exists:users,id',
            'description' => 'required|string',
            'attachment'  => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        // Handle file upload (if any)
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('complaints', 'public');
        }

        // Create the complaint
        $complaint = Complaint::create([
            'customer_id' => Auth::id(),
            'business_id' => $request->business_id,
            'description' => $request->description,
            'status'      => 'pending',
            'priority'    => 'high',
            'attachment'  => $attachmentPath,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Complaint submitted successfully',
            'complaint' => $complaint
        ], 200);
    }

    /**
     * Get complaints by business_id
    */
    public function getComplaintsByBusiness(Request $request)
    {
        try {
            $businessId = Auth::id();
            $complaints = Complaint::where('business_id', $businessId)->orderBy('created_at', 'desc')->paginate(10);

            if ($complaints->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No complaints found for this business.'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Complaints retrieved successfully.',
                'complaints' => $complaints
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
