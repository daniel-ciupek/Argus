<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\AgentCommand;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommandUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly AgentCommand $command,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('agent.'.$this->command->agent_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'CommandUpdated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->command->id,
            'type' => $this->command->type->value,
            'status' => $this->command->status->value,
            'result' => $this->command->result,
            'completed_at' => $this->command->completed_at?->toIso8601String(),
        ];
    }
}
