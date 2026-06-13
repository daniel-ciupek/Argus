<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BudgetPeriod;
use App\Models\Agent;
use App\Models\Budget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agent_id' => Agent::factory(),
            'period' => fake()->randomElement(BudgetPeriod::cases()),
            'limit_amount' => fake()->randomFloat(4, 1, 100),
            'currency' => 'USD',
        ];
    }

    public function monthly(): static
    {
        return $this->state(['period' => BudgetPeriod::Monthly]);
    }

    public function global(): static
    {
        return $this->state(['agent_id' => null]);
    }
}
