<?php

declare(strict_types=1);

use App\Enums\CommandStatus;
use App\Enums\CommandType;
use App\Events\CommandUpdated;
use App\Models\Agent;
use App\Models\AgentCommand;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;

// ── Helpers ──────────────────────────────────────────────────────────────────

function controlSign(Agent $agent, string $method, string $path, string $body = '', ?string $timestamp = null): array
{
    $ts = $timestamp ?? (string) time();
    $canonical = implode("\n", [$method, $path, $ts, $body]);
    $sig = 'sha256='.hash_hmac('sha256', $canonical, $agent->ingest_secret);

    return ['X-Signature' => $sig, 'X-Timestamp' => $ts];
}

function pullGet(Agent $agent): TestResponse
{
    $path = "/api/ingest/{$agent->slug}/commands";
    $headers = controlSign($agent, 'GET', $path);

    // Use get() not getJson() — getJson sends body '[]' which breaks the canonical HMAC.
    return test()->get($path, $headers);
}

// ── Pull tests ────────────────────────────────────────────────────────────────

it('returns pending commands and marks them dispatched', function () {
    $agent = Agent::factory()->create();
    $cmd = AgentCommand::factory()->for($agent)->forType(CommandType::TaskRun)->create([
        'payload' => ['task_id' => 1],
        'expires_at' => now()->addMinutes(5),
    ]);

    $response = pullGet($agent);

    $response->assertOk()
        ->assertJsonPath('commands.0.id', $cmd->id)
        ->assertJsonPath('commands.0.type', 'task.run')
        ->assertJsonPath('commands.0.payload.task_id', 1);

    expect($cmd->fresh()->status)->toBe(CommandStatus::Dispatched)
        ->and($cmd->fresh()->dispatched_at)->not->toBeNull();
});

it('does not return commands from other agents', function () {
    $agent = Agent::factory()->create();
    AgentCommand::factory()->for(Agent::factory())->create();

    pullGet($agent)->assertOk()->assertJsonPath('commands', []);
});

it('does not return already-dispatched or terminal commands', function () {
    $agent = Agent::factory()->create();
    AgentCommand::factory()->for($agent)->dispatched()->create();
    AgentCommand::factory()->for($agent)->succeeded()->create();
    AgentCommand::factory()->for($agent)->failed()->create();
    AgentCommand::factory()->for($agent)->expired()->create();

    pullGet($agent)->assertOk()->assertJsonPath('commands', []);
});

it('does not return expired pending commands', function () {
    $agent = Agent::factory()->create();
    AgentCommand::factory()->for($agent)->create(['expires_at' => now()->subMinute()]);

    pullGet($agent)->assertOk()->assertJsonPath('commands', []);
});

it('updates agent last_seen_at on pull', function () {
    $agent = Agent::factory()->create(['last_seen_at' => null]);
    pullGet($agent);

    expect($agent->fresh()->last_seen_at)->not->toBeNull();
});

it('rejects pull with invalid signature', function () {
    $agent = Agent::factory()->create();
    $path = "/api/ingest/{$agent->slug}/commands";

    $this->getJson($path, [
        'X-Signature' => 'sha256=bad',
        'X-Timestamp' => (string) time(),
    ])->assertUnauthorized();
});

it('rejects pull with expired timestamp (replay protection)', function () {
    $agent = Agent::factory()->create();
    $path = "/api/ingest/{$agent->slug}/commands";
    $headers = controlSign($agent, 'GET', $path, '', (string) (time() - 400));

    $this->getJson($path, $headers)->assertUnauthorized();
});

it('rejects pull when agent is inactive (kill-switch)', function () {
    $agent = Agent::factory()->create(['is_active' => false]);

    $path = "/api/ingest/{$agent->slug}/commands";
    $headers = controlSign($agent, 'GET', $path);

    $this->getJson($path, $headers)->assertUnauthorized();
});

// ── Result report tests ───────────────────────────────────────────────────────

it('accepts a result report and broadcasts CommandUpdated', function () {
    Event::fake([CommandUpdated::class]);

    $agent = Agent::factory()->create();
    $cmd = AgentCommand::factory()->for($agent)->dispatched()->create();
    $path = "/api/ingest/{$agent->slug}/commands/{$cmd->id}";
    $body = json_encode(['status' => 'succeeded', 'result' => ['output' => 'ok']]);
    assert($body !== false);

    $headers = controlSign($agent, 'POST', $path, $body);

    $this->postJson($path, json_decode($body, true), $headers)->assertOk();

    expect($cmd->fresh()->status)->toBe(CommandStatus::Succeeded)
        ->and($cmd->fresh()->result)->toBe(['output' => 'ok'])
        ->and($cmd->fresh()->completed_at)->not->toBeNull();

    Event::assertDispatched(CommandUpdated::class, fn ($e) => $e->command->id === $cmd->id);
});

it('is idempotent — reporting result on a terminal command returns ok without changes', function () {
    Event::fake([CommandUpdated::class]);

    $agent = Agent::factory()->create();
    $cmd = AgentCommand::factory()->for($agent)->succeeded()->create(['result' => ['original' => true]]);
    $path = "/api/ingest/{$agent->slug}/commands/{$cmd->id}";
    $body = json_encode(['status' => 'failed']);
    assert($body !== false);

    $headers = controlSign($agent, 'POST', $path, $body);
    $this->postJson($path, json_decode($body, true), $headers)->assertOk();

    expect($cmd->fresh()->status)->toBe(CommandStatus::Succeeded);
    Event::assertNotDispatched(CommandUpdated::class);
});

it('returns 404 when reporting result for a command belonging to another agent (IDOR)', function () {
    $agent = Agent::factory()->create();
    $other = Agent::factory()->create();
    $cmd = AgentCommand::factory()->for($other)->dispatched()->create();

    $path = "/api/ingest/{$agent->slug}/commands/{$cmd->id}";
    $body = json_encode(['status' => 'succeeded']);
    assert($body !== false);

    $headers = controlSign($agent, 'POST', $path, $body);
    $this->postJson($path, json_decode($body, true), $headers)->assertNotFound();
});

it('rejects result with invalid status value', function () {
    $agent = Agent::factory()->create();
    $cmd = AgentCommand::factory()->for($agent)->dispatched()->create();
    $path = "/api/ingest/{$agent->slug}/commands/{$cmd->id}";
    $body = json_encode(['status' => 'invalid-status']);
    assert($body !== false);

    $headers = controlSign($agent, 'POST', $path, $body);
    $this->postJson($path, json_decode($body, true), $headers)->assertUnprocessable();
});

it('rejects result report with invalid signature', function () {
    $agent = Agent::factory()->create();
    $cmd = AgentCommand::factory()->for($agent)->dispatched()->create();
    $path = "/api/ingest/{$agent->slug}/commands/{$cmd->id}";

    $this->postJson($path, ['status' => 'succeeded'], [
        'X-Signature' => 'sha256=bad',
        'X-Timestamp' => (string) time(),
    ])->assertUnauthorized();
});
