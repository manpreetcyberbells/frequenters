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

        // Get notifications for a specific user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Get notifications for a specific business
        if ($request->has('business_id')) {
            $query->where('business_id', $request->business_id);
        }

        // Get only broadcast notifications
        if ($request->has('is_broadcast')) {
            $query->where('is_broadcast', 1);
        }

        // Filter by status (pending, sent, delivered, failed)
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Order by latest notifications
        $notifications = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'success' => true,
            'notifications' => $notifications
        ]);
    }
}
