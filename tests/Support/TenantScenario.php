<?php

namespace Tests\Support;

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
use App\Models\User;

class TenantScenario
{
    public function __construct(
        public Tenant $tenant,
        public User $owner,
        public User $admin,
        public User $member,
        public Role $role,
        public Transaction $transaction,
        public Contact $contact,
        public Milestone $milestone,
        public Form $form,
        public Document $document,
        public DocumentReview $documentReview,
        public DocumentExtraction $documentExtraction,
        public Listing $listing,
        public Lease $lease,
        public LeaseNotification $leaseNotification,
        public PropertyDistribution $propertyDistribution,
        public Tenant $outsideTenant,
        public User $outsideUser,
        public Transaction $outsideTransaction,
        public Document $outsideDocument,
    ) {}

    /**
     * Create a realistic pair of tenants for isolation and workflow tests.
     */
    public static function create(): self
    {
        $tenant = Tenant::factory()
            ->mlsAssociation('Chicago Association of Realtors')
            ->create();

        $owner = User::factory()
            ->asTenantOwner($tenant)
            ->create();

        $admin = User::factory()
            ->asTenantAdmin($tenant)
            ->create();

        $member = User::factory()
            ->withTenant($tenant)
            ->create();

        $role = Role::factory()
            ->for($tenant)
            ->create([
                'name' => 'Transaction Coordinator',
                'slug' => 'transaction-coordinator',
            ]);

        $transaction = Transaction::factory()
            ->forTenant($tenant)
            ->ownedBy($owner)
            ->residentialSale()
            ->active()
            ->create();

        $contact = Contact::factory()
            ->forTransaction($transaction)
            ->create(['contact_type' => Contact::TYPE_BUYER]);

        $milestone = Milestone::factory()
            ->forTransaction($transaction)
            ->create(['title' => 'Attorney review deadline']);

        $form = Form::factory()
            ->for($tenant)
            ->create(['form_type' => 'purchase_agreement']);

        $document = Document::factory()
            ->forTransaction($transaction)
            ->uploadedBy($owner)
            ->create([
                'form_id' => $form->id,
                'document_type' => 'purchase_agreement',
            ]);

        $documentReview = DocumentReview::factory()
            ->forDocument($document)
            ->reviewedBy($admin, TenantMembership::ROLE_ADMIN)
            ->create();

        $documentExtraction = DocumentExtraction::factory()
            ->forDocument($document)
            ->create([
                'field_name' => 'purchase_price',
                'extracted_value' => '625000',
            ]);

        $listing = Listing::factory()
            ->forTransaction($transaction)
            ->create();

        $lease = Lease::factory()
            ->forTransaction($transaction)
            ->commercial()
            ->create();

        $leaseNotification = LeaseNotification::factory()
            ->forLease($lease)
            ->create();

        $propertyDistribution = PropertyDistribution::factory()
            ->forListing($listing)
            ->create();

        $outsideTenant = Tenant::factory()
            ->mlsAssociation('North Shore Barrington Association of Realtors')
            ->create();

        $outsideUser = User::factory()
            ->asTenantOwner($outsideTenant)
            ->create();

        $outsideTransaction = Transaction::factory()
            ->forTenant($outsideTenant)
            ->ownedBy($outsideUser)
            ->active()
            ->create();

        $outsideDocument = Document::factory()
            ->forTransaction($outsideTransaction)
            ->uploadedBy($outsideUser)
            ->create();

        return new self(
            tenant: $tenant,
            owner: $owner,
            admin: $admin,
            member: $member,
            role: $role,
            transaction: $transaction,
            contact: $contact,
            milestone: $milestone,
            form: $form,
            document: $document,
            documentReview: $documentReview,
            documentExtraction: $documentExtraction,
            listing: $listing,
            lease: $lease,
            leaseNotification: $leaseNotification,
            propertyDistribution: $propertyDistribution,
            outsideTenant: $outsideTenant,
            outsideUser: $outsideUser,
            outsideTransaction: $outsideTransaction,
            outsideDocument: $outsideDocument,
        );
    }
}
