<?php

namespace Database\Factories;

use App\Actions\Documents\BuildDocumentStoragePath;
use App\Models\Document;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $transaction = Transaction::factory()->create();
        $filename = fake()->slug().'.pdf';

        return [
            'tenant_id' => $transaction->tenant_id,
            'transaction_id' => $transaction->id,
            'form_id' => null,
            'uploaded_by_user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'document_type' => fake()->randomElement(['purchase_agreement', 'lease_agreement', 'disclosure']),
            'status' => Document::STATUS_UPLOADED,
            'storage_disk' => config('documents.storage.disk', 'documents'),
            'storage_path' => app(BuildDocumentStoragePath::class)->handle($transaction->tenant_id, $filename),
            'original_filename' => $filename,
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(10_000, 5_000_000),
            'metadata' => [],
        ];
    }

    /**
     * Attach the document to an existing transaction.
     */
    public function forTransaction(Transaction $transaction): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $transaction->tenant_id,
            'transaction_id' => $transaction->getKey(),
            'storage_path' => app(BuildDocumentStoragePath::class)->handle(
                $transaction->tenant_id,
                $attributes['original_filename'] ?? fake()->slug().'.pdf',
            ),
        ]);
    }

    /**
     * Indicate who uploaded the document.
     */
    public function uploadedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'uploaded_by_user_id' => $user->getKey(),
        ]);
    }
}
