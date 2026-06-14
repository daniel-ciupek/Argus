<?php

declare(strict_types=1);

use App\Enums\BudgetPeriod;
use App\Models\Agent;
use App\Models\Alert;
use App\Models\Budget;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->agent = Agent::factory()->for($this->user)->create(['is_active' => true]);
});

// ── GET /budgets ────────────────────────────────────────────────────────────

it('redirects guests to login', function (): void {
    $this->get('/budgets')->assertRedirect('/login');
});

it('renders the Budgets page with correct props', function (): void {
    $this->actingAs($this->user)
        ->get('/budgets')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Budgets')
            ->has('budgets')
            ->has('alerts')
            ->has('agents')
            ->has('periods')
        );
});

it('lists only the user\'s own budgets', function (): void {
    Budget::factory()->for($this->agent)->create([
        'period' => BudgetPeriod::Daily,
        'limit_amount' => '10.0000',
    ]);

    $foreign = Budget::factory()
        ->for(Agent::factory()->for(User::factory()))
        ->create();
    Alert::factory()->for($foreign)->create();

    $this->actingAs($this->user)
        ->get('/budgets')
        ->assertInertia(fn (Assert $page) => $page
            ->has('budgets', 1)
            ->has('alerts', 0)
        );
});

it('includes current_spent and agent_name in each budget row', function (): void {
    Budget::factory()->for($this->agent)->create([
        'period' => BudgetPeriod::Monthly,
        'limit_amount' => '50.0000',
        'currency' => 'USD',
    ]);

    $this->actingAs($this->user)
        ->get('/budgets')
        ->assertInertia(fn (Assert $page) => $page
            ->has('budgets.0', fn (Assert $b) => $b
                ->has('current_spent')
                ->has('agent_name')
                ->has('limit_amount')
                ->has('period')
                ->etc()
            )
        );
});

it('includes alerts with acknowledged_at in the response', function (): void {
    $budget = Budget::factory()->for($this->agent)->create();
    Alert::factory()->for($budget)->create(['acknowledged_at' => null]);
    Alert::factory()->for($budget)->acknowledged()->create();

    $this->actingAs($this->user)
        ->get('/budgets')
        ->assertInertia(fn (Assert $page) => $page
            ->has('alerts', 2)
        );
});

// ── POST /budgets ───────────────────────────────────────────────────────────

it('creates a budget and redirects', function (): void {
    $this->actingAs($this->user)
        ->post('/budgets', [
            'agent_id' => $this->agent->id,
            'period' => 'daily',
            'limit_amount' => '25.00',
            'currency' => 'USD',
        ])
        ->assertRedirect('/budgets');

    expect(Budget::where('agent_id', $this->agent->id)->count())->toBe(1);
});

it('validates that agent_id belongs to the authenticated user (IDOR)', function (): void {
    $foreign = Agent::factory()->for(User::factory())->create();

    $this->actingAs($this->user)
        ->post('/budgets', [
            'agent_id' => $foreign->id,
            'period' => 'daily',
            'limit_amount' => '10.00',
        ])
        ->assertSessionHasErrors('agent_id');

    expect(Budget::count())->toBe(0);
});

it('validates that period is a known value', function (): void {
    $this->actingAs($this->user)
        ->post('/budgets', [
            'agent_id' => $this->agent->id,
            'period' => 'weekly',
            'limit_amount' => '10.00',
        ])
        ->assertSessionHasErrors('period');
});

it('validates that limit_amount is positive', function (): void {
    $this->actingAs($this->user)
        ->post('/budgets', [
            'agent_id' => $this->agent->id,
            'period' => 'daily',
            'limit_amount' => '0',
        ])
        ->assertSessionHasErrors('limit_amount');
});

// ── DELETE /budgets/{budget} ────────────────────────────────────────────────

it('deletes a budget and redirects', function (): void {
    $budget = Budget::factory()->for($this->agent)->create();

    $this->actingAs($this->user)
        ->delete("/budgets/{$budget->id}")
        ->assertRedirect('/budgets');

    expect(Budget::find($budget->id))->toBeNull();
});

it('forbids deleting another user\'s budget (IDOR)', function (): void {
    $foreign = Budget::factory()
        ->for(Agent::factory()->for(User::factory()))
        ->create();

    $this->actingAs($this->user)
        ->delete("/budgets/{$foreign->id}")
        ->assertForbidden();

    expect(Budget::find($foreign->id))->not->toBeNull();
});

it('cascades deletion of alerts when a budget is deleted', function (): void {
    $budget = Budget::factory()->for($this->agent)->create();
    Alert::factory()->for($budget)->create();

    $this->actingAs($this->user)
        ->delete("/budgets/{$budget->id}")
        ->assertRedirect();

    expect(Alert::where('budget_id', $budget->id)->count())->toBe(0);
});
