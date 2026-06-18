<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CommandType;
use App\Models\Agent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommandRequest extends FormRequest
{
    public function authorize(): bool
    {
        $agent = $this->route('agent');

        return $agent instanceof Agent
            && $this->user()?->can('control', $agent) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = CommandType::tryFrom((string) $this->input('type'));

        $payloadRules = match ($type?->targetKind()) {
            'task' => [
                'payload.task_id' => ['required', 'integer', Rule::exists('tasks', 'id')],
            ],
            'mcp' => [
                'payload.mcp_name' => ['required', 'string', 'max:255'],
            ],
            default => [],
        };

        if ($type === CommandType::AgentInstruct) {
            $payloadRules['payload.text'] = ['required', 'string', 'max:2000'];
        }

        return array_merge([
            'type' => ['required', Rule::enum(CommandType::class)],
        ], $payloadRules);
    }
}
