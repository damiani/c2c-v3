<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Form>
 */
class FormFactory extends Factory
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
            'title' => fake()->sentence(4),
            'source' => fake()->randomElement([
                Form::SOURCE_TENANT,
                Form::SOURCE_MLS,
                Form::SOURCE_SYSTEM,
            ]),
            'form_type' => fake()->randomElement(['purchase_agreement', 'lease_agreement', 'disclosure']),
            'external_identifier' => fake()->optional()->bothify('FORM-####'),
            'metadata' => [],
        ];
    }
}
