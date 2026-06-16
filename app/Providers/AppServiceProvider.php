<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Agent;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        RateLimiter::for('command-poll', function (Request $request): Limit {
            $raw = $request->route('agent');
            $agentSlug = $raw instanceof Agent ? $raw->slug : (string) ($raw ?? 'unknown');

            return Limit::perMinute(config('app.rate_limit_command_poll', 120))
                ->by($agentSlug.'|'.$request->ip());
        });

        RateLimiter::for('ingest', function (Request $request): Limit {
            $raw = $request->route('agent');
            // ThrottleRequests runs before SubstituteBindings, so $raw is normally
            // a plain string (the slug). Guard against it being a resolved model
            // in case the middleware order ever changes.
            $agentSlug = $raw instanceof Agent ? $raw->slug : (string) ($raw ?? 'unknown');

            return Limit::perMinute(config('app.rate_limit_ingest', 60))
                ->by($agentSlug.'|'.$request->ip());
        });
    }
}
