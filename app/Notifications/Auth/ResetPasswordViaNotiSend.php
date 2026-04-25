<?php

namespace App\Notifications\Auth;

use App\Notifications\Channels\NotiSendMailChannel;
use App\Notifications\Messages\NotiSendMessage;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\View;

class ResetPasswordViaNotiSend extends ResetPasswordNotification implements ShouldQueue
{
    use Queueable;

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
}
