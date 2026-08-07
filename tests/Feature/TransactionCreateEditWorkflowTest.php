<?php

use App\Actions\Transactions\SeedDefaultTransactionTemplates;
use App\Models\AuditEvent;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\Transaction;
use App\Models\TransactionFieldDefinition;
use App\Models\TransactionFieldOverride;
use App\Models\TransactionFieldValue;
use App\Models\TransactionTemplate;
use App\Models\User;
use App\Tenancy\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function fieldDefinitionId(string $fieldKey): int
{
    return TransactionFieldDefinition::query()
        ->where('field_key', $fieldKey)
        ->sole()
        ->id;
}

test('transaction routes render the phase four create edit workflow screens', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->asTenantOwner($tenant)->create();

    $this->actingAs($user);

    $this->get(route('transactions.index'))
        ->assertOk()
        ->assertSeeLivewire('transactions.index')
        ->assertSee('New transaction')
        ->assertDontSee('Navigation target ready');

    $this->get(route('transactions.create'))
        ->assertOk()
        ->assertSeeLivewire('transactions.create')
        ->assertSee('Residential Sale');
});

test('users can create a transaction from a resolved template with typed dynamic values', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->asTenantOwner($tenant)->create();

    app(CurrentTenant::class)->set($tenant);

    Livewire::actingAs($user)
        ->test('transactions.create')
        ->set('name', 'Lakeview purchase')
        ->set('status', Transaction::STATUS_ACTIVE)
        ->set('fieldInputs.'.fieldDefinitionId('property.address'), '123 Lakeview Ave')
        ->set('fieldInputs.'.fieldDefinitionId('property.city'), 'Chicago')
        ->set('fieldInputs.'.fieldDefinitionId('property.state'), 'IL')
        ->set('fieldInputs.'.fieldDefinitionId('deal.purchase_price'), '625,000')
        ->set('fieldInputs.'.fieldDefinitionId('deal.contract_acceptance_date'), '2026-08-07')
        ->set('fieldInputs.'.fieldDefinitionId('inspection.is_scheduled'), true)
        ->set('fieldInputs.'.fieldDefinitionId('inspection.date'), '2026-08-14')
        ->call('save')
        ->assertHasNoErrors();

    $transaction = Transaction::query()->sole();

    expect($transaction->tenant_id)->toBe($tenant->id)
        ->and($transaction->owner_user_id)->toBe($user->id)
        ->and($transaction->transaction_template_id)->not->toBeNull()
        ->and($transaction->transaction_template_version)->toBe(1)
        ->and($transaction->status)->toBe(Transaction::STATUS_ACTIVE)
        ->and($transaction->property_address)->toBe('123 Lakeview Ave')
        ->and($transaction->property_data)->toBe([
            'city' => 'Chicago',
            'state' => 'IL',
        ])
        ->and($transaction->field_schema_snapshot['template_key'])->toBe(TransactionTemplate::TEMPLATE_RESIDENTIAL_SALE);

    $purchasePrice = TransactionFieldValue::query()
        ->whereBelongsTo($transaction)
        ->where('field_key', 'deal.purchase_price')
        ->sole();

    $inspectionScheduled = TransactionFieldValue::query()
        ->whereBelongsTo($transaction)
        ->where('field_key', 'inspection.is_scheduled')
        ->sole();

    expect($purchasePrice->value_money_amount)->toBe('625000.00')
        ->and($purchasePrice->value_currency)->toBe('USD')
        ->and($inspectionScheduled->value_boolean)->toBeTrue()
        ->and(AuditEvent::query()->forTenant($tenant)->where('action', 'transaction.created')->count())->toBe(1);
});

test('required template fields show validation feedback on create', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->asTenantOwner($tenant)->create();

    app(CurrentTenant::class)->set($tenant);

    Livewire::actingAs($user)
        ->test('transactions.create')
        ->set('name', 'Incomplete deal')
        ->set('fieldInputs.'.fieldDefinitionId('property.address'), '123 Lakeview Ave')
        ->set('fieldInputs.'.fieldDefinitionId('property.city'), 'Chicago')
        ->set('fieldInputs.'.fieldDefinitionId('property.state'), 'IL')
        ->set('fieldInputs.'.fieldDefinitionId('deal.contract_acceptance_date'), '2026-08-07')
        ->call('save')
        ->assertHasErrors([
            'fieldInputs.'.fieldDefinitionId('deal.purchase_price') => 'required',
        ]);

    expect(Transaction::query()->count())->toBe(0);
});

test('transaction forms render resolved tenant and user field display overrides', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->asTenantOwner($tenant)->create();
    app(SeedDefaultTransactionTemplates::class)();

    $addressDefinition = TransactionFieldDefinition::query()
        ->where('field_key', 'property.address')
        ->sole();

    $areaDefinition = TransactionFieldDefinition::query()
        ->where('field_key', 'property.area')
        ->sole();

    TransactionFieldOverride::factory()
        ->for($tenant)
        ->forDefinition($addressDefinition)
        ->create([
            'scope_type' => TransactionFieldOverride::SCOPE_USER,
            'scope_id' => $user->id,
            'label' => 'Street Address',
        ]);

    TransactionFieldOverride::factory()
        ->for($tenant)
        ->forDefinition($areaDefinition)
        ->create([
            'scope_type' => TransactionFieldOverride::SCOPE_TENANT,
            'scope_id' => $tenant->id,
            'unit' => 'acres',
        ]);

    app(CurrentTenant::class)->set($tenant);

    Livewire::actingAs($user)
        ->test('transactions.create')
        ->assertSee('Street Address')
        ->assertSee('Acres')
        ->assertDontSee('Property Address');
});

