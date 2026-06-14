<?php

declare(strict_types=1);

use App\Enums\BudgetPeriod;
use App\Models\Agent;
use App\Models\Alert;
use App\Models\Budget;
use App\Models\UsageRecord;
use App\Models\User;
use App\Services\BudgetGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->agent = Agent::factory()->for($this->user)->create();
    $this->guard = new BudgetGuard;
});

it('does not create an alert when spending is below the limit', function (): void {
    Budget::factory()->for($this->agent)->create([
        'period' => BudgetPeriod::Daily,
        'limit_amount' => '10.0000',
        'currency' => 'USD',
    ]);

    UsageRecord::factory()->for($this->agent)->create([
        'cost' => '5.000000',
        'currency' => 'USD',
        'occurred_at' => now(),
    ]);

    $this->guard->check($this->agent);

    expect(Alert::count())->toBe(0);
});

it('creates an alert when daily spending reaches the limit', function (): void {
    $budget = Budget::factory()->for($this->agent)->create([
        'period' => BudgetPeriod::Daily,
        'limit_amount' => '10.0000',
        'currency' => 'USD',
    ]);

    UsageRecord::factory()->for($this->agent)->create([
        'cost' => '10.000000',
        'currency' => 'USD',
        'occurred_at' => now(),
    ]);

    $this->guard->check($this->agent);

    expect(Alert::where('budget_id', $budget->id)->count())->toBe(1);
    $alert = Alert::where('budget_id', $budget->id)->first();
    expect($alert->triggered_at)->not->toBeNull()
        ->and($alert->acknowledged_at)->toBeNull()
        ->and($alert->channel)->toBe('database');
});

it('creates an alert when monthly spending exceeds the limit', function (): void {
    $budget = Budget::factory()->for($this->agent)->create([
        'period' => BudgetPeriod::Monthly,
        'limit_amount' => '50.0000',
        'currency' => 'USD',
    ]);

    UsageRecord::factory()->for($this->agent)->create([
        'cost' => '75.000000',
        'currency' => 'USD',
        'occurred_at' => now()->startOfMonth()->addDays(3),
    ]);

    $this->guard->check($this->agent);

    expect(Alert::where('budget_id', $budget->id)->count())->toBe(1);
});

it('does not create a duplicate alert when one already exists for the period', function (): void {
    $budget = Budget::factory()->for($this->agent)->create([
        'period' => BudgetPeriod::Daily,
        'limit_amount' => '10.0000',
        'currency' => 'USD',
    ]);

    UsageRecord::factory()->for($this->agent)->create([
        'cost' => '15.000000',
        'currency' => 'USD',
        'occurred_at' => now(),
    ]);

    // First check creates the alert.
    $this->guard->check($this->agent);
    // Second check (e.g. after another usage event) must not duplicate it.
    $this->guard->check($this->agent);

    expect(Alert::where('budget_id', $budget->id)->count())->toBe(1);
});

it('ignores usage from a previous period when checking daily budget', function (): void {
    Budget::factory()->for($this->agent)->create([
        'period' => BudgetPeriod::Daily,
        'limit_amount' => '10.0000',
        'currency' => 'USD',
    ]);

    // Cost recorded yesterday — outside today's window.
    UsageRecord::factory()->for($this->agent)->create([
        'cost' => '20.000000',
        'currency' => 'USD',
        'occurred_at' => now()->subDay(),
    ]);

    $this->guard->check($this->agent);

    expect(Alert::count())->toBe(0);
});

it('does not check budgets belonging to another agent', function (): void {
    $otherAgent = Agent::factory()->for($this->user)->create();

    Budget::factory()->for($otherAgent)->create([
        'period' => BudgetPeriod::Daily,
        'limit_amount' => '1.0000',
        'currency' => 'USD',
    ]);

    // Spending belongs to $this->agent, not $otherAgent.
    UsageRecord::factory()->for($this->agent)->create([
        'cost' => '5.000000',
        'currency' => 'USD',
        'occurred_at' => now(),
    ]);

    // Checking $this->agent — should not trigger the other agent's budget.
    $this->guard->check($this->agent);

    expect(Alert::count())->toBe(0);
});

it('creates separate alerts for daily and monthly budgets when both are exceeded', function (): void {
    $daily = Budget::factory()->for($this->agent)->create([
        'period' => BudgetPeriod::Daily,
        'limit_amount' => '5.0000',
        'currency' => 'USD',
    ]);

    $monthly = Budget::factory()->for($this->agent)->create([
        'period' => BudgetPeriod::Monthly,
        'limit_amount' => '20.0000',
        'currency' => 'USD',
    ]);

    UsageRecord::factory()->for($this->agent)->create([
        'cost' => '25.000000',
        'currency' => 'USD',
        'occurred_at' => now(),
    ]);

    $this->guard->check($this->agent);

    expect(Alert::where('budget_id', $daily->id)->count())->toBe(1)
        ->and(Alert::where('budget_id', $monthly->id)->count())->toBe(1);
});
