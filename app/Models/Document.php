<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int|null $transaction_id
 * @property int|null $form_id
 * @property int|null $uploaded_by_user_id
 * @property string $title
 * @property string $document_type
 * @property string $status
 * @property string $storage_disk
 * @property string $storage_path
 * @property string $original_filename
 * @property string|null $mime_type
 * @property int|null $file_size
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'tenant_id',
    'transaction_id',
    'form_id',
    'uploaded_by_user_id',
    'title',
    'document_type',
    'status',
    'storage_disk',
    'storage_path',
    'original_filename',
    'mime_type',
    'file_size',
    'metadata',
])]
class Document extends Model
{
    use BelongsToTenant;

    public const string STATUS_UPLOADED = 'uploaded';

    public const string STATUS_IN_REVIEW = 'in_review';

    public const string STATUS_APPROVED = 'approved';

    public const string STATUS_SIGNED = 'signed';

    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * Get the transaction for the document.
     *
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the form used to create the document.
     *
     * @return BelongsTo<Form, $this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Get the user who uploaded the document.
     *
     * @return BelongsTo<User, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    /**
     * Get document reviews.
     *
     * @return HasMany<DocumentReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(DocumentReview::class);
    }

    /**
     * Get document extractions.
     *
     * @return HasMany<DocumentExtraction, $this>
     */
    public function extractions(): HasMany
    {
        return $this->hasMany(DocumentExtraction::class);
    }
}
