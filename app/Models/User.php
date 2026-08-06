<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $locale
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'locale', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's preferred locale for notifications and formatting.
     */
    public function preferredLocale(): string
    {
        return $this->locale;
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /**
     * Get the user's tenant memberships.
     *
     * @return HasMany<TenantMembership, $this>
     */
    public function tenantMemberships(): HasMany
    {
        return $this->hasMany(TenantMembership::class);
    }

    /**
     * Get the tenants the user belongs to.
     *
     * @return BelongsToMany<Tenant, $this>
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get transactions owned by the user.
     *
     * @return HasMany<Transaction, $this>
     */
    public function ownedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'owner_user_id');
    }

    /**
     * Get documents uploaded by the user.
     *
     * @return HasMany<Document, $this>
     */
    public function uploadedDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'uploaded_by_user_id');
    }

    /**
     * Get document reviews assigned to the user.
     *
     * @return HasMany<DocumentReview, $this>
     */
    public function documentReviews(): HasMany
    {
        return $this->hasMany(DocumentReview::class, 'reviewer_user_id');
    }

    /**
     * Get identity provider accounts linked to the user.
     *
     * @return HasMany<IdentityProviderAccount, $this>
     */
    public function identityProviderAccounts(): HasMany
    {
        return $this->hasMany(IdentityProviderAccount::class);
    }

    /**
     * Determine if the user belongs to a tenant.
     */
    public function belongsToTenant(Tenant $tenant): bool
    {
        return $this->tenants()
            ->whereKey($tenant->getKey())
            ->exists();
    }
}
