<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentReview;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentReview>
 */
class DocumentReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $document = Document::factory()->create();

        return [
            'tenant_id' => $document->tenant_id,
            'document_id' => $document->id,
            'reviewer_user_id' => User::factory(),
            'reviewer_role' => TenantMembership::ROLE_MEMBER,
            'status' => DocumentReview::STATUS_PENDING,
            'notes' => null,
            'annotations' => [],
            'reviewed_at' => null,
        ];
    }

    /**
     * Attach the review to an existing document.
     */
    public function forDocument(Document $document): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $document->tenant_id,
            'document_id' => $document->getKey(),
        ]);
    }

    /**
     * Assign the review to an existing user.
     */
    public function reviewedBy(User $user, string $role = TenantMembership::ROLE_MEMBER): static
    {
        return $this->state(fn (array $attributes) => [
            'reviewer_user_id' => $user->getKey(),
            'reviewer_role' => $role,
        ]);
    }
}
