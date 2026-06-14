<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class MaxJsonSize implements ValidationRule
{
    public function __construct(private readonly int $maxBytes) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $encoded = json_encode($value);

        if ($encoded === false || strlen($encoded) > $this->maxBytes) {
            $fail("The :attribute must not exceed {$this->maxBytes} bytes when serialized to JSON.");
        }
    }
}
