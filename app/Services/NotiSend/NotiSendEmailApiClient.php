<?php

namespace App\Services\NotiSend;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class NotiSendEmailApiClient
{
    /**
     * @param array<string, mixed> $payload
     */
    public function send(array $payload): Response
    {
        $primaryBaseUrl = (string) config('services.notisend.base_url');
        $reserveBaseUrl = (string) config('services.notisend.reserve_base_url');

        try {
            $response = $this->request($primaryBaseUrl)->post('/email/messages', $payload);
        } catch (ConnectionException $exception) {
            if ($reserveBaseUrl === '' || $reserveBaseUrl === $primaryBaseUrl) {
                throw new NotiSendRequestException(null, $exception->getMessage());
            }

            try {
                $response = $this->request($reserveBaseUrl)->post('/email/messages', $payload);
            } catch (ConnectionException $reserveException) {
                throw new NotiSendRequestException(null, $reserveException->getMessage());
            }
        }

        if (! $response->successful()) {
            throw NotiSendRequestException::fromResponse($response);
        }

        return $response;
    }

    private function request(string $baseUrl): PendingRequest
    {
        return Http::baseUrl(rtrim($baseUrl, '/'))
            ->acceptJson()
            ->asJson()
            ->withToken((string) config('services.notisend.api_token'))
            ->timeout((int) config('services.notisend.timeout', 20));
    }
}
