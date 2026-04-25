<?php

namespace App\Notifications\Channels;

use App\Models\PasswordResetMailDelivery;
use App\Notifications\Messages\NotiSendMessage;
use App\Services\NotiSend\NotiSendEmailApiClient;
use App\Services\NotiSend\NotiSendRequestException;
use Throwable;

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

        try {
            $this->client->send($message->toPayload());

            if (method_exists($notification, 'markNotiSendDelivered')) {
                $notification->markNotiSendDelivered();
            }
        } catch (NotiSendRequestException $exception) {
            if (method_exists($notification, 'markNotiSendFailed')) {
                $notification->markNotiSendFailed(
                    $this->errorCodeForStatus($exception->status),
                    $exception->getMessage(),
                );
            }
        } catch (Throwable $exception) {
            report($exception);

            if (method_exists($notification, 'markNotiSendTransportFailed')) {
                $notification->markNotiSendTransportFailed($exception->getMessage());
            }
        }
    }

    private function errorCodeForStatus(?int $status): string
    {
        return match ($status) {
            401, 403 => PasswordResetMailDelivery::ERROR_API_AUTH_FAILED,
            402 => PasswordResetMailDelivery::ERROR_API_BALANCE_INSUFFICIENT,
            422 => PasswordResetMailDelivery::ERROR_API_UNPROCESSABLE,
            429 => PasswordResetMailDelivery::ERROR_API_RATE_LIMITED,
            500, 502, 503, 504 => PasswordResetMailDelivery::ERROR_API_SERVICE_UNAVAILABLE,
            null => PasswordResetMailDelivery::ERROR_API_TRANSPORT_FAILED,
            default => PasswordResetMailDelivery::ERROR_API_REQUEST_FAILED,
        };
    }
}
