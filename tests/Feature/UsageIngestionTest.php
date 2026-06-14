<?php

declare(strict_types=1);

use App\Jobs\AggregateUsageJob;
use App\Models\Agent;
use App\Models\AiModel;
use App\Models\Task;
use App\Models\UsageRecord;
use App\Models\User;
use App\Services\UsageCalculator;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;

function makeUsagePayload(array $overrides = []): array
{
    return array_merge([
        'provider' => 'openai',
        'model' => 'gpt-4o',
        'input_tokens' => 1000,
        'output_tokens' => 500,
        'occurred_at' => now()->toIso8601String(),
        'timestamp' => time(),
    ], $overrides);
}

function signUsage(string $body, string $secret): string
{
    return 'sha256='.hash_hmac('sha256', $body, $secret);
}

function usagePost(Agent $agent, array $payload, ?string $signature = null): TestResponse
{
    $body = json_encode($payload);
    $sig = $signature ?? signUsage($body, $agent->ingest_secret);

    return test()->postJson("/api/ingest/{$agent->slug}/usage", $payload, ['X-Signature' => $sig]);
}

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->agent = Agent::factory()->for($this->user)->create([
        'is_active' => true,
        'ingest_secret' => 'test-secret-key-32-bytes-long-ok!',
    ]);
    $this->model = AiModel::factory()->create([
        'provider' => 'openai',
        'name' => 'gpt-4o',
        'input_price_per_1k' => '0.003000',
        'output_price_per_1k' => '0.006000',
        'currency' => 'USD',
    ]);
});

it('accepts a valid signed usage request and dispatches the job', function (): void {
    Queue::fake();

    usagePost($this->agent, makeUsagePayload())
        ->assertStatus(202)
        ->assertJson(['message' => 'Accepted.']);

    Queue::assertPushed(AggregateUsageJob::class);
});

it('rejects a usage request with an invalid signature', function (): void {
    Queue::fake();

    usagePost($this->agent, makeUsagePayload(), 'sha256=badhash')
        ->assertStatus(401)
        ->assertJson(['error' => 'Invalid signature.']);

    Queue::assertNothingPushed();
});

it('rejects a usage request with an expired timestamp', function (): void {
    usagePost($this->agent, makeUsagePayload(['timestamp' => time() - 400]))
        ->assertStatus(401)
        ->assertJson(['error' => 'Request timestamp is missing or too old (replay protection).']);
});

it('returns 422 for an unknown model', function (): void {
    usagePost($this->agent, makeUsagePayload(['model' => 'does-not-exist']))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['model']);
});

it('returns 422 when required fields are missing', function (): void {
    usagePost($this->agent, makeUsagePayload(['input_tokens' => null]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['input_tokens']);
});

it('returns 422 for negative token counts', function (): void {
    usagePost($this->agent, makeUsagePayload(['output_tokens' => -5]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['output_tokens']);
});

it('rejects a task that belongs to another agent (IDOR)', function (): void {
    $otherTask = Task::factory()->create();

    usagePost($this->agent, makeUsagePayload(['task_id' => $otherTask->id]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['task_id']);
});

it('accepts a task that belongs to the same agent', function (): void {
    Queue::fake();
    $task = Task::factory()->for($this->agent)->create();

    usagePost($this->agent, makeUsagePayload(['task_id' => $task->id]))
        ->assertStatus(202);

    Queue::assertPushed(AggregateUsageJob::class);
});

it('stores a usage record with cost computed from the catalogue', function (): void {
    $payload = makeUsagePayload(['input_tokens' => 1000, 'output_tokens' => 500]);

    (new AggregateUsageJob($this->agent, $payload))->handle(app(UsageCalculator::class));

    $record = UsageRecord::where('agent_id', $this->agent->id)->first();

    // 1000 * 0.003/1000 + 500 * 0.006/1000 = 0.003 + 0.003 = 0.006
    expect($record)->not->toBeNull()
        ->and($record->ai_model_id)->toBe($this->model->id)
        ->and($record->input_tokens)->toBe(1000)
        ->and($record->output_tokens)->toBe(500)
        ->and((string) $record->cost)->toBe('0.006000')
        ->and($record->currency)->toBe('USD');

    $this->agent->refresh();
    expect($this->agent->last_seen_at)->not->toBeNull();
});
