<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Agent;
use App\Services\HmacVerifier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyControlSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $agent = $request->route('agent');

        if (! $agent instanceof Agent) {
            return response()->json(['error' => 'Agent not found.'], Response::HTTP_UNAUTHORIZED);
        }

        if (! $agent->is_active) {
            return response()->json(['error' => 'Agent is inactive.'], Response::HTTP_UNAUTHORIZED);
        }

        $error = HmacVerifier::verify($request, $agent->ingest_secret);

        if ($error !== null) {
            return response()->json(['error' => $error], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
