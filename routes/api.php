<?php

declare(strict_types=1);

use App\Http\Controllers\CommandPullController;
use App\Http\Controllers\IngestController;
use App\Http\Middleware\VerifyControlSignature;
use App\Http\Middleware\VerifyHmacSignature;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:ingest'])
    ->prefix('ingest')
    ->group(function (): void {
        Route::post('{agent:slug}/events', [IngestController::class, 'event'])
            ->middleware(VerifyHmacSignature::class)
            ->where('agent', '[a-z0-9\-]+');

        Route::post('{agent:slug}/usage', [IngestController::class, 'usage'])
            ->middleware(VerifyHmacSignature::class)
            ->where('agent', '[a-z0-9\-]+');

        Route::post('{agent:slug}/tasks', [IngestController::class, 'task'])
            ->middleware(VerifyHmacSignature::class)
            ->where('agent', '[a-z0-9\-]+');

        Route::post('{agent:slug}/mcp', [IngestController::class, 'mcp'])
            ->middleware(VerifyHmacSignature::class)
            ->where('agent', '[a-z0-9\-]+');
    });

Route::middleware(['throttle:command-poll', VerifyControlSignature::class])
    ->prefix('ingest')
    ->group(function (): void {
        Route::get('{agent:slug}/commands', [CommandPullController::class, 'index'])
            ->where('agent', '[a-z0-9\-]+');

        Route::post('{agent:slug}/commands/{command}', [CommandPullController::class, 'result'])
            ->where('agent', '[a-z0-9\-]+')
            ->where('command', '[0-9]+');
    });
