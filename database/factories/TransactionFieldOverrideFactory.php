<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TransactionFieldDefinition;
use App\Models\TransactionFieldOverride;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionFieldOverride>
 */
class TransactionFieldOverrideFactory extends Factory
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
            'field_definition_id' => TransactionFieldDefinition::factory(),
            'scope_type' => TransactionFieldOverride::SCOPE_TENANT,
            'scope_id' => fn (array $attributes) => $attributes['tenant_id'],
            'label' => fake()->words(3, true),
            'unit' => null,
            'format' => null,
            'option_labels' => null,
            'is_required' => null,
            'is_visible' => null,
            'sort_order' => null,
            'metadata' => [],
        ];
    }

    /**
     * Scope the override to the given tenant.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->getKey(),
            'scope_type' => TransactionFieldOverride::SCOPE_TENANT,
            'scope_id' => $tenant->getKey(),
        ]);
    }

    /**
     * Scope the override to a team identifier inside the tenant.
     */
    public function forTeam(Tenant $tenant, int $teamId): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->getKey(),
            'scope_type' => TransactionFieldOverride::SCOPE_TEAM,
            'scope_id' => $teamId,
        ]);
    }

    /**
     * Scope the override to a user's display preference inside the tenant.
     */
    public function forUser(Tenant $tenant, User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->getKey(),
            'scope_type' => TransactionFieldOverride::SCOPE_USER,
            'scope_id' => $user->getKey(),
        ]);
    }

    /**
     * Override an existing field definition.
     */
    public function forDefinition(TransactionFieldDefinition $definition): static
    {
        return $this->state(fn (array $attributes) => [
            'field_definition_id' => $definition->getKey(),
        ]);
    }
}
