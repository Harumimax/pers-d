<?php

namespace App\Notifications\DictionarySubscriptions;

use App\Notifications\Channels\NotiSendMailChannel;
use App\Notifications\Messages\NotiSendMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\View;

class DictionaryShareInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $ownerEmail,
        private readonly string $dictionaryName,
        private readonly string $invitationUrl,
        private readonly string $registerUrl,
        private readonly bool $hasExistingAccount,
    ) {
    }

    /**
     * @param mixed $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return [NotiSendMailChannel::class];
    }

    /**
     * @param mixed $notifiable
     */
    public function toNotiSend($notifiable): NotiSendMessage
    {
        $subject = __('dictionary-subscriptions.email.subject', [
            'dictionary' => $this->dictionaryName,
        ], $notifiable->preferredLocaleOrDefault());

        $text = $this->hasExistingAccount
            ? __('dictionary-subscriptions.email.existing.text', [
                'owner' => $this->ownerEmail,
                'dictionary' => $this->dictionaryName,
                'link' => $this->invitationUrl,
            ], $notifiable->preferredLocaleOrDefault())
            : __('dictionary-subscriptions.email.new_user.text', [
                'owner' => $this->ownerEmail,
                'dictionary' => $this->dictionaryName,
                'register' => $this->registerUrl,
                'link' => $this->invitationUrl,
            ], $notifiable->preferredLocaleOrDefault());

        return new NotiSendMessage(
            to: $notifiable->email,
            subject: $subject,
            text: $text,
            html: View::make('emails.dictionary-subscriptions.invitation', [
                'ownerEmail' => $this->ownerEmail,
                'dictionaryName' => $this->dictionaryName,
                'invitationUrl' => $this->invitationUrl,
                'registerUrl' => $this->registerUrl,
                'hasExistingAccount' => $this->hasExistingAccount,
                'locale' => $notifiable->preferredLocaleOrDefault(),
            ])->render(),
        );
    }
}
