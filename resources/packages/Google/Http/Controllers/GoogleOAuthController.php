<?php

namespace Packages\Google\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Packages\Google\Models\GoogleAccount;

class GoogleOAuthController extends Controller
{
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
                'email' => __('app.auth.google_failed'),
            ]);
        }

        $existingAccount = GoogleAccount::where('google_id', $googleUser->getId())->first();

        $user = $existingAccount?->user
            ?? User::where('email', $googleUser->getEmail())->first()
            ?? $this->createUser($googleUser);

        $this->storeGoogleAccount($user, $googleUser, $existingAccount);

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Google has already verified this email address, so the account is created
     * pre-verified and with no local password at all. OAuth is itself the
     * authentication mechanism; Google's own guidance is not to make a user
     * invent a password during an OAuth sign-in. A password is only ever
     * created later, on request, from Settings.
     */
    private function createUser(SocialiteUser $googleUser): User
    {
        $user = User::create([
            'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: $googleUser->getEmail(),
            'email' => $googleUser->getEmail(),
            'password' => null,
        ]);

        // email_verified_at is intentionally not mass-assignable; Google already verified this address.
        $user->forceFill(['email_verified_at' => now()])->save();

        event(new Registered($user));

        return $user;
    }

    private function storeGoogleAccount(User $user, SocialiteUser $googleUser, ?GoogleAccount $existingAccount): GoogleAccount
    {
        return GoogleAccount::updateOrCreate(
            ['google_id' => $googleUser->getId()],
            [
                'user_id' => $user->id,
                'email' => $googleUser->getEmail(),
                'name' => $googleUser->getName(),
                'avatar' => $googleUser->getAvatar(),
                'access_token' => $googleUser->token,
                // Google omits refresh_token when one was already issued and isn't being re-consented; keep the old one in that case.
                'refresh_token' => $googleUser->refreshToken ?? $existingAccount?->refresh_token,
                'token_type' => 'Bearer',
                'expires_at' => now()->addSeconds($googleUser->expiresIn),
                'scopes' => $googleUser->approvedScopes ?: ($existingAccount?->scopes ?? []),
            ]
        );
    }
}
