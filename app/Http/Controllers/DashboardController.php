<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EventType;
use App\Enums\McpStatus;
use App\Enums\TaskStatus;
use App\Models\Agent;
use App\Models\Event;
use App\Models\McpConnection;
use App\Models\Task;
use App\Models\UsageRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /** Reporting window for cost/event KPIs. */
    private const WINDOW_DAYS = 30;

    /** Decimal places of the stored cost column. */
    private const COST_SCALE = 6;

    /** How many recent events to surface in the activity feed. */
    private const RECENT_LIMIT = 8;

    public function index(Request $request): Response
    {
        $user = $request->user();
        assert($user !== null);

        /** @var Collection<int, int> $agentIds */
        $agentIds = $user->agents()->pluck('id');
        $since = now()->subDays(self::WINDOW_DAYS - 1)->startOfDay();

        return Inertia::render('Dashboard', [
            'periodDays' => self::WINDOW_DAYS,
            'stats' => $this->stats($agentIds, $since),
            'recent' => $this->recent($agentIds),
        ]);
    }

    /**
     * Headline KPIs scoped to the user's agents. Each value comes from a small
     * aggregate query (no N+1, no per-row loading).
     *
     * @param  Collection<int, int>  $agentIds
     * @return array{
     *     cost: string,
     *     events: int,
     *     errors: int,
     *     agents: array{active: int, total: int},
     *     mcp: array{connected: int, total: int},
     *     tasks: array{failed: int, total: int}
     * }
     */
    private function stats(Collection $agentIds, Carbon $since): array
    {
        $cost = UsageRecord::query()
            ->whereIn('agent_id', $agentIds)
            ->where('occurred_at', '>=', $since)
            ->sum('cost');

        $events = Event::query()
            ->whereIn('agent_id', $agentIds)
            ->where('occurred_at', '>=', $since);

        return [
            'cost' => $this->scale($cost),
            'events' => (clone $events)->count(),
            'errors' => (clone $events)->where('type', EventType::Error)->count(),
            'agents' => [
                'active' => Agent::query()->whereIn('id', $agentIds)->where('is_active', true)->count(),
                'total' => $agentIds->count(),
            ],
            'mcp' => [
                'connected' => McpConnection::query()->whereIn('agent_id', $agentIds)->where('status', McpStatus::Connected)->count(),
                'total' => McpConnection::query()->whereIn('agent_id', $agentIds)->count(),
            ],
            'tasks' => [
                'failed' => Task::query()->whereIn('agent_id', $agentIds)->where('status', TaskStatus::Failed)->count(),
                'total' => Task::query()->whereIn('agent_id', $agentIds)->count(),
            ],
        ];
    }

    /**
     * Most recent events across the user's agents, newest first.
     *
     * @param  Collection<int, int>  $agentIds
     * @return array<int, array{id: int, type: string, level: string|null, message: string|null, agent_name: string, occurred_at: string|null}>
     */
    private function recent(Collection $agentIds): array
    {
        return Event::query()
            ->whereIn('agent_id', $agentIds)
            ->with('agent:id,name')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(self::RECENT_LIMIT)
            ->get()
            ->map(fn (Event $event): array => [
                'id' => $event->id,
                'type' => $event->type->value,
                'level' => $event->level,
                'message' => $event->message,
                'agent_name' => $event->agent->name,
                'occurred_at' => $event->occurred_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * Format a numeric value as a fixed-scale decimal string, keeping precision
     * via BCMath rather than floats.
     */
    private function scale(mixed $value): string
    {
        return bcadd((string) $value, '0', self::COST_SCALE);
    }
}
