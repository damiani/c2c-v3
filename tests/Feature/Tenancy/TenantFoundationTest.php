<?php

use App\Actions\Tenancy\ResolveTenantContext;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

test('users can belong to tenants through memberships', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();

    TenantMembership::factory()
        ->for($tenant)
        ->for($user)
        ->owner()
        ->create();

    expect($tenant->fresh()->memberships)->toHaveCount(1)
        ->and($tenant->users()->first()->is($user))->toBeTrue()
        ->and($user->tenants()->first()->is($tenant))->toBeTrue()
        ->and($user->tenantMemberships()->first()->role)->toBe(TenantMembership::ROLE_OWNER);
});

test('tenant policies isolate users by membership and role', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $outsider = User::factory()->create();
    $tenant = Tenant::factory()->create();

    TenantMembership::factory()
        ->for($tenant)
        ->for($owner, 'user')
        ->owner()
        ->create();

    TenantMembership::factory()
        ->for($tenant)
        ->for($member, 'user')
        ->create();

    expect(Gate::forUser($owner)->allows('view', $tenant))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('update', $tenant))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('delete', $tenant))->toBeTrue()
        ->and(Gate::forUser($member)->allows('view', $tenant))->toBeTrue()
        ->and(Gate::forUser($member)->denies('update', $tenant))->toBeTrue()
        ->and(Gate::forUser($member)->denies('delete', $tenant))->toBeTrue()
        ->and(Gate::forUser($outsider)->denies('view', $tenant))->toBeTrue()
        ->and(Gate::forUser($outsider)->denies('update', $tenant))->toBeTrue();
});

test('tenant context resolver only returns tenants the user belongs to', function () {
    $user = User::factory()->create();
    $firstTenant = Tenant::factory()->create();
    $secondTenant = Tenant::factory()->create();
    $outsideTenant = Tenant::factory()->create();

    TenantMembership::factory()
        ->for($firstTenant)
        ->for($user, 'user')
        ->create();

    TenantMembership::factory()
        ->for($secondTenant)
        ->for($user, 'user')
        ->create();

    $resolver = app(ResolveTenantContext::class);

    expect($resolver->handle($user)?->is($firstTenant))->toBeTrue()
        ->and($resolver->handle($user, $secondTenant)?->is($secondTenant))->toBeTrue()
        ->and($resolver->handle($user, $secondTenant->id)?->is($secondTenant))->toBeTrue()
        ->and($resolver->handle($user, $outsideTenant))->toBeNull();
});
