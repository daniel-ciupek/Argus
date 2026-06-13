<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AiModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiModel>
 */
class AiModelFactory extends Factory
{
    protected $model = AiModel::class;

    private static int $counter = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $providers = [
            ['provider' => 'openai', 'name' => 'gpt-4o', 'input' => '0.002500', 'output' => '0.010000'],
            ['provider' => 'openai', 'name' => 'gpt-4o-mini', 'input' => '0.000150', 'output' => '0.000600'],
            ['provider' => 'anthropic', 'name' => 'claude-opus-4-8', 'input' => '0.015000', 'output' => '0.075000'],
            ['provider' => 'anthropic', 'name' => 'claude-sonnet-4-6', 'input' => '0.003000', 'output' => '0.015000'],
        ];

        $model = $providers[self::$counter % count($providers)];
        self::$counter++;

        return [
            'provider' => $model['provider'],
            'name' => $model['name'],
            'input_price_per_1k' => $model['input'],
            'output_price_per_1k' => $model['output'],
            'currency' => 'USD',
        ];
    }

    public function openai(): static
    {
        return $this->state(['provider' => 'openai', 'name' => 'gpt-4o']);
    }

    public function anthropic(): static
    {
        return $this->state(['provider' => 'anthropic', 'name' => 'claude-opus-4-8']);
    }
}
