<?php
// ═══════════════════════════════════════════════════════════════════
// CollaborationController.php
// ═══════════════════════════════════════════════════════════════════
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Collaboration;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CollaborationController extends Controller
{
    public function __construct(
        private readonly FCMService $fcmService
    ) {}

    public function bTobCollaboration(Request $request)
    {
        if (Auth::user()->role !== 'business') {
            return response()->json(['message' => 'Please login with a valid Business account'], 403);
        }

        $validated = $request->validate([
            'partner_business_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn($q) => $q->where('role', 'business')),
            ],
            'target_business_type' => 'required|string|max:255',
            'description'          => 'required|string',
            'start_date'           => 'required|date',
            'end_date'             => 'required|date|after:start_date',
            'contact_name'         => 'nullable|string|max:255',
            'contact_email'        => 'nullable|email',
            'contact_phone'        => 'nullable|string|max:20',
            'social_links'         => 'nullable|string',
        ]);

        try {
            $validated['creator_business_id'] = Auth::id();
            $collab = Collaboration::create($validated);
            $fcmToken = DB::table('business_profiles')->where('business_id', $validated['partner_business_id'])->value('fcm_token');
            if (!empty($fcmToken)) {
                try {
                    $this->fcmService->sendToBusinessApp(
                        $fcmToken,
                        Auth::user()->name,
                        $validated['description'],
                        url('storage/notifications/notification.png')
                    );
                } catch (\Throwable $e) {
                    \Log::error("Collaboration FCM failed for business {$validated['partner_business_id']}: " . $e->getMessage());
                }
            }

            createNotification($validated['partner_business_id'], $validated['description'], 'push', null, true);

            return response()->json([
                'message' => 'Collaboration request created successfully',
                'data'    => $collab,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function getbTobListing()
    {
        if (Auth::user()->role !== 'business') {
            return response()->json(['message' => 'Please login with a valid Business account'], 403);
        }

        $businessId = Auth::id();
        $collabs = Collaboration::where('creator_business_id', $businessId)
            ->orWhere('partner_business_id', $businessId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($collabs);
    }
}
