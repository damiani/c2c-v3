<?php

use App\Actions\Transactions\SeedDefaultTransactionTemplates;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionFieldDefinition;
use App\Models\TransactionFieldOverride;
use App\Models\TransactionFieldValue;
use App\Models\TransactionTemplate;
use App\Models\User;
use App\TransactionFields\TransactionFieldResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('dynamic transaction field foundation tables expose required columns', function () {
    expect(Schema::hasColumns('transaction_field_definitions', [
        'tenant_id',
        'scope_type',
        'scope_id',
        'field_key',
        'label',
        'data_type',
        'default_unit',
        'default_format',
        'value_schema',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('transaction_templates', [
            'tenant_id',
            'scope_type',
            'scope_id',
            'template_key',
            'transaction_type',
            'version',
            'is_default',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('transaction_template_fields', [
            'transaction_template_id',
            'field_definition_id',
            'field_key',
            'section',
            'visibility_rules',
            'calculation_rules',
            'date_trigger_rules',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('transaction_field_overrides', [
            'tenant_id',
            'field_definition_id',
            'scope_type',
            'scope_id',
            'label',
            'unit',
            'format',
            'option_labels',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('transaction_field_values', [
            'tenant_id',
            'transaction_id',
            'field_definition_id',
            'field_key',
            'data_type',
            'value_money_amount',
            'value_currency',
            'value_date',
            'value_unit',
            'selected_option_key',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('transactions', [
            'transaction_template_id',
            'transaction_template_version',
            'field_schema_snapshot',
        ]))->toBeTrue();
});

test('default residential sale template seeding is idempotent and stable-key based', function () {
    $action = app(SeedDefaultTransactionTemplates::class);

    $template = $action();
    $again = $action();

    expect($again->is($template))->toBeTrue()
        ->and(TransactionTemplate::query()->count())->toBe(1)
        ->and(TransactionFieldDefinition::query()->count())->toBe(12)
        ->and($template->template_key)->toBe(TransactionTemplate::TEMPLATE_RESIDENTIAL_SALE)
        ->and($template->transaction_type)->toBe(Transaction::TYPE_RESIDENTIAL_SALE)
        ->and($template->version)->toBe(1)
        ->and($template->is_default)->toBeTrue();

    $fields = $template->fields()->with('definition')->orderBy('sort_order')->get();

    expect($fields)->toHaveCount(12)
        ->and($fields->pluck('field_key')->all())->toContain(
            'property.address',
            'deal.purchase_price',
            'inspection.is_scheduled',
            'inspection.date',
        )
        ->and($fields->firstWhere('field_key', 'deal.purchase_price')->definition->data_type)
        ->toBe(TransactionFieldDefinition::TYPE_MONEY)
        ->and($fields->firstWhere('field_key', 'inspection.date')->visibility_rules)
        ->toBe([
            'all' => [
                ['field' => 'inspection.is_scheduled', 'operator' => 'equals', 'value' => true],
            ],
        ]);
});

test('field resolver applies tenant team and user display overrides in deterministic order', function () {
    $tenant = Tenant::factory()->create();
    $outsideTenant = Tenant::factory()->create();
    $user = User::factory()->withTenant($tenant)->create();
    $teamId = 101;

    $template = app(SeedDefaultTransactionTemplates::class)();
    $definition = TransactionFieldDefinition::query()
        ->where('field_key', 'deal.purchase_price')
        ->sole();

    TransactionFieldOverride::factory()
        ->forTenant($tenant)
        ->forDefinition($definition)
        ->create([
            'label' => 'Sale Price',
            'format' => 'currency_no_decimals',
        ]);

    TransactionFieldOverride::factory()
        ->forTeam($tenant, $teamId)
        ->forDefinition($definition)
        ->create([
            'label' => 'Contract Price',
            'unit' => 'usd',
        ]);

    TransactionFieldOverride::factory()
        ->forUser($tenant, $user)
        ->forDefinition($definition)
        ->create([
            'label' => 'My Price',
        ]);

    TransactionFieldOverride::factory()
        ->forTenant($outsideTenant)
        ->forDefinition($definition)
        ->create([
            'label' => 'Outside Price',
        ]);

    $resolved = app(TransactionFieldResolver::class)
        ->resolveForTemplate($template, $tenant, $teamId, $user)
        ->firstWhere('field_key', 'deal.purchase_price');

    expect($resolved['field_definition_id'])->toBe($definition->id)
        ->and($resolved['label'])->toBe('My Price')
        ->and($resolved['unit'])->toBe('usd')
        ->and($resolved['format'])->toBe('currency_no_decimals')
        ->and($resolved['data_type'])->toBe(TransactionFieldDefinition::TYPE_MONEY);
});

test('transaction field values store canonical typed values directly on the tenant record', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->asTenantOwner($tenant)->create();
    $template = app(SeedDefaultTransactionTemplates::class)();
    $definition = TransactionFieldDefinition::query()
        ->where('field_key', 'deal.purchase_price')
        ->sole();

    $transaction = Transaction::factory()
        ->forTenant($tenant)
        ->ownedBy($user)
        ->usingTemplate($template)
        ->create();

    $value = TransactionFieldValue::factory()
        ->forTransaction($transaction)
        ->forDefinition($definition)
        ->updatedBy($user)
        ->money(625000, 'USD')
        ->create();

    expect($value->tenant_id)->toBe($tenant->id)
        ->and($value->transaction->is($transaction))->toBeTrue()
        ->and($value->definition->is($definition))->toBeTrue()
        ->and($value->typedValue())->toBe([
            'amount' => '625000.00',
            'currency' => 'USD',
        ]);

    $value->setTypedValue(['amount' => 700000, 'currency' => 'USD']);
    $value->save();

    expect($value->fresh()->typedValue())->toBe([
        'amount' => '700000.00',
        'currency' => 'USD',
    ]);
});

test('tenant-scoped custom definitions and values do not leak across tenant queries', function () {
    $tenant = Tenant::factory()->create();
    $outsideTenant = Tenant::factory()->create();
    $user = User::factory()->asTenantOwner($tenant)->create();
    $outsideUser = User::factory()->asTenantOwner($outsideTenant)->create();

    $insideDefinition = TransactionFieldDefinition::factory()
        ->forTenant($tenant, $user)
        ->areaQuantity()
        ->create([
            'field_key' => 'property.lot_size',
            'label' => 'Lot Size',
        ]);

    $outsideDefinition = TransactionFieldDefinition::factory()
        ->forTenant($outsideTenant, $outsideUser)
        ->areaQuantity()
        ->create([
            'field_key' => 'property.lot_size',
            'label' => 'Lot Size',
        ]);

    $insideTransaction = Transaction::factory()->forTenant($tenant)->ownedBy($user)->create();
    $outsideTransaction = Transaction::factory()->forTenant($outsideTenant)->ownedBy($outsideUser)->create();

    $insideValue = TransactionFieldValue::factory()
        ->forTransaction($insideTransaction)
        ->forDefinition($insideDefinition)
        ->create([
            'data_type' => TransactionFieldDefinition::TYPE_QUANTITY,
            'value_decimal' => '0.500000',
            'value_unit' => 'acres',
        ]);

    TransactionFieldValue::factory()
        ->forTransaction($outsideTransaction)
        ->forDefinition($outsideDefinition)
        ->create([
            'data_type' => TransactionFieldDefinition::TYPE_QUANTITY,
            'value_decimal' => '3.000000',
            'value_unit' => 'acres',
        ]);

    expect(TransactionFieldDefinition::query()->forTenant($tenant)->pluck('id')->all())->toBe([$insideDefinition->id])
        ->and(TransactionFieldValue::query()->forTenant($tenant)->pluck('id')->all())->toBe([$insideValue->id]);
});
