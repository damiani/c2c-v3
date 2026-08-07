<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TransactionTemplate>
 */
class TransactionTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'tenant_id' => null,
            'created_by_user_id' => null,
            'scope_type' => TransactionTemplate::SCOPE_SYSTEM,
            'scope_id' => 0,
            'template_key' => Str::slug($name, '_'),
            'name' => Str::title($name),
            'transaction_type' => Transaction::TYPE_RESIDENTIAL_SALE,
            'description' => fake()->sentence(),
            'version' => 1,
            'status' => TransactionTemplate::STATUS_ACTIVE,
            'is_default' => false,
            'metadata' => [],
        ];
    }

    /**
     * Indicate that the template is the system residential sale default.
     */
    public function residentialSaleDefault(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => null,
            'scope_type' => TransactionTemplate::SCOPE_SYSTEM,
            'scope_id' => 0,
            'template_key' => TransactionTemplate::TEMPLATE_RESIDENTIAL_SALE,
            'name' => 'Residential Sale',
            'transaction_type' => Transaction::TYPE_RESIDENTIAL_SALE,
            'description' => 'Default residential sale transaction workspace.',
            'version' => 1,
            'status' => TransactionTemplate::STATUS_ACTIVE,
            'is_default' => true,
        ]);
    }

    /**
     * Scope the template to a tenant.
     */
    public function forTenant(Tenant $tenant, ?User $createdBy = null): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->getKey(),
            'created_by_user_id' => $createdBy?->getKey(),
            'scope_type' => TransactionTemplate::SCOPE_TENANT,
            'scope_id' => $tenant->getKey(),
        ]);
    }
}
