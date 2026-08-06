<?php

use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('new users are registered into the default C2C tenant', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Jordan Broker',
        'email' => 'jordan@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'jordan@example.test')->firstOrFail();
    $tenant = Tenant::query()->where('slug', 'c2c')->firstOrFail();

    expect($user->tenantMemberships)
        ->toHaveCount(1)
        ->and($user->tenantMemberships->first()->tenant->is($tenant))->toBeTrue()
        ->and($user->tenantMemberships->first()->role)->toBe(TenantMembership::ROLE_MEMBER)
        ->and($user->locale)->toBe('en');

    $this->assertAuthenticatedAs($user);
});

test('registration accepts supported locale preferences', function () {
    $this->post(route('register.store'), [
        'name' => 'Sofia Broker',
        'email' => 'sofia@example.test',
        'locale' => 'es',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    expect(User::query()->where('email', 'sofia@example.test')->firstOrFail()->locale)->toBe('es');
});

test('registration rejects unsupported locale preferences', function () {
    $this->post(route('register.store'), [
        'name' => 'Unsupported Locale',
        'email' => 'unsupported@example.test',
        'locale' => 'fr',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('locale');

    expect(User::query()->where('email', 'unsupported@example.test')->exists())->toBeFalse();
});
