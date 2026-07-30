<?php

namespace CatFlow\Auth\Services;

use CatFlow\Auth\Models\GoogleAccount;
use CatFlow\User\Models\User;
use Illuminate\Auth\Events\Registered;
use Laravel\Socialite\Two\User as SocialiteUser;

class GoogleAuthenticator
{
    /**
     * Resolve the local user for a Google sign-in: link to an existing
     * account by google_id or email, or create a new user, then persist
     * the (possibly refreshed) Google account details.
     */
    public function authenticate(SocialiteUser $googleUser): User
    {
        $existingAccount = GoogleAccount::where('google_id', $googleUser->getId())->first();

        $user = $existingAccount?->user
            ?? User::where('email', $googleUser->getEmail())->first()
            ?? $this->createUser($googleUser);

        $this->storeAccount($user, $googleUser, $existingAccount);

        return $user;
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

    private function storeAccount(User $user, SocialiteUser $googleUser, ?GoogleAccount $existingAccount): GoogleAccount
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
