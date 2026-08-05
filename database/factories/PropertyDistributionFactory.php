<?php

namespace Database\Factories;

use App\Models\Listing;
use App\Models\PropertyDistribution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyDistribution>
 */
class PropertyDistributionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $listing = Listing::factory()->create();

        return [
            'tenant_id' => $listing->tenant_id,
            'transaction_id' => $listing->transaction_id,
            'listing_id' => $listing->id,
            'channel' => PropertyDistribution::CHANNEL_WHATSAPP,
            'recipient_groups' => [
                ['name' => 'Brokerage Partners', 'external_id' => fake()->uuid()],
            ],
            'status' => PropertyDistribution::STATUS_DRAFT,
            'sent_at' => null,
            'delivery_status' => [],
            'metadata' => [],
        ];
    }
}
