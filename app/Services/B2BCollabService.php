<?php

namespace App\Services;

use App\Events\B2BMessageSent;
use App\Events\CollabPostCreated;
use App\Jobs\NotifyNearbyBusinessesJob;
use App\Models\B2BConversation;
use App\Models\B2BMessage;
use App\Models\BusinessAddress;
use App\Models\CollabPost;
use App\Models\CollabPostResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class B2BCollabService
{
    // Create Post
    public function createPost(int $businessId, array $data): CollabPost
    {
        $address = BusinessAddress::where('business_id', $businessId)->first();
        $expiresAt = now()->addDays($data['duration_days'] ?? 7);
        $post = CollabPost::create([
            'business_id'       => $businessId,
            'post_type'         => $data['post_type'],
            'headline'          => $data['headline'],
            'description'       => $data['description'],
            'target_industries' => $data['target_industries'],
            'duration_days'     => $data['duration_days'] ?? 7,
            'expires_at'        => $expiresAt,
            'contact_type'      => $data['contact_type'],
            'contact_name'      => $data['contact_type'] === 'direct' ? ($data['contact_name'] ?? null) : null,
            'contact_email'     => $data['contact_type'] === 'direct' ? ($data['contact_email'] ?? null) : null,
            'contact_phone'     => $data['contact_type'] === 'direct' ? ($data['contact_phone'] ?? null) : null,
            'latitude'          => $address?->latitude,
            'longitude'         => $address?->longitude,
            'is_active'         => true,
        ]);

        // Dispatch event → Queue job for nearby notifications
        CollabPostCreated::dispatch($post);
        NotifyNearbyBusinessesJob::dispatch($post->id)->onQueue('notifications');

        return $post;
    }

    // Get Nearby Posts (for dashboard feed)
    public function getNearbyPosts(int $businessId, float $radiusMiles = 5.0, array $filters = []): LengthAwarePaginator
    {
        $address = BusinessAddress::where('business_id', $businessId)->first();
        $query = CollabPost::with(['business:id,name,profile_image', 'businessProfile:business_id,business_name,logo,industry_type'])
            ->active()
            ->where('business_id', '!=', $businessId); // Don't show own posts

        // Location filter
        if ($address?->latitude && $address?->longitude) {
            $query->withinRadius($address->latitude, $address->longitude, $radiusMiles);
        }

        // Optional: filter by post_type
        if (!empty($filters['post_type'])) {
            $query->where('post_type', $filters['post_type']);
        }

        // Optional: filter by industry
        if (!empty($filters['industry'])) {
            $query->whereJsonContains('target_industries', $filters['industry']);
        }

        return $query->paginate(15);
    }

    // Respond to Post → creates conversation + first message
    public function respondToPost(int $collabPostId, int $responderBusinessId, string $message): array
    {
        return DB::transaction(function () use ($collabPostId, $responderBusinessId, $message) {
            $post = CollabPost::active()->findOrFail($collabPostId);

            if ($post->business_id === $responderBusinessId) {
                throw new \DomainException('Cannot respond to your own post.');
            }

            if ($post->hasRespondedBy($responderBusinessId)) {
                throw new \DomainException('You have already responded to this post.');
            }

            // Log the response
            $response = CollabPostResponse::create([
                'collab_post_id'        => $collabPostId,
                'responder_business_id' => $responderBusinessId,
                'message'               => $message,
                'status'                => 'pending',
            ]);

            // If contact_type is in_app → create a conversation
            $conversation = null;
            if ($post->contact_type === 'in_app') {
                $conversation = B2BConversation::firstOrCreate([
                    'collab_post_id' => $collabPostId,
                    'business_a_id'  => $post->business_id,
                    'business_b_id'  => $responderBusinessId,
                ]);

                $b2bMessage = B2BMessage::create([
                    'conversation_id' => $conversation->id,
                    'sender_id'       => $responderBusinessId,
                    'body'            => $message,
                ]);

                // Broadcast real-time
                broadcast(new B2BMessageSent($b2bMessage))->toOthers();

                // Notify post owner
                $this->notifyPostOwner($post, $responderBusinessId, $message);
            }

            return [
                'response'     => $response,
                'conversation' => $conversation,
                'contact_type' => $post->contact_type,
                'contact_info' => $post->contact_type === 'direct' ? [
                    'name'  => $post->contact_name,
                    'email' => $post->contact_email,
                    'phone' => $post->contact_phone,
                ] : null,
            ];
        });
    }

    // Send Message in an existing conversation
    public function sendMessage(int $conversationId, int $senderBusinessId, string $body): B2BMessage
    {
        $conversation = B2BConversation::findOrFail($conversationId);
        // Security: only parties of this conversation can send
        if (!in_array($senderBusinessId, [$conversation->business_a_id, $conversation->business_b_id])) {
            throw new \DomainException('Unauthorized to send messages in this conversation.');
        }

        $message = B2BMessage::create([
            'conversation_id' => $conversationId,
            'sender_id'       => $senderBusinessId,
            'body'            => $body,
        ]);

        // Real-time broadcast
        broadcast(new B2BMessageSent($message->load('sender')))->toOthers();

        // FCM to other party
        $receiverId = $conversation->business_a_id === $senderBusinessId ? $conversation->business_b_id : $conversation->business_a_id;
        $this->sendFcmToBusiness($receiverId, $message->sender->name ?? 'Business', $body);

        return $message;
    }

    // Mark messages as read
    public function markConversationRead(int $conversationId, int $businessId): void
    {
        B2BMessage::where('conversation_id', $conversationId)->where('sender_id', '!=', $businessId)->whereNull('read_at')->update(['read_at' => now()]);
    }

    // Get conversation list
    public function getConversationList(int $businessId): \Illuminate\Support\Collection
    {
        return B2BConversation::with([
            'latestMessage',
            'businessA:id,name,profile_image',
            'businessB:id,name,profile_image',
            'collabPost:id,headline,post_type,is_active,expires_at',
        ])
        ->where('business_a_id', $businessId)
        ->orWhere('business_b_id', $businessId)
        ->orderByDesc('updated_at')
        ->get()
        ->map(function (B2BConversation $conv) use ($businessId) {
            $otherParty = $conv->otherParty($businessId);
            $unread = B2BMessage::where('conversation_id', $conv->id)->where('sender_id', '!=', $businessId)->whereNull('read_at')->count();
            return [
                'conversation_id'   => $conv->id,
                'title'             => $conv->collabPost?->headline ?? 'B2B Conversation',
                'post_type'         => $conv->collabPost?->post_type,
                'post_is_active'    => $conv->collabPost?->is_active,
                'collab_post_id'    => $conv->collab_post_id,
                'other_business'    => [
                    'id'            => $otherParty->id,
                    'name'          => $otherParty->name,
                    'profile_image' => $otherParty->profile_image,
                ],
                'last_message'      => $conv->latestMessage?->body ? \Str::limit($conv->latestMessage->body, 50) : 'No messages yet',
                'last_message_time' => $conv->latestMessage?->created_at ?->diffForHumans() ?? '',
                'last_message_at'   => $conv->latestMessage?->created_at,
                'unread_count'      => $unread,
            ];
        })
        ->sortByDesc('last_message_at')
        ->values();
    }

    // Private Helpers
    private function notifyPostOwner(CollabPost $post, int $responderBusinessId, string $message): void
    {
        $fcmToken = DB::table('business_profiles')->where('business_id', $post->business_id)->value('fcm_token');
        $responderName = DB::table('users')->where('id', $responderBusinessId)->value('name');
        if ($fcmToken) {
            app(FCMService::class)->sendNotificationToDevice(
                $fcmToken,
                "New response from {$responderName}",
                $message,
                asset('storage/notifications/notification.png')
            );
        }

        createNotification($post->business_id, "New response to your B2B post: {$post->headline}", 'push', null, true);
    }

    private function sendFcmToBusiness(int $businessId, string $senderName, string $message): void
    {
        $fcmToken = DB::table('business_profiles')->where('business_id', $businessId)->value('fcm_token');
        if ($fcmToken) {
            try {
                app(FCMService::class)->sendNotificationToDevice(
                    $fcmToken,
                    $senderName,
                    $message,
                    asset('storage/notifications/notification.png')
                );
            } catch (\Throwable $e) {
                Log::error("B2B FCM failed: " . $e->getMessage());
            }
        }
    }
}