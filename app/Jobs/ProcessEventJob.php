<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\EventType;
use App\Models\Agent;
use App\Models\Event;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessEventJob implements ShouldQueue
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
        Event::create([
            'agent_id' => $this->agent->id,
            'type' => EventType::from($this->data['type']),
            'level' => $this->data['level'],
            'message' => $this->data['message'],
            'payload' => $this->data['payload'] ?? null,
            'occurred_at' => $this->data['occurred_at'] ?? now(),
        ]);

        $this->agent->update(['last_seen_at' => now()]);
    }
}
