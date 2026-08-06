<?php

use App\Models\IdentityProviderAccount;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\DefaultTenant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('tenants expose branding and localization defaults', function () {
    $tenant = Tenant::factory()->branded()->create([
        'default_locale' => 'es',
        'supported_locales' => ['en', 'es'],
    ]);

    expect($tenant->brandedName())->toBe('Chicago REALTORS')
        ->and($tenant->primary_color)->toBe('#0f766e')
        ->and($tenant->accent_color)->toBe('#f59e0b')
        ->and($tenant->sender_name)->toBe('Chicago REALTORS Contract2Close')
        ->and($tenant->enabled_integrations)->toBe(['mls-feed', 'forms-library'])
        ->and($tenant->supportsLocale('es'))->toBeTrue()
        ->and($tenant->supportsLocale('fr'))->toBeFalse();
});

test('default C2C tenant is created idempotently with supported locales', function () {
    $defaultTenant = app(DefaultTenant::class);

    $first = $defaultTenant->findOrCreate();
    $second = $defaultTenant->findOrCreate();

    expect($first->is($second))->toBeTrue()
        ->and($first->slug)->toBe('c2c')
        ->and($first->supported_locales)->toBe(['en', 'es']);
});

test('identity provider links are tenant scoped for future SSO', function () {
    $user = User::factory()->create();
    $firstTenant = Tenant::factory()->create();
    $secondTenant = Tenant::factory()->create();

    $firstLink = IdentityProviderAccount::factory()
        ->forTenant($firstTenant)
        ->forUser($user)
        ->mls()
        ->create(['provider_user_id' => 'mls-user-123']);

    $secondLink = IdentityProviderAccount::factory()
        ->forTenant($secondTenant)
        ->forUser($user)
        ->mls()
        ->create(['provider_user_id' => 'mls-user-123']);

    expect($firstLink->tenant->is($firstTenant))->toBeTrue()
        ->and($firstLink->user->is($user))->toBeTrue()
        ->and($secondLink->tenant->is($secondTenant))->toBeTrue()
        ->and(IdentityProviderAccount::forTenant($firstTenant)->count())->toBe(1)
        ->and(IdentityProviderAccount::forTenant($secondTenant)->count())->toBe(1);
});

test('identity provider links cannot duplicate provider identities inside a tenant', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    IdentityProviderAccount::factory()
        ->forTenant($tenant)
        ->forUser($user)
        ->create(['provider_user_id' => 'google-user-123']);

    expect(fn () => IdentityProviderAccount::factory()
        ->forTenant($tenant)
        ->forUser($user)
        ->create(['provider_user_id' => 'google-user-123']))
        ->toThrow(QueryException::class);
});
