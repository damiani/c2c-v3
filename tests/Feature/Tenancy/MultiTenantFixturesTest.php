<?php

use App\Models\Contact;
use App\Models\Document;
use App\Models\DocumentExtraction;
use App\Models\DocumentReview;
use App\Models\Lease;
use App\Models\LeaseNotification;
use App\Models\Listing;
use App\Models\Milestone;
use App\Models\PropertyDistribution;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TenantScenario;

uses(RefreshDatabase::class);

test('tenant scenario creates realistic related records inside one tenant', function () {
    $scenario = TenantScenario::create();

    expect($scenario->tenant->slug)->toBe('chicago-association-of-realtors')
        ->and($scenario->owner->belongsToTenant($scenario->tenant))->toBeTrue()
        ->and($scenario->admin->belongsToTenant($scenario->tenant))->toBeTrue()
        ->and($scenario->member->belongsToTenant($scenario->tenant))->toBeTrue()
        ->and($scenario->transaction->tenant_id)->toBe($scenario->tenant->id)
        ->and($scenario->transaction->owner_user_id)->toBe($scenario->owner->id)
        ->and($scenario->contact->tenant_id)->toBe($scenario->tenant->id)
        ->and($scenario->contact->transaction_id)->toBe($scenario->transaction->id)
        ->and($scenario->milestone->tenant_id)->toBe($scenario->tenant->id)
        ->and($scenario->form->tenant_id)->toBe($scenario->tenant->id)
        ->and($scenario->document->tenant_id)->toBe($scenario->tenant->id)
        ->and($scenario->document->form_id)->toBe($scenario->form->id)
        ->and($scenario->documentReview->tenant_id)->toBe($scenario->tenant->id)
        ->and($scenario->documentReview->reviewer_user_id)->toBe($scenario->admin->id)
        ->and($scenario->documentExtraction->tenant_id)->toBe($scenario->tenant->id)
        ->and($scenario->listing->tenant_id)->toBe($scenario->tenant->id)
        ->and($scenario->lease->tenant_id)->toBe($scenario->tenant->id)
        ->and($scenario->leaseNotification->tenant_id)->toBe($scenario->tenant->id)
        ->and($scenario->propertyDistribution->tenant_id)->toBe($scenario->tenant->id)
        ->and($scenario->propertyDistribution->transaction_id)->toBe($scenario->transaction->id);
});

test('tenant scenario includes outside records for cross tenant isolation assertions', function () {
    $scenario = TenantScenario::create();

    expect($scenario->outsideTenant->is($scenario->tenant))->toBeFalse()
        ->and($scenario->outsideUser->belongsToTenant($scenario->outsideTenant))->toBeTrue()
        ->and($scenario->outsideUser->belongsToTenant($scenario->tenant))->toBeFalse()
        ->and(Transaction::query()->forTenant($scenario->tenant)->pluck('id')->all())->toBe([$scenario->transaction->id])
        ->and(Transaction::query()->forTenant($scenario->outsideTenant)->pluck('id')->all())->toBe([$scenario->outsideTransaction->id])
        ->and(Document::query()->forTenant($scenario->tenant)->pluck('id')->all())->toBe([$scenario->document->id])
        ->and(Document::query()->forTenant($scenario->outsideTenant)->pluck('id')->all())->toBe([$scenario->outsideDocument->id]);
});

test('factory states create tenant memberships with realistic roles', function () {
    $c2c = Tenant::factory()->c2c()->create();
    $owner = User::factory()->asTenantOwner($c2c)->create();
    $admin = User::factory()->asTenantAdmin($c2c)->create();
    $member = User::factory()->withTenant($c2c)->create();

    expect($c2c->slug)->toBe('c2c')
        ->and($owner->tenantMemberships()->whereBelongsTo($c2c)->first()?->role)->toBe(TenantMembership::ROLE_OWNER)
        ->and($admin->tenantMemberships()->whereBelongsTo($c2c)->first()?->role)->toBe(TenantMembership::ROLE_ADMIN)
        ->and($member->tenantMemberships()->whereBelongsTo($c2c)->first()?->role)->toBe(TenantMembership::ROLE_MEMBER);
});

test('child factory states inherit tenant ownership from their parent records', function () {
    $tenant = Tenant::factory()->mlsAssociation('Miami Realtors')->create();
    $owner = User::factory()->asTenantOwner($tenant)->create();
    $transaction = Transaction::factory()
        ->forTenant($tenant)
        ->ownedBy($owner)
        ->commercialLease()
        ->active()
        ->create();

    $contact = Contact::factory()->forTransaction($transaction)->create();
    $milestone = Milestone::factory()->forTransaction($transaction)->create();
    $document = Document::factory()->forTransaction($transaction)->uploadedBy($owner)->create();
    $review = DocumentReview::factory()->forDocument($document)->reviewedBy($owner, TenantMembership::ROLE_OWNER)->create();
    $extraction = DocumentExtraction::factory()->forDocument($document)->create();
    $listing = Listing::factory()->forTransaction($transaction)->create();
    $lease = Lease::factory()->forTransaction($transaction)->commercial()->create();
    $notification = LeaseNotification::factory()->forLease($lease)->create();
    $distribution = PropertyDistribution::factory()->forListing($listing)->create();

    expect($contact->tenant_id)->toBe($tenant->id)
        ->and($milestone->tenant_id)->toBe($tenant->id)
        ->and($document->tenant_id)->toBe($tenant->id)
        ->and($document->uploaded_by_user_id)->toBe($owner->id)
        ->and($document->storage_path)->toStartWith("documents/tenants/{$tenant->id}/documents/")
        ->and($document->storage_path)->toEndWith('/'.$document->original_filename)
        ->and($review->tenant_id)->toBe($tenant->id)
        ->and($review->reviewer_role)->toBe(TenantMembership::ROLE_OWNER)
        ->and($extraction->tenant_id)->toBe($tenant->id)
        ->and($listing->tenant_id)->toBe($tenant->id)
        ->and($lease->tenant_id)->toBe($tenant->id)
        ->and($lease->lease_type)->toBe(Lease::TYPE_COMMERCIAL)
        ->and($notification->tenant_id)->toBe($tenant->id)
        ->and($distribution->tenant_id)->toBe($tenant->id)
        ->and($distribution->transaction_id)->toBe($transaction->id);
});
