<?php

declare(strict_types=1);

use App\Models\AiModel;
use App\Services\UsageCalculator;
use Tests\TestCase;

uses(TestCase::class);

function makeModel(string $inputPrice, string $outputPrice): AiModel
{
    return new AiModel([
        'provider' => 'test',
        'name' => 'test-model',
        'input_price_per_1k' => $inputPrice,
        'output_price_per_1k' => $outputPrice,
        'currency' => 'USD',
    ]);
}

it('computes cost from input and output tokens', function (): void {
    $model = makeModel('0.003000', '0.006000');

    // 1000 * 0.003/1000 + 500 * 0.006/1000 = 0.003 + 0.003
    expect((new UsageCalculator)->cost($model, 1000, 500))->toBe('0.006000');
});

it('returns zero when there are no tokens', function (): void {
    $model = makeModel('0.003000', '0.006000');

    expect((new UsageCalculator)->cost($model, 0, 0))->toBe('0.000000');
});

it('returns zero when the model is free', function (): void {
    $model = makeModel('0.000000', '0.000000');

    expect((new UsageCalculator)->cost($model, 5000, 5000))->toBe('0.000000');
});

it('rounds half-up at the sixth decimal', function (): void {
    // 1 token * 0.0015/1000 = 0.0000015 -> 0.000002
    $model = makeModel('0.001500', '0.000000');

    expect((new UsageCalculator)->cost($model, 1, 0))->toBe('0.000002');
});

it('keeps sub-cent precision without float drift', function (): void {
    // 333 * 0.001/1000 = 0.000333 exactly
    $model = makeModel('0.001000', '0.000000');

    expect((new UsageCalculator)->cost($model, 333, 0))->toBe('0.000333');
});

it('rejects negative token counts', function (): void {
    $model = makeModel('0.003000', '0.006000');

    expect(fn () => (new UsageCalculator)->cost($model, -1, 0))
        ->toThrow(InvalidArgumentException::class);
});
