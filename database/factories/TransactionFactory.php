<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'owner_user_id' => User::factory(),
            'transaction_type' => fake()->randomElement([
                Transaction::TYPE_RESIDENTIAL_SALE,
                Transaction::TYPE_PURCHASE,
                Transaction::TYPE_RESIDENTIAL_RENTAL,
                Transaction::TYPE_COMMERCIAL_SALE,
                Transaction::TYPE_COMMERCIAL_LEASE,
            ]),
            'status' => Transaction::STATUS_DRAFT,
            'name' => fake()->streetAddress(),
            'property_address' => fake()->address(),
            'property_data' => [
                'city' => fake()->city(),
                'state' => fake()->stateAbbr(),
            ],
            'metadata' => [],
            'opened_at' => now(),
            'closed_at' => null,
        ];
    }

    /**
     * Scope the transaction to an existing tenant.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->getKey(),
        ]);
    }

    /**
     * Assign an owner user to the transaction.
     */
    public function ownedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_user_id' => $user->getKey(),
        ]);
    }

    /**
     * Indicate that the transaction is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Transaction::STATUS_ACTIVE,
        ]);
    }

    /**
     * Indicate that the transaction is a residential sale.
     */
    public function residentialSale(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => Transaction::TYPE_RESIDENTIAL_SALE,
        ]);
    }

    /**
     * Indicate that the transaction is a commercial lease.
     */
    public function commercialLease(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => Transaction::TYPE_COMMERCIAL_LEASE,
        ]);
    }
}
