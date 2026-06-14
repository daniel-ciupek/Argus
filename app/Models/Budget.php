<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BudgetPeriod;
use Database\Factories\BudgetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property BudgetPeriod $period
 * @property string $limit_amount
 */
class Budget extends Model
{
    /** @use HasFactory<BudgetFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'agent_id',
        'period',
        'limit_amount',
        'currency',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'period' => BudgetPeriod::class,
            'limit_amount' => 'decimal:4',
        ];
    }

    /** @return BelongsTo<Agent, $this> */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /** @return HasMany<Alert, $this> */
    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }
}
