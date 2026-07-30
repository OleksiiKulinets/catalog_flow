<?php

namespace CatFlow\User\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use CatFlow\Auth\Models\GoogleAccount;
use CatFlow\Settings\Models\ApiKey;

#[Fillable(['name', 'email', 'password', 'locale'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Laravel's default factory-name convention only resolves models under
     * the app's root namespace (App\Models\*); this model lives under
     * CatFlow\User\Models, so the factory has to be pointed at explicitly.
     */
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function apiKey(): HasOne
    {
        return $this->hasOne(ApiKey::class);
    }

    public function googleAccount(): HasOne
    {
        return $this->hasOne(GoogleAccount::class);
    }

    /**
     * False for OAuth-only accounts, which have no local password at all.
     */
    public function hasUsablePassword(): bool
    {
        return $this->password !== null;
    }
}
