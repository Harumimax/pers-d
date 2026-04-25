<?php

namespace App\Notifications\Auth;

use App\Models\PasswordResetMailDelivery;
use App\Notifications\Channels\NotiSendMailChannel;
use App\Notifications\Messages\NotiSendMessage;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;

class ResetPasswordViaNotiSend extends ResetPasswordNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(string $token, public readonly ?int $deliveryId = null)
    {
        parent::__construct($token);
    }

    /**
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return [NotiSendMailChannel::class];
    }

    /**
     * @param  mixed  $notifiable
     */
    public function toNotiSend($notifiable): NotiSendMessage
    {
        $resetUrl = $this->resetUrl($notifiable);

        return new NotiSendMessage(
            to: (string) $notifiable->getEmailForPasswordReset(),
            subject: __('auth.reset_password_mail.subject'),
            text: implode("\n\n", [
                __('auth.reset_password_mail.intro'),
                __('auth.reset_password_mail.action_label').': '.$resetUrl,
                __('auth.reset_password_mail.expire', [
                    'count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
                ]),
                __('auth.reset_password_mail.outro'),
            ]),
            html: View::make('emails.auth.reset-password', [
                'resetUrl' => $resetUrl,
                'expireMinutes' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
            ])->render(),
        );
    }

    public function markNotiSendDelivered(): void
    {
        $this->updateDelivery([
            'delivery_status' => PasswordResetMailDelivery::STATUS_SENT,
            'delivered_at' => Carbon::now(),
            'delivery_error' => null,
            'delivery_error_message' => null,
        ]);
    }

    public function markNotiSendFailed(string $deliveryErrorCode, string $message): void
    {
        $this->updateDelivery([
            'delivery_status' => PasswordResetMailDelivery::STATUS_FAILED,
            'delivery_error' => $deliveryErrorCode,
            'delivery_error_message' => $message,
        ]);
    }

    public function markNotiSendTransportFailed(string $message): void
    {
        $this->markNotiSendFailed(
            PasswordResetMailDelivery::ERROR_MAIL_TRANSPORT_FAILED,
            $message,
        );
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function updateDelivery(array $attributes): void
    {
        if ($this->deliveryId === null) {
            return;
        }

        $delivery = PasswordResetMailDelivery::query()->find($this->deliveryId);

        if (! $delivery instanceof PasswordResetMailDelivery || $delivery->delivery_status !== PasswordResetMailDelivery::STATUS_PENDING) {
            return;
        }

        $delivery->forceFill($attributes)->save();
    }
}
