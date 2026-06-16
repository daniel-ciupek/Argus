<?php

declare(strict_types=1);

namespace App\Enums;

enum CommandType: string
{
    case TaskRun = 'task.run';
    case TaskEnable = 'task.enable';
    case TaskDisable = 'task.disable';
    case TaskCancel = 'task.cancel';
    case McpEnable = 'mcp.enable';
    case McpDisable = 'mcp.disable';
    case McpRestart = 'mcp.restart';
    case AgentInstruct = 'agent.instruct';
    case AgentPause = 'agent.pause';
    case AgentResume = 'agent.resume';
    case AgentStop = 'agent.stop';

    public function targetKind(): string
    {
        return match (true) {
            str_starts_with($this->value, 'task.') => 'task',
            str_starts_with($this->value, 'mcp.') => 'mcp',
            default => 'agent',
        };
    }
}
