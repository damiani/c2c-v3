<?php

namespace App\Concerns\Transactions;

use App\Actions\Transactions\SeedDefaultTransactionTemplates;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionFieldDefinition;
use App\Models\TransactionFieldValue;
use App\Models\TransactionTemplate;
use App\Tenancy\CurrentTenant;
use App\TransactionFields\TransactionFieldResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

trait ManagesTransactionFieldInputs
{
    protected function currentTenant(): ?Tenant
    {
        return app(CurrentTenant::class)->get();
    }

    /**
     * @return Collection<int, TransactionTemplate>
     */
    protected function availableTemplates(): Collection
    {
        $tenant = $this->currentTenant();

        app(SeedDefaultTransactionTemplates::class)();

        if ($tenant === null) {
            return collect();
        }

        return TransactionTemplate::query()
            ->with('fields.definition')
            ->where('status', TransactionTemplate::STATUS_ACTIVE)
            ->where(function ($query) use ($tenant): void {
                $query
                    ->where('scope_type', TransactionTemplate::SCOPE_SYSTEM)
                    ->orWhere(function ($query) use ($tenant): void {
                        $query
                            ->where('scope_type', TransactionTemplate::SCOPE_TENANT)
                            ->where('tenant_id', $tenant->id)
                            ->where('scope_id', $tenant->id);
                    });
            })
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    public function selectedTemplate(): ?TransactionTemplate
    {
        if ($this->templateId === null) {
            return null;
        }

        return $this->availableTemplates()->firstWhere('id', (int) $this->templateId);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function resolvedFields(?TransactionTemplate $template = null): Collection
    {
        $tenant = $this->currentTenant();
        $template ??= $this->selectedTemplate();

        if ($tenant === null || $template === null) {
            return collect();
        }

        return app(TransactionFieldResolver::class)
            ->resolveForTemplate($template, $tenant, null, auth()->user())
            ->filter(fn (array $field): bool => (bool) $field['is_visible'])
            ->sortBy('sort_order')
            ->values();
    }

    protected function initializeTemplateSelection(?Transaction $transaction = null): void
    {
        $templates = $this->availableTemplates();

        if ($transaction?->transaction_template_id !== null) {
            $this->templateId = $transaction->transaction_template_id;
        } elseif ($this->templateId === null) {
            $this->templateId = $templates->first()?->id;
        }

        $this->initializeFieldInputs($transaction);
    }

    protected function initializeFieldInputs(?Transaction $transaction = null): void
    {
        $existingValues = $transaction
            ? $transaction->fieldValues()->get()->keyBy('field_definition_id')
            : collect();

        $this->fieldInputs = $this->resolvedFields()
            ->mapWithKeys(function (array $field) use ($existingValues): array {
                $definitionId = (int) $field['field_definition_id'];
                $existingValue = $existingValues->get($definitionId);

                return [
                    $definitionId => $existingValue instanceof TransactionFieldValue
                        ? $this->inputValueFromStoredValue($existingValue)
                        : $this->defaultInputValue($field['data_type']),
                ];
            })
            ->all();
    }

    protected function defaultInputValue(string $dataType): mixed
    {
        return match ($dataType) {
            TransactionFieldDefinition::TYPE_BOOLEAN => false,
            default => '',
        };
    }

    protected function inputValueFromStoredValue(TransactionFieldValue $value): mixed
    {
        return match ($value->data_type) {
            TransactionFieldDefinition::TYPE_BOOLEAN => (bool) $value->value_boolean,
            TransactionFieldDefinition::TYPE_MONEY => $value->value_money_amount,
            TransactionFieldDefinition::TYPE_DATE => $value->value_date?->format('Y-m-d') ?? '',
            TransactionFieldDefinition::TYPE_DATETIME => $value->value_datetime?->format('Y-m-d\TH:i') ?? '',
            TransactionFieldDefinition::TYPE_SELECT => $value->selected_option_key ?? '',
            TransactionFieldDefinition::TYPE_INTEGER => $value->value_integer,
            TransactionFieldDefinition::TYPE_DECIMAL,
            TransactionFieldDefinition::TYPE_PERCENTAGE,
            TransactionFieldDefinition::TYPE_QUANTITY => $value->value_decimal,
            TransactionFieldDefinition::TYPE_JSON => $value->value_json === null ? '' : json_encode($value->value_json),
            default => $value->value_text ?? '',
        };
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function dynamicFieldRules(): array
    {
        return $this->resolvedFields()
            ->mapWithKeys(function (array $field): array {
                $definitionId = (int) $field['field_definition_id'];
                $rules = [(bool) $field['is_required'] ? 'required' : 'nullable'];

                return [
                    "fieldInputs.{$definitionId}" => array_merge($rules, $this->rulesForDataType($field)),
                ];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $field
     * @return list<mixed>
     */
    protected function rulesForDataType(array $field): array
    {
        return match ($field['data_type']) {
            TransactionFieldDefinition::TYPE_LONG_TEXT => ['string', 'max:5000'],
            TransactionFieldDefinition::TYPE_MONEY,
            TransactionFieldDefinition::TYPE_DECIMAL,
            TransactionFieldDefinition::TYPE_PERCENTAGE,
            TransactionFieldDefinition::TYPE_QUANTITY => ['numeric'],
            TransactionFieldDefinition::TYPE_INTEGER => ['integer'],
            TransactionFieldDefinition::TYPE_DATE,
            TransactionFieldDefinition::TYPE_DATETIME => ['date'],
            TransactionFieldDefinition::TYPE_BOOLEAN => ['boolean'],
            TransactionFieldDefinition::TYPE_SELECT => ['string', Rule::in(array_keys($this->normalizedOptions($field)))],
            TransactionFieldDefinition::TYPE_JSON => ['json'],
            default => ['string', 'max:500'],
        };
    }

    /**
     * @return array<string, string>
     */
    protected function dynamicFieldAttributes(): array
    {
        return $this->resolvedFields()
            ->mapWithKeys(fn (array $field): array => [
                'fieldInputs.'.(int) $field['field_definition_id'] => (string) $field['label'],
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, string>
     */
    protected function normalizedOptions(array $field): array
    {
        $options = $field['options'] ?? [];
        $optionLabels = $field['option_labels'] ?? [];

        if (! is_array($options)) {
            return [];
        }

        return collect($options)
            ->mapWithKeys(function (mixed $option, string|int $key) use ($optionLabels): array {
                if (is_array($option)) {
                    $optionKey = (string) ($option['key'] ?? $key);
                    $label = (string) ($optionLabels[$optionKey] ?? $option['label'] ?? Str::headline($optionKey));

                    return [$optionKey => $label];
                }

                $optionKey = is_string($key) ? $key : (string) $option;
                $label = (string) ($optionLabels[$optionKey] ?? $option);

                return [$optionKey => $label];
            })
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function fieldSchemaSnapshot(TransactionTemplate $template): array
    {
        return [
            'template_id' => $template->id,
            'template_key' => $template->template_key,
            'template_version' => $template->version,
            'resolved_at' => now()->toISOString(),
            'fields' => $this->resolvedFields($template)
                ->map(fn (array $field): array => [
                    'field_definition_id' => $field['field_definition_id'],
                    'template_field_id' => $field['template_field_id'],
                    'field_key' => $field['field_key'],
                    'data_type' => $field['data_type'],
                    'label' => $field['label'],
                    'unit' => $field['unit'],
                    'format' => $field['format'],
                    'is_required' => $field['is_required'],
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{property_address: string|null, property_data: array<string, mixed>}
     */
    protected function summaryDataFromInputs(): array
    {
        $fields = $this->resolvedFields();

        return [
            'property_address' => $this->inputForFieldKey($fields, 'property.address'),
            'property_data' => [
                'city' => $this->inputForFieldKey($fields, 'property.city'),
                'state' => $this->inputForFieldKey($fields, 'property.state'),
            ],
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $fields
     */
    protected function inputForFieldKey(Collection $fields, string $fieldKey): ?string
    {
        $field = $fields->firstWhere('field_key', $fieldKey);

        if ($field === null) {
            return null;
        }

        $value = $this->fieldInputs[(int) $field['field_definition_id']] ?? null;

        return $value === null || $value === '' ? null : (string) $value;
    }

    /**
     * @return Collection<string, Collection<int, array<string, mixed>>>
     */
    protected function groupedResolvedFields(): Collection
    {
        return $this->resolvedFields()
            ->groupBy(fn (array $field): string => (string) $field['section']);
    }
}
