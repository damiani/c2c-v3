<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\TransactionTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $tenant_id
 * @property int|null $created_by_user_id
 * @property string $scope_type
 * @property int $scope_id
 * @property string $template_key
 * @property string $name
 * @property string $transaction_type
 * @property string|null $description
 * @property int $version
 * @property string $status
 * @property bool $is_default
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'tenant_id',
    'created_by_user_id',
    'scope_type',
    'scope_id',
    'template_key',
    'name',
    'transaction_type',
    'description',
    'version',
    'status',
    'is_default',
    'metadata',
])]
class TransactionTemplate extends Model
{
    use BelongsToTenant;

    public const string SCOPE_SYSTEM = TransactionFieldDefinition::SCOPE_SYSTEM;

    public const string SCOPE_TENANT = TransactionFieldDefinition::SCOPE_TENANT;

    public const string SCOPE_TEAM = TransactionFieldDefinition::SCOPE_TEAM;

    public const string SCOPE_USER = TransactionFieldDefinition::SCOPE_USER;

    public const string STATUS_ACTIVE = 'active';

    public const string STATUS_DRAFT = 'draft';

    public const string STATUS_ARCHIVED = 'archived';

    public const string TEMPLATE_RESIDENTIAL_SALE = 'residential_sale';

    /** @use HasFactory<TransactionTemplateFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the user that created the template.
     *
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the fields included in this template version.
     *
     * @return HasMany<TransactionTemplateField, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(TransactionTemplateField::class);
    }

    /**
     * Get transactions pinned to this template version.
     *
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
