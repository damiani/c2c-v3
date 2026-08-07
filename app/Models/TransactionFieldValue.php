<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\TransactionFieldValueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $transaction_id
 * @property int $field_definition_id
 * @property int|null $template_field_id
 * @property int|null $updated_by_user_id
 * @property string $field_key
 * @property string $data_type
 * @property string|null $value_text
 * @property bool|null $value_boolean
 * @property int|null $value_integer
 * @property string|null $value_decimal
 * @property string|null $value_money_amount
 * @property string|null $value_currency
 * @property Carbon|null $value_date
 * @property Carbon|null $value_datetime
 * @property array<int|string, mixed>|null $value_json
 * @property string|null $value_unit
 * @property string|null $selected_option_key
 * @property string $source_type
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'tenant_id',
    'transaction_id',
    'field_definition_id',
    'template_field_id',
    'updated_by_user_id',
    'field_key',
    'data_type',
    'value_text',
    'value_boolean',
    'value_integer',
    'value_decimal',
    'value_money_amount',
    'value_currency',
    'value_date',
    'value_datetime',
    'value_json',
    'value_unit',
    'selected_option_key',
    'source_type',
    'metadata',
])]
class TransactionFieldValue extends Model
{
    use BelongsToTenant;

    public const string SOURCE_USER = 'user';

    public const string SOURCE_AI_EXTRACTION = 'ai_extraction';

    public const string SOURCE_CALCULATION = 'calculation';

    /** @use HasFactory<TransactionFieldValueFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value_boolean' => 'boolean',
            'value_decimal' => 'decimal:6',
            'value_money_amount' => 'decimal:2',
            'value_date' => 'immutable_date',
            'value_datetime' => 'immutable_datetime',
            'value_json' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the transaction this value belongs to.
     *
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the stable field definition for this value.
     *
     * @return BelongsTo<TransactionFieldDefinition, $this>
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(TransactionFieldDefinition::class, 'field_definition_id');
    }

    /**
     * Get the template placement that originally rendered this value.
     *
     * @return BelongsTo<TransactionTemplateField, $this>
     */
    public function templateField(): BelongsTo
    {
        return $this->belongsTo(TransactionTemplateField::class, 'template_field_id');
    }

    /**
     * Get the user who last updated this value.
     *
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    /**
     * Get this record's canonical PHP value based on its data type.
     */
    public function typedValue(): mixed
    {
        return match ($this->data_type) {
            TransactionFieldDefinition::TYPE_BOOLEAN => $this->value_boolean,
            TransactionFieldDefinition::TYPE_INTEGER => $this->value_integer,
            TransactionFieldDefinition::TYPE_DECIMAL,
            TransactionFieldDefinition::TYPE_PERCENTAGE,
            TransactionFieldDefinition::TYPE_QUANTITY => $this->value_decimal,
            TransactionFieldDefinition::TYPE_MONEY => [
                'amount' => $this->value_money_amount,
                'currency' => $this->value_currency,
            ],
            TransactionFieldDefinition::TYPE_DATE => $this->value_date,
            TransactionFieldDefinition::TYPE_DATETIME => $this->value_datetime,
            TransactionFieldDefinition::TYPE_SELECT => $this->selected_option_key,
            TransactionFieldDefinition::TYPE_JSON => $this->value_json,
            default => $this->value_text,
        };
    }

    /**
     * Store a canonical value in the data-type specific columns.
     */
    public function setTypedValue(mixed $value, ?string $currency = null, ?string $unit = null): void
    {
        $this->value_text = null;
        $this->value_boolean = null;
        $this->value_integer = null;
        $this->value_decimal = null;
        $this->value_money_amount = null;
        $this->value_currency = null;
        $this->value_date = null;
        $this->value_datetime = null;
        $this->value_json = null;
        $this->value_unit = $unit;
        $this->selected_option_key = null;

        match ($this->data_type) {
            TransactionFieldDefinition::TYPE_BOOLEAN => $this->value_boolean = (bool) $value,
            TransactionFieldDefinition::TYPE_INTEGER => $this->value_integer = (int) $value,
            TransactionFieldDefinition::TYPE_DECIMAL,
            TransactionFieldDefinition::TYPE_PERCENTAGE,
            TransactionFieldDefinition::TYPE_QUANTITY => $this->value_decimal = (string) $value,
            TransactionFieldDefinition::TYPE_MONEY => $this->setMoneyValue($value, $currency),
            TransactionFieldDefinition::TYPE_DATE => $this->value_date = $value,
            TransactionFieldDefinition::TYPE_DATETIME => $this->value_datetime = $value,
            TransactionFieldDefinition::TYPE_SELECT => $this->selected_option_key = (string) $value,
            TransactionFieldDefinition::TYPE_JSON => $this->value_json = is_array($value) ? $value : ['value' => $value],
            default => $this->value_text = (string) $value,
        };
    }

    /**
     * Store a canonical money value and ISO currency.
     */
    private function setMoneyValue(mixed $value, ?string $currency): void
    {
        if (is_array($value)) {
            $this->value_money_amount = (string) ($value['amount'] ?? 0);
            $this->value_currency = (string) ($value['currency'] ?? $currency ?? 'USD');

            return;
        }

        $this->value_money_amount = (string) $value;
        $this->value_currency = $currency ?? 'USD';
    }
}
