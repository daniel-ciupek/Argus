<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;

/**
 * Canonical HMAC-SHA256 verification shared by all ingest and control endpoints.
 *
 * Canonical string: METHOD\nPATH\nX-Timestamp\nRAW_BODY
 * Timestamp travels as the X-Timestamp header (not embedded in the body).
 * This prevents cross-endpoint replay attacks and keeps the signing contract
 * identical for both ingest and control channels.
 */
class HmacVerifier
{
    public const MAX_DRIFT_SECONDS = 300;

    /**
     * Verify X-Signature against the canonical HMAC string.
     * Returns null on success or an error message on failure.
     */
    public static function verify(Request $request, string $secret): ?string
    {
        $signature = $request->header('X-Signature');

        if (! $signature || ! str_starts_with($signature, 'sha256=')) {
            return 'Missing or malformed X-Signature header.';
        }

        $timestamp = $request->header('X-Timestamp');

        if ($timestamp === null || abs(time() - (int) $timestamp) > self::MAX_DRIFT_SECONDS) {
            return 'Request timestamp is missing or too old (replay protection).';
        }

        $canonical = implode("\n", [
            $request->method(),
            $request->getPathInfo(),
            $timestamp,
            $request->getContent(),
        ]);

        $expected = 'sha256='.hash_hmac('sha256', $canonical, $secret);

        if (! hash_equals($expected, $signature)) {
            return 'Invalid signature.';
        }

        return null;
    }
}
