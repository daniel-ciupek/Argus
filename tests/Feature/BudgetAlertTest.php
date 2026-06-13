<?php

declare(strict_types=1);

use App\Enums\BudgetPeriod;
use App\Models\Agent;
use App\Models\Alert;
use App\Models\Budget;

it('creates a valid budget via factory', function () {
    $budget = Budget::factory()->create();

    expect($budget->exists)->toBeTrue()
        ->and($budget->period)->toBeInstanceOf(BudgetPeriod::class);
});

it('can be created without an agent (global budget)', function () {
    $budget = Budget::factory()->global()->create();

    expect($budget->agent_id)->toBeNull()
        ->and($budget->agent)->toBeNull();
});

it('belongs to an agent when agent_id is set', function () {
    $budget = Budget::factory()->create();

    expect($budget->agent)->toBeInstanceOf(Agent::class);
});

it('creates an alert linked to a budget', function () {
    $budget = Budget::factory()->create();
    $alert = Alert::factory()->for($budget)->create();

    expect($alert->budget->id)->toBe($budget->id)
        ->and($alert->acknowledged_at)->toBeNull();
});

it('marks an alert as acknowledged', function () {
    $alert = Alert::factory()->acknowledged()->create();

    expect($alert->acknowledged_at)->not->toBeNull();
});

it('lists alerts for a budget', function () {
    $budget = Budget::factory()->create();
    Alert::factory()->count(2)->for($budget)->create();

    expect($budget->alerts)->toHaveCount(2);
});
