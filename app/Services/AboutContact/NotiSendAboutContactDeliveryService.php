<?php

namespace App\Services\AboutContact;

use App\Mail\AboutContactMessage as AboutContactMail;
use App\Models\AboutContactMessage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;

class NotiSendAboutContactDeliveryService implements AboutContactDeliveryServiceInterface
{
    public function send(AboutContactMessage $contactMessage, string $locale): void
    {
        $payload = $this->payload($contactMessage, $locale);
        $primaryBaseUrl = (string) config('services.notisend.base_url');
        $reserveBaseUrl = (string) config('services.notisend.reserve_base_url');

        try {
            $response = $this->request($primaryBaseUrl)->post('/email/messages', $payload);
        } catch (ConnectionException $exception) {
            if ($reserveBaseUrl === '' || $reserveBaseUrl === $primaryBaseUrl) {
                throw new AboutContactDeliveryException(
                    AboutContactMessage::ERROR_API_TRANSPORT_FAILED,
                    $exception->getMessage(),
                );
            }

            try {
                $response = $this->request($reserveBaseUrl)->post('/email/messages', $payload);
            } catch (ConnectionException $reserveException) {
                throw new AboutContactDeliveryException(
                    AboutContactMessage::ERROR_API_TRANSPORT_FAILED,
                    $reserveException->getMessage(),
                );
            }
        }

        if (! $response->successful()) {
            throw new AboutContactDeliveryException(
                $this->errorCodeForStatus($response->status()),
                $this->errorMessageFromResponse($response),
            );
        }
    }

    private function request(string $baseUrl): PendingRequest
    {
        return Http::baseUrl(rtrim($baseUrl, '/'))
            ->acceptJson()
            ->asJson()
            ->withToken((string) config('services.notisend.api_token'))
            ->timeout((int) config('services.notisend.timeout', 20));
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

    private function errorCodeForStatus(int $status): string
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

    private function errorMessageFromResponse(Response $response): string
    {
        $payload = $response->json();

        if (is_array($payload)) {
            $firstError = $payload['errors'][0]['detail'] ?? null;

            if (is_string($firstError) && trim($firstError) !== '') {
                return $firstError;
            }

            $message = $payload['message'] ?? null;

            if (is_string($message) && trim($message) !== '') {
                return $message;
            }
        }

        $body = trim($response->body());

        return $body !== ''
            ? $body
            : 'NotiSend API request failed with HTTP status '.$response->status().'.';
    }
}
