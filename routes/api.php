<?php

declare(strict_types=1);

use App\Http\Controllers\IngestController;
use App\Http\Middleware\VerifyHmacSignature;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:60,1'])
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
    });
