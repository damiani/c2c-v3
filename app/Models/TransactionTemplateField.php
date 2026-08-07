<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\TransactionTemplateFieldFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $tenant_id
 * @property int $transaction_template_id
 * @property int $field_definition_id
 * @property string $field_key
 * @property string $section
 * @property string|null $label
 * @property string|null $unit
 * @property string|null $format
 * @property array<int|string, mixed>|null $options
 * @property bool $is_required
 * @property bool $is_visible
 * @property int $sort_order
 * @property array<string, mixed>|null $visibility_rules
 * @property array<string, mixed>|null $validation_rules
 * @property array<string, mixed>|null $calculation_rules
 * @property array<string, mixed>|null $date_trigger_rules
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'tenant_id',
    'transaction_template_id',
    'field_definition_id',
    'field_key',
    'section',
    'label',
    'unit',
    'format',
    'options',
    'is_required',
    'is_visible',
    'sort_order',
    'visibility_rules',
    'validation_rules',
    'calculation_rules',
    'date_trigger_rules',
    'metadata',
])]
class TransactionTemplateField extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<TransactionTemplateFieldFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'is_visible' => 'boolean',
            'visibility_rules' => 'array',
            'validation_rules' => 'array',
            'calculation_rules' => 'array',
            'date_trigger_rules' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the template that owns this field placement.
     *
     * @return BelongsTo<TransactionTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(TransactionTemplate::class, 'transaction_template_id');
    }

    /**
     * Get the stable field definition for this placement.
     *
     * @return BelongsTo<TransactionFieldDefinition, $this>
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(TransactionFieldDefinition::class, 'field_definition_id');
    }

    /**
     * Get transaction values written through this template field.
     *
     * @return HasMany<TransactionFieldValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(TransactionFieldValue::class, 'template_field_id');
    }
}
