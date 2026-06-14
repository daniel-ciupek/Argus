<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\McpStatus;
use Database\Factories\McpConnectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Agent $agent
 * @property McpStatus $status
 * @property array<string, mixed>|null $meta
 */
class McpConnection extends Model
{
    /** @use HasFactory<McpConnectionFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'agent_id',
        'name',
        'status',
        'meta',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => McpStatus::class,
            'meta' => 'array',
        ];
    }

    /** @return BelongsTo<Agent, $this> */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
