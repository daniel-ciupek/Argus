<?php

declare(strict_types=1);

use App\Enums\McpStatus;
use App\Models\Agent;
use App\Models\McpConnection;

it('creates a valid mcp_connection via factory', function () {
    $conn = McpConnection::factory()->create();

    expect($conn->exists)->toBeTrue()
        ->and($conn->status)->toBeInstanceOf(McpStatus::class);
});

it('belongs to an agent', function () {
    $conn = McpConnection::factory()->create();

    expect($conn->agent)->toBeInstanceOf(Agent::class);
});

it('stores and retrieves jsonb meta', function () {
    $meta = ['url' => 'http://mcp.local', 'version' => '1.2'];

    $conn = McpConnection::factory()->withMeta($meta)->create();

    expect(McpConnection::find($conn->id)->meta)->toBe($meta);
});

it('casts status to McpStatus enum', function () {
    $conn = McpConnection::factory()->connected()->create();

    expect($conn->status)->toBe(McpStatus::Connected);
});
