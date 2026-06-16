<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CommandStatus;
use App\Events\CommandUpdated;
use App\Http\Requests\ReportCommandResultRequest;
use App\Models\Agent;
use App\Models\AgentCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CommandPullController extends Controller
{
    public function index(Agent $agent): JsonResponse
    {
        $commands = DB::transaction(function () use ($agent): array {
            $pending = $agent->commands()
                ->where('status', CommandStatus::Pending->value)
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->lockForUpdate()
                ->get();

            $now = now();
            $result = [];

            foreach ($pending as $command) {
                $command->update([
                    'status' => CommandStatus::Dispatched,
                    'dispatched_at' => $now,
                ]);

                $result[] = [
                    'id' => $command->id,
                    'type' => $command->type->value,
                    'payload' => $command->payload,
                    'expires_at' => $command->expires_at?->toIso8601String(),
                ];
            }

            $agent->update(['last_seen_at' => $now]);

            return $result;
        });

        return response()->json(['commands' => $commands]);
    }

    public function result(ReportCommandResultRequest $request, Agent $agent, AgentCommand $command): JsonResponse
    {
        if ($command->agent_id !== $agent->id) {
            abort(Response::HTTP_NOT_FOUND);
        }

        if ($command->status->isTerminal()) {
            return response()->json(['status' => 'ok']);
        }

        $status = CommandStatus::from($request->string('status')->toString());

        $command->update([
            'status' => $status,
            'result' => $request->input('result'),
            'completed_at' => $status->isTerminal() ? now() : null,
        ]);

        $command->refresh();
        broadcast(new CommandUpdated($command))->toOthers();

        return response()->json(['status' => 'ok']);
    }
}
