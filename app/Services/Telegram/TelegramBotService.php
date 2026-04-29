<?php

namespace App\Services\Telegram;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TelegramBotService
{
    private const MAX_ATTEMPTS = 3;

    public function sendMessage(string $chatId, string $text, array $extra = []): array
    {
        return $this->post('sendMessage', array_merge([
            'chat_id' => $chatId,
            'text' => $text,
        ], $extra));
    }

    public function setWebhook(string $url): array
    {
        return $this->post('setWebhook', [
            'url' => $url,
        ]);
    }

    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null): array
    {
        $payload = [
            'callback_query_id' => $callbackQueryId,
        ];

        if ($text !== null && $text !== '') {
            $payload['text'] = $text;
        }

        return $this->post('answerCallbackQuery', $payload);
    }

    public function clearInlineKeyboard(string $chatId, int $messageId): array
    {
        return $this->post('editMessageReplyMarkup', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'reply_markup' => [
                'inline_keyboard' => [],
            ],
        ]);
    }

    private function post(string $method, array $payload): array
    {
        $attempt = 0;

        beginning:
        $attempt++;

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->timeout(20)
                ->post($this->apiBaseUrl().'/'.$method, $payload);

            $response->throw();

            return $response->json() ?? [];
        } catch (ConnectionException|RequestException $exception) {
            $status = $exception instanceof RequestException
                ? $exception->response?->status()
                : null;

            if ($this->shouldRetry($exception, $attempt)) {
                Log::warning('telegram.api.retrying_request', [
                    'method' => $method,
                    'attempt' => $attempt,
                    'status' => $status,
                    'message' => $exception->getMessage(),
                ]);

                usleep(200000 * $attempt);
                goto beginning;
            }

            Log::error('telegram.api.request_failed', [
                'method' => $method,
                'attempt' => $attempt,
                'status' => $status,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function shouldRetry(ConnectionException|RequestException $exception, int $attempt): bool
    {
        if ($attempt >= self::MAX_ATTEMPTS) {
            return false;
        }

        if ($exception instanceof ConnectionException) {
            return true;
        }

        $status = $exception->response?->status();

        if ($status === null) {
            return false;
        }

        if ($status === 429) {
            return true;
        }

        return $status >= 500;
    }

    private function apiBaseUrl(): string
    {
        $botToken = (string) config('services.telegram.bot_token');

        if ($botToken === '') {
            throw new RuntimeException('TELEGRAM_BOT_TOKEN is not configured.');
        }

        return 'https://api.telegram.org/bot'.$botToken;
    }
}
