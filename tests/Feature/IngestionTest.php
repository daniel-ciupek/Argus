<?php

declare(strict_types=1);

use App\Jobs\ProcessEventJob;
use App\Models\Agent;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;

function makePayload(array $overrides = []): array
{
    return array_merge([
        'type' => 'log',
        'level' => 'info',
        'message' => 'Test event',
        'payload' => ['key' => 'value'],
        'occurred_at' => now()->toIso8601String(),
    ], $overrides);
}

/** Canonical HMAC: sha256(METHOD\nPATH\nTimestamp\nBODY, secret) */
function signCanonical(string $method, string $path, string $timestamp, string $body, string $secret): string
{
    $canonical = implode("\n", [$method, $path, $timestamp, $body]);

    return 'sha256='.hash_hmac('sha256', $canonical, $secret);
}

function ingestPost(Agent $agent, array $payload, ?string $signature = null, ?string $timestamp = null): TestResponse
{
    $path = "/api/ingest/{$agent->slug}/events";
    $body = json_encode($payload);
    $ts = $timestamp ?? (string) time();
    $sig = $signature ?? signCanonical('POST', $path, $ts, $body, $agent->ingest_secret);

    return test()->postJson($path, $payload, [
        'X-Signature' => $sig,
        'X-Timestamp' => $ts,
    ]);
}

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->agent = Agent::factory()->for($this->user)->create([
        'is_active' => true,
        'ingest_secret' => 'test-secret-key-32-bytes-long-ok!',
    ]);
});

it('accepts a valid signed request and dispatches job', function (): void {
    Queue::fake();

    ingestPost($this->agent, makePayload())
        ->assertStatus(202)
        ->assertJson(['message' => 'Accepted.']);

    Queue::assertPushed(ProcessEventJob::class);
});

it('rejects a request with an invalid signature', function (): void {
    Queue::fake();

    $this->postJson("/api/ingest/{$this->agent->slug}/events", makePayload(), [
        'X-Signature' => 'sha256=badhash',
        'X-Timestamp' => (string) time(),
    ])->assertStatus(401)->assertJson(['error' => 'Invalid signature.']);

    Queue::assertNothingPushed();
});

it('rejects a request with a missing X-Signature header', function (): void {
    $this->postJson("/api/ingest/{$this->agent->slug}/events", makePayload(), [
        'X-Timestamp' => (string) time(),
    ])->assertStatus(401)->assertJson(['error' => 'Missing or malformed X-Signature header.']);
});

it('rejects a request with a malformed X-Signature header', function (): void {
    $this->postJson("/api/ingest/{$this->agent->slug}/events", makePayload(), [
        'X-Signature' => 'not-sha256-format',
        'X-Timestamp' => (string) time(),
    ])->assertStatus(401)->assertJson(['error' => 'Missing or malformed X-Signature header.']);
});

it('rejects a request with a missing X-Timestamp header', function (): void {
    $payload = makePayload();
    $body = json_encode($payload);
    $sig = signCanonical('POST', "/api/ingest/{$this->agent->slug}/events", '', $body, $this->agent->ingest_secret);

    $this->postJson("/api/ingest/{$this->agent->slug}/events", $payload, [
        'X-Signature' => $sig,
    ])->assertStatus(401)->assertJson(['error' => 'Request timestamp is missing or too old (replay protection).']);
});

it('rejects a request with an expired X-Timestamp (replay attack)', function (): void {
    ingestPost($this->agent, makePayload(), null, (string) (time() - 400))
        ->assertStatus(401)
        ->assertJson(['error' => 'Request timestamp is missing or too old (replay protection).']);
});

it('rejects a request with a future X-Timestamp beyond drift window', function (): void {
    ingestPost($this->agent, makePayload(), null, (string) (time() + 400))
        ->assertStatus(401)
        ->assertJson(['error' => 'Request timestamp is missing or too old (replay protection).']);
});

it('returns 404 for an unknown agent slug', function (): void {
    $this->postJson('/api/ingest/nonexistent-agent/events', makePayload(), [
        'X-Signature' => 'sha256=anything',
        'X-Timestamp' => (string) time(),
    ])->assertStatus(404);
});

it('processes the event job and saves event to database', function (): void {
    $payload = makePayload(['message' => 'Job processed correctly', 'payload' => ['foo' => 'bar']]);

    $job = new ProcessEventJob($this->agent, $payload);
    $job->handle();

    $event = Event::where('agent_id', $this->agent->id)->first();
    expect($event)->not->toBeNull()
        ->and($event->message)->toBe('Job processed correctly')
        ->and($event->payload)->toBe(['foo' => 'bar'])
        ->and($event->level)->toBe('info');

    $this->agent->refresh();
    expect($this->agent->last_seen_at)->not->toBeNull();
});

it('validates required fields and returns 422 on invalid input', function (): void {
    ingestPost($this->agent, makePayload(['type' => 'invalid-type']))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['type']);
});
