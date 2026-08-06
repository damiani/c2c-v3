<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $display_name
 * @property string $slug
 * @property string $status
 * @property string|null $logo_path
 * @property string $primary_color
 * @property string $accent_color
 * @property string|null $sender_name
 * @property string|null $sender_email
 * @property string $default_locale
 * @property array<int, string>|null $supported_locales
 * @property array<int, string>|null $enabled_integrations
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'display_name',
    'slug',
    'status',
    'logo_path',
    'primary_color',
    'accent_color',
    'sender_name',
    'sender_email',
    'default_locale',
    'supported_locales',
    'enabled_integrations',
])]
class Tenant extends Model
{
    public const string STATUS_ACTIVE = 'active';

    public const string STATUS_INACTIVE = 'inactive';

    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
        'primary_color' => '#2563eb',
        'accent_color' => '#16a34a',
        'default_locale' => 'en',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'supported_locales' => 'array',
            'enabled_integrations' => 'array',
        ];
    }

    /**
     * Get the tenant's memberships.
     *
     * @return HasMany<TenantMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(TenantMembership::class);
    }

    /**
     * Get the tenant's audit events.
     *
     * @return HasMany<AuditEvent, $this>
     */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(AuditEvent::class);
    }

    /**
     * Get the tenant's roles.
     *
     * @return HasMany<Role, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    /**
     * Get the tenant's transactions.
     *
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the tenant's documents.
     *
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get identity provider accounts linked inside the tenant.
     *
     * @return HasMany<IdentityProviderAccount, $this>
     */
    public function identityProviderAccounts(): HasMany
    {
        return $this->hasMany(IdentityProviderAccount::class);
    }

    /**
     * Get the users who belong to the tenant.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get the display name shown in tenant-branded UI and mail.
     */
    public function brandedName(): string
    {
        return $this->display_name ?? $this->name;
    }

    /**
     * Determine if a locale is enabled for this tenant.
     */
    public function supportsLocale(string $locale): bool
    {
        return in_array($locale, $this->supported_locales ?? [$this->default_locale], true);
    }
}
