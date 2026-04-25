<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\Auth\ResetPasswordViaNotiSend;
use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'preferred_locale', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function dictionaries(): HasMany
    {
        return $this->hasMany(UserDictionary::class);
    }

    public function gameSessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

    public function hasPreferredLocale(): bool
    {
        $preferredLocale = trim((string) $this->preferred_locale);

        return $preferredLocale !== '';
    }

    public function preferredLocaleOrDefault(): string
    {
        return $this->hasPreferredLocale()
            ? (string) $this->preferred_locale
            : (string) app()->getLocale();
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(
            (new ResetPasswordViaNotiSend($token))
                ->locale($this->preferredLocaleOrDefault())
        );
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(
            (new VerifyEmailNotification())
                ->locale($this->preferredLocaleOrDefault())
        );
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
}
