<?php

use Illuminate\Support\Facades\Broadcast;

/**
 * For One-to-One Chat Channels
 * Example: chat.3
 */
Broadcast::channel('chat.{receiverId}', function ($user, $receiverId) {

    // echo $receiverId;
    // Allow if user is the receiver or sender
    return (int) $user->id === (int) $receiverId || $user->can('chat-with', $receiverId);
    // return (int) $user->id === (int) $receiverId;
});

/**
 * For Group Business Messaging
 * Example: group.business.3
 */
Broadcast::channel('group.business.{businessId}', function ($user, $businessId) {
    // Add your logic, e.g., check if the user is a customer of the business
    return true;
});

// B2B private conversation channel
Broadcast::channel('b2b.conversation.{conversationId}', function ($user, $conversationId) {
    $businessId = $user->effectiveBusinessId();
    if (!$businessId) return false;

    return \App\Models\B2BConversation::where('id', $conversationId)
        ->where(fn($q) => $q->where('business_a_id', $businessId)
                            ->orWhere('business_b_id', $businessId))
        ->exists();
});
