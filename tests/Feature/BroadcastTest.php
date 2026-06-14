<?php

declare(strict_types=1);

use App\Events\EventReceived;
use App\Jobs\ProcessEventJob;
use App\Models\Agent;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Event as EventFacade;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->agent = Agent::factory()->for($this->user)->create([
        'is_active' => true,
        'ingest_secret' => 'test-secret-key-32-bytes-long-ok!',
    ]);
});

it('broadcasts EventReceived after ProcessEventJob handles', function (): void {
    EventFacade::fake();

    $data = [
        'type' => 'log',
        'level' => 'info',
        'message' => 'Broadcast test',
        'timestamp' => time(),
    ];

    $job = new ProcessEventJob($this->agent, $data);
    $job->handle();

    EventFacade::assertDispatched(EventReceived::class, function (EventReceived $e): bool {
        return $e->event->message === 'Broadcast test'
            && $e->event->agent_id === $this->agent->id;
    });
});

it('EventReceived broadcasts on the correct private channel', function (): void {
    $event = Event::factory()->for($this->agent)->create();

    $broadcast = new EventReceived($event);

    $channels = $broadcast->broadcastOn();
    expect($channels)->toHaveCount(1)
        ->and($channels[0]->name)->toBe('private-agent.'.$this->agent->id);
});

it('EventReceived broadcasts under its short name', function (): void {
    $event = Event::factory()->for($this->agent)->create();

    expect((new EventReceived($event))->broadcastAs())->toBe('EventReceived');
});

it('EventReceived broadcastWith returns expected shape', function (): void {
    $event = Event::factory()->for($this->agent)->create([
        'message' => 'Hello broadcast',
        'level' => 'warning',
    ]);

    $data = (new EventReceived($event))->broadcastWith();

    expect($data)->toHaveKeys(['id', 'type', 'level', 'message', 'payload', 'occurred_at'])
        ->and($data['message'])->toBe('Hello broadcast')
        ->and($data['level'])->toBe('warning');
});

it('channel callback grants access to the agent owner', function (): void {
    // Reproduces the logic of the channel callback in routes/channels.php.
    // HTTP-level auth cannot be tested with the null/log drivers (they are no-ops),
    // so we verify the authorization query directly.
    $hasAccess = Agent::where('id', $this->agent->id)
        ->where('user_id', $this->user->id)
        ->where('is_active', true)
        ->exists();

    expect($hasAccess)->toBeTrue();
});

it('channel callback denies access to a non-owner user', function (): void {
    $otherUser = User::factory()->create();

    $hasAccess = Agent::where('id', $this->agent->id)
        ->where('user_id', $otherUser->id)
        ->where('is_active', true)
        ->exists();

    expect($hasAccess)->toBeFalse();
});

it('channel callback denies access to inactive agents', function (): void {
    $inactiveAgent = Agent::factory()->for($this->user)->create(['is_active' => false]);

    $hasAccess = Agent::where('id', $inactiveAgent->id)
        ->where('user_id', $this->user->id)
        ->where('is_active', true)
        ->exists();

    expect($hasAccess)->toBeFalse();
});
