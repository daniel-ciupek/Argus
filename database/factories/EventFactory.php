<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EventType;
use App\Models\Agent;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agent_id' => Agent::factory(),
            'type' => fake()->randomElement(EventType::cases()),
            'level' => fake()->randomElement(['info', 'warning', 'error', null]),
            'message' => fake()->sentence(),
            'payload' => null,
            'occurred_at' => now()->subMinutes(fake()->numberBetween(0, 10080)),
        ];
    }

    public function log(): static
    {
        return $this->state(['type' => EventType::Log, 'level' => 'info']);
    }

    public function error(): static
    {
        return $this->state(['type' => EventType::Error, 'level' => 'error']);
    }

    public function withPayload(mixed $payload): static
    {
        return $this->state(['payload' => $payload]);
    }
}
