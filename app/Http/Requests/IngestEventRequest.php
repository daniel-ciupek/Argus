<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\EventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IngestEventRequest extends FormRequest
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
            'type' => ['required', 'string', Rule::enum(EventType::class)],
            'level' => ['required', 'string', Rule::in(['debug', 'info', 'warning', 'error'])],
            'message' => ['required', 'string', 'max:10000'],
            'payload' => ['nullable', 'array'],
            'occurred_at' => ['nullable', 'date'],
            'timestamp' => ['required', 'integer'],
        ];
    }
}
