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
