<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\IdentityProviderAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $user_id
 * @property string $provider
 * @property string $provider_user_id
 * @property string|null $email
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $linked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tenant_id', 'user_id', 'provider', 'provider_user_id', 'email', 'metadata', 'linked_at'])]
class IdentityProviderAccount extends Model
{
    use BelongsToTenant;

    public const string PROVIDER_GOOGLE = 'google';

    public const string PROVIDER_MICROSOFT = 'microsoft';

    public const string PROVIDER_MLS = 'mls';

    /** @use HasFactory<IdentityProviderAccountFactory> */
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
            'linked_at' => 'datetime',
        ];
    }

    /**
     * Get the user linked to the external identity.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
