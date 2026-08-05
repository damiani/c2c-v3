<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\PropertyDistributionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int|null $transaction_id
 * @property int|null $listing_id
 * @property string $channel
 * @property array<int, array<string, mixed>> $recipient_groups
 * @property string $status
 * @property Carbon|null $sent_at
 * @property array<string, mixed>|null $delivery_status
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'tenant_id',
    'transaction_id',
    'listing_id',
    'channel',
    'recipient_groups',
    'status',
    'sent_at',
    'delivery_status',
    'metadata',
])]
class PropertyDistribution extends Model
{
    use BelongsToTenant;

    public const string CHANNEL_WHATSAPP = 'whatsapp';

    public const string STATUS_DRAFT = 'draft';

    public const string STATUS_SENT = 'sent';

    public const string STATUS_FAILED = 'failed';

    /** @use HasFactory<PropertyDistributionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recipient_groups' => 'array',
            'sent_at' => 'immutable_datetime',
            'delivery_status' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the transaction for the distribution.
     *
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the listing for the distribution.
     *
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
