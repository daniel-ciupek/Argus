<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CommandStatus;
use App\Enums\CommandType;
use App\Http\Requests\StoreCommandRequest;
use App\Models\Agent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommandController extends Controller
{
    public function index(Request $request, Agent $agent): JsonResponse
    {
        $this->authorize('control', $agent);

        $user = $request->user();
        assert($user !== null);

        $commands = $agent->commands()
            ->where('issued_by', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'type', 'status', 'payload', 'created_at', 'dispatched_at', 'completed_at'])
            ->map(fn ($cmd) => [
                'id' => $cmd->id,
                'type' => $cmd->type->value,
                'status' => $cmd->status->value,
                'payload' => $cmd->payload,
                'created_at' => $cmd->created_at->toIso8601String(),
                'dispatched_at' => $cmd->dispatched_at?->toIso8601String(),
                'completed_at' => $cmd->completed_at?->toIso8601String(),
            ]);

        return response()->json($commands);
    }

    public function store(StoreCommandRequest $request, Agent $agent): RedirectResponse
    {
        $user = $request->user();
        assert($user !== null);

        $type = CommandType::from($request->string('type')->toString());

        $agent->commands()->create([
            'issued_by' => $user->id,
            'type' => $type,
            'status' => CommandStatus::Pending,
            'payload' => $request->input('payload'),
            'expires_at' => now()->addMinutes(5),
        ]);

        return redirect()->back();
    }
}
