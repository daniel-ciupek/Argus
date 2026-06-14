<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\AiModel;
use App\Models\UsageRecord;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->agent = Agent::factory()->for($this->user)->create();

    $this->modelA = AiModel::factory()->create(['provider' => 'openai', 'name' => 'gpt-4o']);
    $this->modelB = AiModel::factory()->create(['provider' => 'google', 'name' => 'gemini-1.5-pro']);
});

function usage(Agent $agent, AiModel $model, array $attrs): UsageRecord
{
    return UsageRecord::factory()
        ->for($agent)
        ->for($model, 'aiModel')
        ->create($attrs);
}

it('redirects guests to login', function (): void {
    $this->get('/costs')->assertRedirect('/login');
});

it('renders the Costs page with aggregation props', function (): void {
    $this->actingAs($this->user)
        ->get('/costs')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Costs')
            ->has('totals')
            ->has('daily')
            ->has('perModel')
            ->where('periodDays', 30)
        );
});

it('aggregates totals across the user\'s usage', function (): void {
    usage($this->agent, $this->modelA, ['input_tokens' => 1000, 'output_tokens' => 500, 'cost' => '0.006000', 'occurred_at' => now()]);
    usage($this->agent, $this->modelA, ['input_tokens' => 2000, 'output_tokens' => 0, 'cost' => '0.005000', 'occurred_at' => now()]);
    usage($this->agent, $this->modelB, ['input_tokens' => 100, 'output_tokens' => 100, 'cost' => '0.002000', 'occurred_at' => now()->subDays(2)]);

    $this->actingAs($this->user)
        ->get('/costs')
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.cost', '0.013000')
            ->where('totals.input_tokens', 3100)
            ->where('totals.output_tokens', 600)
            ->where('totals.calls', 3)
        );
});

it('breaks usage down per model, most expensive first', function (): void {
    usage($this->agent, $this->modelA, ['input_tokens' => 1500, 'output_tokens' => 2000, 'cost' => '0.011000', 'occurred_at' => now()]);
    usage($this->agent, $this->modelB, ['input_tokens' => 100, 'output_tokens' => 100, 'cost' => '0.002000', 'occurred_at' => now()]);

    $this->actingAs($this->user)
        ->get('/costs')
        ->assertInertia(fn (Assert $page) => $page
            ->has('perModel', 2)
            ->where('perModel.0.name', 'gpt-4o')
            ->where('perModel.0.cost', '0.011000')
            ->where('perModel.0.tokens', 3500)
            ->where('perModel.0.calls', 1)
            ->where('perModel.1.name', 'gemini-1.5-pro')
            ->where('perModel.1.cost', '0.002000')
        );
});

it('buckets usage by day, oldest first', function (): void {
    usage($this->agent, $this->modelA, ['input_tokens' => 1000, 'output_tokens' => 500, 'cost' => '0.006000', 'occurred_at' => now()]);
    usage($this->agent, $this->modelA, ['input_tokens' => 100, 'output_tokens' => 100, 'cost' => '0.002000', 'occurred_at' => now()->subDays(2)]);

    $this->actingAs($this->user)
        ->get('/costs')
        ->assertInertia(fn (Assert $page) => $page
            ->has('daily', 2)
            ->where('daily.0.date', now()->subDays(2)->toDateString())
            ->where('daily.0.tokens', 200)
            ->where('daily.1.date', now()->toDateString())
            ->where('daily.1.tokens', 1500)
        );
});

it('excludes usage from other users', function (): void {
    $otherUser = User::factory()->create();
    $otherAgent = Agent::factory()->for($otherUser)->create();
    usage($otherAgent, $this->modelA, ['input_tokens' => 9999, 'output_tokens' => 9999, 'cost' => '99.000000', 'occurred_at' => now()]);

    $this->actingAs($this->user)
        ->get('/costs')
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.calls', 0)
            ->where('totals.cost', '0.000000')
        );
});

it('excludes usage older than the reporting window', function (): void {
    usage($this->agent, $this->modelA, ['input_tokens' => 1000, 'output_tokens' => 0, 'cost' => '0.003000', 'occurred_at' => now()->subDays(40)]);

    $this->actingAs($this->user)
        ->get('/costs')
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.calls', 0)
            ->has('daily', 0)
        );
});
