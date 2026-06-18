<?php

declare(strict_types=1);

use App\Enums\McpStatus;
use App\Events\McpStatusUpdated;
use App\Jobs\UpsertMcpConnectionJob;
use App\Models\Agent;
use App\Models\McpConnection;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;

function makeMcpPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'filesystem',
        'status' => 'connected',
        'meta' => ['root' => '/home/agent'],
    ], $overrides);
}

function mcpPost(Agent $agent, array $payload, ?string $signature = null): TestResponse
{
    $path = "/api/ingest/{$agent->slug}/mcp";
    $body = json_encode($payload);
    $ts = (string) time();
    $canonical = implode("\n", ['POST', $path, $ts, $body]);
    $sig = $signature ?? 'sha256='.hash_hmac('sha256', $canonical, $agent->ingest_secret);

    return test()->postJson($path, $payload, ['X-Signature' => $sig, 'X-Timestamp' => $ts]);
}

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->agent = Agent::factory()->for($this->user)->create([
        'is_active' => true,
        'ingest_secret' => 'test-secret-key-32-bytes-long-ok!',
    ]);
});

it('accepts a valid signed mcp request and dispatches the job', function (): void {
    Queue::fake();

    mcpPost($this->agent, makeMcpPayload())
        ->assertStatus(202)
        ->assertJson(['message' => 'Accepted.']);

    Queue::assertPushed(UpsertMcpConnectionJob::class);
});

it('rejects an mcp request with an invalid signature', function (): void {
    Queue::fake();

    mcpPost($this->agent, makeMcpPayload(), 'sha256=badhash')
        ->assertStatus(401);

    Queue::assertNothingPushed();
});

it('returns 422 for an invalid status', function (): void {
    mcpPost($this->agent, makeMcpPayload(['status' => 'broken']))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

it('returns 422 when meta is not an array', function (): void {
    mcpPost($this->agent, makeMcpPayload(['meta' => 'nope']))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['meta']);
});

it('broadcasts McpStatusUpdated under its short name', function (): void {
    $connection = McpConnection::factory()->for($this->agent)->create();

    expect((new McpStatusUpdated($connection))->broadcastAs())->toBe('McpStatusUpdated');
});

it('creates a connection on first report and stores meta', function (): void {
    Event::fake([McpStatusUpdated::class]);

    (new UpsertMcpConnectionJob($this->agent, makeMcpPayload(['name' => 'github', 'meta' => ['org' => 'acme']])))->handle();

    $connection = McpConnection::where('agent_id', $this->agent->id)->where('name', 'github')->first();
    expect($connection)->not->toBeNull()
        ->and($connection->status)->toBe(McpStatus::Connected)
        ->and($connection->meta)->toBe(['org' => 'acme']);

    Event::assertDispatched(McpStatusUpdated::class, fn ($e) => $e->mcpConnection->is($connection));
});

it('updates an existing connection instead of duplicating it', function (): void {
    Event::fake([McpStatusUpdated::class]);

    (new UpsertMcpConnectionJob($this->agent, makeMcpPayload(['name' => 'slack', 'status' => 'connected'])))->handle();
    (new UpsertMcpConnectionJob($this->agent, makeMcpPayload(['name' => 'slack', 'status' => 'error', 'meta' => ['error' => 'auth expired']])))->handle();

    $connections = McpConnection::where('agent_id', $this->agent->id)->where('name', 'slack')->get();
    expect($connections)->toHaveCount(1)
        ->and($connections->first()->status)->toBe(McpStatus::Error)
        ->and($connections->first()->meta)->toBe(['error' => 'auth expired']);
});

it('updates the agent last_seen_at on report', function (): void {
    Event::fake([McpStatusUpdated::class]);
    $this->agent->update(['last_seen_at' => null]);

    (new UpsertMcpConnectionJob($this->agent, makeMcpPayload()))->handle();

    $this->agent->refresh();
    expect($this->agent->last_seen_at)->not->toBeNull();
});
