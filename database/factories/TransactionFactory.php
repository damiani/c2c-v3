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
}
