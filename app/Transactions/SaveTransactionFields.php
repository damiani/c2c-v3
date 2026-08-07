<?php

namespace App\Transactions;

use App\Models\Transaction;
use App\Models\TransactionFieldDefinition;
use App\Models\TransactionFieldValue;
use App\Models\User;
use Illuminate\Support\Collection;

class SaveTransactionFields
{
    /**
     * Persist resolved dynamic field input values for a transaction.
     *
     * @param  Collection<int, array<string, mixed>>  $resolvedFields
     * @param  array<int|string, mixed>  $fieldInputs
     */
    public function handle(Transaction $transaction, Collection $resolvedFields, array $fieldInputs, User $user): void
    {
        foreach ($resolvedFields as $field) {
            $definitionId = (int) $field['field_definition_id'];
            $value = $fieldInputs[$definitionId] ?? null;

            if ($this->isBlank($value, (string) $field['data_type'])) {
                $transaction->fieldValues()
                    ->where('field_definition_id', $definitionId)
                    ->delete();

                continue;
            }

            $fieldValue = TransactionFieldValue::query()->firstOrNew([
                'transaction_id' => $transaction->id,
                'field_definition_id' => $definitionId,
            ]);

            $fieldValue->fill([
                'tenant_id' => $transaction->tenant_id,
                'template_field_id' => $field['template_field_id'],
                'updated_by_user_id' => $user->id,
                'field_key' => $field['field_key'],
                'data_type' => $field['data_type'],
                'source_type' => TransactionFieldValue::SOURCE_USER,
                'metadata' => [
                    'label' => $field['label'],
                    'format' => $field['format'],
                ],
            ]);

            $fieldValue->setTypedValue(
                value: $this->normalizedValue($value, (string) $field['data_type']),
                currency: $this->currencyForField($field),
                unit: $field['unit'] ?? null,
            );

            $fieldValue->save();
        }
    }

    private function isBlank(mixed $value, string $dataType): bool
    {
        if ($dataType === TransactionFieldDefinition::TYPE_BOOLEAN) {
            return $value === null;
        }

        return $value === null || $value === '';
    }

    private function normalizedValue(mixed $value, string $dataType): mixed
    {
        if ($dataType === TransactionFieldDefinition::TYPE_JSON && is_string($value)) {
            return json_decode($value, true) ?? ['value' => $value];
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function currencyForField(array $field): ?string
    {
        if ($field['data_type'] !== TransactionFieldDefinition::TYPE_MONEY) {
            return null;
        }

        $schema = $field['value_schema'] ?? [];

        return is_array($schema) ? (string) ($schema['currency'] ?? 'USD') : 'USD';
    }
}
