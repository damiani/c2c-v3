<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

class SetUserLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->preferredLocale() ?? config('app.locale');

        if (! in_array($locale, array_keys(config('localization.supported_locales')), true)) {
            $locale = config('app.fallback_locale');
        }

        App::setLocale($locale);
        Context::addHidden('locale', $locale);

        return $next($request);
    }
}
