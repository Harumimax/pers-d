<?php

namespace App\Services\NotiSend;

use Illuminate\Http\Client\Response;
use RuntimeException;

class NotiSendRequestException extends RuntimeException
{
    public function __construct(
        public readonly ?int $status,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function fromResponse(Response $response): self
    {
        $payload = $response->json();

        if (is_array($payload)) {
            $firstError = $payload['errors'][0]['detail'] ?? null;

            if (is_string($firstError) && trim($firstError) !== '') {
                return new self($response->status(), $firstError);
            }

            $message = $payload['message'] ?? null;

            if (is_string($message) && trim($message) !== '') {
                return new self($response->status(), $message);
            }
        }

        $body = trim($response->body());

        return new self(
            $response->status(),
            $body !== ''
                ? $body
                : 'NotiSend API request failed with HTTP status '.$response->status().'.',
        );
    }
}
