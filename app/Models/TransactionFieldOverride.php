<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\TransactionFieldOverrideFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $field_definition_id
 * @property string $scope_type
 * @property int $scope_id
 * @property string|null $label
 * @property string|null $unit
 * @property string|null $format
 * @property array<int|string, mixed>|null $option_labels
 * @property bool|null $is_required
 * @property bool|null $is_visible
 * @property int|null $sort_order
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'tenant_id',
    'field_definition_id',
    'scope_type',
    'scope_id',
    'label',
    'unit',
    'format',
    'option_labels',
    'is_required',
    'is_visible',
    'sort_order',
    'metadata',
])]
class TransactionFieldOverride extends Model
{
    use BelongsToTenant;

    public const string SCOPE_TENANT = TransactionFieldDefinition::SCOPE_TENANT;

    public const string SCOPE_TEAM = TransactionFieldDefinition::SCOPE_TEAM;

    public const string SCOPE_USER = TransactionFieldDefinition::SCOPE_USER;

    /** @use HasFactory<TransactionFieldOverrideFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'option_labels' => 'array',
            'is_required' => 'boolean',
            'is_visible' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the field definition this override customizes.
     *
     * @return BelongsTo<TransactionFieldDefinition, $this>
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(TransactionFieldDefinition::class, 'field_definition_id');
    }
}
