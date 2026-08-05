<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use App\Models\Builders\AuditEventBuilder;
use Database\Factories\AuditEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string|null $actor_type
 * @property int|null $actor_id
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string $action
 * @property string $source
 * @property array<string, mixed>|null $metadata
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $occurred_at
 * @property Carbon|null $created_at
 */
#[Fillable([
    'tenant_id',
    'actor_type',
    'actor_id',
    'subject_type',
    'subject_id',
    'action',
    'source',
    'metadata',
    'ip_address',
    'user_agent',
    'occurred_at',
])]
class AuditEvent extends Model
{
    use BelongsToTenant;

    public const string SOURCE_APPLICATION = 'application';

    public const string SOURCE_SYSTEM = 'system';

    public const string SOURCE_JOB = 'job';

    public const null UPDATED_AT = null;

    /** @use HasFactory<AuditEventFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    /**
     * Save the model to the database.
     *
     * @param  array<string, mixed>  $options
     */
    public function save(array $options = []): bool
    {
        if ($this->exists && $this->isDirty()) {
            throw new LogicException('Audit events are append-only and cannot be updated.');
        }

        return parent::save($options);
    }

    /**
     * Delete the model from the database.
     */
    public function delete(): ?bool
    {
        throw new LogicException('Audit events are append-only and cannot be deleted.');
    }

    /**
     * Force a hard delete on the model.
     */
    public function forceDelete(): ?bool
    {
        throw new LogicException('Audit events are append-only and cannot be deleted.');
    }

    /**
     * Get the actor that caused the audit event.
     *
     * @return MorphTo<Model, $this>
     */
    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the subject changed by the audit event.
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  Builder  $query
     * @return AuditEventBuilder<static>
     */
    public function newEloquentBuilder($query): AuditEventBuilder
    {
        return new AuditEventBuilder($query);
    }
}
