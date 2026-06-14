<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BudgetPeriod;
use App\Enums\EventType;
use App\Enums\McpStatus;
use App\Enums\TaskStatus;
use App\Models\Agent;
use App\Models\AiModel;
use App\Models\Alert;
use App\Models\Budget;
use App\Models\Event;
use App\Models\McpConnection;
use App\Models\Task;
use App\Models\UsageRecord;
use App\Models\User;
use App\Services\UsageCalculator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $usageCalculator = new UsageCalculator;

        // ── Demo user ──────────────────────────────────────────────────────
        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@hermes.local',
            'password' => bcrypt('password'),
        ]);

        // ── AI model price list ────────────────────────────────────────────
        $models = [
            ['provider' => 'openai',     'name' => 'gpt-4o',          'input' => '0.002500', 'output' => '0.010000'],
            ['provider' => 'openai',     'name' => 'gpt-4o-mini',     'input' => '0.000150', 'output' => '0.000600'],
            ['provider' => 'anthropic',  'name' => 'claude-opus-4-8', 'input' => '0.015000', 'output' => '0.075000'],
            ['provider' => 'anthropic',  'name' => 'claude-sonnet-4-6', 'input' => '0.003000', 'output' => '0.015000'],
            ['provider' => 'google',     'name' => 'gemini-1.5-pro',  'input' => '0.001250', 'output' => '0.005000'],
        ];

        $aiModels = collect($models)->map(fn (array $m) => AiModel::create([
            'provider' => $m['provider'],
            'name' => $m['name'],
            'input_price_per_1k' => $m['input'],
            'output_price_per_1k' => $m['output'],
            'currency' => 'USD',
        ]));

        // ── Demo agent ─────────────────────────────────────────────────────
        $agent = Agent::create([
            'user_id' => $user->id,
            'name' => 'Hermes Agent',
            'slug' => 'hermes-agent-'.Str::lower(Str::random(6)),
            'ingest_secret' => Str::random(40),
            'is_active' => true,
            'last_seen_at' => now()->subMinutes(3),
        ]);

        // ── MCP connections ────────────────────────────────────────────────
        $mcpSeed = [
            ['name' => 'filesystem',   'status' => McpStatus::Connected, 'meta' => ['root' => '/home/agent']],
            ['name' => 'brave-search', 'status' => McpStatus::Connected, 'meta' => null],
            ['name' => 'github',       'status' => McpStatus::Connected, 'meta' => ['org' => 'daniel-ciupek']],
            ['name' => 'postgres',     'status' => McpStatus::Disabled,  'meta' => null],
            ['name' => 'slack',        'status' => McpStatus::Error,     'meta' => ['error' => 'auth expired']],
        ];

        foreach ($mcpSeed as $mcp) {
            McpConnection::create([
                'agent_id' => $agent->id,
                'name' => $mcp['name'],
                'status' => $mcp['status'],
                'meta' => $mcp['meta'],
            ]);
        }

        // ── Scheduled tasks ────────────────────────────────────────────────
        $taskSeed = [
            ['name' => 'Daily report digest',  'schedule' => '0 8 * * *',   'status' => TaskStatus::Completed],
            ['name' => 'Repository sync',      'schedule' => '*/30 * * * *', 'status' => TaskStatus::Completed],
            ['name' => 'Cost aggregation',     'schedule' => '0 * * * *',    'status' => TaskStatus::Running],
            ['name' => 'Alert check',          'schedule' => '*/5 * * * *',  'status' => TaskStatus::Pending],
        ];

        $tasks = collect($taskSeed)->map(fn (array $t) => Task::create([
            'agent_id' => $agent->id,
            'name' => $t['name'],
            'schedule' => $t['schedule'],
            'status' => $t['status'],
            'last_run_at' => $t['status'] !== TaskStatus::Pending ? now()->subMinutes(rand(5, 120)) : null,
            'next_run_at' => now()->addMinutes(rand(5, 60)),
        ]));

        // ── ~1 week of events + usage records (daily buckets) ─────────────
        $eventTypes = [
            ['type' => EventType::Log,      'level' => 'info',    'message' => 'Agent started task execution'],
            ['type' => EventType::ToolCall,  'level' => 'info',    'message' => 'Called brave-search with query: "laravel reverb"'],
            ['type' => EventType::ToolCall,  'level' => 'info',    'message' => 'Called filesystem: read /tmp/report.md'],
            ['type' => EventType::Log,       'level' => 'info',    'message' => 'Task completed successfully'],
            ['type' => EventType::TaskRun,   'level' => 'info',    'message' => 'Scheduled task triggered'],
            ['type' => EventType::Error,     'level' => 'error',   'message' => 'Tool call failed: connection timeout'],
            ['type' => EventType::Log,       'level' => 'warning', 'message' => 'Rate limit approaching (80%)'],
        ];

        for ($daysAgo = 6; $daysAgo >= 0; $daysAgo--) {
            $eventsPerDay = rand(15, 40);
            $baseTime = now()->subDays($daysAgo)->startOfDay()->addHours(8);

            for ($i = 0; $i < $eventsPerDay; $i++) {
                $template = $eventTypes[array_rand($eventTypes)];
                $occurredAt = $baseTime->copy()->addMinutes($i * rand(10, 30));

                Event::create([
                    'agent_id' => $agent->id,
                    'type' => $template['type'],
                    'level' => $template['level'],
                    'message' => $template['message'],
                    'payload' => $template['type'] === EventType::ToolCall
                        ? ['tool' => 'brave-search', 'duration_ms' => rand(120, 800)]
                        : null,
                    'occurred_at' => $occurredAt,
                ]);

                // Create a usage record for most events (simulates LLM calls).
                if (rand(0, 2) > 0) {
                    /** @var AiModel $aiModel */
                    $aiModel = $aiModels->random();
                    $inputTokens = rand(200, 3000);
                    $outputTokens = rand(100, 1500);

                    UsageRecord::create([
                        'agent_id' => $agent->id,
                        'ai_model_id' => $aiModel->id,
                        'task_id' => rand(0, 1) ? $tasks->random()->id : null,
                        'input_tokens' => $inputTokens,
                        'output_tokens' => $outputTokens,
                        'cost' => $usageCalculator->cost($aiModel, $inputTokens, $outputTokens),
                        'currency' => $aiModel->currency,
                        'occurred_at' => $occurredAt,
                    ]);
                }
            }
        }

        // ── Budgets + alerts ───────────────────────────────────────────────
        $monthlyBudget = Budget::create([
            'agent_id' => $agent->id,
            'period' => BudgetPeriod::Monthly,
            'limit_amount' => '50.0000',
            'currency' => 'USD',
        ]);

        $dailyBudget = Budget::create([
            'agent_id' => $agent->id,
            'period' => BudgetPeriod::Daily,
            'limit_amount' => '5.0000',
            'currency' => 'USD',
        ]);

        // Unacknowledged alert on monthly budget (visible warning in UI).
        Alert::create([
            'budget_id' => $monthlyBudget->id,
            'amount' => '52.3400',
            'channel' => 'database',
            'triggered_at' => now()->subHours(2),
            'acknowledged_at' => null,
        ]);

        // Already-acknowledged alert on daily budget (shows historical record).
        Alert::create([
            'budget_id' => $dailyBudget->id,
            'amount' => '6.1200',
            'channel' => 'database',
            'triggered_at' => now()->subDays(1)->subHours(3),
            'acknowledged_at' => now()->subDays(1),
        ]);
    }
}
