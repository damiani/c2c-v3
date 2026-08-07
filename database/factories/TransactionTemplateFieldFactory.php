<?php

namespace Database\Factories;

use App\Models\TransactionFieldDefinition;
use App\Models\TransactionTemplate;
use App\Models\TransactionTemplateField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionTemplateField>
 */
class TransactionTemplateFieldFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $definition = TransactionFieldDefinition::factory();

        return [
            'tenant_id' => null,
            'transaction_template_id' => TransactionTemplate::factory(),
            'field_definition_id' => $definition,
            'field_key' => fake()->unique()->slug(),
            'section' => 'general',
            'label' => null,
            'unit' => null,
            'format' => null,
            'options' => null,
            'is_required' => false,
            'is_visible' => true,
            'sort_order' => fake()->numberBetween(1, 100),
            'visibility_rules' => null,
            'validation_rules' => null,
            'calculation_rules' => null,
            'date_trigger_rules' => null,
            'metadata' => [],
        ];
    }

    /**
     * Place the field on an existing template.
     */
    public function forTemplate(TransactionTemplate $template): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $template->tenant_id,
            'transaction_template_id' => $template->getKey(),
        ]);
    }

    /**
     * Use an existing field definition.
     */
    public function forDefinition(TransactionFieldDefinition $definition): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $definition->tenant_id,
            'field_definition_id' => $definition->getKey(),
            'field_key' => $definition->field_key,
        ]);
    }

    /**
     * Indicate the field is required.
     */
    public function required(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => true,
        ]);
    }
}
