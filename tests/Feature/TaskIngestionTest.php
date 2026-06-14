<?php

declare(strict_types=1);

use App\Enums\TaskStatus;
use App\Events\TaskStatusUpdated;
use App\Jobs\UpsertTaskJob;
use App\Models\Agent;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;

function makeTaskPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Daily report digest',
        'status' => 'running',
        'schedule' => '0 8 * * *',
        'last_run_at' => now()->subHour()->toIso8601String(),
        'next_run_at' => now()->addHour()->toIso8601String(),
        'timestamp' => time(),
    ], $overrides);
}

function taskPost(Agent $agent, array $payload, ?string $signature = null): TestResponse
{
    $body = json_encode($payload);
    $sig = $signature ?? 'sha256='.hash_hmac('sha256', $body, $agent->ingest_secret);

    return test()->postJson("/api/ingest/{$agent->slug}/tasks", $payload, ['X-Signature' => $sig]);
}

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->agent = Agent::factory()->for($this->user)->create([
        'is_active' => true,
        'ingest_secret' => 'test-secret-key-32-bytes-long-ok!',
    ]);
});

it('accepts a valid signed task request and dispatches the job', function (): void {
    Queue::fake();

    taskPost($this->agent, makeTaskPayload())
        ->assertStatus(202)
        ->assertJson(['message' => 'Accepted.']);

    Queue::assertPushed(UpsertTaskJob::class);
});

it('rejects a task request with an invalid signature', function (): void {
    Queue::fake();

    taskPost($this->agent, makeTaskPayload(), 'sha256=badhash')
        ->assertStatus(401);

    Queue::assertNothingPushed();
});

it('returns 422 for an invalid status', function (): void {
    taskPost($this->agent, makeTaskPayload(['status' => 'not-a-status']))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

it('returns 422 when the name is missing', function (): void {
    taskPost($this->agent, makeTaskPayload(['name' => '']))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('creates a task on first report', function (): void {
    Event::fake([TaskStatusUpdated::class]);

    (new UpsertTaskJob($this->agent, makeTaskPayload(['name' => 'Repo sync', 'status' => 'completed'])))->handle();

    $task = Task::where('agent_id', $this->agent->id)->where('name', 'Repo sync')->first();
    expect($task)->not->toBeNull()
        ->and($task->status)->toBe(TaskStatus::Completed);

    Event::assertDispatched(TaskStatusUpdated::class, fn ($e) => $e->task->is($task));
});

it('updates an existing task instead of duplicating it', function (): void {
    Event::fake([TaskStatusUpdated::class]);

    (new UpsertTaskJob($this->agent, makeTaskPayload(['name' => 'Repo sync', 'status' => 'running'])))->handle();
    (new UpsertTaskJob($this->agent, makeTaskPayload(['name' => 'Repo sync', 'status' => 'completed'])))->handle();

    $tasks = Task::where('agent_id', $this->agent->id)->where('name', 'Repo sync')->get();
    expect($tasks)->toHaveCount(1)
        ->and($tasks->first()->status)->toBe(TaskStatus::Completed);
});

it('updates the agent last_seen_at on report', function (): void {
    Event::fake([TaskStatusUpdated::class]);
    $this->agent->update(['last_seen_at' => null]);

    (new UpsertTaskJob($this->agent, makeTaskPayload()))->handle();

    $this->agent->refresh();
    expect($this->agent->last_seen_at)->not->toBeNull();
});
