<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\Auth\ResetPasswordViaNotiSend;
use Throwable;
use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'preferred_locale', 'tg_login', 'tg_chat_id', 'tg_linked_at', 'password'])]
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

    public function telegramSetting(): HasOne
    {
        return $this->hasOne(TelegramSetting::class);
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
        $locale = $this->preferredLocaleOrDefault();

        $delivery = PasswordResetMailDelivery::query()->create([
            'user_id' => $this->getKey(),
            'email' => (string) $this->getEmailForPasswordReset(),
            'locale' => $locale,
            'delivery_status' => PasswordResetMailDelivery::STATUS_PENDING,
        ]);

        try {
            $this->notify(
                (new ResetPasswordViaNotiSend($token, $delivery->id))
                    ->locale($locale)
            );
        } catch (Throwable $exception) {
            report($exception);

            $delivery->forceFill([
                'delivery_status' => PasswordResetMailDelivery::STATUS_FAILED,
                'delivery_error' => PasswordResetMailDelivery::ERROR_DISPATCH_FAILED,
                'delivery_error_message' => $exception->getMessage(),
            ])->save();
        }
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
            'tg_linked_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
