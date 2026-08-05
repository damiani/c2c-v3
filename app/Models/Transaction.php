<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int|null $owner_user_id
 * @property string $transaction_type
 * @property string $status
 * @property string $name
 * @property string|null $property_address
 * @property array<string, mixed>|null $property_data
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $opened_at
 * @property Carbon|null $closed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'tenant_id',
    'owner_user_id',
    'transaction_type',
    'status',
    'name',
    'property_address',
    'property_data',
    'metadata',
    'opened_at',
    'closed_at',
])]
class Transaction extends Model
{
    use BelongsToTenant;

    public const string TYPE_RESIDENTIAL_SALE = 'residential_sale';

    public const string TYPE_PURCHASE = 'purchase';

    public const string TYPE_RESIDENTIAL_RENTAL = 'residential_rental';

    public const string TYPE_COMMERCIAL_SALE = 'commercial_sale';

    public const string TYPE_COMMERCIAL_LEASE = 'commercial_lease';

    public const string TYPE_CUSTOM = 'custom';

    public const string STATUS_DRAFT = 'draft';

    public const string STATUS_ACTIVE = 'active';

    public const string STATUS_PENDING_CLOSE = 'pending_close';

    public const string STATUS_CLOSED = 'closed';

    public const string STATUS_TERMINATED = 'terminated';

    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'property_data' => 'array',
            'metadata' => 'array',
            'opened_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }

    /**
     * Get the owning user for the transaction.
     *
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * Get the transaction contacts.
     *
     * @return HasMany<Contact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get the transaction documents.
     *
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get the transaction milestones.
     *
     * @return HasMany<Milestone, $this>
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class);
    }

    /**
     * Get the transaction listing.
     *
     * @return HasOne<Listing, $this>
     */
    public function listing(): HasOne
    {
        return $this->hasOne(Listing::class);
    }

    /**
     * Get the transaction lease.
     *
     * @return HasOne<Lease, $this>
     */
    public function lease(): HasOne
    {
        return $this->hasOne(Lease::class);
    }

    /**
     * Get property distributions for the transaction.
     *
     * @return HasMany<PropertyDistribution, $this>
     */
    public function propertyDistributions(): HasMany
    {
        return $this->hasMany(PropertyDistribution::class);
    }
}
