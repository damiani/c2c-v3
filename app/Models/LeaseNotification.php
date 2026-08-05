<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\LeaseNotificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $lease_id
 * @property int $lead_time_months
 * @property string $alert_type
 * @property Carbon $alert_at
 * @property Carbon|null $fired_at
 * @property string $status
 * @property string|null $agent_action
 * @property string|null $escalation_status
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'tenant_id',
    'lease_id',
    'lead_time_months',
    'alert_type',
    'alert_at',
    'fired_at',
    'status',
    'agent_action',
    'escalation_status',
    'metadata',
])]
class LeaseNotification extends Model
{
    use BelongsToTenant;

    public const string STATUS_SCHEDULED = 'scheduled';

    public const string STATUS_FIRED = 'fired';

    public const string STATUS_ACKNOWLEDGED = 'acknowledged';

    public const string ESCALATION_NORMAL = 'normal';

    public const string ESCALATION_ELEVATED = 'elevated';

    public const string ESCALATION_URGENT = 'urgent';

    /** @use HasFactory<LeaseNotificationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'alert_at' => 'immutable_datetime',
            'fired_at' => 'immutable_datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the lease for the notification.
     *
     * @return BelongsTo<Lease, $this>
     */
    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }
}
