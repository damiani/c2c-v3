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
            'slug' => Str::slug($name),
            'status' => Tenant::STATUS_ACTIVE,
        ];
    }

    /**
     * Indicate that the tenant is the default C2C retail tenant.
     */
    public function c2c(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'C2C',
            'slug' => 'c2c',
            'status' => Tenant::STATUS_ACTIVE,
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
                'slug' => Str::slug($tenantName),
                'status' => Tenant::STATUS_ACTIVE,
            ];
        });
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
