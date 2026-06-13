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
        'timestamp' => time(),
    ], $overrides);
}

function signPayload(string $body, string $secret): string
{
    return 'sha256='.hash_hmac('sha256', $body, $secret);
}

function ingestPost(Agent $agent, array $payload, ?string $signature = null): TestResponse
{
    $body = json_encode($payload);
    $sig = $signature ?? signPayload($body, $agent->ingest_secret);

    return test()->postJson(
        "/api/ingest/{$agent->slug}/events",
        $payload,
        ['X-Signature' => $sig],
    );
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

    $payload = makePayload();
    $body = json_encode($payload);
    $sig = signPayload($body, $this->agent->ingest_secret);

    $this->postJson("/api/ingest/{$this->agent->slug}/events", $payload, ['X-Signature' => $sig])
        ->assertStatus(202)
        ->assertJson(['message' => 'Accepted.']);

    Queue::assertPushed(ProcessEventJob::class);
});

it('rejects a request with an invalid signature', function (): void {
    Queue::fake();

    $payload = makePayload();

    $this->postJson("/api/ingest/{$this->agent->slug}/events", $payload, ['X-Signature' => 'sha256=badhash'])
        ->assertStatus(401)
        ->assertJson(['error' => 'Invalid signature.']);

    Queue::assertNothingPushed();
});

it('rejects a request with a missing X-Signature header', function (): void {
    $payload = makePayload();

    $this->postJson("/api/ingest/{$this->agent->slug}/events", $payload)
        ->assertStatus(401)
        ->assertJson(['error' => 'Missing or malformed X-Signature header.']);
});

it('rejects a request with a malformed X-Signature header', function (): void {
    $payload = makePayload();

    $this->postJson("/api/ingest/{$this->agent->slug}/events", $payload, ['X-Signature' => 'not-sha256-format'])
        ->assertStatus(401)
        ->assertJson(['error' => 'Missing or malformed X-Signature header.']);
});

it('rejects a request with an expired timestamp (replay attack)', function (): void {
    $payload = makePayload(['timestamp' => time() - 400]);
    $body = json_encode($payload);
    $sig = signPayload($body, $this->agent->ingest_secret);

    $this->postJson("/api/ingest/{$this->agent->slug}/events", $payload, ['X-Signature' => $sig])
        ->assertStatus(401)
        ->assertJson(['error' => 'Request timestamp is missing or too old (replay protection).']);
});

it('rejects a request with a future timestamp beyond drift window', function (): void {
    $payload = makePayload(['timestamp' => time() + 400]);
    $body = json_encode($payload);
    $sig = signPayload($body, $this->agent->ingest_secret);

    $this->postJson("/api/ingest/{$this->agent->slug}/events", $payload, ['X-Signature' => $sig])
        ->assertStatus(401)
        ->assertJson(['error' => 'Request timestamp is missing or too old (replay protection).']);
});

it('returns 404 for an unknown agent slug', function (): void {
    $payload = makePayload();
    $body = json_encode($payload);
    $sig = signPayload($body, 'any-secret');

    $this->postJson('/api/ingest/nonexistent-agent/events', $payload, ['X-Signature' => $sig])
        ->assertStatus(404);
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
    $payload = makePayload(['type' => 'invalid-type']);
    $body = json_encode($payload);
    $sig = signPayload($body, $this->agent->ingest_secret);

    $this->postJson("/api/ingest/{$this->agent->slug}/events", $payload, ['X-Signature' => $sig])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['type']);
});
