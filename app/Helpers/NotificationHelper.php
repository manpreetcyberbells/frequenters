<?php

use App\Models\Notification;

function createNotification($businessId, $message, $type = 'push', $userId = null, $isBroadcast = false, $campaignId = null, $priority = 'medium', $scheduledTime = null) 
{
    return Notification::create([
        'user_id'        => $isBroadcast ? null : $userId, // Null for broadcast
        'business_id'    => $businessId,
        'type'           => $type,
        'status'         => 'pending',
        'message'        => $message,
        'campaign_id'    => $campaignId,
        'retry_count'    => 0,
        'priority'       => $priority,
        'scheduled_time' => $scheduledTime,
        'is_broadcast'   => $isBroadcast,
    ]);
}

