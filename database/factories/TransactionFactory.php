<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionTemplate;
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
            'transaction_template_id' => null,
            'transaction_template_version' => null,
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
            'field_schema_snapshot' => null,
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
     * Pin the transaction to a template version.
     */
    public function usingTemplate(TransactionTemplate $template): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $template->tenant_id ?? ($attributes['tenant_id'] ?? Tenant::factory()),
            'transaction_template_id' => $template->getKey(),
            'transaction_template_version' => $template->version,
            'transaction_type' => $template->transaction_type,
            'field_schema_snapshot' => [
                'template_key' => $template->template_key,
                'version' => $template->version,
            ],
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
