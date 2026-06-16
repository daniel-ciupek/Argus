<?php

declare(strict_types=1);

namespace App\Enums;

enum CommandStatus: string
{
    case Pending = 'pending';
    case Dispatched = 'dispatched';
    case Acknowledged = 'acknowledged';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Expired = 'expired';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Succeeded, self::Failed, self::Expired => true,
            default => false,
        };
    }
}
