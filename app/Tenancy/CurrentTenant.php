<?php

namespace App\Tenancy;

use App\Models\Tenant;

class CurrentTenant
{
    private ?Tenant $tenant = null;

    /**
     * Store the resolved tenant for the current request.
     */
    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    /**
     * Get the resolved tenant for the current request.
     */
    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    /**
     * Get the current tenant id.
     */
    public function id(): ?int
    {
        return $this->tenant?->id;
    }
}
