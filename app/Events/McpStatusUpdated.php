<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\McpConnection;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class McpStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly McpConnection $mcpConnection,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('agent.'.$this->mcpConnection->agent_id),
        ];
    }

    /**
     * Short broadcast name so the frontend can listen with `.McpStatusUpdated`
     * instead of the fully-qualified class name.
     */
    public function broadcastAs(): string
    {
        return 'McpStatusUpdated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->mcpConnection->id,
            'agent_id' => $this->mcpConnection->agent_id,
            'name' => $this->mcpConnection->name,
            'status' => $this->mcpConnection->status->value,
            'meta' => $this->mcpConnection->meta,
        ];
    }
}
