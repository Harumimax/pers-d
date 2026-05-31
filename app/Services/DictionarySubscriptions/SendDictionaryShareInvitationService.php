<?php

namespace App\Services\DictionarySubscriptions;

use App\Models\DictionaryShareInvitation;
use App\Models\User;
use App\Notifications\DictionarySubscriptions\DictionaryShareInvitationNotification;
use App\Support\Notifications\NotiSendRecipient;
use Illuminate\Support\Facades\Notification;

class SendDictionaryShareInvitationService
{
    public function send(DictionaryShareInvitation $invitation, string $rawToken): void
    {
        $invitation->loadMissing(['dictionary.owner']);

        $owner = $invitation->owner;
        $dictionary = $invitation->dictionary;
        $existingUser = User::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($invitation->target_email)])
            ->first();

        $recipient = new NotiSendRecipient(
            email: $invitation->target_email,
            locale: $existingUser?->preferredLocaleOrDefault() ?? $owner?->preferredLocaleOrDefault() ?? app()->getLocale(),
        );

        Notification::send(
            $recipient,
            new DictionaryShareInvitationNotification(
                ownerEmail: (string) $owner?->email,
                dictionaryName: (string) $dictionary?->name,
                invitationUrl: route('dictionary-subscriptions.show', ['token' => $rawToken]),
                registerUrl: route('register'),
                hasExistingAccount: $existingUser !== null,
            ),
        );
    }
}
