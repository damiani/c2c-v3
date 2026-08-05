<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\DocumentExtractionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $document_id
 * @property string $field_name
 * @property string|null $extracted_value
 * @property string|null $confidence_score
 * @property bool $agent_confirmed
 * @property string|null $correction_value
 * @property int|null $confirmed_by_user_id
 * @property Carbon|null $confirmed_at
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'tenant_id',
    'document_id',
    'field_name',
    'extracted_value',
    'confidence_score',
    'agent_confirmed',
    'correction_value',
    'confirmed_by_user_id',
    'confirmed_at',
    'metadata',
])]
class DocumentExtraction extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<DocumentExtractionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confidence_score' => 'decimal:4',
            'agent_confirmed' => 'boolean',
            'confirmed_at' => 'immutable_datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the document for the extraction.
     *
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the user that confirmed the extraction.
     *
     * @return BelongsTo<User, $this>
     */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }
}
