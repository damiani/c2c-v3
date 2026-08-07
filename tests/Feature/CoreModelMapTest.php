<?php

use App\Models\Contact;
use App\Models\Document;
use App\Models\DocumentExtraction;
use App\Models\DocumentReview;
use App\Models\Form;
use App\Models\Lease;
use App\Models\LeaseNotification;
use App\Models\Listing;
use App\Models\Milestone;
use App\Models\PropertyDistribution;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\Transaction;
use App\Models\TransactionFieldDefinition;
use App\Models\TransactionFieldOverride;
use App\Models\TransactionFieldValue;
use App\Models\TransactionTemplate;
use App\Models\TransactionTemplateField;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('phase one core model tables expose required tenant owned columns', function () {
    $tables = [
        'roles' => ['tenant_id', 'name', 'slug', 'permissions'],
        'transactions' => ['tenant_id', 'owner_user_id', 'transaction_type', 'status', 'property_data'],
        'contacts' => ['tenant_id', 'transaction_id', 'display_name', 'contact_type'],
        'milestones' => ['tenant_id', 'transaction_id', 'title', 'status', 'due_at'],
        'forms' => ['tenant_id', 'title', 'source', 'form_type'],
        'documents' => ['tenant_id', 'transaction_id', 'form_id', 'uploaded_by_user_id', 'storage_disk', 'storage_path'],
        'listings' => ['tenant_id', 'transaction_id', 'property_details', 'marketing_channels'],
        'leases' => ['tenant_id', 'transaction_id', 'lease_type', 'end_date', 'renewal_lead_months'],
        'document_reviews' => ['tenant_id', 'document_id', 'reviewer_user_id', 'reviewer_role', 'annotations'],
        'document_extractions' => ['tenant_id', 'document_id', 'field_name', 'confidence_score', 'agent_confirmed'],
        'lease_notifications' => ['tenant_id', 'lease_id', 'lead_time_months', 'alert_at', 'escalation_status'],
        'property_distributions' => ['tenant_id', 'transaction_id', 'listing_id', 'channel', 'recipient_groups'],
        'transaction_field_definitions' => ['tenant_id', 'scope_type', 'scope_id', 'field_key', 'data_type'],
        'transaction_templates' => ['tenant_id', 'scope_type', 'scope_id', 'template_key', 'transaction_type', 'version'],
        'transaction_template_fields' => ['tenant_id', 'transaction_template_id', 'field_definition_id', 'field_key'],
        'transaction_field_overrides' => ['tenant_id', 'field_definition_id', 'scope_type', 'scope_id'],
        'transaction_field_values' => ['tenant_id', 'transaction_id', 'field_definition_id', 'field_key', 'data_type'],
    ];

    foreach ($tables as $table => $columns) {
        expect(Schema::hasTable($table))->toBeTrue();
        expect(Schema::hasColumns($table, $columns))->toBeTrue();
    }
});

