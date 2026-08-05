<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentExtraction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentExtraction>
 */
class DocumentExtractionFactory extends Factory
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
            'field_name' => fake()->randomElement(['buyer_name', 'property_address', 'closing_date', 'rent_amount']),
            'extracted_value' => fake()->sentence(3),
            'confidence_score' => fake()->randomFloat(4, 0.5000, 0.9999),
            'agent_confirmed' => false,
            'correction_value' => null,
            'confirmed_by_user_id' => null,
            'confirmed_at' => null,
            'metadata' => [],
        ];
    }

    /**
     * Attach the extraction result to an existing document.
     */
    public function forDocument(Document $document): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $document->tenant_id,
            'document_id' => $document->getKey(),
        ]);
    }
}
