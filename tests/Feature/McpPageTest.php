<?php

declare(strict_types=1);

use App\Enums\McpStatus;
use App\Models\Agent;
use App\Models\McpConnection;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->agent = Agent::factory()->for($this->user)->create(['is_active' => true]);
});

it('redirects guests to login', function (): void {
    $this->get('/mcp')->assertRedirect('/login');
});

it('renders the Mcp page with props', function (): void {
    $this->actingAs($this->user)
        ->get('/mcp')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Mcp')
            ->has('connections')
            ->has('filters')
            ->has('statuses', 3)
        );
});

it('lists the user\'s connections and excludes others', function (): void {
    McpConnection::factory()->for($this->agent)->create(['name' => 'filesystem']);

    $otherAgent = Agent::factory()->for(User::factory())->create();
    McpConnection::factory()->for($otherAgent)->create(['name' => 'github']);

    $this->actingAs($this->user)
        ->get('/mcp')
        ->assertInertia(fn (Assert $page) => $page
            ->has('connections', 1)
            ->where('connections.0.name', 'filesystem')
        );
});

it('filters connections by status', function (): void {
    McpConnection::factory()->for($this->agent)->create(['name' => 'ok', 'status' => McpStatus::Connected]);
    McpConnection::factory()->for($this->agent)->create(['name' => 'down', 'status' => McpStatus::Error]);

    $this->actingAs($this->user)
        ->get('/mcp?status=error')
        ->assertInertia(fn (Assert $page) => $page
            ->has('connections', 1)
            ->where('connections.0.name', 'down')
            ->where('filters.status', 'error')
        );
});

it('ignores an invalid status filter', function (): void {
    McpConnection::factory()->for($this->agent)->create();

    $this->actingAs($this->user)
        ->get('/mcp?status=bogus')
        ->assertInertia(fn (Assert $page) => $page
            ->has('connections', 1)
            ->where('filters.status', null)
        );
});

it('forbids filtering by another user\'s agent (IDOR)', function (): void {
    $foreignAgent = Agent::factory()->for(User::factory())->create();

    $this->actingAs($this->user)
        ->get("/mcp?agent={$foreignAgent->id}")
        ->assertForbidden();
});
