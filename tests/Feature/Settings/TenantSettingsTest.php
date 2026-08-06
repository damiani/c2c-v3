<?php

use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function setCurrentTenantForSettings(Tenant $tenant): void
{
    app(CurrentTenant::class)->set($tenant);
}

test('tenant admins can view tenant settings', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->asTenantAdmin($tenant)->create();

    $this->actingAs($admin)
        ->withSession([config('tenancy.session_key') => $tenant->id])
        ->get(route('tenant.edit'))
        ->assertOk()
        ->assertSee('Tenant settings');
});

test('tenant members cannot view tenant settings', function () {
    $tenant = Tenant::factory()->create();
    $member = User::factory()->withTenant($tenant)->create();

    $this->actingAs($member)
        ->withSession([config('tenancy.session_key') => $tenant->id])
        ->get(route('tenant.edit'))
        ->assertForbidden();
});

test('tenant settings nav item is visible only to tenant settings managers', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->asTenantAdmin($tenant)->create();
    $member = User::factory()->withTenant($tenant)->create();

    $this->actingAs($admin)
        ->withSession([config('tenancy.session_key') => $tenant->id])
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Tenant');

    $this->actingAs($member)
        ->withSession([config('tenancy.session_key') => $tenant->id])
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertDontSee('Tenant');
});

test('tenant admins can update branding locales and visible integrations', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->asTenantAdmin($tenant)->create();

    setCurrentTenantForSettings($tenant);

    Livewire::actingAs($admin)
        ->test('pages::settings.tenant')
        ->set('display_name', 'Chicago Association of Realtors')
        ->set('logo_path', 'tenant-logos/chicago.svg')
        ->set('primary_color', '#123ABC')
        ->set('accent_color', '#0f766e')
        ->set('sender_name', 'Chicago Transactions')
        ->set('sender_email', 'transactions@example.test')
        ->set('default_locale', 'es')
        ->set('supported_locales', ['en', 'es'])
        ->set('enabled_integrations', ['mls-feed', 'forms-library'])
        ->call('updateTenantSettings')
        ->assertHasNoErrors();

    $tenant->refresh();

    expect($tenant->display_name)->toBe('Chicago Association of Realtors')
        ->and($tenant->logo_path)->toBe('tenant-logos/chicago.svg')
        ->and($tenant->primary_color)->toBe('#123abc')
        ->and($tenant->accent_color)->toBe('#0f766e')
        ->and($tenant->sender_name)->toBe('Chicago Transactions')
        ->and($tenant->sender_email)->toBe('transactions@example.test')
        ->and($tenant->default_locale)->toBe('es')
        ->and($tenant->supported_locales)->toBe(['en', 'es'])
        ->and($tenant->enabled_integrations)->toBe(['mls-feed', 'forms-library']);
});

test('tenant settings updates are scoped to the current tenant', function () {
    $firstTenant = Tenant::factory()->create(['display_name' => 'First Tenant']);
    $secondTenant = Tenant::factory()->branded()->create(['display_name' => 'Second Tenant']);
    $admin = User::factory()
        ->asTenantAdmin($firstTenant)
        ->asTenantAdmin($secondTenant)
        ->create();

    setCurrentTenantForSettings($secondTenant);

    Livewire::actingAs($admin)
        ->test('pages::settings.tenant')
        ->set('display_name', 'Updated Second Tenant')
        ->set('enabled_integrations', ['calendar-sync'])
        ->call('updateTenantSettings')
        ->assertHasNoErrors();

    expect($firstTenant->refresh()->display_name)->toBe('First Tenant')
        ->and($firstTenant->enabled_integrations)->toBe([])
        ->and($secondTenant->refresh()->display_name)->toBe('Updated Second Tenant')
        ->and($secondTenant->enabled_integrations)->toBe(['calendar-sync']);
});

test('tenant members cannot update tenant settings', function () {
    $tenant = Tenant::factory()->create(['display_name' => 'Original Tenant']);
    $member = User::factory()->withTenant($tenant)->create();

    setCurrentTenantForSettings($tenant);

    Livewire::actingAs($member)
        ->test('pages::settings.tenant')
        ->assertForbidden();

    expect($tenant->refresh()->display_name)->toBe('Original Tenant');
});

test('tenant settings validate colors and integrations', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->asTenantAdmin($tenant)->create();

    setCurrentTenantForSettings($tenant);

    Livewire::actingAs($admin)
        ->test('pages::settings.tenant')
        ->set('primary_color', 'blue')
        ->set('enabled_integrations', ['unknown-integration'])
        ->call('updateTenantSettings')
        ->assertHasErrors([
            'primary_color',
            'enabled_integrations.0',
        ]);
});

test('tenant default locale must be supported by the tenant', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->asTenantAdmin($tenant)->create();

    setCurrentTenantForSettings($tenant);

    Livewire::actingAs($admin)
        ->test('pages::settings.tenant')
        ->set('default_locale', 'es')
        ->set('supported_locales', ['en'])
        ->call('updateTenantSettings')
        ->assertHasErrors(['default_locale']);
});
