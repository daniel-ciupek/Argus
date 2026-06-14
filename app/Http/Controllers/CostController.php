<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\UsageRecord;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CostController extends Controller
{
    /** Size of the reporting window shown on the Costs page. */
    private const WINDOW_DAYS = 30;

    /** Decimal places of the stored cost column. */
    private const COST_SCALE = 6;

    public function index(Request $request): Response
    {
        $agentIds = $request->user()->agents()->pluck('id');
        $since = now()->subDays(self::WINDOW_DAYS - 1)->startOfDay();

        // Base query builder (stdClass rows) scoped to the user's agents and window.
        $scoped = fn (): Builder => UsageRecord::query()
            ->whereIn('agent_id', $agentIds)
            ->where('occurred_at', '>=', $since)
            ->toBase();

        return Inertia::render('Costs', [
            'periodDays' => self::WINDOW_DAYS,
            'totals' => $this->totals($scoped()),
            'daily' => $this->daily($scoped()),
            'perModel' => $this->perModel($scoped()),
        ]);
    }

    /**
     * @return array{cost: string, input_tokens: int, output_tokens: int, calls: int}
     */
    private function totals(Builder $query): array
    {
        $row = $query
            ->selectRaw('COALESCE(SUM(cost), 0) AS cost')
            ->selectRaw('COALESCE(SUM(input_tokens), 0) AS input_tokens')
            ->selectRaw('COALESCE(SUM(output_tokens), 0) AS output_tokens')
            ->selectRaw('COUNT(*) AS calls')
            ->first();

        if ($row === null) {
            return ['cost' => $this->scale(0), 'input_tokens' => 0, 'output_tokens' => 0, 'calls' => 0];
        }

        return [
            'cost' => $this->scale($row->cost),
            'input_tokens' => (int) $row->input_tokens,
            'output_tokens' => (int) $row->output_tokens,
            'calls' => (int) $row->calls,
        ];
    }

    /**
     * Daily cost/token buckets, oldest first. Days without usage are omitted;
     * the frontend zero-fills the timeline for the chart.
     *
     * @return list<array{date: string, cost: string, tokens: int}>
     */
    private function daily(Builder $query): array
    {
        return $query
            ->selectRaw('DATE(occurred_at) AS date')
            ->selectRaw('SUM(cost) AS cost')
            ->selectRaw('SUM(input_tokens + output_tokens) AS tokens')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn (object $row): array => [
                'date' => (string) $row->date,
                'cost' => $this->scale($row->cost),
                'tokens' => (int) $row->tokens,
            ])
            ->all();
    }

    /**
     * Cost/token breakdown per model, most expensive first.
     *
     * @return list<array{provider: string, name: string, cost: string, tokens: int, calls: int}>
     */
    private function perModel(Builder $query): array
    {
        return $query
            ->join('ai_models', 'ai_models.id', '=', 'usage_records.ai_model_id')
            ->selectRaw('ai_models.provider AS provider')
            ->selectRaw('ai_models.name AS name')
            ->selectRaw('SUM(cost) AS cost')
            ->selectRaw('SUM(input_tokens + output_tokens) AS tokens')
            ->selectRaw('COUNT(*) AS calls')
            ->groupBy('ai_models.id', 'ai_models.provider', 'ai_models.name')
            ->orderByDesc('cost')
            ->get()
            ->map(fn (object $row): array => [
                'provider' => (string) $row->provider,
                'name' => (string) $row->name,
                'cost' => $this->scale($row->cost),
                'tokens' => (int) $row->tokens,
                'calls' => (int) $row->calls,
            ])
            ->all();
    }

    /**
     * Format a numeric value as a fixed-scale decimal string (e.g. "0.000000"),
     * keeping precision via BCMath rather than floats.
     */
    private function scale(mixed $value): string
    {
        return bcadd((string) $value, '0', self::COST_SCALE);
    }
}
