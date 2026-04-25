<?php

namespace App\Services\AboutContact;

use App\Mail\AboutContactMessage as AboutContactMail;
use App\Models\AboutContactMessage;
use App\Services\NotiSend\NotiSendEmailApiClient;
use App\Services\NotiSend\NotiSendRequestException;
use Illuminate\Support\Facades\App;

class NotiSendAboutContactDeliveryService implements AboutContactDeliveryServiceInterface
{
    public function __construct(
        private readonly NotiSendEmailApiClient $client,
    ) {
    }

    public function send(AboutContactMessage $contactMessage, string $locale): void
    {
        $payload = $this->payload($contactMessage, $locale);

        try {
            $this->client->send($payload);
        } catch (NotiSendRequestException $exception) {
            throw new AboutContactDeliveryException(
                $this->errorCodeForStatus($exception->status),
                $exception->getMessage(),
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(AboutContactMessage $contactMessage, string $locale): array
    {
        $content = $this->renderContent($contactMessage, $locale);

        return [
            'from_email' => (string) config('services.notisend.from_email'),
            'from_name' => (string) config('services.notisend.from_name'),
            'to' => (string) config('mail.about_contact_recipient'),
            'subject' => $content['subject'],
            'text' => $content['text'],
            'html' => $content['html'],
            'smtp_headers' => [
                'Reply-To' => $contactMessage->contact_email,
            ],
        ];
    }

    /**
     * @return array{subject:string,text:string,html:string}
     */
    private function renderContent(AboutContactMessage $contactMessage, string $locale): array
    {
        $previousLocale = App::currentLocale();
        App::setLocale($locale);

        try {
            $mailable = new AboutContactMail($contactMessage);

            return [
                'subject' => $mailable->envelope()->subject,
                'text' => implode("\n", [
                    __('about.contact.email').': '.$contactMessage->contact_email,
                    __('about.contact.subject').': '.$contactMessage->subject,
                    __('about.contact.message').':',
                    $contactMessage->message,
                ]),
                'html' => $mailable->render(),
            ];
        } finally {
            App::setLocale($previousLocale);
        }
    }

    private function errorCodeForStatus(?int $status): string
    {
        return match ($status) {
            401 => AboutContactMessage::ERROR_API_AUTH_FAILED,
            402 => AboutContactMessage::ERROR_API_BALANCE_INSUFFICIENT,
            422 => AboutContactMessage::ERROR_API_UNPROCESSABLE,
            429 => AboutContactMessage::ERROR_API_RATE_LIMITED,
            503 => AboutContactMessage::ERROR_API_SERVICE_UNAVAILABLE,
            default => AboutContactMessage::ERROR_API_REQUEST_FAILED,
        };
    }
}
