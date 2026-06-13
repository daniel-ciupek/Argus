<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('creates a valid agent via factory', function () {
    $agent = Agent::factory()->create();

    expect($agent->exists)->toBeTrue()
        ->and($agent->is_active)->toBeBool()
        ->and($agent->slug)->not->toBeEmpty();
});

it('belongs to a user', function () {
    $agent = Agent::factory()->create();

    expect($agent->user)->toBeInstanceOf(User::class);
});

it('is listed under the owning user', function () {
    $user = User::factory()->create();
    Agent::factory()->count(2)->for($user)->create();

    expect($user->agents)->toHaveCount(2);
});

it('encrypts the ingest secret at rest but exposes it decrypted', function () {
    $agent = Agent::factory()->create(['ingest_secret' => 'super-secret-value']);

    // Accessor returns the plaintext...
    expect($agent->ingest_secret)->toBe('super-secret-value');

    // ...but the raw column value is encrypted, not the plaintext.
    $raw = DB::table('agents')->where('id', $agent->id)->value('ingest_secret');
    expect($raw)->not->toBe('super-secret-value');
});

it('enforces unique slugs', function () {
    Agent::factory()->create(['slug' => 'duplicate-slug']);

    Agent::factory()->create(['slug' => 'duplicate-slug']);
})->throws(QueryException::class);
