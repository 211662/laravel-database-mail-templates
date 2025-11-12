<?php

namespace App\Events;

use App\Models\InboxMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewEmailReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(InboxMessage $message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return new Channel('temp-email.' . $this->message->tempEmail->email);
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs()
    {
        return 'new.email';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith()
    {
        return [
            'message_id' => $this->message->id,
            'from' => $this->message->sender,
            'subject' => $this->message->subject,
            'has_2fa_code' => !empty($this->message->two_fa_code),
            'two_fa_code' => $this->message->two_fa_code,
            'received_at' => $this->message->received_at->toIso8601String(),
        ];
    }
}
