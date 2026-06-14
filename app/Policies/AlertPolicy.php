<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Alert;
use App\Models\User;

class AlertPolicy
{
    public function update(User $user, Alert $alert): bool
    {
        return $alert->budget->agent?->user_id === $user->id;
    }
}
