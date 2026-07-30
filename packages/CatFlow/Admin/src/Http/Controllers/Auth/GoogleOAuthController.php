<?php

namespace CatFlow\Admin\Http\Controllers\Auth;

use CatFlow\Admin\Http\Controllers\Controller;
use CatFlow\Auth\Services\GoogleAuthenticator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleOAuthController extends Controller
{
    public function __construct(private readonly GoogleAuthenticator $authenticator)
    {
    }

    /**
     * Redirect the user to Google's OAuth consent screen.
     *
     * access_type=offline + prompt=consent guarantee a refresh_token is issued on every
     * connection, since Google only includes it on the first-ever consent otherwise.
     *
     * @see https://developers.google.com/identity/protocols/oauth2/web-server#offline
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
            ])
            ->redirect();
    }

    /**
     * Handle Google's callback: link to an existing account by google_id or email,
     * or create a new user, then log them in.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')->withErrors([
                'email' => __('auth::auth.google_failed'),
            ]);
        }

        $user = $this->authenticator->authenticate($googleUser);

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
