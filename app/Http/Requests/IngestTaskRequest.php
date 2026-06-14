<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IngestTaskRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::enum(TaskStatus::class)],
            'schedule' => ['nullable', 'string', 'max:255'],
            'last_run_at' => ['nullable', 'date'],
            'next_run_at' => ['nullable', 'date'],
            'timestamp' => ['required', 'integer'],
        ];
    }
}
