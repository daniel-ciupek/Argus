<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BudgetPeriod;
use App\Http\Requests\StoreBudgetRequest;
use App\Models\Alert;
use App\Models\Budget;
use App\Models\UsageRecord;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        assert($user !== null);
        $agentIds = $user->agents()->pluck('id');

        // Two aggregation queries (one per period type) instead of one per budget.
        $dailySpent = $this->batchSpent($agentIds, now()->startOfDay());
        $monthlySpent = $this->batchSpent($agentIds, now()->startOfMonth());

        $budgets = Budget::whereIn('agent_id', $agentIds)
            ->with('agent:id,name')
            ->orderBy('period')
            ->get()
            ->map(fn (Budget $budget): array => [
                'id' => $budget->id,
                'agent_id' => $budget->agent_id,
                'agent_name' => $budget->agent?->name,
                'period' => $budget->period->value,
                'limit_amount' => (string) $budget->limit_amount,
                'currency' => $budget->currency,
                'current_spent' => match ($budget->period) {
                    BudgetPeriod::Daily => $dailySpent[$budget->agent_id][$budget->currency] ?? '0',
                    BudgetPeriod::Monthly => $monthlySpent[$budget->agent_id][$budget->currency] ?? '0',
                },
            ])
            ->all();

        $alerts = Alert::whereHas('budget', fn ($q) => $q->whereIn('agent_id', $agentIds))
            ->with('budget.agent:id,name')
            ->orderByDesc('triggered_at')
            ->get()
            ->map(fn (Alert $alert): array => [
                'id' => $alert->id,
                'budget_id' => $alert->budget_id,
                'amount' => (string) $alert->amount,
                'channel' => $alert->channel,
                'triggered_at' => $alert->triggered_at->toIso8601String(),
                'acknowledged_at' => $alert->acknowledged_at?->toIso8601String(),
                'budget_period' => $alert->budget->period->value,
                'budget_currency' => $alert->budget->currency,
                'agent_name' => $alert->budget->agent?->name,
            ])
            ->all();

        $agents = $user->agents()->select('id', 'name')->get();

        return Inertia::render('Budgets', [
            'budgets' => $budgets,
            'alerts' => $alerts,
            'agents' => $agents,
            'periods' => array_map(fn (BudgetPeriod $p): string => $p->value, BudgetPeriod::cases()),
        ]);
    }

    public function store(StoreBudgetRequest $request): RedirectResponse
    {
        Budget::create([
            'agent_id' => $request->integer('agent_id'),
            'period' => BudgetPeriod::from($request->string('period')->toString()),
            'limit_amount' => $request->input('limit_amount'),
            'currency' => strtoupper($request->string('currency', 'USD')->toString()),
        ]);

        return redirect()->route('budgets');
    }

    public function destroy(Budget $budget): RedirectResponse
    {
        $this->authorize('delete', $budget);
        $budget->delete();

        return redirect()->route('budgets');
    }

    /**
     * Returns total spending per (agent_id, currency) since the given date.
     * Uses a single GROUP BY query so callers avoid N+1 when iterating budgets.
     *
     * @param  Collection<int, int>  $agentIds
     * @return array<int, array<string, string>>
     */
    private function batchSpent(Collection $agentIds, Carbon $since): array
    {
        $rows = UsageRecord::whereIn('agent_id', $agentIds)
            ->where('occurred_at', '>=', $since)
            ->selectRaw('agent_id, currency, SUM(cost) as total')
            ->groupBy('agent_id', 'currency')
            ->toBase()
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->agent_id][$row->currency] = (string) $row->total;
        }

        return $map;
    }
}
