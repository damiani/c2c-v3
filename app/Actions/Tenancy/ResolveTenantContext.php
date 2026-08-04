<?php

namespace App\Actions\Tenancy;

use App\Models\Tenant;
use App\Models\User;

class ResolveTenantContext
{
    /**
     * Resolve the current tenant for a user from an optional requested tenant.
     */
    public function handle(User $user, Tenant|int|null $tenant = null): ?Tenant
    {
        $query = $user->tenants()
            ->orderBy('tenant_memberships.created_at')
            ->orderBy('tenant_memberships.id');

        if ($tenant instanceof Tenant) {
            return $query->whereKey($tenant->getKey())->first();
        }

        if ($tenant !== null) {
            return $query->whereKey($tenant)->first();
        }

        return $query->first();
    }
}
