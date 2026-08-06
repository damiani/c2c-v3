<?php

namespace Database\Factories;

use App\Authorization\TenantPermissionRegistry;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->jobTitle();

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'permissions' => [],
            'is_system' => false,
        ];
    }

    /**
     * Indicate that the role is a built-in system role.
     */
    public function system(string $slug): static
    {
        return $this->state(function (array $attributes) use ($slug) {
            $definition = TenantPermissionRegistry::systemRole($slug);

            return [
                'name' => $definition['name'] ?? Str::headline($slug),
                'slug' => $slug,
                'description' => $definition['description'] ?? null,
                'permissions' => $definition['permissions'] ?? [],
                'is_system' => true,
            ];
        });
    }
}
