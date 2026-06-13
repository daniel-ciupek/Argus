<?php

declare(strict_types=1);

use App\Models\AiModel;
use Illuminate\Database\QueryException;

it('creates a valid ai_model via factory', function () {
    $model = AiModel::factory()->create([
        'provider' => 'openai',
        'name' => 'gpt-4o-test',
    ]);

    expect($model->exists)->toBeTrue()
        ->and($model->provider)->toBe('openai')
        ->and($model->currency)->toBe('USD');
});

it('casts prices to decimal strings', function () {
    $model = AiModel::factory()->create([
        'provider' => 'test',
        'name' => 'model-decimal',
        'input_price_per_1k' => '0.002500',
        'output_price_per_1k' => '0.010000',
    ]);

    // Decimal cast returns a numeric string, not float — safe for money math.
    expect($model->input_price_per_1k)->toBe('0.002500')
        ->and($model->output_price_per_1k)->toBe('0.010000');
});

it('enforces unique provider + name combination', function () {
    AiModel::factory()->create(['provider' => 'openai', 'name' => 'gpt-unique']);

    AiModel::factory()->create(['provider' => 'openai', 'name' => 'gpt-unique']);
})->throws(QueryException::class);

it('allows same model name under different providers', function () {
    AiModel::factory()->create(['provider' => 'openai', 'name' => 'turbo']);
    AiModel::factory()->create(['provider' => 'anthropic', 'name' => 'turbo']);

    expect(AiModel::count())->toBe(2);
});
