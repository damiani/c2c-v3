<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\TenantMembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
     * Get the user for the membership.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
