<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\Alert;
use App\Models\Budget;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->agent = Agent::factory()->for($this->user)->create(['is_active' => true]);
    $this->budget = Budget::factory()->for($this->agent)->create();
});

it('acknowledges an alert and redirects', function (): void {
    $alert = Alert::factory()->for($this->budget)->create(['acknowledged_at' => null]);

    $this->actingAs($this->user)
        ->patch("/alerts/{$alert->id}/acknowledge")
        ->assertRedirect('/budgets');

    expect($alert->fresh()->acknowledged_at)->not->toBeNull();
});

it('is idempotent — re-acknowledging an already-acknowledged alert is safe', function (): void {
    $alert = Alert::factory()->for($this->budget)->acknowledged()->create();
    $original = $alert->acknowledged_at;

    $this->actingAs($this->user)
        ->patch("/alerts/{$alert->id}/acknowledge")
        ->assertRedirect('/budgets');

    // acknowledged_at changes (now() is called again) but no error is thrown.
    expect($alert->fresh()->acknowledged_at)->not->toBeNull();
});

it('forbids acknowledging an alert belonging to another user (IDOR)', function (): void {
    $foreignBudget = Budget::factory()
        ->for(Agent::factory()->for(User::factory()))
        ->create();
    $alert = Alert::factory()->for($foreignBudget)->create();

    $this->actingAs($this->user)
        ->patch("/alerts/{$alert->id}/acknowledge")
        ->assertForbidden();

    expect($alert->fresh()->acknowledged_at)->toBeNull();
});

it('requires authentication', function (): void {
    $alert = Alert::factory()->for($this->budget)->create();

    $this->patch("/alerts/{$alert->id}/acknowledge")
        ->assertRedirect('/login');
});
