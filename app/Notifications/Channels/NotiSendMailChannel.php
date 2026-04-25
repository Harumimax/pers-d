<?php

namespace App\Notifications\Channels;

use App\Notifications\Messages\NotiSendMessage;
use App\Services\NotiSend\NotiSendEmailApiClient;

class NotiSendMailChannel
{
    public function __construct(
        private readonly NotiSendEmailApiClient $client,
    ) {
    }

    public function send(object $notifiable, object $notification): void
    {
        if (! method_exists($notification, 'toNotiSend')) {
            return;
        }

        $message = $notification->toNotiSend($notifiable);

        if (! $message instanceof NotiSendMessage) {
            return;
        }

        $this->client->send($message->toPayload());
    }
}
