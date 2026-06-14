<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Event;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EventReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Event $event,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('agent.'.$this->event->agent_id),
        ];
    }

    /**
     * Short broadcast name so the frontend can listen with `.EventReceived`
     * instead of the fully-qualified class name.
     */
    public function broadcastAs(): string
    {
        return 'EventReceived';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->event->id,
            'type' => $this->event->type->value,
            'level' => $this->event->level,
            'message' => $this->event->message,
            'payload' => $this->event->payload,
            'occurred_at' => $this->event->occurred_at?->toIso8601String(),
        ];
    }
}
