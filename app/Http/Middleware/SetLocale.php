<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = config('app.supported_locales', [config('app.locale')]);
        $defaultLocale = config('app.locale');
        $sessionLocale = $request->session()->get('ui_locale');
        $userLocale = $this->authenticatedUserLocale($request->user());

        $locale = $defaultLocale;

        if ($userLocale !== null && in_array($userLocale, $supportedLocales, true)) {
            $locale = $userLocale;
        } elseif (is_string($sessionLocale) && in_array($sessionLocale, $supportedLocales, true)) {
            $locale = $sessionLocale;
        }

        $request->session()->put('ui_locale', $locale);

        app()->setLocale($locale);

        return $next($request);
    }

    private function authenticatedUserLocale(?Authenticatable $user): ?string
    {
        if ($user === null) {
            return null;
        }

        $preferredLocale = trim((string) data_get($user, 'preferred_locale'));

        return $preferredLocale !== '' ? $preferredLocale : null;
    }
}
