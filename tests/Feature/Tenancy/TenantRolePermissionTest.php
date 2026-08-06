<?php

use App\Actions\Tenancy\EnsureSystemRoles;
use App\Authorization\TenantPermission;
use App\Authorization\TenantPermissionRegistry;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Tenancy\DefaultTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

test('system tenant role definitions can be created for a tenant', function () {
    $tenant = Tenant::factory()->create();

    $roles = app(EnsureSystemRoles::class)->handle($tenant);

    expect($roles)->toHaveKeys(TenantPermissionRegistry::systemRoleSlugs())
        ->and($roles[TenantMembership::ROLE_OWNER]->is_system)->toBeTrue()
        ->and($roles[TenantMembership::ROLE_OWNER]->permissions)->toBe([TenantPermission::All])
        ->and($tenant->roles()->system()->count())->toBe(count(TenantPermissionRegistry::systemRoleSlugs()));
});

test('tenant policy methods use the permission matrix', function () {
    $tenant = Tenant::factory()->create();
    app(EnsureSystemRoles::class)->handle($tenant);

    $owner = User::factory()->asTenantOwner($tenant)->create();
    $admin = User::factory()->asTenantAdmin($tenant)->create();
    $member = User::factory()->withTenant($tenant)->create();

    expect(Gate::forUser($owner)->allows('view', $tenant))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('update', $tenant))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('delete', $tenant))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('manageRoles', $tenant))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('view', $tenant))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $tenant))->toBeTrue()
        ->and(Gate::forUser($admin)->denies('delete', $tenant))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('manageMembers', $tenant))->toBeTrue()
        ->and(Gate::forUser($member)->allows('view', $tenant))->toBeTrue()
        ->and(Gate::forUser($member)->denies('update', $tenant))->toBeTrue()
        ->and(Gate::forUser($member)->denies('manageMembers', $tenant))->toBeTrue();
});

test('tenant permission gate checks named permissions with tenant context', function () {
    $tenant = Tenant::factory()->create();
    app(EnsureSystemRoles::class)->handle($tenant);

    $backOffice = User::factory()->create();

    TenantMembership::factory()
        ->for($tenant)
        ->for($backOffice, 'user')
        ->backOffice()
        ->create();

    expect(Gate::forUser($backOffice)->allows('tenant-permission', [$tenant, TenantPermission::ComplianceManage]))->toBeTrue()
        ->and(Gate::forUser($backOffice)->allows('tenant-permission', [$tenant, TenantPermission::TenantManageBranding]))->toBeFalse()
        ->and(Gate::forUser($backOffice)->allows('tenant-permission', [$tenant, 'unknown.permission']))->toBeFalse();
});

test('tenant permission checks do not leak across tenant memberships', function () {
    $firstTenant = Tenant::factory()->create();
    $secondTenant = Tenant::factory()->create();

    app(EnsureSystemRoles::class)->handle($firstTenant);
    app(EnsureSystemRoles::class)->handle($secondTenant);

    $user = User::factory()
        ->asTenantAdmin($firstTenant)
        ->withTenant($secondTenant)
        ->create();

    expect($user->hasTenantPermission($firstTenant, TenantPermission::TenantManageMembers))->toBeTrue()
        ->and($user->hasTenantPermission($secondTenant, TenantPermission::TenantManageMembers))->toBeFalse()
        ->and(Gate::forUser($user)->allows('manageMembers', $firstTenant))->toBeTrue()
        ->and(Gate::forUser($user)->denies('manageMembers', $secondTenant))->toBeTrue();
});

test('custom tenant role definitions can grant scoped permissions', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    Role::factory()
        ->for($tenant)
        ->create([
            'name' => 'Transaction Coordinator',
            'slug' => 'transaction-coordinator',
            'permissions' => [
                TenantPermission::TenantView,
                TenantPermission::DocumentsReview,
            ],
        ]);

    TenantMembership::factory()
        ->for($tenant)
        ->for($user, 'user')
        ->create(['role' => 'transaction-coordinator']);

    $membership = $user->membershipForTenant($tenant);

    expect($membership?->roleDefinition()?->slug)->toBe('transaction-coordinator')
        ->and($user->hasTenantPermission($tenant, TenantPermission::DocumentsReview))->toBeTrue()
        ->and($user->hasTenantPermission($tenant, TenantPermission::TenantManageMembers))->toBeFalse();
});

test('custom tenant role permissions do not leak to the same role slug in another tenant', function () {
    $firstTenant = Tenant::factory()->create();
    $secondTenant = Tenant::factory()->create();
    $user = User::factory()->create();

    Role::factory()
        ->for($firstTenant)
        ->create([
            'name' => 'Regional Manager',
            'slug' => 'regional-manager',
            'permissions' => [
                TenantPermission::TenantView,
                TenantPermission::TenantManageMembers,
            ],
        ]);

    TenantMembership::factory()
        ->for($firstTenant)
        ->for($user, 'user')
        ->create(['role' => 'regional-manager']);

    TenantMembership::factory()
        ->for($secondTenant)
        ->for($user, 'user')
        ->create(['role' => 'regional-manager']);

    expect($user->hasTenantPermission($firstTenant, TenantPermission::TenantManageMembers))->toBeTrue()
        ->and($user->hasTenantPermission($secondTenant, TenantPermission::TenantManageMembers))->toBeFalse()
        ->and(Gate::forUser($user)->allows('manageMembers', $firstTenant))->toBeTrue()
        ->and(Gate::forUser($user)->denies('manageMembers', $secondTenant))->toBeTrue();
});

test('known roles keep registry fallback permissions without persisted role records', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->asTenantAdmin($tenant)->create();

    expect($tenant->roles()->count())->toBe(0)
        ->and($admin->hasTenantPermission($tenant, TenantPermission::TenantManageBranding))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $tenant))->toBeTrue();
});

test('default C2C tenant includes system role definitions', function () {
    $tenant = app(DefaultTenant::class)->findOrCreate();

    expect($tenant->roles()->system()->count())->toBe(count(TenantPermissionRegistry::systemRoleSlugs()))
        ->and($tenant->roles()->where('slug', TenantMembership::ROLE_MEMBER)->first()?->permissions)->toBe([
            TenantPermission::TenantView,
            TenantPermission::TransactionsViewOwn,
            TenantPermission::DocumentsUpload,
        ]);
});
