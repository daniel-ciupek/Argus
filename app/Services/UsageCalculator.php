<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AiModel;
use InvalidArgumentException;

/**
 * Computes the monetary cost of an LLM call from a model's per-1k-token price list.
 *
 * Money is calculated with BCMath (arbitrary precision) rather than floats to avoid
 * binary rounding errors; the result is a string matching the `decimal:6` column.
 */
final class UsageCalculator
{
    /** Prices in the catalogue are expressed per this many tokens. */
    private const TOKENS_PER_UNIT = 1000;

    /** Decimal places of the stored `cost` column (matches usage_records.cost). */
    private const COST_SCALE = 6;

    /** Working precision kept during intermediate steps before final rounding. */
    private const INTERNAL_SCALE = 12;

    /**
     * Cost of a single call, rounded half-up to 6 decimals.
     */
    public function cost(AiModel $model, int $inputTokens, int $outputTokens): string
    {
        if ($inputTokens < 0 || $outputTokens < 0) {
            throw new InvalidArgumentException('Token counts cannot be negative.');
        }

        $inputCost = $this->lineCost($inputTokens, (string) $model->input_price_per_1k);
        $outputCost = $this->lineCost($outputTokens, (string) $model->output_price_per_1k);

        $total = bcadd($inputCost, $outputCost, self::INTERNAL_SCALE);

        return $this->roundHalfUp($total);
    }

    /**
     * Cost contribution of one token bucket: tokens * price / 1000.
     */
    private function lineCost(int $tokens, string $pricePer1k): string
    {
        $product = bcmul((string) $tokens, $pricePer1k, self::INTERNAL_SCALE);

        return bcdiv($product, (string) self::TOKENS_PER_UNIT, self::INTERNAL_SCALE);
    }

    /**
     * BCMath truncates, so we nudge by half a unit of the target scale to round
     * half-up. Costs are always non-negative, so a positive nudge is correct.
     */
    private function roundHalfUp(string $value): string
    {
        $halfUnit = '0.'.str_repeat('0', self::COST_SCALE).'5';
        $nudged = bcadd($value, $halfUnit, self::INTERNAL_SCALE);

        return bcadd($nudged, '0', self::COST_SCALE);
    }
}
