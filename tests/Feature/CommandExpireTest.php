<?php

declare(strict_types=1);

use App\Enums\CommandStatus;
use App\Events\CommandUpdated;
use App\Models\Agent;
use App\Models\AgentCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;

it('expires pending commands past their expires_at', function () {
    Event::fake();

    $agent = Agent::factory()->create();
    $cmd = AgentCommand::factory()->for($agent)->create([
        'status' => CommandStatus::Pending,
        'expires_at' => now()->subMinute(),
    ]);

    $this->artisan('commands:expire')->assertSuccessful();

    expect($cmd->fresh()->status)->toBe(CommandStatus::Expired)
        ->and($cmd->fresh()->completed_at)->not->toBeNull();
});

it('expires dispatched commands past their expires_at', function () {
    Event::fake();

    $agent = Agent::factory()->create();
    $cmd = AgentCommand::factory()->for($agent)->dispatched()->create([
        'expires_at' => now()->subMinute(),
    ]);

    $this->artisan('commands:expire')->assertSuccessful();

    expect($cmd->fresh()->status)->toBe(CommandStatus::Expired);
});

it('does not expire commands with future expires_at', function () {
    Event::fake();

    $agent = Agent::factory()->create();
    $cmd = AgentCommand::factory()->for($agent)->create([
        'status' => CommandStatus::Pending,
        'expires_at' => now()->addMinutes(5),
    ]);

    $this->artisan('commands:expire')->assertSuccessful();

    expect($cmd->fresh()->status)->toBe(CommandStatus::Pending);
});

it('does not touch already terminal commands', function () {
    Event::fake();

    $agent = Agent::factory()->create();
    $succeeded = AgentCommand::factory()->for($agent)->succeeded()->create([
        'expires_at' => now()->subMinute(),
    ]);
    $failed = AgentCommand::factory()->for($agent)->failed()->create([
        'expires_at' => now()->subMinute(),
    ]);

    $this->artisan('commands:expire')->assertSuccessful();

    expect($succeeded->fresh()->status)->toBe(CommandStatus::Succeeded)
        ->and($failed->fresh()->status)->toBe(CommandStatus::Failed);
});

it('broadcasts CommandUpdated for each expired command', function () {
    Event::fake();

    $agent = Agent::factory()->create();
    AgentCommand::factory()->for($agent)->count(3)->create([
        'status' => CommandStatus::Pending,
        'expires_at' => now()->subMinute(),
    ]);

    $this->artisan('commands:expire')->assertSuccessful();

    Event::assertDispatched(CommandUpdated::class, 3);
});

it('broadcasts nothing when no commands expire', function () {
    Event::fake();

    $this->artisan('commands:expire')->assertSuccessful();

    Event::assertNotDispatched(CommandUpdated::class);
});

it('is scheduled to run every minute', function () {
    $events = app(Schedule::class)->events();

    $found = collect($events)->contains(
        fn ($e) => str_contains($e->command ?? '', 'commands:expire'),
    );

    expect($found)->toBeTrue();
});
