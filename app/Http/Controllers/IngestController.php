<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\IngestEventRequest;
use App\Http\Requests\IngestMcpRequest;
use App\Http\Requests\IngestTaskRequest;
use App\Http\Requests\IngestUsageRequest;
use App\Jobs\AggregateUsageJob;
use App\Jobs\ProcessEventJob;
use App\Jobs\UpsertMcpConnectionJob;
use App\Jobs\UpsertTaskJob;
use App\Models\Agent;
use Illuminate\Http\JsonResponse;

class IngestController extends Controller
{
    public function event(IngestEventRequest $request, Agent $agent): JsonResponse
    {
        ProcessEventJob::dispatch($agent, $request->validated());

        return response()->json(['message' => 'Accepted.'], 202);
    }

    public function usage(IngestUsageRequest $request, Agent $agent): JsonResponse
    {
        AggregateUsageJob::dispatch($agent, $request->validated());

        return response()->json(['message' => 'Accepted.'], 202);
    }

    public function task(IngestTaskRequest $request, Agent $agent): JsonResponse
    {
        UpsertTaskJob::dispatch($agent, $request->validated());

        return response()->json(['message' => 'Accepted.'], 202);
    }

    public function mcp(IngestMcpRequest $request, Agent $agent): JsonResponse
    {
        UpsertMcpConnectionJob::dispatch($agent, $request->validated());

        return response()->json(['message' => 'Accepted.'], 202);
    }
}
