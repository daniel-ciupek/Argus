<?php

declare(strict_types=1);

namespace App\Enums;

enum McpStatus: string
{
    case Connected = 'connected';
    case Disabled = 'disabled';
    case Error = 'error';
}