test('transaction forms render Flux controls for dates selects and booleans', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->asTenantOwner($tenant)->create();
    $template = app(SeedDefaultTransactionTemplates::class)();

    $transaction = Transaction::factory()
        ->forTenant($tenant)
        ->ownedBy($user)
        ->usingTemplate($template)
        ->create(['name' => 'Flux control check']);

    app(CurrentTenant::class)->set($tenant);

    Livewire::actingAs($user)
        ->test('transactions.create')
        ->assertSee('data-flux-card', false)
        ->assertSee('data-flux-field', false)
        ->assertSee('data-flux-input', false)
        ->assertSee('data-flux-input-group', false)
        ->assertSee('data-flux-date-picker', false)
        ->assertSee('data-flux-select', false)
        ->assertSee('data-flux-checkbox', false)
        ->assertDontSee('<section', false)
        ->assertDontSee('type="number"', false)
        ->assertDontSee('type="date"', false)
        ->assertDontSee('data-flux-select-native', false);

    Livewire::actingAs($user)
        ->test('transactions.edit', ['transaction' => $transaction])
        ->assertSee('data-flux-card', false)
        ->assertSee('data-flux-field', false)
        ->assertSee('data-flux-input', false)
        ->assertSee('data-flux-input-group', false)
        ->assertSee('data-flux-date-picker', false)
        ->assertSee('data-flux-select', false)
        ->assertSee('data-flux-checkbox', false)
        ->assertSee('$money($input)', false)
        ->assertSee('wire:model.live.debounce.700ms', false)
        ->assertDontSee('<section', false)
        ->assertDontSee('type="number"', false)
        ->assertDontSee('type="date"', false)
        ->assertDontSee('data-flux-select-native', false)
        ->assertDontSee('Save changes');
});

test('users can edit an existing transaction without changing its pinned template version', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->asTenantOwner($tenant)->create();
    $template = app(SeedDefaultTransactionTemplates::class)();

    $transaction = Transaction::factory()
        ->forTenant($tenant)
        ->ownedBy($user)
        ->usingTemplate($template)
        ->create([
            'name' => 'Original Lakeview sale',
            'property_address' => '123 Old Ave',
        ]);

    app(CurrentTenant::class)->set($tenant);

    Livewire::actingAs($user)
        ->test('transactions.edit', ['transaction' => $transaction])
        ->set('name', 'Updated Lakeview sale')
        ->set('status', Transaction::STATUS_PENDING_CLOSE)
        ->set('fieldInputs.'.fieldDefinitionId('property.address'), '456 Lakeview Ave')
        ->set('fieldInputs.'.fieldDefinitionId('property.city'), 'Chicago')
        ->set('fieldInputs.'.fieldDefinitionId('property.state'), 'IL')
        ->set('fieldInputs.'.fieldDefinitionId('deal.purchase_price'), '650,000')
        ->set('fieldInputs.'.fieldDefinitionId('deal.contract_acceptance_date'), '2026-08-01')
        ->assertHasNoErrors()
        ->assertSee('Saved');

    $transaction->refresh();

    expect($transaction->name)->toBe('Updated Lakeview sale')
        ->and($transaction->status)->toBe(Transaction::STATUS_PENDING_CLOSE)
        ->and($transaction->property_address)->toBe('456 Lakeview Ave')
        ->and($transaction->transaction_template_version)->toBe(1)
        ->and($transaction->fieldValues()->where('field_key', 'deal.purchase_price')->sole()->value_money_amount)->toBe('650000.00')
        ->and(AuditEvent::query()->forTenant($tenant)->where('action', 'transaction.updated')->count())->toBeGreaterThanOrEqual(1);
});

test('transaction index and edit enforce tenant and owner visibility', function () {
    $tenant = Tenant::factory()->create();
    $member = User::factory()->withTenant($tenant, TenantMembership::ROLE_MEMBER)->create();
    $otherMember = User::factory()->withTenant($tenant, TenantMembership::ROLE_MEMBER)->create();
    $admin = User::factory()->asTenantAdmin($tenant)->create();

    $owned = Transaction::factory()
        ->forTenant($tenant)
        ->ownedBy($member)
        ->active()
        ->create(['name' => 'Owned Lakeview deal']);

    $other = Transaction::factory()
        ->forTenant($tenant)
        ->ownedBy($otherMember)
        ->active()
        ->create(['name' => 'Other tenant member deal']);

    $outsideTenant = Tenant::factory()->create();
    $outsideUser = User::factory()->asTenantOwner($outsideTenant)->create();
    $outside = Transaction::factory()
        ->forTenant($outsideTenant)
        ->ownedBy($outsideUser)
        ->active()
        ->create(['name' => 'Outside tenant deal']);

    app(CurrentTenant::class)->set($tenant);

    Livewire::actingAs($member)
        ->test('transactions.index')
        ->assertSee('Owned Lakeview deal')
        ->assertDontSee('Other tenant member deal')
        ->assertDontSee('Outside tenant deal');

    Livewire::actingAs($admin)
        ->test('transactions.index')
        ->assertSee('Owned Lakeview deal')
        ->assertSee('Other tenant member deal')
        ->assertDontSee('Outside tenant deal');

    Livewire::actingAs($member)
        ->test('transactions.edit', ['transaction' => $owned])
        ->assertStatus(200);

    Livewire::actingAs($member)
        ->test('transactions.edit', ['transaction' => $other])
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('transactions.edit', $outside))
        ->assertNotFound();
});