test('core model factories preserve tenant ownership through the transaction document and lease graph', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->create();

    TenantMembership::factory()
        ->for($tenant)
        ->for($owner, 'user')
        ->owner()
        ->create();

    $role = Role::factory()->for($tenant)->create();
    $transaction = Transaction::factory()
        ->create([
            'tenant_id' => $tenant->id,
            'owner_user_id' => $owner->id,
            'transaction_type' => Transaction::TYPE_RESIDENTIAL_RENTAL,
        ]);
    $contact = Contact::factory()->create([
        'tenant_id' => $tenant->id,
        'transaction_id' => $transaction->id,
    ]);
    $milestone = Milestone::factory()->create([
        'tenant_id' => $tenant->id,
        'transaction_id' => $transaction->id,
    ]);
    $form = Form::factory()->for($tenant)->create();
    $document = Document::factory()
        ->create([
            'tenant_id' => $tenant->id,
            'transaction_id' => $transaction->id,
            'form_id' => $form->id,
            'uploaded_by_user_id' => $owner->id,
        ]);
    $review = DocumentReview::factory()->create([
        'tenant_id' => $tenant->id,
        'document_id' => $document->id,
        'reviewer_user_id' => $owner->id,
    ]);
    $extraction = DocumentExtraction::factory()->create([
        'tenant_id' => $tenant->id,
        'document_id' => $document->id,
        'confirmed_by_user_id' => $owner->id,
        'agent_confirmed' => true,
        'confirmed_at' => now(),
    ]);
    $listing = Listing::factory()->create([
        'tenant_id' => $tenant->id,
        'transaction_id' => $transaction->id,
    ]);
    $lease = Lease::factory()->create([
        'tenant_id' => $tenant->id,
        'transaction_id' => $transaction->id,
    ]);
    $leaseNotification = LeaseNotification::factory()->create([
        'tenant_id' => $tenant->id,
        'lease_id' => $lease->id,
    ]);
    $propertyDistribution = PropertyDistribution::factory()->create([
        'tenant_id' => $tenant->id,
        'transaction_id' => $transaction->id,
        'listing_id' => $listing->id,
    ]);
    $fieldDefinition = TransactionFieldDefinition::factory()->forTenant($tenant, $owner)->create();
    $template = TransactionTemplate::factory()->forTenant($tenant, $owner)->create();
    $templateField = TransactionTemplateField::factory()
        ->forTemplate($template)
        ->forDefinition($fieldDefinition)
        ->create();
    $fieldOverride = TransactionFieldOverride::factory()
        ->forTenant($tenant)
        ->forDefinition($fieldDefinition)
        ->create();
    $fieldValue = TransactionFieldValue::factory()
        ->forTransaction($transaction)
        ->forDefinition($fieldDefinition)
        ->forTemplateField($templateField)
        ->updatedBy($owner)
        ->create();

    expect($role->tenant->is($tenant))->toBeTrue()
        ->and($transaction->tenant->is($tenant))->toBeTrue()
        ->and($transaction->owner->is($owner))->toBeTrue()
        ->and($contact->transaction->is($transaction))->toBeTrue()
        ->and($milestone->transaction->is($transaction))->toBeTrue()
        ->and($document->transaction->is($transaction))->toBeTrue()
        ->and($document->form->is($form))->toBeTrue()
        ->and($document->uploadedBy->is($owner))->toBeTrue()
        ->and($review->document->is($document))->toBeTrue()
        ->and($review->reviewer->is($owner))->toBeTrue()
        ->and($extraction->document->is($document))->toBeTrue()
        ->and($extraction->confirmedBy->is($owner))->toBeTrue()
        ->and($listing->transaction->is($transaction))->toBeTrue()
        ->and($lease->transaction->is($transaction))->toBeTrue()
        ->and($leaseNotification->lease->is($lease))->toBeTrue()
        ->and($propertyDistribution->listing->is($listing))->toBeTrue()
        ->and($propertyDistribution->transaction->is($transaction))->toBeTrue()
        ->and($fieldDefinition->tenant->is($tenant))->toBeTrue()
        ->and($template->tenant->is($tenant))->toBeTrue()
        ->and($templateField->template->is($template))->toBeTrue()
        ->and($templateField->definition->is($fieldDefinition))->toBeTrue()
        ->and($fieldOverride->definition->is($fieldDefinition))->toBeTrue()
        ->and($fieldValue->transaction->is($transaction))->toBeTrue()
        ->and($fieldValue->definition->is($fieldDefinition))->toBeTrue()
        ->and($fieldValue->templateField->is($templateField))->toBeTrue();

    foreach ([$contact, $milestone, $document, $review, $extraction, $listing, $lease, $leaseNotification, $propertyDistribution, $fieldDefinition, $template, $templateField, $fieldOverride, $fieldValue] as $model) {
        expect($model->tenant_id)->toBe($tenant->id);
    }
});

test('core model factories can create default records', function (string $modelClass) {
    expect($modelClass::factory()->create())->toBeInstanceOf($modelClass);
})->with([
    Role::class,
    Transaction::class,
    Contact::class,
    Milestone::class,
    Form::class,
    Document::class,
    Listing::class,
    Lease::class,
    DocumentReview::class,
    DocumentExtraction::class,
    LeaseNotification::class,
    PropertyDistribution::class,
    TransactionFieldDefinition::class,
    TransactionTemplate::class,
    TransactionTemplateField::class,
    TransactionFieldOverride::class,
    TransactionFieldValue::class,
]);

