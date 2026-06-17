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
            'category_ids'      => $data['category_ids'],
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

        // Optional: filter by category (single ID match inside JSON array)
        if (!empty($filters['category_id'])) {
            $query->whereJsonContains('category_ids', (int) $filters['category_id']);
        }

        $paginator = $query->paginate(15);

        $postIds = collect($paginator->items())->pluck('id')->all();

        // 1 query: which posts has the current business already responded to?
        $respondedPostIds = CollabPostResponse::whereIn('collab_post_id', $postIds)
            ->where('responder_business_id', $businessId)
            ->pluck('collab_post_id')
            ->flip()
            ->all();

        // 1 query: resolve all category IDs on this page to names
        $allCategoryIds = collect($paginator->items())
            ->flatMap(fn($post) => $post->category_ids ?? [])
            ->filter(fn($id) => is_int($id) || ctype_digit((string) $id))
            ->unique()
            ->values()
            ->all();

        $categoryMap = \App\Models\Admin\Category::whereIn('id', $allCategoryIds)
            ->pluck('name', 'id');

        // 1 query: chat_enabled check (JOIN)
        // chat_enabled = true jab Business A (post owner) ne is post ki conversation me reply kiya ho
        $chatEnabledPostIds = DB::table('b2b_messages')
            ->join('b2b_conversations', 'b2b_conversations.id', '=', 'b2b_messages.conversation_id')
            ->whereIn('b2b_conversations.collab_post_id', $postIds)
            ->where('b2b_conversations.business_b_id', $businessId)
            ->whereColumn('b2b_messages.sender_id', 'b2b_conversations.business_a_id')
            ->distinct()
            ->pluck('b2b_conversations.collab_post_id')
            ->flip()
            ->all();

        // Attach has_responded + chat_enabled + category_names to every post
        $paginator->through(function ($post) use ($categoryMap, $respondedPostIds, $chatEnabledPostIds) {
            $post->has_responded  = array_key_exists($post->id, $respondedPostIds);
            $post->chat_enabled   = array_key_exists($post->id, $chatEnabledPostIds);
            $post->category_names = collect($post->category_ids ?? [])
                ->map(fn($id) => $categoryMap->get((int) $id))
                ->filter()
                ->values()
                ->all();
            return $post;
        });

        return $paginator;
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
        $conversations = B2BConversation::with([
            'latestMessage',
            'businessA:id,name,profile_image',
            'businessB:id,name,profile_image',
            'collabPost:id,headline,post_type,is_active,expires_at,business_id',
        ])
        ->where('business_a_id', $businessId)
        ->orWhere('business_b_id', $businessId)
        ->orderByDesc('updated_at')
        ->get()
        ->filter(function (B2BConversation $conv) use ($businessId) {
            $isBusinessA = $conv->business_a_id === $businessId;
            if ($isBusinessA) {
                return B2BMessage::where('conversation_id', $conv->id)->exists();
            }

            return B2BMessage::where('conversation_id', $conv->id)->where('sender_id', $conv->business_a_id)->exists();
        });
        $convIds = $conversations->pluck('id')->all();

        // 1 query: unread counts per conversation
        $unreadCounts = B2BMessage::whereIn('conversation_id', $convIds)
            ->where('sender_id', '!=', $businessId)
            ->whereNull('read_at')
            ->selectRaw('conversation_id, COUNT(*) as cnt')
            ->groupBy('conversation_id')
            ->pluck('cnt', 'conversation_id');

        // 1 query: chat_enabled check (JOIN - reliable on all MySQL versions)
        // chat_enabled = true jab Business A (post creator) ne reply kiya ho
        $chatEnabledConvIds = DB::table('b2b_messages')
            ->join('b2b_conversations', 'b2b_conversations.id', '=', 'b2b_messages.conversation_id')
            ->whereIn('b2b_messages.conversation_id', $convIds)
            ->whereColumn('b2b_messages.sender_id', 'b2b_conversations.business_a_id')
            ->distinct()
            ->pluck('b2b_messages.conversation_id')
            ->flip()
            ->all();

        return $conversations
        ->map(function (B2BConversation $conv) use ($businessId, $unreadCounts, $chatEnabledConvIds) {
            $otherParty = $conv->otherParty($businessId);
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
                'last_message_time' => $conv->latestMessage?->created_at?->diffForHumans() ?? '',
                'last_message_at'   => $conv->latestMessage?->created_at,
                'unread_count'      => (int) ($unreadCounts[$conv->id] ?? 0),
                'chat_enabled'      => array_key_exists($conv->id, $chatEnabledConvIds),
            ];
        })
        ->sortByDesc('last_message_at')
        ->values();
    }

    // GET my-posts with responders detail
    public function getMyPosts(int $businessId): \Illuminate\Pagination\LengthAwarePaginator
    {
        $posts = CollabPost::where('business_id', $businessId)
            ->withCount('responses')
            ->with([
                'responses.responderBusiness:id,name,profile_image',
                'responses.responderProfile:business_id,business_name,logo',
            ])
            ->orderByDesc('created_at')
            ->paginate(15);

        $postIds = collect($posts->items())->pluck('id')->all();

        // 1 query: conversations for these posts
        $conversations = B2BConversation::whereIn('collab_post_id', $postIds)
            ->get(['id', 'collab_post_id', 'business_b_id']);

        // Lookup map: "post_id:responder_business_id" => { conversation_id, name }
        $convMap = $conversations->keyBy(
            fn($c) => $c->collab_post_id . ':' . $c->business_b_id
        );

        // 1 query: resolve category_ids => names for all posts on this page
        $allCategoryIds = collect($posts->items())
            ->flatMap(fn($post) => $post->category_ids ?? [])
            ->filter(fn($id) => is_int($id) || ctype_digit((string) $id))
            ->unique()
            ->values()
            ->all();

        $categoryMap = \App\Models\Admin\Category::whereIn('id', $allCategoryIds)
            ->pluck('name', 'id');

        $posts->through(function ($post) use ($convMap, $categoryMap) {
            // category_names
            $post->category_names = collect($post->category_ids ?? [])
                ->map(fn($id) => $categoryMap->get((int) $id))
                ->filter()
                ->values()
                ->all();

            // responders with conversation_id + conversation_with name
            $post->responders = $post->responses->map(function ($response) use ($post, $convMap) {
                $convKey  = $post->id . ':' . $response->responder_business_id;
                $conv     = $convMap->get($convKey);
                return [
                    'responder' => [
                        'id'            => $response->responderBusiness->id,
                        'name'          => $response->responderBusiness->name,
                        'profile_image' => $response->responderBusiness->profile_image,
                        'business_name' => $response->responderProfile?->business_name,
                        'logo'          => $response->responderProfile?->logo,
                    ],
                    'message'           => $response->message,
                    'status'            => $response->status,
                    'responded_at'      => $response->created_at,
                    'conversation_id'   => $conv?->id,
                    'conversation_title' => $post->headline,
                ];
            })->values()->all();

            unset($post->responses);
            return $post;
        });

        return $posts;
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