<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BudgetPeriod;
use App\Models\Agent;
use App\Models\Alert;
use App\Models\Budget;
use App\Models\UsageRecord;
use Carbon\Carbon;

class BudgetGuard
{
    private const int SCALE = 6;

    public function check(Agent $agent): void
    {
        $budgets = Budget::where('agent_id', $agent->id)->get();

        foreach ($budgets as $budget) {
            $since = $this->periodStart($budget->period);
            $spent = $this->spent($agent->id, $budget->currency, $since);

            if (bccomp($spent, (string) $budget->limit_amount, self::SCALE) < 0) {
                continue;
            }

            if ($this->alreadyAlerted($budget->id, $since)) {
                continue;
            }

            Alert::create([
                'budget_id' => $budget->id,
                'amount' => $spent,
                'channel' => 'database',
                'triggered_at' => now(),
            ]);
        }
    }

    private function spent(int $agentId, string $currency, Carbon $since): string
    {
        return (string) UsageRecord::where('agent_id', $agentId)
            ->where('currency', $currency)
            ->where('occurred_at', '>=', $since)
            ->sum('cost');
    }

    private function periodStart(BudgetPeriod $period): Carbon
    {
        return match ($period) {
            BudgetPeriod::Daily => now()->startOfDay(),
            BudgetPeriod::Monthly => now()->startOfMonth(),
        };
    }

    private function alreadyAlerted(int $budgetId, Carbon $since): bool
    {
        return Alert::where('budget_id', $budgetId)
            ->where('triggered_at', '>=', $since)
            ->exists();
    }
}
