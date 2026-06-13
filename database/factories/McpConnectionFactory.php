<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\McpStatus;
use App\Models\Agent;
use App\Models\McpConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<McpConnection>
 */
class McpConnectionFactory extends Factory
{
    protected $model = McpConnection::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agent_id' => Agent::factory(),
            'name' => fake()->randomElement(['filesystem', 'brave-search', 'github', 'postgres', 'slack']),
            'status' => fake()->randomElement(McpStatus::cases()),
            'meta' => null,
        ];
    }

    public function connected(): static
    {
        return $this->state(['status' => McpStatus::Connected]);
    }

    public function withMeta(mixed $meta): static
    {
        return $this->state(['meta' => $meta]);
    }
}
