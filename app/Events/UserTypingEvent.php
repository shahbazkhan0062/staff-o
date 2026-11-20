<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Lightweight "is typing" / "stopped typing" signal, broadcast the same way
 * MessageEvent is: onto the receiver's own `notifications.{id}` private
 * channel, which they're already authorized for and already subscribed to
 * (see src/hooks/useEcho.js on the frontend). Nothing is persisted — this
 * is fire-and-forget, purely for the UI indicator.
 */
class UserTypingEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $senderId;
    public string $senderName;
    public int $receiverId;
    public bool $isTyping;

    public function __construct(int $senderId, string $senderName, int $receiverId, bool $isTyping)
    {
        $this->senderId = $senderId;
        $this->senderName = $senderName;
        $this->receiverId = $receiverId;
        $this->isTyping = $isTyping;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('notifications.' . $this->receiverId);
    }

    public function broadcastAs(): string
    {
        return 'typing';
    }

    public function broadcastWith()
    {
        return [
            'sender_id' => $this->senderId,
            'sender_name' => $this->senderName,
            'is_typing' => $this->isTyping,
        ];
    }
}
