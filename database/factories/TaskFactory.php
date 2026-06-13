<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\Agent;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lastRun = fake()->optional()->dateTimeBetween('-7 days', 'now');

        return [
            'agent_id' => Agent::factory(),
            'name' => fake()->words(3, true),
            'schedule' => fake()->optional()->randomElement(['0 * * * *', '*/5 * * * *', '0 0 * * *']),
            'status' => fake()->randomElement(TaskStatus::cases()),
            'last_run_at' => $lastRun,
            'next_run_at' => $lastRun ? fake()->dateTimeBetween('now', '+1 day') : null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => TaskStatus::Pending, 'last_run_at' => null, 'next_run_at' => null]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => TaskStatus::Completed,
            'last_run_at' => now()->subMinutes(5),
            'next_run_at' => now()->addHour(),
        ]);
    }
}
