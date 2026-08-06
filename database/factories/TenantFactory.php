<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'display_name' => null,
            'slug' => Str::slug($name),
            'status' => Tenant::STATUS_ACTIVE,
            'logo_path' => null,
            'primary_color' => '#2563eb',
            'accent_color' => '#16a34a',
            'sender_name' => null,
            'sender_email' => null,
            'default_locale' => 'en',
            'supported_locales' => ['en', 'es'],
            'enabled_integrations' => [],
        ];
    }

    /**
     * Indicate that the tenant is the default C2C retail tenant.
     */
    public function c2c(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'C2C',
            'display_name' => 'Contract2Close',
            'slug' => 'c2c',
            'status' => Tenant::STATUS_ACTIVE,
            'supported_locales' => ['en', 'es'],
        ]);
    }

    /**
     * Indicate that the tenant represents an MLS or association partner.
     */
    public function mlsAssociation(?string $name = null): static
    {
        return $this->state(function (array $attributes) use ($name) {
            $tenantName = $name ?? fake()->city().' Association of Realtors';

            return [
                'name' => $tenantName,
                'display_name' => $tenantName,
                'slug' => Str::slug($tenantName),
                'status' => Tenant::STATUS_ACTIVE,
            ];
        });
    }

    /**
     * Indicate that the tenant has custom branding settings.
     */
    public function branded(): static
    {
        return $this->state(fn (array $attributes) => [
            'display_name' => 'Chicago REALTORS',
            'logo_path' => 'tenant-logos/chicago-realtors.svg',
            'primary_color' => '#0f766e',
            'accent_color' => '#f59e0b',
            'sender_name' => 'Chicago REALTORS Contract2Close',
            'sender_email' => 'transactions@example.test',
            'enabled_integrations' => ['mls-feed', 'forms-library'],
        ]);
    }

    /**
     * Indicate that the tenant is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Tenant::STATUS_INACTIVE,
        ]);
    }

    /**
     * Create a membership for the tenant after it is created.
     */
    public function withMembership(?User $user = null, string $role = TenantMembership::ROLE_MEMBER): static
    {
        return $this->afterCreating(function (Tenant $tenant) use ($user, $role): void {
            TenantMembership::factory()
                ->for($tenant)
                ->for($user ?? User::factory()->create(), 'user')
                ->create(['role' => $role]);
        });
    }

    /**
     * Create an owner membership for the tenant after it is created.
     */
    public function withOwner(?User $user = null): static
    {
        return $this->withMembership($user, TenantMembership::ROLE_OWNER);
    }
}
