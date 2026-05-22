<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class ComplaintController extends Controller
{
    public function __construct(
        private readonly FCMService $fcmService
    ) {}

    public function createComplaint(Request $request)
    {
        $validated = $request->validate([
            'business_id' => 'required|exists:users,id',
            'description' => 'required|string',
            'attachment'  => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $attachmentPath = $request->hasFile('attachment')
            ? $request->file('attachment')->store('complaints', 'public')
            : null;

        $complaint = Complaint::create([
            'customer_id' => Auth::id(),
            'business_id' => $validated['business_id'],
            'description' => $validated['description'],
            'status'      => 'pending',
            'priority'    => 'high',
            'attachment'  => $attachmentPath,
        ]);

        $fcmToken = DB::table('business_profiles')
            ->where('business_id', $validated['business_id'])
            ->value('fcm_token');

        if ($fcmToken) {
            $this->fcmService->sendNotificationToDevice(
                $fcmToken,
                "You've Got a New Complaint",
                'A customer just raised a concern. Tap to check and respond.',
                asset('storage/notifications/notification.png')
            );
        }
        createNotification($validated['business_id'], 'A customer raised a complaint.', 'push', null, true);

        return response()->json(['status' => true, 'message' => 'Complaint submitted successfully', 'complaint' => $complaint], 201);
    }

    public function getComplaintsByBusiness()
    {
        try {
            $complaints = Complaint::where('business_id', Auth::id())
                ->orderByDesc('created_at')
                ->paginate(10);

            if ($complaints->isEmpty()) {
                return response()->json(['status' => false, 'message' => 'No complaints found for this business.'], 404);
            }

            return response()->json(['status' => true, 'message' => 'Complaints retrieved successfully.', 'complaints' => $complaints]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Something went wrong.', 'error' => $e->getMessage()], 500);
        }
    }
}
