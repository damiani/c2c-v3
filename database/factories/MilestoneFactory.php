<?php

namespace Database\Factories;

use App\Models\Milestone;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Milestone>
 */
class MilestoneFactory extends Factory
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
            'title' => fake()->sentence(3),
            'status' => Milestone::STATUS_PENDING,
            'due_at' => now()->addDays(fake()->numberBetween(3, 45)),
            'completed_at' => null,
            'sort_order' => fake()->numberBetween(1, 10),
            'metadata' => [],
        ];
    }

    /**
     * Attach the milestone to an existing transaction.
     */
    public function forTransaction(Transaction $transaction): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $transaction->tenant_id,
            'transaction_id' => $transaction->getKey(),
        ]);
    }
}
