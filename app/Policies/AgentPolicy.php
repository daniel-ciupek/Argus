<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Agent;
use App\Models\User;

class AgentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Agent $agent): bool
    {
        return $agent->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Agent $agent): bool
    {
        return $agent->user_id === $user->id;
    }

    public function delete(User $user, Agent $agent): bool
    {
        return $agent->user_id === $user->id;
    }

    public function control(User $user, Agent $agent): bool
    {
        return $agent->user_id === $user->id;
    }
}
