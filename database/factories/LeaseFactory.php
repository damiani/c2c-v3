<?php

namespace Database\Factories;

use App\Models\Lease;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lease>
 */
class LeaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $transaction = Transaction::factory()->create();
        $leaseType = fake()->randomElement([Lease::TYPE_RESIDENTIAL, Lease::TYPE_COMMERCIAL]);

        return [
            'tenant_id' => $transaction->tenant_id,
            'transaction_id' => $transaction->id,
            'lease_type' => $leaseType,
            'status' => Lease::STATUS_DRAFT,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'rent_amount' => fake()->randomFloat(2, 1200, 25_000),
            'rent_currency' => 'USD',
            'escalation_schedule' => [],
            'renewal_lead_months' => $leaseType === Lease::TYPE_COMMERCIAL ? 5 : 4,
            'metadata' => [],
        ];
    }

    /**
     * Attach the lease to an existing transaction.
     */
    public function forTransaction(Transaction $transaction): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $transaction->tenant_id,
            'transaction_id' => $transaction->getKey(),
        ]);
    }

    /**
     * Indicate that the lease is commercial.
     */
    public function commercial(): static
    {
        return $this->state(fn (array $attributes) => [
            'lease_type' => Lease::TYPE_COMMERCIAL,
            'renewal_lead_months' => 5,
        ]);
    }

    /**
     * Indicate that the lease is residential.
     */
    public function residential(): static
    {
        return $this->state(fn (array $attributes) => [
            'lease_type' => Lease::TYPE_RESIDENTIAL,
            'renewal_lead_months' => 4,
        ]);
    }
}
