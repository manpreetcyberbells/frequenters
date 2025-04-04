<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Models\CustomerMessageResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MessageController extends Controller
{
    /**
     *  Common Function :: Mark Message as Read 
    */
    private function markMessageAsReadInternal($messageId, $userId)
    {
        $message = Message::find($messageId);
        if (!$message) return;

        $readByUsers = json_decode($message->read_by, true) ?? [];

        if (!in_array($userId, $readByUsers)) {
            $readByUsers[] = $userId;
            $message->read_by = json_encode($readByUsers);
            $message->save();
        }
    }

    /**
     *  Getting Customers 
    */
    public function getCustomersForMessage($businessId, $groupType)
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

        $eligibleCustomers = $customers->diff($declinedCustomers);

        // Log for declined customer IDs
        Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/declined_customers_log.log'),
        ])->info('Declined Customers:', ['declined_customers' => $declinedCustomers]);
         
        // Log for eligible customers
        Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/eligible_customers_log.log'),
        ])->info('Eligible Customers:', ['eligible_customers' => $eligibleCustomers]);

        return $eligibleCustomers;
    }


    /**
     *  API Function Sending Message to Customer 
    */
    public function sendBusinessMessage(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($user->role !== 'business') {
            return response()->json(['message' => 'Please login with a valid Business account'], 403);
        }

        $validated = $request->validate([
            'message_text' => 'nullable|string|max:500',
            'group_target' => 'required|in:new,regular,daily',
            'chat_enabled' => 'required|boolean'
        ]);

        $businessId = $user->id;
        $groupType = $validated['group_target'];
        $messageText = $validated['message_text'];
        $chatEnabled = $validated['chat_enabled'];

        $customers = $this->getCustomersForMessage($businessId, $groupType);
        if ($customers->isEmpty()) {
            return response()->json(['message' => 'No customers in this group'], 404);
        }

        $message = Message::create([
            'business_id' => $businessId,
            'message_text' => $messageText,
            'group_target' => $groupType,
            'chat_enabled' => $chatEnabled,
            'is_group_message' => 1,
        ]);

        return response()->json(['message' => 'Message sent successfully', 'message_id' => $message]);
    }


    /**
     *  API Function Getting Customer Messages 
    */
    public function getCustomerMessages($customerId)
    {
        $messages = Message::join('customer_business_relationships', function ($join) use ($customerId) {
                $join->on('message.business_id', '=', 'customer_business_relationships.business_id')
                    ->where('customer_business_relationships.customer_id', '=', $customerId);
            })
            ->select('message.*')
            ->distinct()
            ->get();

        // Fetch all business names in one query
        $businessIds = $messages->pluck('business_id')->unique();
        $businesses = User::whereIn('id', $businessIds)->pluck('name', 'id');

        // Attach business names
        foreach ($messages as $message) {
            $message->business_name = $businesses[$message->business_id] ?? null;
            $message->time_ago = Carbon::parse($message->created_at)->diffForHumans();
        }

        return response()->json($messages);
    }


    /**
     *  API Function Customer respond to Message
    */
    public function respondToMessage(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($user->role !== 'customer') {
            return response()->json(['message' => 'Please login with a valid Customer account'], 403);
        }

        $validated = $request->validate([
            'business_id' => 'required|exists:users,id',
            'message_id' => 'required|exists:message,id',
            'status' => 'required|in:accepted,declined',
        ]);

        $validated['customer_id'] = $user->id;

        $response = CustomerMessageResponse::create($validated);

        return response()->json(['chat_enabled' => $validated['status'] == 'accepted']);
    }

    /**
     *  API Function One to One Message
    */
    public function sendOneToOneMessage(Request $request)
    {
        // echo"<pre>"; print_r(auth()->user());
        // die('HELLO, Please wait!!');
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message_text' => 'required|string|max:500',
        ]);

        $receiverId = $validated['receiver_id'];
        $messageText = $validated['message_text'];

        // Check if receiver exists
        $receiver = User::find($receiverId);
        if (!$receiver) {
            return response()->json(['message' => 'Receiver not found'], 404);
        }

        // Insert one-to-one message
        $message = Message::create([
            'sender_id' => $user->id,
            'receiver_id' => $receiverId,
            'message_text' => $messageText,
            'is_group_message' => false,
            'chat_enabled' => true,
        ]);

        // Apply Read to message
        $this->markMessageAsReadInternal($message->id, $user->id);

        return response()->json(['message' => 'Message sent successfully', 'message_id' => $message->id]);
    }

    /**
     *  API Function to get chat
    */
    public function getChatMessages(Request $request, $receiverId)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Fetch all messages between the two users
        $messages = Message::where(function ($query) use ($user, $receiverId) {
                $query->where('sender_id', $user->id)->where('receiver_id', $receiverId);
            })
            ->orWhere(function ($query) use ($user, $receiverId) {
                $query->where('sender_id', $receiverId)->where('receiver_id', $user->id);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        // Get all involved user IDs
        $userIds = $messages->pluck('sender_id')->merge($messages->pluck('receiver_id'))->unique();

        // Fetch all users in one query
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        // Attach user details to messages
        foreach ($messages as $message) {
            if ($message->receiver_id == $user->id) {
                $this->markMessageAsReadInternal($message->id, $user->id);
            }

            $message['sender_name'] = $users[$message->sender_id]->name ?? null;
            $message['receiver_name'] = $users[$message->receiver_id]->name ?? null;
            $message['sender_image'] = $users[$message->sender_id]->profile_image ?? null;
            $message['receiver_image'] = $users[$message->receiver_id]->profile_image ?? null;
            $message->time_ago = Carbon::parse($message->created_at)->diffForHumans();
            $message->read_by = json_decode($message->read_by, true);
        }

        return response()->json(['messages' => $messages]);
    }



}
