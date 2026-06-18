<?php

declare(strict_types=1);

use App\Models\Agent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

// ---------------------------------------------------------------------------
// Local helper — avoids cross-file function dependency.
// ---------------------------------------------------------------------------
function ingestEvent(Agent $agent): TestResponse
{
    $path = "/api/ingest/{$agent->slug}/events";
    $payload = [
        'type' => 'log',
        'level' => 'info',
        'message' => 'rate-limit probe',
        'occurred_at' => now()->toIso8601String(),
    ];
    $body = json_encode($payload);
    $ts = (string) time();
    $canonical = implode("\n", ['POST', $path, $ts, $body]);
    $sig = 'sha256='.hash_hmac('sha256', $canonical, $agent->ingest_secret);

    return test()->postJson($path, $payload, ['X-Signature' => $sig, 'X-Timestamp' => $ts]);
}

uses(RefreshDatabase::class);

// RATE_LIMIT_INGEST=2 in phpunit.xml → limit is 2 req/min in tests.

it('allows requests within the rate limit', function (): void {
    $agent = Agent::factory()->create();

    ingestEvent($agent)->assertStatus(202);
    ingestEvent($agent)->assertStatus(202);
});

it('returns 429 on the request that exceeds the per-agent limit', function (): void {
    $agent = Agent::factory()->create();

    ingestEvent($agent); // 1st
    ingestEvent($agent); // 2nd — limit reached
    ingestEvent($agent)->assertStatus(429); // 3rd — throttled
});

it('includes Retry-After header in the 429 response', function (): void {
    $agent = Agent::factory()->create();

    ingestEvent($agent);
    ingestEvent($agent);

    $response = ingestEvent($agent);
    $response->assertStatus(429);
    expect($response->headers->has('Retry-After'))->toBeTrue();
});

it('rate limits are isolated per agent slug', function (): void {
    [$agent1, $agent2] = Agent::factory()->count(2)->create();

    // Exhaust agent1's limit.
    ingestEvent($agent1);
    ingestEvent($agent1);
    ingestEvent($agent1)->assertStatus(429);

    // agent2 has its own independent counter.
    ingestEvent($agent2)->assertStatus(202);
});
