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

it('allows any authenticated user to create agents', function (): void {
    $user = User::factory()->create();

    expect($user->can('create', Agent::class))->toBeTrue();
});

it('allows any authenticated user to list their agents', function (): void {
    $user = User::factory()->create();

    expect($user->can('viewAny', Agent::class))->toBeTrue();
});

it('allows the owner to update their agent', function (): void {
    $user = User::factory()->create();
    $agent = Agent::factory()->for($user)->create();

    expect($user->can('update', $agent))->toBeTrue();
});

it('forbids updating another user\'s agent', function (): void {
    $user = User::factory()->create();
    $other = Agent::factory()->for(User::factory())->create();

    expect($user->can('update', $other))->toBeFalse();
});

it('allows the owner to delete their agent', function (): void {
    $user = User::factory()->create();
    $agent = Agent::factory()->for($user)->create();

    expect($user->can('delete', $agent))->toBeTrue();
});

it('forbids deleting another user\'s agent', function (): void {
    $user = User::factory()->create();
    $other = Agent::factory()->for(User::factory())->create();

    expect($user->can('delete', $other))->toBeFalse();
});

it('allows the owner to control their agent', function (): void {
    $user = User::factory()->create();
    $agent = Agent::factory()->for($user)->create();

    expect($user->can('control', $agent))->toBeTrue();
});

it('forbids controlling another user\'s agent', function (): void {
    $user = User::factory()->create();
    $other = Agent::factory()->for(User::factory())->create();

    expect($user->can('control', $other))->toBeFalse();
});
