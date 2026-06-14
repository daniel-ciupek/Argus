<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\User;

it('allows the owner to view their agent', function (): void {
    $user = User::factory()->create();
    $agent = Agent::factory()->for($user)->create();

    expect($user->can('view', $agent))->toBeTrue();
});

it('forbids viewing another user\'s agent', function (): void {
    $user = User::factory()->create();
    $otherAgent = Agent::factory()->for(User::factory())->create();

    expect($user->can('view', $otherAgent))->toBeFalse();
});
