<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramBotService
{
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
        $response = Http::asJson()
            ->acceptJson()
            ->timeout(20)
            ->post($this->apiBaseUrl().'/'.$method, $payload);

        $response->throw();

        return $response->json() ?? [];
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
