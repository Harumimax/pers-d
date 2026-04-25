<?php

namespace App\Jobs;

use App\Models\AboutContactMessage;
use App\Services\AboutContact\AboutContactDeliveryException;
use App\Services\AboutContact\AboutContactDeliveryServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Throwable;

class SendAboutContactMessageJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public function __construct(
        public int $contactMessageId,
        public string $locale,
    ) {
    }

    public function handle(AboutContactDeliveryServiceInterface $deliveryService): void
    {
        $contactMessage = AboutContactMessage::query()->find($this->contactMessageId);

        if (! $contactMessage instanceof AboutContactMessage || $contactMessage->delivery_status !== AboutContactMessage::STATUS_PENDING) {
            return;
        }

        try {
            $deliveryService->send($contactMessage, $this->locale);

            $contactMessage->forceFill([
                'delivery_status' => AboutContactMessage::STATUS_SENT,
                'delivered_at' => Carbon::now(),
                'delivery_error' => null,
                'delivery_error_message' => null,
            ])->save();
        } catch (AboutContactDeliveryException $exception) {
            $contactMessage->forceFill([
                'delivery_status' => AboutContactMessage::STATUS_FAILED,
                'delivery_error' => $exception->deliveryErrorCode,
                'delivery_error_message' => $exception->getMessage(),
            ])->save();
        } catch (Throwable $exception) {
            report($exception);

            $contactMessage->forceFill([
                'delivery_status' => AboutContactMessage::STATUS_FAILED,
                'delivery_error' => AboutContactMessage::ERROR_MAIL_TRANSPORT_FAILED,
                'delivery_error_message' => $exception->getMessage(),
            ])->save();
        }
    }
}
