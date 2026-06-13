<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\IngestEventRequest;
use App\Jobs\ProcessEventJob;
use App\Models\Agent;
use Illuminate\Http\JsonResponse;

class IngestController extends Controller
{
    public function event(IngestEventRequest $request, Agent $agent): JsonResponse
    {
        ProcessEventJob::dispatch($agent, $request->validated());

        return response()->json(['message' => 'Accepted.'], 202);
    }
}
