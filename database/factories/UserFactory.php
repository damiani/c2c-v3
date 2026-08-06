<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'locale' => config('app.locale'),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * Indicate that the user prefers Spanish.
     */
    public function spanishLocale(): static
    {
        return $this->state(fn (array $attributes) => [
            'locale' => 'es',
        ]);
    }

    /**
     * Create a tenant membership for the user after they are created.
     */
    public function withTenant(?Tenant $tenant = null, string $role = TenantMembership::ROLE_MEMBER): static
    {
        return $this->afterCreating(function (User $user) use ($tenant, $role): void {
            TenantMembership::factory()
                ->for($tenant ?? Tenant::factory()->create())
                ->for($user, 'user')
                ->create(['role' => $role]);
        });
    }

    /**
     * Create a tenant owner membership for the user after they are created.
     */
    public function asTenantOwner(?Tenant $tenant = null): static
    {
        return $this->withTenant($tenant, TenantMembership::ROLE_OWNER);
    }

    /**
     * Create a tenant admin membership for the user after they are created.
     */
    public function asTenantAdmin(?Tenant $tenant = null): static
    {
        return $this->withTenant($tenant, TenantMembership::ROLE_ADMIN);
    }
}
