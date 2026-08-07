<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionFieldDefinition;
use App\Models\TransactionFieldValue;
use App\Models\TransactionTemplateField;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionFieldValue>
 */
class TransactionFieldValueFactory extends Factory
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
            'transaction_id' => Transaction::factory(),
            'field_definition_id' => TransactionFieldDefinition::factory(),
            'template_field_id' => null,
            'updated_by_user_id' => null,
            'field_key' => fake()->unique()->slug(),
            'data_type' => TransactionFieldDefinition::TYPE_TEXT,
            'value_text' => fake()->sentence(),
            'value_boolean' => null,
            'value_integer' => null,
            'value_decimal' => null,
            'value_money_amount' => null,
            'value_currency' => null,
            'value_date' => null,
            'value_datetime' => null,
            'value_json' => null,
            'value_unit' => null,
            'selected_option_key' => null,
            'source_type' => TransactionFieldValue::SOURCE_USER,
            'metadata' => [],
        ];
    }

    /**
     * Scope the value to a transaction.
     */
    public function forTransaction(Transaction $transaction): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $transaction->tenant_id,
            'transaction_id' => $transaction->getKey(),
        ]);
    }

    /**
     * Use a field definition for this value.
     */
    public function forDefinition(TransactionFieldDefinition $definition): static
    {
        return $this->state(fn (array $attributes) => [
            'field_definition_id' => $definition->getKey(),
            'field_key' => $definition->field_key,
            'data_type' => $definition->data_type,
            'value_unit' => $definition->default_unit,
        ]);
    }

    /**
     * Use a template field placement for this value.
     */
    public function forTemplateField(TransactionTemplateField $templateField): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $templateField->tenant_id ?? ($attributes['tenant_id'] ?? null),
            'template_field_id' => $templateField->getKey(),
            'field_definition_id' => $templateField->field_definition_id,
            'field_key' => $templateField->field_key,
        ]);
    }

    /**
     * Assign the user that last updated this value.
     */
    public function updatedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'updated_by_user_id' => $user->getKey(),
        ]);
    }

    /**
     * Store a money value.
     */
    public function money(string|int|float $amount = 625000, string $currency = 'USD'): static
    {
        return $this->state(fn (array $attributes) => [
            'data_type' => TransactionFieldDefinition::TYPE_MONEY,
            'value_text' => null,
            'value_money_amount' => (string) $amount,
            'value_currency' => $currency,
        ]);
    }

    /**
     * Store a selected option value.
     */
    public function select(string $optionKey): static
    {
        return $this->state(fn (array $attributes) => [
            'data_type' => TransactionFieldDefinition::TYPE_SELECT,
            'value_text' => null,
            'selected_option_key' => $optionKey,
        ]);
    }
}
