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

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Unique name per factory call — the (provider, name) unique index
        // is satisfied as long as names don't collide, so we make them
        // random rather than cycling through a fixed list.
        return [
            'provider' => fake()->randomElement(['openai', 'anthropic', 'google', 'mistral']),
            'name' => 'model-'.fake()->unique()->lexify('????????'),
            'input_price_per_1k' => '0.002500',
            'output_price_per_1k' => '0.010000',
            'currency' => 'USD',
        ];
    }

    public function openai(): static
    {
        return $this->state([
            'provider' => 'openai',
            'name' => 'gpt-4o-'.fake()->unique()->lexify('????'),
        ]);
    }

    public function anthropic(): static
    {
        return $this->state([
            'provider' => 'anthropic',
            'name' => 'claude-'.fake()->unique()->lexify('????'),
        ]);
    }
}
