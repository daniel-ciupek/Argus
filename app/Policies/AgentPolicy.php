<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Agent;
use App\Models\User;

class AgentPolicy
{
    /**
     * Only the owner may view an agent and its resources (tasks, MCP, logs, costs).
     */
    public function view(User $user, Agent $agent): bool
    {
        return $agent->user_id === $user->id;
    }
}
