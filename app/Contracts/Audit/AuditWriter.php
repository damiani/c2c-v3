<?php

namespace App\Contracts\Audit;

use App\Models\AuditEvent;
use App\Models\Tenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

interface AuditWriter
{
    /**
     * Record an append-only audit event.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        Tenant|int $tenant,
        string $action,
        ?Model $actor = null,
        ?Model $subject = null,
        array $metadata = [],
        ?string $source = null,
        ?CarbonInterface $occurredAt = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): AuditEvent;
}
