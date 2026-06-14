<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\McpStatusUpdated;
use App\Models\Agent;
use App\Models\McpConnection;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Creates or updates an MCP connection by name, then broadcasts its current
 * state. Connections are stateful, so the agent reports the latest snapshot and
 * we upsert on (agent_id, name) rather than appending.
 */
class UpsertMcpConnectionJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly Agent $agent,
        public readonly array $data,
    ) {}

    public function handle(): void
    {
        $connection = McpConnection::updateOrCreate(
            ['agent_id' => $this->agent->id, 'name' => $this->data['name']],
            [
                'status' => $this->data['status'],
                'meta' => $this->data['meta'] ?? null,
            ],
        );

        $this->agent->update(['last_seen_at' => now()]);

        broadcast(new McpStatusUpdated($connection));
    }
}
