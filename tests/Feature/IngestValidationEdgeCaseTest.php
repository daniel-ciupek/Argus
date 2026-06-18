<?php

declare(strict_types=1);

use App\Http\Middleware\ThrottleRequests;
use App\Models\Agent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------
function postIngestEvent(Agent $agent, array $payload): TestResponse
{
    $path = "/api/ingest/{$agent->slug}/events";
    $body = json_encode($payload);
    $ts = (string) time();
    $canonical = implode("\n", ['POST', $path, $ts, $body]);
    $sig = 'sha256='.hash_hmac('sha256', $canonical, $agent->ingest_secret);

    return test()->withoutMiddleware(ThrottleRequests::class)
        ->postJson($path, $payload, ['X-Signature' => $sig, 'X-Timestamp' => $ts]);
}

function postIngestMcp(Agent $agent, array $payload): TestResponse
{
    $path = "/api/ingest/{$agent->slug}/mcp";
    $body = json_encode($payload);
    $ts = (string) time();
    $canonical = implode("\n", ['POST', $path, $ts, $body]);
    $sig = 'sha256='.hash_hmac('sha256', $canonical, $agent->ingest_secret);

    return test()->withoutMiddleware(ThrottleRequests::class)
        ->postJson($path, $payload, ['X-Signature' => $sig, 'X-Timestamp' => $ts]);
}

function baseEventPayload(array $overrides = []): array
{
    return array_merge([
        'type' => 'log',
        'level' => 'info',
        'message' => 'ok',
        'occurred_at' => now()->toIso8601String(),
    ], $overrides);
}

function baseMcpPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'test-mcp',
        'status' => 'connected',
    ], $overrides);
}

// ---------------------------------------------------------------------------
// message field
// ---------------------------------------------------------------------------
it('accepts a message at exactly the 2000-character limit', function (): void {
    $agent = Agent::factory()->create();

    postIngestEvent($agent, baseEventPayload(['message' => str_repeat('x', 2000)]))
        ->assertStatus(202);
});

it('rejects a message longer than 2000 characters', function (): void {
    $agent = Agent::factory()->create();

    postIngestEvent($agent, baseEventPayload(['message' => str_repeat('x', 2001)]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['message']);
});

// ---------------------------------------------------------------------------
// payload field (64 KB JSON limit)
// ---------------------------------------------------------------------------
it('accepts a payload within 64 KB', function (): void {
    $agent = Agent::factory()->create();
    // ~100 bytes of JSON
    $payload = baseEventPayload(['payload' => ['data' => str_repeat('a', 80)]]);

    postIngestEvent($agent, $payload)->assertStatus(202);
});

it('rejects a payload whose JSON serialization exceeds 64 KB', function (): void {
    $agent = Agent::factory()->create();
    // ~66 KB of JSON
    $payload = baseEventPayload(['payload' => ['data' => str_repeat('a', 66_000)]]);

    postIngestEvent($agent, $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['payload']);
});

// ---------------------------------------------------------------------------
// meta field (16 KB JSON limit for MCP)
// ---------------------------------------------------------------------------
it('accepts mcp meta within 16 KB', function (): void {
    $agent = Agent::factory()->create();
    $payload = baseMcpPayload(['meta' => ['info' => str_repeat('b', 100)]]);

    postIngestMcp($agent, $payload)->assertStatus(202);
});

it('rejects mcp meta whose JSON serialization exceeds 16 KB', function (): void {
    $agent = Agent::factory()->create();
    $payload = baseMcpPayload(['meta' => ['info' => str_repeat('b', 17_000)]]);

    postIngestMcp($agent, $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['meta']);
});
