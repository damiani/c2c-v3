<?php

use App\Contracts\Audit\AuditWriter;
use App\Models\AuditEvent;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('audit writer records tenant scoped events with actor subject and metadata', function () {
    $tenant = Tenant::factory()->create();
    $actor = User::factory()->create();
    $subject = TenantMembership::factory()
        ->for($tenant)
        ->for($actor, 'user')
        ->owner()
        ->create();
    $occurredAt = now()->subMinute()->startOfSecond();

    $event = app(AuditWriter::class)->record(
        tenant: $tenant,
        action: 'tenant.membership.created',
        actor: $actor,
        subject: $subject,
        metadata: ['role' => TenantMembership::ROLE_OWNER],
        source: AuditEvent::SOURCE_SYSTEM,
        occurredAt: $occurredAt,
        ipAddress: '127.0.0.1',
        userAgent: 'Pest',
    );

    expect($event->tenant->is($tenant))->toBeTrue()
        ->and($event->actor->is($actor))->toBeTrue()
        ->and($event->subject->is($subject))->toBeTrue()
        ->and($event->action)->toBe('tenant.membership.created')
        ->and($event->source)->toBe(AuditEvent::SOURCE_SYSTEM)
        ->and($event->metadata)->toBe(['role' => TenantMembership::ROLE_OWNER])
        ->and($event->ip_address)->toBe('127.0.0.1')
        ->and($event->user_agent)->toBe('Pest')
        ->and($event->occurred_at->equalTo($occurredAt))->toBeTrue();
});

test('audit events can be scoped to a tenant', function () {
    $tenant = Tenant::factory()->create();
    $outsideTenant = Tenant::factory()->create();
    $tenantEvent = AuditEvent::factory()->for($tenant)->create();

    AuditEvent::factory()->for($outsideTenant)->create();

    expect(AuditEvent::query()->forTenant($tenant)->pluck('id')->all())
        ->toBe([$tenantEvent->id])
        ->and($tenant->auditEvents()->pluck('id')->all())
        ->toBe([$tenantEvent->id]);
});

test('audit events are append only for model updates and deletes', function () {
    $event = AuditEvent::factory()->create([
        'action' => 'tenant.created',
    ]);

    expect(fn () => $event->update(['action' => 'tenant.updated']))
        ->toThrow(LogicException::class, 'Audit events are append-only')
        ->and(fn () => $event->updateQuietly(['action' => 'tenant.updated']))
        ->toThrow(LogicException::class, 'Audit events are append-only')
        ->and(fn () => $event->delete())
        ->toThrow(LogicException::class, 'Audit events are append-only')
        ->and(fn () => $event->deleteQuietly())
        ->toThrow(LogicException::class, 'Audit events are append-only')
        ->and($event->fresh()->action)
        ->toBe('tenant.created');
});

test('audit events are append only for eloquent builder mutations', function () {
    $event = AuditEvent::factory()->create([
        'action' => 'tenant.created',
    ]);

    expect(fn () => AuditEvent::query()->whereKey($event)->update(['action' => 'tenant.updated']))
        ->toThrow(LogicException::class, 'Audit events are append-only')
        ->and(fn () => AuditEvent::query()->whereKey($event)->delete())
        ->toThrow(LogicException::class, 'Audit events are append-only')
        ->and(fn () => AuditEvent::query()->upsert([
            [
                'id' => $event->id,
                'tenant_id' => $event->tenant_id,
                'action' => 'tenant.updated',
            ],
        ], ['id'], ['action']))
        ->toThrow(LogicException::class, 'Audit events are append-only')
        ->and($event->fresh()->action)
        ->toBe('tenant.created');
});
