<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function getNotifications(Request $request)
    {
        $query = Notification::query();

        // Business
        if ($request->has('business_id')) {
            $query->where('business_id', $request->business_id);
        }

        // Customer
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Broadcast
        if ($request->has('is_broadcast')) {
            $query->where('is_broadcast', 1);
        }

        // Status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $notifications = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'success'       => true,
            'notifications' => $notifications,
        ]);
    }
}
