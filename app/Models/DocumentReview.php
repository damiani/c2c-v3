<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\DocumentReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $document_id
 * @property int $reviewer_user_id
 * @property string $reviewer_role
 * @property string $status
 * @property string|null $notes
 * @property array<string, mixed>|null $annotations
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tenant_id', 'document_id', 'reviewer_user_id', 'reviewer_role', 'status', 'notes', 'annotations', 'reviewed_at'])]
class DocumentReview extends Model
{
    use BelongsToTenant;

    public const string STATUS_PENDING = 'pending';

    public const string STATUS_IN_REVIEW = 'in_review';

    public const string STATUS_APPROVED = 'approved';

    public const string STATUS_FLAGGED = 'flagged';

    /** @use HasFactory<DocumentReviewFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'annotations' => 'array',
            'reviewed_at' => 'immutable_datetime',
        ];
    }

    /**
     * Get the reviewed document.
     *
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the reviewer user.
     *
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }
}
