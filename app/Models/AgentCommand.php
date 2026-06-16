<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CommandStatus;
use App\Enums\CommandType;
use Database\Factories\AgentCommandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $agent_id
 * @property int $issued_by
 * @property CommandType $type
 * @property CommandStatus $status
 * @property array<string, mixed>|null $payload
 * @property array<string, mixed>|null $result
 * @property Carbon|null $dispatched_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class AgentCommand extends Model
{
    /** @use HasFactory<AgentCommandFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'agent_id',
        'issued_by',
        'type',
        'status',
        'payload',
        'result',
        'dispatched_at',
        'completed_at',
        'expires_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'type' => CommandType::class,
            'status' => CommandStatus::class,
            'payload' => 'array',
            'result' => 'array',
            'dispatched_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Agent, $this> */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /** @return BelongsTo<User, $this> */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
