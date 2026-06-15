<?php

declare(strict_types=1);

use App\Enums\EventType;
use App\Enums\McpStatus;
use App\Enums\TaskStatus;
use App\Models\Agent;
use App\Models\AiModel;
use App\Models\Event;
use App\Models\McpConnection;
use App\Models\Task;
use App\Models\UsageRecord;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->agent = Agent::factory()->for($this->user)->create(['is_active' => true]);
});

it('redirects guests to login', function (): void {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('renders the Dashboard with stats and recent props', function (): void {
    $this->actingAs($this->user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('periodDays', 30)
            ->has('stats')
            ->has('recent')
        );
});

it('aggregates KPIs scoped to the user\'s agents', function (): void {
    $model = AiModel::factory()->create();
    UsageRecord::factory()->for($this->agent)->for($model, 'aiModel')
        ->create(['cost' => '0.004000', 'occurred_at' => now()]);
    UsageRecord::factory()->for($this->agent)->for($model, 'aiModel')
        ->create(['cost' => '0.001000', 'occurred_at' => now()]);

    Event::factory()->for($this->agent)->count(3)->create(['type' => EventType::Log, 'occurred_at' => now()]);
    Event::factory()->for($this->agent)->error()->create(['occurred_at' => now()]);

    McpConnection::factory()->for($this->agent)->create(['status' => McpStatus::Connected]);
    McpConnection::factory()->for($this->agent)->create(['status' => McpStatus::Error]);

    Task::factory()->for($this->agent)->create(['status' => TaskStatus::Failed]);
    Task::factory()->for($this->agent)->create(['status' => TaskStatus::Completed]);

    $this->actingAs($this->user)
        ->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.cost', '0.005000')
            ->where('stats.events', 4)
            ->where('stats.errors', 1)
            ->where('stats.agents.active', 1)
            ->where('stats.agents.total', 1)
            ->where('stats.mcp.connected', 1)
            ->where('stats.mcp.total', 2)
            ->where('stats.tasks.failed', 1)
            ->where('stats.tasks.total', 2)
        );
});

it('excludes data from other users', function (): void {
    $otherUser = User::factory()->create();
    $otherAgent = Agent::factory()->for($otherUser)->create();
    Event::factory()->for($otherAgent)->count(5)->create(['occurred_at' => now()]);

    $this->actingAs($this->user)
        ->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.events', 0)
            ->where('stats.agents.total', 1)
            ->has('recent', 0)
        );
});

it('excludes cost and events older than the reporting window', function (): void {
    $model = AiModel::factory()->create();
    UsageRecord::factory()->for($this->agent)->for($model, 'aiModel')
        ->create(['cost' => '9.000000', 'occurred_at' => now()->subDays(40)]);
    Event::factory()->for($this->agent)->create(['occurred_at' => now()->subDays(40)]);

    $this->actingAs($this->user)
        ->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.cost', '0.000000')
            ->where('stats.events', 0)
        );
});

it('returns recent events newest first with agent name', function (): void {
    Event::factory()->for($this->agent)->create([
        'message' => 'older',
        'occurred_at' => now()->subMinutes(10),
    ]);
    Event::factory()->for($this->agent)->create([
        'message' => 'newest',
        'occurred_at' => now(),
    ]);

    $this->actingAs($this->user)
        ->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->has('recent', 2)
            ->where('recent.0.message', 'newest')
            ->where('recent.0.agent_name', $this->agent->name)
            ->where('recent.1.message', 'older')
        );
});
