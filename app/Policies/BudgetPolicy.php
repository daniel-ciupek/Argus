<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Budget;
use App\Models\User;

class BudgetPolicy
{
    public function delete(User $user, Budget $budget): bool
    {
        return $budget->agent?->user_id === $user->id;
    }
}
