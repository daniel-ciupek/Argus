<?php

declare(strict_types=1);

use App\Enums\CommandStatus;
use App\Enums\CommandType;
use App\Models\Agent;
use App\Models\AgentCommand;
use App\Models\McpConnection;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->agent = Agent::factory()->for($this->user)->create();
});

// ── Auth & authorization ──────────────────────────────────────────────────────

it('redirects guests to login on store', function () {
    $this->post(route('commands.store', $this->agent))
        ->assertRedirect(route('login'));
});

it('returns 403 when non-owner tries to issue a command', function () {
    $other = User::factory()->create();
    $task = Task::factory()->for($this->agent)->create();

    $this->actingAs($other)
        ->post(route('commands.store', $this->agent), [
            'type' => CommandType::TaskRun->value,
            'payload' => ['task_id' => $task->id],
        ])
        ->assertForbidden();
});

it('returns 403 when non-owner accesses commands index', function () {
    $other = User::factory()->create();

    $this->actingAs($other)
        ->getJson(route('commands.index', $this->agent))
        ->assertForbidden();
});

// ── Successful dispatch ───────────────────────────────────────────────────────

it('owner can issue a task.run command', function () {
    Event::fake();
    $task = Task::factory()->for($this->agent)->create();

    $this->actingAs($this->user)
        ->post(route('commands.store', $this->agent), [
            'type' => CommandType::TaskRun->value,
            'payload' => ['task_id' => $task->id],
        ])
        ->assertRedirect();

    $cmd = AgentCommand::where('agent_id', $this->agent->id)->first();
    expect($cmd)->not->toBeNull()
        ->and($cmd->type)->toBe(CommandType::TaskRun)
        ->and($cmd->status)->toBe(CommandStatus::Pending)
        ->and($cmd->issued_by)->toBe($this->user->id)
        ->and($cmd->payload['task_id'])->toBe($task->id)
        ->and($cmd->expires_at)->not->toBeNull();
});

it('owner can issue an mcp.enable command', function () {
    Event::fake();
    McpConnection::factory()->for($this->agent)->create(['name' => 'my-mcp']);

    $this->actingAs($this->user)
        ->post(route('commands.store', $this->agent), [
            'type' => CommandType::McpEnable->value,
            'payload' => ['mcp_name' => 'my-mcp'],
        ])
        ->assertRedirect();

    $cmd = AgentCommand::where('agent_id', $this->agent->id)->first();
    expect($cmd)->not->toBeNull()
        ->and($cmd->type)->toBe(CommandType::McpEnable)
        ->and($cmd->payload['mcp_name'])->toBe('my-mcp');
});

it('owner can issue an agent.pause command without payload', function () {
    Event::fake();

    $this->actingAs($this->user)
        ->post(route('commands.store', $this->agent), [
            'type' => CommandType::AgentPause->value,
        ])
        ->assertRedirect();

    $cmd = AgentCommand::where('agent_id', $this->agent->id)->first();
    expect($cmd)->not->toBeNull()
        ->and($cmd->type)->toBe(CommandType::AgentPause)
        ->and($cmd->status)->toBe(CommandStatus::Pending);
});

it('owner can issue an agent.instruct command with text', function () {
    Event::fake();

    $this->actingAs($this->user)
        ->post(route('commands.store', $this->agent), [
            'type' => CommandType::AgentInstruct->value,
            'payload' => ['text' => 'Stop what you are doing.'],
        ])
        ->assertRedirect();

    $cmd = AgentCommand::where('agent_id', $this->agent->id)->first();
    expect($cmd)->not->toBeNull()
        ->and($cmd->type)->toBe(CommandType::AgentInstruct)
        ->and($cmd->payload['text'])->toBe('Stop what you are doing.');
});

// ── Validation ────────────────────────────────────────────────────────────────

it('rejects unknown command type', function () {
    $this->actingAs($this->user)
        ->post(route('commands.store', $this->agent), ['type' => 'bad.type'])
        ->assertSessionHasErrors('type');
});

it('requires task_id for task.run', function () {
    $this->actingAs($this->user)
        ->post(route('commands.store', $this->agent), [
            'type' => CommandType::TaskRun->value,
        ])
        ->assertSessionHasErrors('payload.task_id');
});

it('rejects task_id that does not exist', function () {
    $this->actingAs($this->user)
        ->post(route('commands.store', $this->agent), [
            'type' => CommandType::TaskRun->value,
            'payload' => ['task_id' => 99999],
        ])
        ->assertSessionHasErrors('payload.task_id');
});

it('requires mcp_name for mcp.disable', function () {
    $this->actingAs($this->user)
        ->post(route('commands.store', $this->agent), [
            'type' => CommandType::McpDisable->value,
        ])
        ->assertSessionHasErrors('payload.mcp_name');
});

it('requires text for agent.instruct', function () {
    $this->actingAs($this->user)
        ->post(route('commands.store', $this->agent), [
            'type' => CommandType::AgentInstruct->value,
            'payload' => [],
        ])
        ->assertSessionHasErrors('payload.text');
});

// ── Scoping ───────────────────────────────────────────────────────────────────

it('commands index returns only commands issued by current user for that agent', function () {
    $task = Task::factory()->for($this->agent)->create();

    AgentCommand::factory()->for($this->agent)->create([
        'issued_by' => $this->user->id,
        'type' => CommandType::TaskRun,
        'status' => CommandStatus::Pending,
        'payload' => ['task_id' => $task->id],
    ]);

    AgentCommand::factory()->for($this->agent)->create([
        'issued_by' => User::factory()->create()->id,
        'type' => CommandType::AgentPause,
        'status' => CommandStatus::Pending,
    ]);

    $this->actingAs($this->user)
        ->getJson(route('commands.index', $this->agent))
        ->assertOk()
        ->assertJsonCount(1);
});
