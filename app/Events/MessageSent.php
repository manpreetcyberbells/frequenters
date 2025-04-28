<?php

namespace App\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        // return ['chat.' . $this->message->receiver_id];
        return new Channel('chat.' . $this->message->receiver_id);
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }

    public function broadcastWith()
    {
        \Log::info('Broadcast payload:', [
            'sender_id' => $this->message->sender_id,
            'receiver_id' => $this->message->receiver_id,
            'text' => $this->message->text,
            'timestamp' => $this->message->timestamp,
        ]);

        return [
            'sender_id' => $this->message->sender_id,
            'receiver_id' => $this->message->receiver_id,
            'text' => $this->message->text,
            'timestamp' => $this->message->timestamp,
        ];
    }
}

