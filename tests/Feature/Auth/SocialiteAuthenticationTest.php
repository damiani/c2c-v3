<?php

use App\Actions\IdentityProviders\LinkIdentityProviderAccount;
use App\IdentityProviders\ExternalIdentity;
use App\Models\IdentityProviderAccount;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Tenancy\CurrentTenant;
use App\Tenancy\DefaultTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('guests can start google authentication', function () {
    Socialite::fake('google');

    $this->get(route('sso.redirect', 'google'))
        ->assertRedirect();
});

test('google callback creates a default tenant user and identity link', function () {
    $tenant = app(DefaultTenant::class)->findOrCreate();

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-123',
        'name' => 'Google Broker',
        'email' => 'google-broker@example.test',
        'approvedScopes' => ['openid', 'email', 'profile'],
    ]));

    $this->withSession([
        config('sso.session.intent') => 'login',
        config('sso.session.tenant_id') => $tenant->id,
    ])
        ->get(route('sso.callback', 'google'))
        ->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'google-broker@example.test')->firstOrFail();

    $this->assertAuthenticatedAs($user);
    $this->assertTrue($user->belongsToTenant($tenant));
    $this->assertDatabaseHas('identity_provider_accounts', [
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'provider' => IdentityProviderAccount::PROVIDER_GOOGLE,
        'provider_user_id' => 'google-123',
        'email' => 'google-broker@example.test',
    ]);
});

test('google callback logs in an existing linked user for the tenant', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->withTenant($tenant)->create();

    IdentityProviderAccount::factory()
        ->forTenant($tenant)
        ->forUser($user)
        ->create(['provider_user_id' => 'google-123']);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-123',
        'name' => $user->name,
        'email' => $user->email,
    ]));

    $this->withSession([
        config('sso.session.intent') => 'login',
        config('sso.session.tenant_id') => $tenant->id,
    ])
        ->get(route('sso.callback', 'google'))
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('identity provider ids are isolated by tenant', function () {
    $defaultTenant = app(DefaultTenant::class)->findOrCreate();
    $outsideTenant = Tenant::factory()->create();
    $outsideUser = User::factory()->withTenant($outsideTenant)->create();

    IdentityProviderAccount::factory()
        ->forTenant($outsideTenant)
        ->forUser($outsideUser)
        ->create(['provider_user_id' => 'google-123']);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-123',
        'name' => 'Default Tenant Broker',
        'email' => 'default-tenant-broker@example.test',
    ]));

    $this->withSession([
        config('sso.session.intent') => 'login',
        config('sso.session.tenant_id') => $defaultTenant->id,
    ])
        ->get(route('sso.callback', 'google'))
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertFalse(auth()->user()->is($outsideUser));
    $this->assertDatabaseHas('identity_provider_accounts', [
        'tenant_id' => $defaultTenant->id,
        'provider' => IdentityProviderAccount::PROVIDER_GOOGLE,
        'provider_user_id' => 'google-123',
        'email' => 'default-tenant-broker@example.test',
    ]);
    $this->assertDatabaseHas('identity_provider_accounts', [
        'tenant_id' => $outsideTenant->id,
        'user_id' => $outsideUser->id,
        'provider' => IdentityProviderAccount::PROVIDER_GOOGLE,
        'provider_user_id' => 'google-123',
    ]);
});

test('google callback adds a matching email user to the tenant', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['email' => 'shared-email@example.test']);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-123',
        'name' => $user->name,
        'email' => 'shared-email@example.test',
    ]));

    $this->withSession([
        config('sso.session.intent') => 'login',
        config('sso.session.tenant_id') => $tenant->id,
    ])
        ->get(route('sso.callback', 'google'))
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('tenant_memberships', [
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'role' => TenantMembership::ROLE_MEMBER,
    ]);
});

test('authenticated users can link google accounts to the current tenant', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->withTenant($tenant)->create();

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-123',
        'name' => $user->name,
        'email' => $user->email,
    ]));

    $this->actingAs($user)
        ->withSession([
            config('sso.session.intent') => 'link',
            config('sso.session.tenant_id') => $tenant->id,
            config('tenancy.session_key') => $tenant->id,
        ])
        ->get(route('sso.callback', 'google'))
        ->assertRedirect(route('security.edit'));

    $this->assertDatabaseHas('identity_provider_accounts', [
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'provider' => IdentityProviderAccount::PROVIDER_GOOGLE,
        'provider_user_id' => 'google-123',
    ]);
});

test('identity provider links cannot be reused by another user in the same tenant', function () {
    $tenant = Tenant::factory()->create();
    $linkedUser = User::factory()->withTenant($tenant)->create();
    $otherUser = User::factory()->withTenant($tenant)->create();

    IdentityProviderAccount::factory()
        ->forTenant($tenant)
        ->forUser($linkedUser)
        ->create(['provider_user_id' => 'google-123']);

    app(LinkIdentityProviderAccount::class)->handle($tenant, $otherUser, new ExternalIdentity(
        provider: IdentityProviderAccount::PROVIDER_GOOGLE,
        providerUserId: 'google-123',
        email: $otherUser->email,
        name: $otherUser->name,
    ));
})->throws(ValidationException::class);

test('users can unlink connected accounts from security settings', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->withTenant($tenant)->create();
    $account = IdentityProviderAccount::factory()
        ->forTenant($tenant)
        ->forUser($user)
        ->create();

    app(CurrentTenant::class)->set($tenant);

    $this->actingAs($user)
        ->withSession([
            config('tenancy.session_key') => $tenant->id,
        ]);

    Livewire::test('pages::settings.security')
        ->call('unlinkIdentityProviderAccount', $account->id)
        ->assertHasNoErrors();

    $this->assertModelMissing($account);
});
