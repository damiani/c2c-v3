<?php

namespace App\Models\Builders;

use Illuminate\Database\Eloquent\Builder;
use LogicException;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends Builder<TModel>
 */
class AuditEventBuilder extends Builder
{
    /**
     * Update records in the database.
     *
     * @param  array<string, mixed>  $values
     */
    public function update(array $values): int
    {
        throw new LogicException('Audit events are append-only and cannot be updated.');
    }

    /**
     * Insert new records or update existing records in the database.
     *
     * @param  array<int, array<string, mixed>>  $values
     * @param  array<int, string>|string  $uniqueBy
     * @param  array<int, string>|null  $update
     */
    public function upsert(array $values, $uniqueBy, $update = null): int
    {
        throw new LogicException('Audit events are append-only and cannot be upserted.');
    }

    /**
     * Delete records from the database.
     */
    public function delete(): mixed
    {
        throw new LogicException('Audit events are append-only and cannot be deleted.');
    }

    /**
     * Force delete records from the database.
     */
    public function forceDelete(): mixed
    {
        throw new LogicException('Audit events are append-only and cannot be deleted.');
    }
}
