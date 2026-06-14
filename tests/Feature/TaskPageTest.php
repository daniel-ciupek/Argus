<?php

declare(strict_types=1);

use App\Enums\TaskStatus;
use App\Models\Agent;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->agent = Agent::factory()->for($this->user)->create(['is_active' => true]);
});

it('redirects guests to login', function (): void {
    $this->get('/tasks')->assertRedirect('/login');
});

it('renders the Tasks page with props', function (): void {
    $this->actingAs($this->user)
        ->get('/tasks')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tasks')
            ->has('tasks')
            ->has('filters')
            ->has('statuses', 4)
        );
});

it('lists the user\'s tasks and excludes others', function (): void {
    Task::factory()->for($this->agent)->create(['name' => 'Mine']);

    $otherAgent = Agent::factory()->for(User::factory())->create();
    Task::factory()->for($otherAgent)->create(['name' => 'Theirs']);

    $this->actingAs($this->user)
        ->get('/tasks')
        ->assertInertia(fn (Assert $page) => $page
            ->has('tasks', 1)
            ->where('tasks.0.name', 'Mine')
        );
});

it('filters tasks by status', function (): void {
    Task::factory()->for($this->agent)->create(['name' => 'Run', 'status' => TaskStatus::Running]);
    Task::factory()->for($this->agent)->create(['name' => 'Done', 'status' => TaskStatus::Completed]);

    $this->actingAs($this->user)
        ->get('/tasks?status=running')
        ->assertInertia(fn (Assert $page) => $page
            ->has('tasks', 1)
            ->where('tasks.0.name', 'Run')
            ->where('filters.status', 'running')
        );
});

it('ignores an invalid status filter', function (): void {
    Task::factory()->for($this->agent)->create();

    $this->actingAs($this->user)
        ->get('/tasks?status=bogus')
        ->assertInertia(fn (Assert $page) => $page
            ->has('tasks', 1)
            ->where('filters.status', null)
        );
});

it('filters tasks by the user\'s own agent', function (): void {
    $agentB = Agent::factory()->for($this->user)->create();
    Task::factory()->for($this->agent)->create(['name' => 'A task']);
    Task::factory()->for($agentB)->create(['name' => 'B task']);

    $this->actingAs($this->user)
        ->get("/tasks?agent={$agentB->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->has('tasks', 1)
            ->where('tasks.0.name', 'B task')
        );
});

it('forbids filtering by another user\'s agent (IDOR)', function (): void {
    $foreignAgent = Agent::factory()->for(User::factory())->create();

    $this->actingAs($this->user)
        ->get("/tasks?agent={$foreignAgent->id}")
        ->assertForbidden();
});
