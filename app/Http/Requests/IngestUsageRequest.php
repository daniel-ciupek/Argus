<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Agent;
use App\Models\AiModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class IngestUsageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Agent $agent */
        $agent = $this->route('agent');

        return [
            'provider' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'input_tokens' => ['required', 'integer', 'min:0'],
            'output_tokens' => ['required', 'integer', 'min:0'],
            // A task may only be attributed to a task that belongs to this agent.
            'task_id' => [
                'nullable',
                'integer',
                Rule::exists('tasks', 'id')->where('agent_id', $agent->id),
            ],
            'occurred_at' => ['nullable', 'date'],
            'timestamp' => ['required', 'integer'],
        ];
    }

    /**
     * Reject usage for a model that is not in the price catalogue, so cost can
     * always be computed. Runs after the basic rules pass.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['provider', 'model'])) {
                return;
            }

            $exists = AiModel::query()
                ->where('provider', $this->string('provider'))
                ->where('name', $this->string('model'))
                ->exists();

            if (! $exists) {
                $validator->errors()->add('model', 'Unknown model for the given provider.');
            }
        });
    }
}
