<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CommandStatus;
use App\Events\CommandUpdated;
use App\Models\AgentCommand;
use Illuminate\Console\Command;

class ExpireCommandsCommand extends Command
{
    protected $signature = 'commands:expire';

    protected $description = 'Mark pending and dispatched commands past their expires_at as expired';

    public function handle(): int
    {
        $expired = AgentCommand::query()
            ->whereIn('status', [CommandStatus::Pending, CommandStatus::Dispatched])
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expired as $command) {
            $command->update([
                'status' => CommandStatus::Expired,
                'completed_at' => now(),
            ]);

            event(new CommandUpdated($command));
        }

        if ($expired->isNotEmpty()) {
            $this->info("Expired {$expired->count()} command(s).");
        }

        return Command::SUCCESS;
    }
}