test('tenant owned core models can be isolated with the shared tenant scope', function (string $modelClass) {
    $tenant = Tenant::factory()->create();
    $outsideTenant = Tenant::factory()->create();

    $inside = createModelForTenant($modelClass, $tenant);
    createModelForTenant($modelClass, $outsideTenant);

    expect($modelClass::query()->forTenant($tenant)->pluck('id')->all())->toBe([$inside->id]);
})->with([
    Role::class,
    Transaction::class,
    Contact::class,
    Milestone::class,
    Form::class,
    Document::class,
    Listing::class,
    Lease::class,
    DocumentReview::class,
    DocumentExtraction::class,
    LeaseNotification::class,
    PropertyDistribution::class,
    TransactionFieldDefinition::class,
    TransactionTemplate::class,
    TransactionTemplateField::class,
    TransactionFieldOverride::class,
    TransactionFieldValue::class,
]);

function createModelForTenant(string $modelClass, Tenant $tenant): Model
{
    $user = User::factory()->create();
    $transaction = Transaction::factory()
        ->create([
            'tenant_id' => $tenant->id,
            'owner_user_id' => $user->id,
        ]);

    $document = fn (): Document => Document::factory()->create([
        'tenant_id' => $tenant->id,
        'transaction_id' => $transaction->id,
    ]);
    $listing = fn (): Listing => Listing::factory()->create([
        'tenant_id' => $tenant->id,
        'transaction_id' => $transaction->id,
    ]);
    $lease = fn (): Lease => Lease::factory()->create([
        'tenant_id' => $tenant->id,
        'transaction_id' => $transaction->id,
    ]);
    $fieldDefinition = fn (): TransactionFieldDefinition => TransactionFieldDefinition::factory()->forTenant($tenant, $user)->create();
    $template = fn (): TransactionTemplate => TransactionTemplate::factory()->forTenant($tenant, $user)->create();
    $templateField = fn (): TransactionTemplateField => TransactionTemplateField::factory()
        ->forTemplate($template())
        ->forDefinition($fieldDefinition())
        ->create();

    return match ($modelClass) {
        Role::class => Role::factory()->for($tenant)->create(),
        Transaction::class => $transaction,
        Contact::class => Contact::factory()->create(['tenant_id' => $tenant->id, 'transaction_id' => $transaction->id]),
        Milestone::class => Milestone::factory()->create(['tenant_id' => $tenant->id, 'transaction_id' => $transaction->id]),
        Form::class => Form::factory()->for($tenant)->create(),
        Document::class => $document(),
        Listing::class => $listing(),
        Lease::class => $lease(),
        DocumentReview::class => DocumentReview::factory()
            ->create(['tenant_id' => $tenant->id, 'document_id' => $document()->id, 'reviewer_user_id' => $user->id]),
        DocumentExtraction::class => DocumentExtraction::factory()
            ->create(['tenant_id' => $tenant->id, 'document_id' => $document()->id]),
        LeaseNotification::class => LeaseNotification::factory()
            ->create(['tenant_id' => $tenant->id, 'lease_id' => $lease()->id]),
        PropertyDistribution::class => PropertyDistribution::factory()
            ->create(['tenant_id' => $tenant->id, 'transaction_id' => $transaction->id, 'listing_id' => $listing()->id]),
        TransactionFieldDefinition::class => $fieldDefinition(),
        TransactionTemplate::class => $template(),
        TransactionTemplateField::class => $templateField(),
        TransactionFieldOverride::class => TransactionFieldOverride::factory()
            ->forTenant($tenant)
            ->forDefinition($fieldDefinition())
            ->create(),
        TransactionFieldValue::class => TransactionFieldValue::factory()
            ->forTransaction($transaction)
            ->forDefinition($fieldDefinition())
            ->create(),
    };
}
