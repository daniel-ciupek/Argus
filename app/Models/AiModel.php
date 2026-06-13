<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AiModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiModel extends Model
{
    /** @use HasFactory<AiModelFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'provider',
        'name',
        'input_price_per_1k',
        'output_price_per_1k',
        'currency',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'input_price_per_1k' => 'decimal:6',
            'output_price_per_1k' => 'decimal:6',
        ];
    }

    /** @return HasMany<UsageRecord, $this> */
    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }
}
