<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int|null $transaction_id
 * @property string $display_name
 * @property string|null $company_name
 * @property string $contact_type
 * @property string|null $email
 * @property string|null $phone
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tenant_id', 'transaction_id', 'display_name', 'company_name', 'contact_type', 'email', 'phone', 'metadata'])]
class Contact extends Model
{
    use BelongsToTenant;

    public const string TYPE_BUYER = 'buyer';

    public const string TYPE_SELLER = 'seller';

    public const string TYPE_TENANT = 'tenant';

    public const string TYPE_LANDLORD = 'landlord';

    public const string TYPE_AGENT = 'agent';

    public const string TYPE_SERVICE_PROVIDER = 'service_provider';

    /** @use HasFactory<ContactFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * Get the transaction for the contact.
     *
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
