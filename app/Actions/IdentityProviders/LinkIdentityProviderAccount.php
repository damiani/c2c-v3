<?php

namespace App\Actions\IdentityProviders;

use App\IdentityProviders\ExternalIdentity;
use App\Models\IdentityProviderAccount;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LinkIdentityProviderAccount
{
    public function handle(Tenant $tenant, User $user, ExternalIdentity $identity): IdentityProviderAccount
    {
        if (! $user->belongsToTenant($tenant)) {
            throw ValidationException::withMessages([
                'provider' => __('This identity provider account cannot be linked outside the current tenant.'),
            ]);
        }

        return DB::transaction(function () use ($tenant, $user, $identity): IdentityProviderAccount {
            $account = IdentityProviderAccount::query()
                ->whereBelongsTo($tenant)
                ->where('provider', $identity->provider)
                ->where('provider_user_id', $identity->providerUserId)
                ->lockForUpdate()
                ->first();

            if ($account !== null && $account->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'provider' => __('This identity provider account is already linked to another user.'),
                ]);
            }

            return IdentityProviderAccount::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'provider' => $identity->provider,
                    'provider_user_id' => $identity->providerUserId,
                ],
                [
                    'user_id' => $user->id,
                    'email' => $identity->email,
                    'metadata' => $identity->metadata,
                    'linked_at' => now(),
                ],
            );
        });
    }
}
