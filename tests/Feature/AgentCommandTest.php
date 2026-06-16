<?php

declare(strict_types=1);

use App\Enums\CommandStatus;
use App\Enums\CommandType;
use App\Models\Agent;
use App\Models\AgentCommand;
use App\Models\User;

it('creates a valid agent_command via factory', function () {
    $command = AgentCommand::factory()->create();

    expect($command->exists)->toBeTrue()
        ->and($command->type)->toBeInstanceOf(CommandType::class)
        ->and($command->status)->toBe(CommandStatus::Pending)
        ->and($command->expires_at)->not->toBeNull();
});

it('belongs to an agent and an issuer', function () {
    $command = AgentCommand::factory()->create();

    expect($command->agent)->toBeInstanceOf(Agent::class)
        ->and($command->issuer)->toBeInstanceOf(User::class);
});

it('is listed under the owning agent', function () {
    $agent = Agent::factory()->create();
    AgentCommand::factory()->count(3)->for($agent)->create();

    expect($agent->commands)->toHaveCount(3);
});

it('casts type and status to enums', function () {
    $command = AgentCommand::factory()->dispatched()->forType(CommandType::TaskRun)->create();

    expect($command->type)->toBe(CommandType::TaskRun)
        ->and($command->status)->toBe(CommandStatus::Dispatched)
        ->and($command->dispatched_at)->not->toBeNull();
});

it('casts payload and result to arrays', function () {
    $command = AgentCommand::factory()->succeeded()->create([
        'payload' => ['task_id' => 42],
    ]);

    expect($command->payload)->toBe(['task_id' => 42])
        ->and($command->result)->toBeArray()
        ->and($command->completed_at)->not->toBeNull();
});

it('correctly identifies terminal statuses', function () {
    expect(CommandStatus::Succeeded->isTerminal())->toBeTrue()
        ->and(CommandStatus::Failed->isTerminal())->toBeTrue()
        ->and(CommandStatus::Expired->isTerminal())->toBeTrue()
        ->and(CommandStatus::Pending->isTerminal())->toBeFalse()
        ->and(CommandStatus::Dispatched->isTerminal())->toBeFalse()
        ->and(CommandStatus::Acknowledged->isTerminal())->toBeFalse();
});

it('CommandType::targetKind returns correct target', function () {
    expect(CommandType::TaskRun->targetKind())->toBe('task')
        ->and(CommandType::TaskCancel->targetKind())->toBe('task')
        ->and(CommandType::McpEnable->targetKind())->toBe('mcp')
        ->and(CommandType::McpRestart->targetKind())->toBe('mcp')
        ->and(CommandType::AgentInstruct->targetKind())->toBe('agent')
        ->and(CommandType::AgentStop->targetKind())->toBe('agent');
});
