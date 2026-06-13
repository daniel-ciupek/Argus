<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Alert;
use App\Models\Budget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alert>
 */
class AlertFactory extends Factory
{
    protected $model = Alert::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'budget_id' => Budget::factory(),
            'amount' => fake()->randomFloat(4, 0.5, 200),
            'channel' => 'database',
            'triggered_at' => now()->subMinutes(fake()->numberBetween(1, 1440)),
            'acknowledged_at' => null,
        ];
    }

    public function acknowledged(): static
    {
        return $this->state(['acknowledged_at' => now()]);
    }
}
