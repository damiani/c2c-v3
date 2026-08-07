<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TransactionFieldDefinition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TransactionFieldDefinition>
 */
class TransactionFieldDefinitionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = fake()->words(3, true);

        return [
            'tenant_id' => null,
            'created_by_user_id' => null,
            'scope_type' => TransactionFieldDefinition::SCOPE_SYSTEM,
            'scope_id' => 0,
            'field_key' => Str::slug($label, '.'),
            'label' => Str::title($label),
            'data_type' => fake()->randomElement([
                TransactionFieldDefinition::TYPE_TEXT,
                TransactionFieldDefinition::TYPE_MONEY,
                TransactionFieldDefinition::TYPE_DATE,
                TransactionFieldDefinition::TYPE_BOOLEAN,
                TransactionFieldDefinition::TYPE_SELECT,
            ]),
            'default_unit' => null,
            'default_format' => null,
            'default_options' => null,
            'value_schema' => [],
            'is_system' => true,
            'is_active' => true,
            'metadata' => [],
        ];
    }

    /**
     * Indicate that the field is a system built-in.
     */
    public function system(?string $fieldKey = null): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => null,
            'scope_type' => TransactionFieldDefinition::SCOPE_SYSTEM,
            'scope_id' => 0,
            'field_key' => $fieldKey ?? ($attributes['field_key'] ?? fake()->unique()->slug()),
            'is_system' => true,
        ]);
    }

    /**
     * Scope the field definition to a tenant.
     */
    public function forTenant(Tenant $tenant, ?User $createdBy = null): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->getKey(),
            'created_by_user_id' => $createdBy?->getKey(),
            'scope_type' => TransactionFieldDefinition::SCOPE_TENANT,
            'scope_id' => $tenant->getKey(),
            'is_system' => false,
        ]);
    }

    /**
     * Indicate that the field stores money.
     */
    public function money(): static
    {
        return $this->state(fn (array $attributes) => [
            'data_type' => TransactionFieldDefinition::TYPE_MONEY,
            'default_format' => 'currency',
            'value_schema' => ['currency' => 'USD'],
        ]);
    }

    /**
     * Indicate that the field stores an area quantity.
     */
    public function areaQuantity(): static
    {
        return $this->state(fn (array $attributes) => [
            'data_type' => TransactionFieldDefinition::TYPE_QUANTITY,
            'default_unit' => 'square_feet',
            'default_format' => 'number',
            'value_schema' => ['unit_type' => 'area'],
        ]);
    }
}
