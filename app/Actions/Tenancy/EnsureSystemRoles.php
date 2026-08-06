<?php

namespace App\Actions\Tenancy;

use App\Authorization\TenantPermissionRegistry;
use App\Models\Role;
use App\Models\Tenant;

class EnsureSystemRoles
{
    /**
     * Ensure a tenant has all built-in permission-bearing role definitions.
     *
     * @return array<string, Role>
     */
    public function handle(Tenant $tenant): array
    {
        $roles = [];

        foreach (TenantPermissionRegistry::systemRoles() as $slug => $definition) {
            $role = Role::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'slug' => $slug,
                ],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'permissions' => $definition['permissions'],
                    'is_system' => true,
                ],
            );

            $roles[$slug] = $role;
        }

        return $roles;
    }
}
