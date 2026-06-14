<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\McpStatus;
use App\Rules\MaxJsonSize;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IngestMcpRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::enum(McpStatus::class)],
            'meta' => ['nullable', 'array', new MaxJsonSize(16_384)],
            'timestamp' => ['required', 'integer'],
        ];
    }
}
