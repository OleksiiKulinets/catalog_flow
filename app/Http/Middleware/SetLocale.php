<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // A freshly created (not yet re-fetched) user's in-memory `locale`
        // attribute can be null even though the `locale` column defaults to
        // 'en' at the database level — Eloquent doesn't hydrate DB-applied
        // defaults back onto the instance after create(). Guard against
        // passing that null through to App::setLocale(), which would leave
        // the app locale null for the rest of the request.
        if ($request->user()?->locale) {
            App::setLocale($request->user()->locale);
        }

        return $next($request);
    }
}
