<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Agent;
use App\Models\AiModel;
use App\Models\UsageRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsageRecord>
 */
class UsageRecordFactory extends Factory
{
    protected $model = UsageRecord::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $inputTokens = fake()->numberBetween(100, 4000);
        $outputTokens = fake()->numberBetween(50, 2000);

        return [
            'agent_id' => Agent::factory(),
            'ai_model_id' => AiModel::factory(),
            'task_id' => null,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            // Approximate cost — seeder will compute real cost from model prices.
            'cost' => number_format(($inputTokens * 0.0025 + $outputTokens * 0.010) / 1000, 6),
            'currency' => 'USD',
            'occurred_at' => now()->subMinutes(fake()->numberBetween(0, 10080)),
        ];
    }
}
