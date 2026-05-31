<?php

namespace Tests\Unit;

use App\Notifications\DictionarySubscriptions\DictionaryShareInvitationNotification;
use App\Support\Notifications\NotiSendRecipient;
use Tests\TestCase;

class DictionaryShareInvitationNotificationTest extends TestCase
{
    public function test_notification_builds_bilingual_existing_user_message(): void
    {
        $notification = new DictionaryShareInvitationNotification(
            ownerEmail: 'owner@example.com',
            dictionaryName: 'Shared dictionary',
            invitationUrl: 'https://wordkeeper.space/invitation/token',
            registerUrl: 'https://wordkeeper.space/register',
            hasExistingAccount: true,
        );

        $message = $notification->toNotiSend(new NotiSendRecipient('friend@example.com', 'en'));

        $this->assertStringContainsString('Invitation to subscribe to dictionary "Shared dictionary"', $message->subject);
        $this->assertStringContainsString('Приглашение подписаться на словарь "Shared dictionary"', $message->subject);
        $this->assertStringContainsString('You are registered on https://wordkeeper.space/.', $message->text);
        $this->assertStringContainsString('Вы зарегистрированы на https://wordkeeper.space/.', $message->text);
        $this->assertStringContainsString('English', $message->html);
        $this->assertStringContainsString('Русский', $message->html);
    }
}
