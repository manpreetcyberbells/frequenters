<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Events\MessageSent;
use App\Models\Message;
use App\Models\CustomerMessageResponse;
use App\Models\User;
use App\Services\FCMService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    public function __construct(
        private readonly FCMService $fcmService
    ) {}

    // Business: Send Group Message
    public function sendBusinessMessage(Request $request)
    {
        if (Auth::user()->role !== 'business') {
            return response()->json(['message' => 'Please login with a valid Business account'], 403);
        }

        $validated = $request->validate([
            'message_text'  => 'nullable|string|max:500',
            'group_target'  => 'required|in:new,regular,daily',
            'chat_enabled'  => 'required|boolean',
            'enable_buttons'=> 'required|boolean',
        ]);

        $businessId  = Auth::id();
        $groupType   = $validated['group_target'];
        $messageText = $validated['message_text'];

        $eligibleCustomers = $this->getCustomersForMessage($businessId, $groupType);
        if ($eligibleCustomers->isEmpty()) {
            return response()->json(['message' => 'No customers in this group'], 404);
        }

        $message = Message::create([
            'business_id'      => $businessId,
            'message_text'     => $messageText,
            'group_target'     => $groupType,
            'chat_enabled'     => $validated['chat_enabled'],
            'enable_buttons'   => $validated['enable_buttons'],
            'is_group_message' => 1,
        ]);

        $businessName = DB::table('business_profiles')->where('business_id', $businessId)->value('business_name');
        $fcmTokens = DB::table('customer_profiles')
            ->whereIn('customer_id', $eligibleCustomers->toArray())
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token', 'customer_id');

        $image = asset('storage/notifications/notification.png');

        foreach ($eligibleCustomers as $customerId) {
            $messageData = (object)[
                'sender_id'   => $businessId,
                'receiver_id' => $customerId,
                'text'        => $messageText,
                'timestamp'   => now(),
            ];

            broadcast(new MessageSent($messageData))->toOthers();

            if (isset($fcmTokens[$customerId])) {
                $this->fcmService->sendNotificationToDevice($fcmTokens[$customerId], $businessName, $messageText, $image);
            }

            customerNotification($customerId, $messageText, 'push', null, true);
        }

        return response()->json(['message' => 'Message sent successfully', 'message_id' => $message->id]);
    }

    // Customer: Get Messages from Businesses
    public function getCustomerMessages(int $customerId)
    {
        $messages = Message::join('customer_business_relationships', function ($join) use ($customerId) {
                $join->on('messages.business_id', '=', 'customer_business_relationships.business_id')
                     ->where('customer_business_relationships.customer_id', '=', $customerId);
            })
            ->select('messages.*')
            ->distinct()
            ->get();

        $businessIds = $messages->pluck('business_id')->unique();
        $businesses  = User::whereIn('id', $businessIds)->pluck('name', 'id');

        foreach ($messages as $message) {
            $message->business_name = $businesses[$message->business_id] ?? null;
            $message->time_ago      = Carbon::parse($message->created_at)->diffForHumans();
        }

        return response()->json($messages);
    }

    // Customer: Respond to Message
    public function respondToMessage(Request $request)
    {
        if (Auth::user()->role !== 'customer') {
            return response()->json(['message' => 'Please login with a valid Customer account'], 403);
        }

        $validated = $request->validate([
            'business_id' => 'required|exists:users,id',
            'message_id'  => 'required|exists:messages,id',
            'status'      => 'required|in:accepted,declined',
        ]);

        $validated['customer_id'] = Auth::id();
        CustomerMessageResponse::create($validated);

        return response()->json(['chat_enabled' => $validated['status'] === 'accepted']);
    }

    // One-to-One Message
    public function sendOneToOneMessage(Request $request)
    {
        $validated = $request->validate([
            'receiver_id'  => 'required|exists:users,id',
            'message_text' => 'required|string|max:500',
        ]);

        $sender     = Auth::user();
        $receiverId = $validated['receiver_id'];
        $messageText= $validated['message_text'];

        $message = Message::create([
            'sender_id'    => $sender->id,
            'receiver_id'  => $receiverId,
            'message_text' => $messageText,
            'is_group_message' => false,
            'chat_enabled' => true,
        ]);

        broadcast(new MessageSent((object)[
            'sender_id'   => $sender->id,
            'receiver_id' => $receiverId,
            'text'        => $messageText,
            'timestamp'   => now(),
        ]));

        $image = asset('storage/notifications/notification.png');

        if ($sender->role === 'customer') {
            $fcmToken = DB::table('business_profiles')->where('business_id', $receiverId)->value('fcm_token');
        } else {
            $fcmToken = DB::table('customer_profiles')->where('customer_id', $receiverId)->value('fcm_token');
        }

        if ($fcmToken) {
            $this->fcmService->sendNotificationToDevice($fcmToken, $sender->name, $messageText, $image);
        }

        // Notification in DB for receiver
        if ($sender->role === 'customer') {
            createNotification($receiverId, $messageText, 'push', null, true);
        } else {
            customerNotification($receiverId, $messageText, 'push', null, true);
        }

        $this->markMessageAsReadInternal($message->id, $sender->id);

        return response()->json(['message' => 'Message sent successfully', 'message_id' => $message->id]);
    }

    // Chat History
    public function getChatMessages(Request $request, int $receiverId)
    {
        $userId = Auth::id();

        $messages = Message::where(fn($q) => $q->where('sender_id', $userId)->where('receiver_id', $receiverId))
            ->orWhere(fn($q) => $q->where('sender_id', $receiverId)->where('receiver_id', $userId))
            ->orderBy('created_at', 'asc')
            ->get();

        $userIds = $messages->pluck('sender_id')->merge($messages->pluck('receiver_id'))->unique();
        $users   = User::whereIn('id', $userIds)->get()->keyBy('id');

        foreach ($messages as $message) {
            if ($message->receiver_id === $userId) {
                $this->markMessageAsReadInternal($message->id, $userId);
            }
            $message['sender_name']    = $users[$message->sender_id]?->name;
            $message['receiver_name']  = $users[$message->receiver_id]?->name;
            $message['sender_image']   = $users[$message->sender_id]?->profile_image;
            $message['receiver_image'] = $users[$message->receiver_id]?->profile_image;
            $message->time_ago         = Carbon::parse($message->created_at)->diffForHumans();
            $message->read_by          = json_decode($message->read_by, true);
        }

        return response()->json(['messages' => $messages]);
    }

    // Chat List
    public function getChatList()
    {
        $currentUserId = Auth::id();
        $latestMessages = Message::select(
                DB::raw('IF(sender_id = ' . $currentUserId . ', receiver_id, sender_id) as other_user_id'),
                DB::raw('MAX(id) as last_message_id')
            )
            ->where(fn($q) => $q->where('sender_id', $currentUserId)->orWhere('receiver_id', $currentUserId))
            ->groupBy('other_user_id')
            ->get();

        $lastMessageIds = $latestMessages->pluck('last_message_id')->toArray();
        $otherUserIds   = $latestMessages->pluck('other_user_id')->toArray();

        $lastMessages = Message::whereIn('id', $lastMessageIds)->get()->keyBy('id');
        $otherUsers   = User::whereIn('id', $otherUserIds)->get()->keyBy('id');

        // Unread counts — bulk
        $unreadCounts = Message::whereIn('sender_id', $otherUserIds)
            ->where('receiver_id', $currentUserId)
            ->whereJsonDoesntContain('read_by', $currentUserId)
            ->selectRaw('sender_id, COUNT(*) as unread')
            ->groupBy('sender_id')
            ->pluck('unread', 'sender_id');

        $chatList = $latestMessages->map(function ($chat) use ($lastMessages, $otherUsers, $unreadCounts, $currentUserId) {
            $lastMessage = $lastMessages[$chat->last_message_id] ?? null;
            $otherUser   = $otherUsers[$chat->other_user_id] ?? null;
            $readByArray = json_decode($lastMessage?->read_by, true) ?? [];

            return [
                'id'                    => $otherUser?->id,
                'name'                  => $otherUser?->name,
                'profile_image'         => $otherUser?->profile_image,
                'last_message'          => $lastMessage?->message_text,
                'last_message_time'     => $lastMessage?->created_at?->format('H:i'),
                'unread_count'          => $unreadCounts[$chat->other_user_id] ?? 0,
                'is_last_message_read'  => in_array($currentUserId, $readByArray),
            ];
        });

        return response()->json($chatList);
    }

    // Private Helpers
    private function getCustomersForMessage(int $businessId, string $groupType)
    {
        $customers = DB::table('customer_business_relationships')
            ->join('users', 'customer_business_relationships.customer_id', '=', 'users.id')
            ->where('customer_business_relationships.business_id', $businessId)
            ->where('customer_business_relationships.customer_tier', $groupType)
            ->pluck('users.id');

        $declinedCustomers = DB::table('customer_message_responses')
            ->whereIn('customer_id', $customers)
            ->where('business_id', $businessId)
            ->where('status', 'declined')
            ->pluck('customer_id');

        return $customers->diff($declinedCustomers);
    }

    private function markMessageAsReadInternal(int $messageId, int $userId): void
    {
        $message = Message::find($messageId);
        if (!$message) return;

        $readByUsers = json_decode($message->read_by, true) ?? [];
        if (!in_array($userId, $readByUsers)) {
            $readByUsers[]    = $userId;
            $message->read_by = json_encode($readByUsers);
            $message->save();
        }
    }
}
