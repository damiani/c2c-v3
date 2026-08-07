<?php

namespace App\Policies;

use App\Authorization\TenantPermission;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view transactions in a tenant.
     */
    public function viewAnyForTenant(User $user, Tenant $tenant): bool
    {
        return $user->belongsToTenant($tenant)
            && (
                $user->hasTenantPermission($tenant, TenantPermission::TransactionsViewOwn)
                || $user->hasTenantPermission($tenant, TenantPermission::TransactionsViewTeam)
                || $user->hasTenantPermission($tenant, TenantPermission::TransactionsViewAll)
            );
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Transaction $transaction): bool
    {
        $tenant = $transaction->tenant;

        if ($tenant === null || ! $user->belongsToTenant($tenant)) {
            return false;
        }

        if (
            $user->hasTenantPermission($tenant, TenantPermission::TransactionsViewAll)
            || $user->hasTenantPermission($tenant, TenantPermission::TransactionsViewTeam)
        ) {
            return true;
        }

        return $user->hasTenantPermission($tenant, TenantPermission::TransactionsViewOwn)
            && $transaction->owner_user_id === $user->getKey();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->tenantMemberships()->exists();
    }

    /**
     * Determine whether the user can create transactions in a tenant.
     */
    public function createForTenant(User $user, Tenant $tenant): bool
    {
        return $user->belongsToTenant($tenant)
            && $user->hasTenantPermission($tenant, TenantPermission::TransactionsCreate);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Transaction $transaction): bool
    {
        $tenant = $transaction->tenant;

        if ($tenant === null || ! $user->belongsToTenant($tenant)) {
            return false;
        }

        if (
            $user->hasTenantPermission($tenant, TenantPermission::TransactionsUpdateAll)
            || $user->hasTenantPermission($tenant, TenantPermission::TransactionsUpdateTeam)
        ) {
            return true;
        }

        return $user->hasTenantPermission($tenant, TenantPermission::TransactionsUpdateOwn)
            && $transaction->owner_user_id === $user->getKey();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Transaction $transaction): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Transaction $transaction): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Transaction $transaction): bool
    {
        return false;
    }
}
