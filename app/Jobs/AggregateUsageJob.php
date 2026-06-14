<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Agent;
use App\Models\AiModel;
use App\Models\UsageRecord;
use App\Services\UsageCalculator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Turns a reported token-usage payload into a stored UsageRecord, pricing the
 * call from the catalogue via UsageCalculator. Dispatched by the ingestion
 * endpoint after validation; all domain logic lives here, not in the controller.
 */
class AggregateUsageJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly Agent $agent,
        public readonly array $data,
    ) {}

    public function handle(UsageCalculator $calculator): void
    {
        $model = AiModel::query()
            ->where('provider', $this->data['provider'])
            ->where('name', $this->data['model'])
            ->firstOrFail();

        $inputTokens = (int) $this->data['input_tokens'];
        $outputTokens = (int) $this->data['output_tokens'];

        UsageRecord::create([
            'agent_id' => $this->agent->id,
            'ai_model_id' => $model->id,
            'task_id' => $this->data['task_id'] ?? null,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'cost' => $calculator->cost($model, $inputTokens, $outputTokens),
            'currency' => $model->currency,
            'occurred_at' => $this->data['occurred_at'] ?? now(),
        ]);

        $this->agent->update(['last_seen_at' => now()]);
    }
}
