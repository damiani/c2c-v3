<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Tenant;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
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
            'transaction_id' => null,
            'display_name' => fake()->name(),
            'company_name' => fake()->optional()->company(),
            'contact_type' => fake()->randomElement([
                Contact::TYPE_BUYER,
                Contact::TYPE_SELLER,
                Contact::TYPE_TENANT,
                Contact::TYPE_LANDLORD,
                Contact::TYPE_SERVICE_PROVIDER,
            ]),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'metadata' => [],
        ];
    }

    /**
     * Attach the contact to an existing transaction.
     */
    public function forTransaction(Transaction $transaction): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $transaction->tenant_id,
            'transaction_id' => $transaction->getKey(),
        ]);
    }
}
