<?php

namespace Packages\Google\Services;

use Illuminate\Support\Facades\Http;
use Packages\Google\Models\GoogleAccount;
use RuntimeException;

class GoogleTokenRefresher
{
    /**
     * Return a valid access token for the given account, refreshing it first if it has expired.
     *
     * @throws RuntimeException if the account has no refresh token, or Google rejects the refresh
     */
    public function validAccessToken(GoogleAccount $account): string
    {
        if (! $account->isExpired()) {
            return $account->access_token;
        }

        return $this->refresh($account);
    }

    /**
     * Exchange the stored refresh token for a new access token.
     *
     * @see https://developers.google.com/identity/protocols/oauth2/web-server#offline
     */
    public function refresh(GoogleAccount $account): string
    {
        if (! $account->refresh_token) {
            throw new RuntimeException(
                'Google account has no refresh token; the user must reconnect their account.'
            );
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'refresh_token',
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $account->refresh_token,
        ]);

        if ($response->failed()) {
            // invalid_grant means the refresh token was revoked or expired; the user must reconnect.
            throw new RuntimeException(
                'Failed to refresh Google access token: '.$response->json('error', $response->body())
            );
        }

        $data = $response->json();

        $account->update([
            'access_token' => $data['access_token'],
            'token_type' => $data['token_type'] ?? $account->token_type,
            'expires_at' => now()->addSeconds($data['expires_in']),
            // Google omits refresh_token on refresh responses; keep the existing one.
        ]);

        return $account->access_token;
    }
}
