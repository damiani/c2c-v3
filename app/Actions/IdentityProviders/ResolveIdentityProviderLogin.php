<?php

namespace App\Actions\IdentityProviders;

use App\IdentityProviders\ExternalIdentity;
use App\Models\IdentityProviderAccount;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResolveIdentityProviderLogin
{
    public function __construct(private LinkIdentityProviderAccount $linkIdentityProviderAccount) {}

    public function handle(Tenant $tenant, ExternalIdentity $identity): User
    {
        return DB::transaction(function () use ($tenant, $identity): User {
            $account = IdentityProviderAccount::query()
                ->whereBelongsTo($tenant)
                ->where('provider', $identity->provider)
                ->where('provider_user_id', $identity->providerUserId)
                ->lockForUpdate()
                ->first();

            if ($account !== null) {
                $this->linkIdentityProviderAccount->handle($tenant, $account->user, $identity);

                return $account->user;
            }

            $user = $this->resolveTenantUserByEmail($tenant, $identity)
                ?? $this->createTenantUser($tenant, $identity);

            $this->linkIdentityProviderAccount->handle($tenant, $user, $identity);

            return $user;
        });
    }

    private function resolveTenantUserByEmail(Tenant $tenant, ExternalIdentity $identity): ?User
    {
        if ($identity->email === null) {
            return null;
        }

        $user = User::query()
            ->where('email', $identity->email)
            ->first();

        if ($user === null) {
            return null;
        }

        if (! $user->belongsToTenant($tenant)) {
            $user->tenantMemberships()->create([
                'tenant_id' => $tenant->id,
                'role' => TenantMembership::ROLE_MEMBER,
            ]);
        }

        return $user;
    }

    private function createTenantUser(Tenant $tenant, ExternalIdentity $identity): User
    {
        $email = $identity->email ?? sprintf('%s-%s@example.invalid', $identity->provider, Str::lower($identity->providerUserId));

        $user = User::query()->create([
            'name' => $identity->name ?: $email,
            'email' => $email,
            'locale' => config('app.locale'),
            'password' => Str::password(40),
        ]);

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        $user->tenantMemberships()->create([
            'tenant_id' => $tenant->id,
            'role' => TenantMembership::ROLE_MEMBER,
        ]);

        return $user;
    }
}
