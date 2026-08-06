<?php

namespace Database\Factories;

use App\Models\IdentityProviderAccount;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IdentityProviderAccount>
 */
class IdentityProviderAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'provider' => IdentityProviderAccount::PROVIDER_GOOGLE,
            'provider_user_id' => fake()->unique()->uuid(),
            'email' => fake()->safeEmail(),
            'metadata' => [],
            'linked_at' => now(),
        ];
    }

    /**
     * Pin the external identity to a tenant.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Pin the external identity to a user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }

    /**
     * Indicate that the identity comes from Microsoft.
     */
    public function microsoft(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => IdentityProviderAccount::PROVIDER_MICROSOFT,
        ]);
    }

    /**
     * Indicate that the identity comes from an MLS association SSO.
     */
    public function mls(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => IdentityProviderAccount::PROVIDER_MLS,
        ]);
    }
}
