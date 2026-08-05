<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\ListingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $transaction_id
 * @property string $status
 * @property array<string, mixed>|null $property_details
 * @property array<string, mixed>|null $marketing_channels
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tenant_id', 'transaction_id', 'status', 'property_details', 'marketing_channels', 'published_at'])]
class Listing extends Model
{
    use BelongsToTenant;

    public const string STATUS_DRAFT = 'draft';

    public const string STATUS_ACTIVE = 'active';

    public const string STATUS_ARCHIVED = 'archived';

    /** @use HasFactory<ListingFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'property_details' => 'array',
            'marketing_channels' => 'array',
            'published_at' => 'immutable_datetime',
        ];
    }

    /**
     * Get the transaction for the listing.
     *
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get property distributions for the listing.
     *
     * @return HasMany<PropertyDistribution, $this>
     */
    public function propertyDistributions(): HasMany
    {
        return $this->hasMany(PropertyDistribution::class);
    }
}
