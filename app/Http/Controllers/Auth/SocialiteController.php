<?php

namespace App\Http\Controllers\Auth;

use App\Actions\IdentityProviders\LinkIdentityProviderAccount;
use App\Actions\IdentityProviders\ResolveIdentityProviderLogin;
use App\Http\Controllers\Controller;
use App\IdentityProviders\ExternalIdentity;
use App\Models\Tenant;
use App\Tenancy\CurrentTenant;
use App\Tenancy\DefaultTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;

class SocialiteController extends Controller
{
    public function redirect(string $provider, Request $request, CurrentTenant $currentTenant, DefaultTenant $defaultTenant): RedirectResponse|Response
    {
        $providerConfig = $this->providerConfig($provider);
        $tenant = $request->user() !== null
            ? $currentTenant->get()
            : $defaultTenant->findOrCreate();

        abort_if($tenant === null, 404);

        $request->session()->put(config('sso.session.intent'), $request->user() === null ? 'login' : 'link');
        $request->session()->put(config('sso.session.tenant_id'), $tenant->id);

        return Socialite::driver($providerConfig['driver'])
            ->scopes($providerConfig['scopes'] ?? [])
            ->redirect();
    }

    public function callback(
        string $provider,
        Request $request,
        LinkIdentityProviderAccount $linkIdentityProviderAccount,
        ResolveIdentityProviderLogin $resolveIdentityProviderLogin,
        DefaultTenant $defaultTenant,
    ): RedirectResponse {
        $providerConfig = $this->providerConfig($provider);
        $tenant = $this->callbackTenant($request, $defaultTenant);
        $identity = ExternalIdentity::fromSocialite(
            provider: $provider,
            user: Socialite::driver($providerConfig['driver'])->user(),
        );

        if ($request->user() !== null || $request->session()->get(config('sso.session.intent')) === 'link') {
            $user = $request->user();

            abort_if($user === null, 403);

            $linkIdentityProviderAccount->handle($tenant, $user, $identity);
            $this->clearSsoSession($request);

            return redirect()
                ->route('security.edit')
                ->with('status', __('Google account connected.'));
        }

        $user = $resolveIdentityProviderLogin->handle($tenant, $identity);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put(config('tenancy.session_key'), $tenant->id);
        $this->clearSsoSession($request);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * @return array{label: string, driver: string, scopes?: list<string>, enabled: bool}
     */
    private function providerConfig(string $provider): array
    {
        $providerConfig = config("sso.providers.{$provider}");

        abort_if(! is_array($providerConfig) || ($providerConfig['enabled'] ?? false) !== true, 404);

        return $providerConfig;
    }

    private function callbackTenant(Request $request, DefaultTenant $defaultTenant): Tenant
    {
        $tenantId = $request->session()->get(config('sso.session.tenant_id'));

        if (is_numeric($tenantId)) {
            $tenant = Tenant::query()->find((int) $tenantId);

            if ($tenant !== null) {
                return $tenant;
            }
        }

        return $defaultTenant->findOrCreate();
    }

    private function clearSsoSession(Request $request): void
    {
        $request->session()->forget([
            config('sso.session.intent'),
            config('sso.session.tenant_id'),
        ]);
    }
}
