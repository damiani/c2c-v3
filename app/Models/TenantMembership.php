<?php

namespace App\Models;

use App\Authorization\TenantPermissionRegistry;
use App\Concerns\BelongsToTenant;
use Database\Factories\TenantMembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $user_id
 * @property string $role
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tenant_id', 'user_id', 'role'])]
class TenantMembership extends Model
{
    use BelongsToTenant;

    public const string ROLE_OWNER = 'owner';

    public const string ROLE_ADMIN = 'admin';

    public const string ROLE_BROKER_ADMIN = 'broker_admin';

    public const string ROLE_COORDINATOR = 'coordinator';

    public const string ROLE_AGENT = 'agent';

    public const string ROLE_BACK_OFFICE = 'back_office';

    public const string ROLE_MEMBER = 'member';

    /** @use HasFactory<TenantMembershipFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => self::ROLE_MEMBER,
    ];

    /**
     * Get roles that can administer tenant settings and membership.
     *
     * @return list<string>
     */
    public static function administrativeRoles(): array
    {
        return [
            self::ROLE_OWNER,
            self::ROLE_ADMIN,
        ];
    }

    /**
     * Get every built-in tenant role slug.
     *
     * @return list<string>
     */
    public static function roles(): array
    {
        return TenantPermissionRegistry::systemRoleSlugs();
    }

    /**
     * Scope memberships by one or more roles.
     *
     * @param  Builder<static>  $query
     * @param  string|list<string>  $roles
     * @return Builder<static>
     */
    public function scopeRole(Builder $query, string|array $roles): Builder
    {
        return $query->whereIn('role', (array) $roles);
    }

    /**
     * Get the user for the membership.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tenant-specific role definition matching this membership.
     */
    public function roleDefinition(): ?Role
    {
        return Role::query()
            ->forTenant($this->tenant_id)
            ->where('slug', $this->role)
            ->first();
    }

    /**
     * Get the resolved permission list for this membership.
     *
     * @return list<string>
     */
    public function permissions(): array
    {
        $roleDefinition = $this->roleDefinition();

        if ($roleDefinition !== null) {
            return TenantPermissionRegistry::normalizePermissions($roleDefinition->permissions);
        }

        return TenantPermissionRegistry::permissionsForRole($this->role);
    }

    /**
     * Determine if the membership grants a tenant permission.
     */
    public function hasPermission(string $permission): bool
    {
        return TenantPermissionRegistry::allows($this->permissions(), $permission);
    }
}
