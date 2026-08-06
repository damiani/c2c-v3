<?php

namespace App\Tenancy;

use App\Actions\Tenancy\EnsureSystemRoles;
use App\Models\Tenant;

class DefaultTenant
{
    public function __construct(private EnsureSystemRoles $ensureSystemRoles) {}

    /**
     * Find or create the default retail C2C tenant.
     */
    public function findOrCreate(): Tenant
    {
        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => config('tenancy.default_tenant.slug', 'c2c')],
            [
                'name' => config('tenancy.default_tenant.name', 'C2C'),
                'status' => Tenant::STATUS_ACTIVE,
                'supported_locales' => array_keys(config('localization.supported_locales', ['en' => 'English'])),
            ],
        );

        $this->ensureSystemRoles->handle($tenant);

        return $tenant;
    }
}
