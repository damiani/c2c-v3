<?php

namespace Database\Factories;

use App\Models\Listing;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $transaction = Transaction::factory()->create();

        return [
            'tenant_id' => $transaction->tenant_id,
            'transaction_id' => $transaction->id,
            'status' => Listing::STATUS_DRAFT,
            'property_details' => [
                'bedrooms' => fake()->numberBetween(1, 6),
                'bathrooms' => fake()->numberBetween(1, 5),
                'area' => fake()->numberBetween(700, 5500),
            ],
            'marketing_channels' => [],
            'published_at' => null,
        ];
    }

    /**
     * Attach the listing to an existing transaction.
     */
    public function forTransaction(Transaction $transaction): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $transaction->tenant_id,
            'transaction_id' => $transaction->getKey(),
        ]);
    }
}
