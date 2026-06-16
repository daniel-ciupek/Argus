<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CommandStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportCommandResultRequest extends FormRequest
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
            'status' => ['required', Rule::in([
                CommandStatus::Acknowledged->value,
                CommandStatus::Succeeded->value,
                CommandStatus::Failed->value,
            ])],
            'result' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
