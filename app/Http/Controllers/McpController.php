<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\McpStatus;
use App\Models\Agent;
use App\Models\McpConnection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class McpController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        assert($user !== null);
        $agentIds = $user->agents()->pluck('id');

        $query = McpConnection::query()
            ->whereIn('agent_id', $agentIds)
            ->with('agent:id,name');

        // Optional agent filter — authorized so a user cannot probe foreign agents.
        $agentFilter = $request->integer('agent') ?: null;
        if ($agentFilter !== null) {
            $agent = Agent::findOrFail($agentFilter);
            $this->authorize('view', $agent);
            $query->where('agent_id', $agent->id);
        }

        // Optional status filter — ignored unless it is a valid enum value.
        $statusFilter = McpStatus::tryFrom((string) $request->string('status'));
        if ($statusFilter !== null) {
            $query->where('status', $statusFilter);
        }

        $connections = $query
            ->orderBy('name')
            ->get()
            ->map(fn (McpConnection $connection): array => [
                'id' => $connection->id,
                'agent_id' => $connection->agent_id,
                'agent_name' => $connection->agent->name,
                'name' => $connection->name,
                'status' => $connection->status->value,
                'meta' => $connection->meta,
            ])
            ->all();

        return Inertia::render('Mcp', [
            'connections' => $connections,
            'filters' => [
                'agent' => $agentFilter,
                'status' => $statusFilter?->value,
            ],
            'statuses' => array_map(fn (McpStatus $s): string => $s->value, McpStatus::cases()),
        ]);
    }
}
