<?php

namespace App\Actions\Audit;

use App\Contracts\Audit\AuditWriter;
use App\Models\AuditEvent;
use App\Models\Tenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class WriteAuditEvent implements AuditWriter
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
    ): AuditEvent {
        return AuditEvent::create([
            'tenant_id' => $tenant instanceof Tenant ? $tenant->getKey() : $tenant,
            'actor_type' => $actor?->getMorphClass(),
            'actor_id' => $actor?->getKey(),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'action' => $action,
            'source' => $source ?? AuditEvent::SOURCE_APPLICATION,
            'metadata' => $metadata,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }
}
