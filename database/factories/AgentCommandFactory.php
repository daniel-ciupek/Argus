<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CommandStatus;
use App\Enums\CommandType;
use App\Models\Agent;
use App\Models\AgentCommand;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgentCommand>
 */
class AgentCommandFactory extends Factory
{
    protected $model = AgentCommand::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agent_id' => Agent::factory(),
            'issued_by' => User::factory(),
            'type' => fake()->randomElement(CommandType::cases()),
            'status' => CommandStatus::Pending,
            'payload' => null,
            'result' => null,
            'dispatched_at' => null,
            'completed_at' => null,
            'expires_at' => now()->addMinutes(10),
        ];
    }

    public function dispatched(): static
    {
        return $this->state([
            'status' => CommandStatus::Dispatched,
            'dispatched_at' => now(),
        ]);
    }

    public function succeeded(): static
    {
        return $this->state([
            'status' => CommandStatus::Succeeded,
            'dispatched_at' => now()->subMinute(),
            'completed_at' => now(),
            'result' => ['message' => 'ok'],
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => CommandStatus::Failed,
            'dispatched_at' => now()->subMinute(),
            'completed_at' => now(),
            'result' => ['error' => 'something went wrong'],
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'status' => CommandStatus::Expired,
            'expires_at' => now()->subMinute(),
        ]);
    }

    public function forType(CommandType $type): static
    {
        return $this->state(['type' => $type]);
    }
}
