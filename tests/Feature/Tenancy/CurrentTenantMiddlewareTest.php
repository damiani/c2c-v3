<?php

use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    Route::middleware('web')->get('/tenant-context-probe', function (CurrentTenant $currentTenant) {
        return response()->json([
            'tenant_id' => $currentTenant->id(),
            'hidden_tenant_id' => Context::getHidden('tenant_id'),
            'tenant_slug' => Context::get('tenant_slug'),
            'locale' => App::currentLocale(),
            'session_tenant_id' => session(config('tenancy.session_key')),
        ]);
    });
});

test('web requests resolve the first tenant membership by default', function () {
    $firstTenant = Tenant::factory()->create();
    $secondTenant = Tenant::factory()->create();
    $user = User::factory()->spanishLocale()->withTenant($firstTenant)->withTenant($secondTenant)->create();

    $this->actingAs($user)
        ->get('/tenant-context-probe')
        ->assertOk()
        ->assertJson([
            'tenant_id' => $firstTenant->id,
            'hidden_tenant_id' => $firstTenant->id,
            'tenant_slug' => $firstTenant->slug,
            'locale' => 'es',
        ]);
});

test('web requests honor a valid current tenant session value', function () {
    $firstTenant = Tenant::factory()->create();
    $secondTenant = Tenant::factory()->create();
    $user = User::factory()->withTenant($firstTenant)->withTenant($secondTenant)->create();

    $this->actingAs($user)
        ->withSession([config('tenancy.session_key') => $secondTenant->id])
        ->get('/tenant-context-probe')
        ->assertOk()
        ->assertJson([
            'tenant_id' => $secondTenant->id,
            'tenant_slug' => $secondTenant->slug,
            'locale' => 'en',
        ]);
});

test('web requests ignore a session tenant outside the user memberships', function () {
    $tenant = Tenant::factory()->create();
    $outsideTenant = Tenant::factory()->create();
    $user = User::factory()->withTenant($tenant)->create();

    $this->actingAs($user)
        ->withSession([config('tenancy.session_key') => $outsideTenant->id])
        ->get('/tenant-context-probe')
        ->assertOk()
        ->assertJson([
            'tenant_id' => $tenant->id,
            'tenant_slug' => $tenant->slug,
            'session_tenant_id' => $tenant->id,
        ]);
});

test('web requests replace non numeric tenant session tampering with the fallback membership', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->withTenant($tenant)->create();

    $this->actingAs($user)
        ->withSession([config('tenancy.session_key') => 'not-a-tenant-id'])
        ->get('/tenant-context-probe')
        ->assertOk()
        ->assertJson([
            'tenant_id' => $tenant->id,
            'tenant_slug' => $tenant->slug,
            'session_tenant_id' => $tenant->id,
        ]);
});

test('web requests clear tenant session tampering when the user has no tenant memberships', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession([config('tenancy.session_key') => $tenant->id])
        ->get('/tenant-context-probe')
        ->assertOk()
        ->assertJson([
            'tenant_id' => null,
            'hidden_tenant_id' => null,
            'tenant_slug' => null,
            'session_tenant_id' => null,
        ])
        ->assertSessionMissing(config('tenancy.session_key'));
});

test('guest web requests do not resolve tenant context from a stale session value', function () {
    $tenant = Tenant::factory()->create();

    $this->withSession([config('tenancy.session_key') => $tenant->id])
        ->get('/tenant-context-probe')
        ->assertOk()
        ->assertJson([
            'tenant_id' => null,
            'hidden_tenant_id' => null,
            'tenant_slug' => null,
            'session_tenant_id' => $tenant->id,
            'locale' => 'en',
        ]);
});
