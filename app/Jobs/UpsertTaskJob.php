<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\TaskStatusUpdated;
use App\Models\Agent;
use App\Models\Task;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Creates or updates a scheduled task by name, then broadcasts its current
 * state. Tasks are stateful, so the agent reports the latest snapshot and we
 * upsert on (agent_id, name) rather than appending.
 */
class UpsertTaskJob implements ShouldQueue
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
        $task = Task::updateOrCreate(
            ['agent_id' => $this->agent->id, 'name' => $this->data['name']],
            [
                'status' => $this->data['status'],
                'schedule' => $this->data['schedule'] ?? null,
                'last_run_at' => $this->data['last_run_at'] ?? null,
                'next_run_at' => $this->data['next_run_at'] ?? null,
            ],
        );

        $this->agent->update(['last_seen_at' => now()]);

        broadcast(new TaskStatusUpdated($task));
    }
}
