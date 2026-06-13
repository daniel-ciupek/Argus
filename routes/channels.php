<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('agent.{agentId}', function (User $user, int $agentId): bool {
    return Agent::where('id', $agentId)
        ->where('user_id', $user->id)
        ->where('is_active', true)
        ->exists();
});
