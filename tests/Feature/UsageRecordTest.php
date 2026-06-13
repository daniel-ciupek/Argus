<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\AiModel;
use App\Models\Task;
use App\Models\UsageRecord;

it('creates a valid usage_record via factory', function () {
    $record = UsageRecord::factory()->create();

    expect($record->exists)->toBeTrue()
        ->and($record->input_tokens)->toBeGreaterThan(0)
        ->and($record->output_tokens)->toBeGreaterThan(0);
});

it('belongs to an agent, ai_model, and optionally a task', function () {
    $task = Task::factory()->create();

    $record = UsageRecord::factory()->create([
        'agent_id' => $task->agent_id,
        'task_id' => $task->id,
    ]);

    expect($record->agent)->toBeInstanceOf(Agent::class)
        ->and($record->aiModel)->toBeInstanceOf(AiModel::class)
        ->and($record->task)->toBeInstanceOf(Task::class);
});

it('can be created without a task', function () {
    $record = UsageRecord::factory()->create(['task_id' => null]);

    expect($record->task)->toBeNull();
});

it('casts cost to decimal string', function () {
    $record = UsageRecord::factory()->create([
        'input_tokens' => 1000,
        'output_tokens' => 500,
        'cost' => '0.007500',
    ]);

    expect($record->cost)->toBe('0.007500');
});
