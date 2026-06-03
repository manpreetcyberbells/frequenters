<?php

use App\Models\Notification;

function createNotification($businessId, $message, $type = 'push', $userId = null, $isBroadcast = false, $campaignId = null, $priority = 'medium', $scheduledTime = null) 
{
    return Notification::create([
        // 'user_id'        => $isBroadcast ? null : $userId, // Null for broadcast
        'business_id'    => $businessId,
        'type'           => $type,
        'status'         => 'sent',
        'message'        => $message,
        'campaign_id'    => $campaignId,
        'retry_count'    => 0,
        'priority'       => $priority,
        'scheduled_time' => $scheduledTime,
        'is_broadcast'   => $isBroadcast,
    ]);
}

function customerNotification($customerId, $message, $type = 'push', $userId = null, $isBroadcast = false, $campaignId = null, $priority = 'medium', $scheduledTime = null)
{
    return Notification::create([
        // 'user_id'        => $isBroadcast ? null : $userId, // Null for broadcast
        'customer_id'    => $customerId,
        'type'           => $type,
        'status'         => 'sent',
        'message'        => $message,
        'campaign_id'    => $campaignId,
        'retry_count'    => 0,
        'priority'       => $priority,
        'scheduled_time' => $scheduledTime,
        'is_broadcast'   => $isBroadcast,
    ]);
}