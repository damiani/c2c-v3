<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantMembership>
 */
class TenantMembershipFactory extends Factory
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
            'role' => TenantMembership::ROLE_MEMBER,
        ];
    }

    /**
     * Indicate that the membership is for a tenant owner.
     */
    public function owner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => TenantMembership::ROLE_OWNER,
        ]);
    }

    /**
     * Indicate that the membership is for a tenant admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => TenantMembership::ROLE_ADMIN,
        ]);
    }

    /**
     * Indicate that the membership is for a broker/admin.
     */
    public function brokerAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => TenantMembership::ROLE_BROKER_ADMIN,
        ]);
    }

    /**
     * Indicate that the membership is for a coordinator or assistant.
     */
    public function coordinator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => TenantMembership::ROLE_COORDINATOR,
        ]);
    }

    /**
     * Indicate that the membership is for an agent.
     */
    public function agent(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => TenantMembership::ROLE_AGENT,
        ]);
    }

    /**
     * Indicate that the membership is for a back-office user.
     */
    public function backOffice(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => TenantMembership::ROLE_BACK_OFFICE,
        ]);
    }
}
