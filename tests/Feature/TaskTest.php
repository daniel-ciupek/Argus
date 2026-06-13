<?php

declare(strict_types=1);

use App\Enums\TaskStatus;
use App\Models\Agent;
use App\Models\Task;

it('creates a valid task via factory', function () {
    $task = Task::factory()->create();

    expect($task->exists)->toBeTrue()
        ->and($task->status)->toBeInstanceOf(TaskStatus::class);
});

it('belongs to an agent', function () {
    $task = Task::factory()->create();

    expect($task->agent)->toBeInstanceOf(Agent::class);
});

it('casts status to TaskStatus enum', function () {
    $task = Task::factory()->completed()->create();

    expect($task->status)->toBe(TaskStatus::Completed)
        ->and($task->last_run_at)->not->toBeNull()
        ->and($task->next_run_at)->not->toBeNull();
});

it('lists tasks for an agent', function () {
    $agent = Agent::factory()->create();
    Task::factory()->count(3)->for($agent)->create();

    expect($agent->tasks)->toHaveCount(3);
});
