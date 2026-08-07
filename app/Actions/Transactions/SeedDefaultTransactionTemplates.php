<?php

namespace App\Actions\Transactions;

use App\Models\Transaction;
use App\Models\TransactionFieldDefinition;
use App\Models\TransactionTemplate;
use App\Models\TransactionTemplateField;

class SeedDefaultTransactionTemplates
{
    /**
     * Seed the system residential sale template and its stable built-in fields.
     */
    public function __invoke(): TransactionTemplate
    {
        $template = TransactionTemplate::query()->updateOrCreate([
            'scope_type' => TransactionTemplate::SCOPE_SYSTEM,
            'scope_id' => 0,
            'template_key' => TransactionTemplate::TEMPLATE_RESIDENTIAL_SALE,
            'version' => 1,
        ], [
            'tenant_id' => null,
            'created_by_user_id' => null,
            'name' => 'Residential Sale',
            'transaction_type' => Transaction::TYPE_RESIDENTIAL_SALE,
            'description' => 'Default residential sale transaction template for core deal details and dates.',
            'status' => TransactionTemplate::STATUS_ACTIVE,
            'is_default' => true,
            'metadata' => ['source' => 'system_seed'],
        ]);

        foreach ($this->residentialSaleFields() as $index => $field) {
            $definition = TransactionFieldDefinition::query()->updateOrCreate([
                'scope_type' => TransactionFieldDefinition::SCOPE_SYSTEM,
                'scope_id' => 0,
                'field_key' => $field['field_key'],
            ], [
                'tenant_id' => null,
                'created_by_user_id' => null,
                'label' => $field['label'],
                'data_type' => $field['data_type'],
                'default_unit' => $field['unit'] ?? null,
                'default_format' => $field['format'] ?? null,
                'default_options' => $field['options'] ?? null,
                'value_schema' => $field['value_schema'] ?? [],
                'is_system' => true,
                'is_active' => true,
                'metadata' => ['source' => 'system_seed'],
            ]);

            TransactionTemplateField::query()->updateOrCreate([
                'transaction_template_id' => $template->id,
                'field_definition_id' => $definition->id,
            ], [
                'tenant_id' => null,
                'field_key' => $definition->field_key,
                'section' => $field['section'],
                'label' => $field['template_label'] ?? null,
                'unit' => $field['template_unit'] ?? null,
                'format' => $field['template_format'] ?? null,
                'options' => $field['template_options'] ?? null,
                'is_required' => $field['required'] ?? false,
                'is_visible' => true,
                'sort_order' => ($index + 1) * 10,
                'visibility_rules' => $field['visibility_rules'] ?? null,
                'validation_rules' => $field['validation_rules'] ?? null,
                'calculation_rules' => $field['calculation_rules'] ?? null,
                'date_trigger_rules' => $field['date_trigger_rules'] ?? null,
                'metadata' => ['source' => 'system_seed'],
            ]);
        }

        return $template->fresh(['fields.definition']);
    }

    /**
     * Built-in residential sale fields inspired by the v2 property/deal schema.
     *
     * @return array<int, array<string, mixed>>
     */
    private function residentialSaleFields(): array
    {
        return [
            [
                'field_key' => 'property.address',
                'label' => 'Property Address',
                'data_type' => TransactionFieldDefinition::TYPE_TEXT,
                'section' => 'property',
                'required' => true,
            ],
            [
                'field_key' => 'property.city',
                'label' => 'City',
                'data_type' => TransactionFieldDefinition::TYPE_TEXT,
                'section' => 'property',
                'required' => true,
            ],
            [
                'field_key' => 'property.state',
                'label' => 'State',
                'data_type' => TransactionFieldDefinition::TYPE_TEXT,
                'section' => 'property',
                'required' => true,
            ],
            [
                'field_key' => 'property.area',
                'label' => 'Property Area',
                'data_type' => TransactionFieldDefinition::TYPE_QUANTITY,
                'section' => 'property',
                'unit' => 'square_feet',
                'format' => 'number',
                'value_schema' => ['unit_type' => 'area'],
            ],
            [
                'field_key' => 'deal.purchase_price',
                'label' => 'Purchase Price',
                'data_type' => TransactionFieldDefinition::TYPE_MONEY,
                'section' => 'deal',
                'format' => 'currency',
                'value_schema' => ['currency' => 'USD'],
                'required' => true,
            ],
            [
                'field_key' => 'deal.loan_amount',
                'label' => 'Loan Amount',
                'data_type' => TransactionFieldDefinition::TYPE_MONEY,
                'section' => 'deal',
                'format' => 'currency',
                'value_schema' => ['currency' => 'USD'],
            ],
            [
                'field_key' => 'deal.contract_acceptance_date',
                'label' => 'Contract Acceptance Date',
                'data_type' => TransactionFieldDefinition::TYPE_DATE,
                'section' => 'dates',
                'format' => 'date_medium',
                'required' => true,
            ],
            [
                'field_key' => 'deal.closing_date',
                'label' => 'Closing Date',
                'data_type' => TransactionFieldDefinition::TYPE_DATE,
                'section' => 'dates',
                'format' => 'date_medium',
                'date_trigger_rules' => [
                    ['trigger' => 'reminder', 'offset_days' => -7],
                    ['trigger' => 'late_status', 'offset_days' => 1],
                ],
            ],
            [
                'field_key' => 'inspection.is_scheduled',
                'label' => 'Inspection Scheduled',
                'data_type' => TransactionFieldDefinition::TYPE_BOOLEAN,
                'section' => 'inspection',
            ],
            [
                'field_key' => 'inspection.date',
                'label' => 'Date of Inspection',
                'data_type' => TransactionFieldDefinition::TYPE_DATE,
                'section' => 'inspection',
                'format' => 'date_medium',
                'visibility_rules' => [
                    'all' => [
                        ['field' => 'inspection.is_scheduled', 'operator' => 'equals', 'value' => true],
                    ],
                ],
            ],
            [
                'field_key' => 'parties.buyer_name',
                'label' => 'Buyer Name',
                'data_type' => TransactionFieldDefinition::TYPE_TEXT,
                'section' => 'parties',
            ],
            [
                'field_key' => 'parties.seller_name',
                'label' => 'Seller Name',
                'data_type' => TransactionFieldDefinition::TYPE_TEXT,
                'section' => 'parties',
            ],
        ];
    }
}
