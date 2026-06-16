<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\User;

it('redirects guests to login', function () {
    $this->get(route('agents'))->assertRedirect(route('login'));
});

it('renders the Agents page with agents list', function () {
    $user = User::factory()->create();
    Agent::factory()->count(2)->for($user)->create();

    $this->actingAs($user)
        ->get(route('agents'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Agents')
            ->has('agents', 2)
        );
});

it('excludes other users agents from the list', function () {
    $user = User::factory()->create();
    Agent::factory()->for(User::factory())->create();

    $this->actingAs($user)
        ->get(route('agents'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('agents', 0));
});

it('creates an agent and redirects with a one-time secret', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('agents.store'), ['name' => 'My Agent']);

    $response->assertRedirect(route('agents'));

    $agent = Agent::where('user_id', $user->id)->first();
    expect($agent)->not->toBeNull()
        ->and($agent->name)->toBe('My Agent')
        ->and($agent->is_active)->toBeTrue();
});

it('generates a unique slug on create', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('agents.store'), ['name' => 'Test Agent']);
    $this->actingAs($user)->post(route('agents.store'), ['name' => 'Test Agent']);

    $slugs = Agent::where('user_id', $user->id)->pluck('slug');
    expect($slugs)->toHaveCount(2)
        ->and($slugs[0])->not->toBe($slugs[1]);
});

it('updates an agent name', function () {
    $user = User::factory()->create();
    $agent = Agent::factory()->for($user)->create(['name' => 'Old Name']);

    $this->actingAs($user)
        ->patch(route('agents.update', $agent), ['name' => 'New Name'])
        ->assertRedirect(route('agents'));

    expect($agent->fresh()->name)->toBe('New Name');
});

it('can deactivate and reactivate an agent', function () {
    $user = User::factory()->create();
    $agent = Agent::factory()->for($user)->create(['is_active' => true]);

    $this->actingAs($user)
        ->patch(route('agents.update', $agent), ['name' => $agent->name, 'is_active' => false]);

    expect($agent->fresh()->is_active)->toBeFalse();

    $this->actingAs($user)
        ->patch(route('agents.update', $agent), ['name' => $agent->name, 'is_active' => true]);

    expect($agent->fresh()->is_active)->toBeTrue();
});

it('forbids updating another users agent (IDOR)', function () {
    $user = User::factory()->create();
    $other = Agent::factory()->for(User::factory())->create();

    $this->actingAs($user)
        ->patch(route('agents.update', $other), ['name' => 'Hacked'])
        ->assertForbidden();
});

it('deletes an agent and redirects', function () {
    $user = User::factory()->create();
    $agent = Agent::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('agents.destroy', $agent))
        ->assertRedirect(route('agents'));

    expect(Agent::find($agent->id))->toBeNull();
});

it('forbids deleting another users agent (IDOR)', function () {
    $user = User::factory()->create();
    $other = Agent::factory()->for(User::factory())->create();

    $this->actingAs($user)
        ->delete(route('agents.destroy', $other))
        ->assertForbidden();
});

it('rotates the ingest secret and shows a new one-time secret', function () {
    $user = User::factory()->create();
    $agent = Agent::factory()->for($user)->create();
    $oldSecret = $agent->ingest_secret;

    $this->actingAs($user)
        ->post(route('agents.rotate-secret', $agent))
        ->assertRedirect(route('agents'));

    expect($agent->fresh()->ingest_secret)->not->toBe($oldSecret);
});

it('forbids rotating secret on another users agent (IDOR)', function () {
    $user = User::factory()->create();
    $other = Agent::factory()->for(User::factory())->create();

    $this->actingAs($user)
        ->post(route('agents.rotate-secret', $other))
        ->assertForbidden();
});

it('old secret no longer passes HMAC after rotation', function () {
    $user = User::factory()->create();
    $agent = Agent::factory()->for($user)->create();
    $oldSecret = $agent->ingest_secret;

    $this->actingAs($user)->post(route('agents.rotate-secret', $agent));

    $body = json_encode(['type' => 'log', 'level' => 'info', 'message' => 'test', 'occurred_at' => now()->toIso8601String()]);
    assert($body !== false);
    $timestamp = (string) now()->timestamp;
    $sig = 'sha256='.hash_hmac('sha256', $body.$timestamp, $oldSecret);

    $this->postJson(
        "/api/ingest/{$agent->slug}/events",
        json_decode($body, true),
        ['X-Signature' => $sig, 'X-Timestamp' => $timestamp]
    )->assertUnauthorized();
});
