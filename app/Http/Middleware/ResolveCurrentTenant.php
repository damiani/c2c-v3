<?php

namespace App\Http\Middleware;

use App\Actions\Tenancy\ResolveTenantContext as ResolveTenantContextAction;
use App\Tenancy\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

class ResolveCurrentTenant
{
    public function __construct(
        private ResolveTenantContextAction $resolveTenantContext,
        private CurrentTenant $currentTenant,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $sessionKey = config('tenancy.session_key', 'current_tenant_id');
        $requestedTenant = $request->session()->get($sessionKey);
        $tenant = $this->resolveTenantContext->handle($user, is_numeric($requestedTenant) ? (int) $requestedTenant : null);

        if ($tenant === null && $requestedTenant !== null) {
            $request->session()->forget($sessionKey);
            $tenant = $this->resolveTenantContext->handle($user);
        }

        if ($tenant !== null) {
            $request->session()->put($sessionKey, $tenant->id);
            $this->currentTenant->set($tenant);
            Context::addHidden('tenant_id', $tenant->id);
            Context::add('tenant_slug', $tenant->slug);
        }

        return $next($request);
    }
}
