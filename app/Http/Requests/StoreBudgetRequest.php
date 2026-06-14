<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\BudgetPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetRequest extends FormRequest
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
        $user = $this->user();
        assert($user !== null);

        return [
            'agent_id' => [
                'required',
                'integer',
                Rule::exists('agents', 'id')->where('user_id', $user->id),
            ],
            'period' => ['required', Rule::enum(BudgetPeriod::class)],
            'limit_amount' => ['required', 'numeric', 'min:0.01', 'max:99999'],
            'currency' => ['sometimes', 'string', 'size:3'],
        ];
    }
}
