<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\LeaseFactory;
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
 * @property string $lease_type
 * @property string $status
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property string|null $rent_amount
 * @property string $rent_currency
 * @property array<string, mixed>|null $escalation_schedule
 * @property int|null $renewal_lead_months
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'tenant_id',
    'transaction_id',
    'lease_type',
    'status',
    'start_date',
    'end_date',
    'rent_amount',
    'rent_currency',
    'escalation_schedule',
    'renewal_lead_months',
    'metadata',
])]
class Lease extends Model
{
    use BelongsToTenant;

    public const string TYPE_RESIDENTIAL = 'residential';

    public const string TYPE_COMMERCIAL = 'commercial';

    public const string STATUS_DRAFT = 'draft';

    public const string STATUS_ACTIVE = 'active';

    public const string STATUS_IN_TERM = 'in_term';

    public const string STATUS_RENEWAL_PENDING = 'renewal_pending';

    public const string STATUS_RENEWED = 'renewed';

    public const string STATUS_EXPIRED = 'expired';

    /** @use HasFactory<LeaseFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'immutable_date',
            'end_date' => 'immutable_date',
            'rent_amount' => 'decimal:2',
            'escalation_schedule' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the transaction for the lease.
     *
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get lease renewal notifications.
     *
     * @return HasMany<LeaseNotification, $this>
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(LeaseNotification::class);
    }
}
