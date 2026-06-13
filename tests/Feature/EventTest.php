<?php

declare(strict_types=1);

use App\Enums\EventType;
use App\Models\Agent;
use App\Models\Event;

it('creates a valid event via factory', function () {
    $event = Event::factory()->create();

    expect($event->exists)->toBeTrue()
        ->and($event->type)->toBeInstanceOf(EventType::class)
        ->and($event->occurred_at)->not->toBeNull();
});

it('belongs to an agent', function () {
    $event = Event::factory()->create();

    expect($event->agent)->toBeInstanceOf(Agent::class);
});

it('stores and retrieves jsonb payload', function () {
    $payload = ['tool' => 'web_search', 'query' => 'laravel reverb', 'results' => 3];

    $event = Event::factory()->withPayload($payload)->create();

    // Reload from DB to verify round-trip through jsonb.
    $fresh = Event::find($event->id);

    expect($fresh->payload)->toBe($payload);
});

it('casts type column to EventType enum', function () {
    $event = Event::factory()->log()->create();

    expect($event->type)->toBe(EventType::Log)
        ->and($event->level)->toBe('info');
});

it('lists events for an agent ordered by occurred_at', function () {
    $agent = Agent::factory()->create();

    Event::factory()->for($agent)->count(3)->create();

    $events = $agent->events()->orderBy('occurred_at')->get();

    expect($events)->toHaveCount(3);
});
