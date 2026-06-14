<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\Agent;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        $agentIds = $request->user()->agents()->pluck('id');

        $query = Task::query()
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
        $statusFilter = TaskStatus::tryFrom((string) $request->string('status'));
        if ($statusFilter !== null) {
            $query->where('status', $statusFilter);
        }

        $tasks = $query
            ->orderBy('name')
            ->get()
            ->map(fn (Task $task): array => [
                'id' => $task->id,
                'agent_id' => $task->agent_id,
                'agent_name' => $task->agent->name,
                'name' => $task->name,
                'status' => $task->status->value,
                'schedule' => $task->schedule,
                'last_run_at' => $task->last_run_at?->toIso8601String(),
                'next_run_at' => $task->next_run_at?->toIso8601String(),
            ])
            ->all();

        return Inertia::render('Tasks', [
            'tasks' => $tasks,
            'filters' => [
                'agent' => $agentFilter,
                'status' => $statusFilter?->value,
            ],
            'statuses' => array_map(fn (TaskStatus $s): string => $s->value, TaskStatus::cases()),
        ]);
    }
}
