<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\TransactionFieldDefinitionFactory;
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
 * @property string $field_key
 * @property string $label
 * @property string $data_type
 * @property string|null $default_unit
 * @property string|null $default_format
 * @property array<int|string, mixed>|null $default_options
 * @property array<string, mixed>|null $value_schema
 * @property bool $is_system
 * @property bool $is_active
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'tenant_id',
    'created_by_user_id',
    'scope_type',
    'scope_id',
    'field_key',
    'label',
    'data_type',
    'default_unit',
    'default_format',
    'default_options',
    'value_schema',
    'is_system',
    'is_active',
    'metadata',
])]
class TransactionFieldDefinition extends Model
{
    use BelongsToTenant;

    public const string SCOPE_SYSTEM = 'system';

    public const string SCOPE_TENANT = 'tenant';

    public const string SCOPE_TEAM = 'team';

    public const string SCOPE_USER = 'user';

    public const string TYPE_TEXT = 'text';

    public const string TYPE_LONG_TEXT = 'long_text';

    public const string TYPE_MONEY = 'money';

    public const string TYPE_DATE = 'date';

    public const string TYPE_DATETIME = 'datetime';

    public const string TYPE_BOOLEAN = 'boolean';

    public const string TYPE_SELECT = 'select';

    public const string TYPE_INTEGER = 'integer';

    public const string TYPE_DECIMAL = 'decimal';

    public const string TYPE_PERCENTAGE = 'percentage';

    public const string TYPE_QUANTITY = 'quantity';

    public const string TYPE_JSON = 'json';

    /** @use HasFactory<TransactionFieldDefinitionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_options' => 'array',
            'value_schema' => 'array',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the user that created the custom field definition.
     *
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get template placements using this field definition.
     *
     * @return HasMany<TransactionTemplateField, $this>
     */
    public function templateFields(): HasMany
    {
        return $this->hasMany(TransactionTemplateField::class, 'field_definition_id');
    }

    /**
     * Get scoped display overrides for this field definition.
     *
     * @return HasMany<TransactionFieldOverride, $this>
     */
    public function overrides(): HasMany
    {
        return $this->hasMany(TransactionFieldOverride::class, 'field_definition_id');
    }

    /**
     * Get transaction values recorded for this field definition.
     *
     * @return HasMany<TransactionFieldValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(TransactionFieldValue::class, 'field_definition_id');
    }
}
