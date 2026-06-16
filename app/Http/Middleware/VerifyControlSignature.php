<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Agent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyControlSignature
{
    private const MAX_TIMESTAMP_DRIFT_SECONDS = 300;

    public function handle(Request $request, Closure $next): Response
    {
        $agent = $request->route('agent');

        if (! $agent instanceof Agent) {
            return response()->json(['error' => 'Agent not found.'], Response::HTTP_UNAUTHORIZED);
        }

        if (! $agent->is_active) {
            return response()->json(['error' => 'Agent is inactive.'], Response::HTTP_UNAUTHORIZED);
        }

        $signature = $request->header('X-Signature');

        if (! $signature || ! str_starts_with($signature, 'sha256=')) {
            return response()->json(['error' => 'Missing or malformed X-Signature header.'], Response::HTTP_UNAUTHORIZED);
        }

        $timestamp = $request->header('X-Timestamp');

        if ($timestamp === null || abs(time() - (int) $timestamp) > self::MAX_TIMESTAMP_DRIFT_SECONDS) {
            return response()->json(['error' => 'Request timestamp is missing or too old (replay protection).'], Response::HTTP_UNAUTHORIZED);
        }

        // Canonical string: METHOD\nPATH\nX-Timestamp\nRAW_BODY
        // Path in the signature prevents cross-endpoint replay attacks.
        $canonical = implode("\n", [
            $request->method(),
            $request->getPathInfo(),
            $timestamp,
            $request->getContent(),
        ]);

        $expected = 'sha256='.hash_hmac('sha256', $canonical, $agent->ingest_secret);

        if (! hash_equals($expected, $signature)) {
            return response()->json(['error' => 'Invalid signature.'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
