<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\FormFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $title
 * @property string $source
 * @property string|null $form_type
 * @property string|null $external_identifier
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tenant_id', 'title', 'source', 'form_type', 'external_identifier', 'metadata'])]
class Form extends Model
{
    use BelongsToTenant;

    public const string SOURCE_TENANT = 'tenant';

    public const string SOURCE_MLS = 'mls';

    public const string SOURCE_SYSTEM = 'system';

    /** @use HasFactory<FormFactory> */
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
     * Get documents created from the form.
     *
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
