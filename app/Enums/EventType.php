<?php

declare(strict_types=1);

namespace App\Enums;

enum EventType: string
{
    case Log = 'log';
    case TaskRun = 'task_run';
    case ToolCall = 'tool_call';
    case Error = 'error';
}
